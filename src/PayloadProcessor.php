<?php declare(strict_types=1);

namespace Grifart\Stateful;
use Grifart\Stateful\Exceptions\ClassNameMappingException;
use Grifart\Stateful\Exceptions\ClassNotFoundException;
use Grifart\Stateful\Exceptions\MalformedMetadataException;
use Grifart\Stateful\Exceptions\MalformedPayloadException;
use Grifart\Stateful\Exceptions\PayloadProcessorException;
use Grifart\Stateful\ExternalSerializer\Serializer;
use Grifart\Stateful\Mapper\Mapper;
use Grifart\Stateful\RemovedClassesDeserializer\RemovedClassesDeserializer;


/**
 * Builds serializable payload from given objects.
 */
final class PayloadProcessor
{
	public const META_FIELD = '@(meta)';

	private const META_FIELD_SERIALIZATION_VERSION = 'serializationVersion';

	/** @var Mapper */
	private $mapper;

	/** @var Serializer */
	private $externalSerializer;

	/** @var RemovedClassesDeserializer */
	private $removedClassesDeserializer;

	/**
	 * PayloadProcessor constructor.
	 * @param Mapper     $mapper
	 * @param Serializer $externalSerializer Used for otherwise unserializable objects (e.g. datetime)
	 */
	public function __construct(
		Mapper $mapper,
		Serializer $externalSerializer,
		?RemovedClassesDeserializer $removedClassesDeserializer = NULL
	)
	{
		$this->mapper = $mapper;
		$this->externalSerializer = $externalSerializer;
		$this->removedClassesDeserializer = $removedClassesDeserializer ?? RemovedClassesDeserializer::from([]);
	}


	// ----------- SERIALIZATION: ------------

	/**
	 * Converts given object into primitive structure composed of scalars and arrays.
	 * Result will be also normalized. This simplifies comparisons of results.
	 *
	 * @param object|array|mixed $value
	 *
	 * @return \Grifart\Stateful\Payload Original data in serializable form
	 */
	public function toPayload($value): Payload
	{
		if(!is_object($value) && !\is_array($value)) {
			throw PayloadProcessorException::onlyObjectsAndArraysCanBeConvertedToPayload(gettype($value));
		}

		$primitivizedData = $this->_toPayload($value);

		// set serialized version
		assert(is_array($primitivizedData)); // object --> array
		$primitivizedData[self::META_FIELD][self::META_FIELD_SERIALIZATION_VERSION] = 1;

		/** @noinspection ReturnNullInspection */
		return new Payload($primitivizedData);
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function _toPayload($value)
	{
		// every object must have been already replaced by State; if not error
		// primitives and arrays are still in in original form
		// goal: make then uniform and deserializable

		// scalars are always serializable
		if (is_scalar($value)) {
			return $value;
		}

		// NULL is not scalar
		if ($value === NULL) {
			return NULL;
		}

		// Array: recursively solve
		if (is_array($value)) {
			$data = [];
			foreach ($value AS $key => $val) {
				/** @noinspection ReturnNullInspection */
				$data[$key] = $this->_toPayload($val);
			}
			return self::addMetaToPayload($data, PayloadMetadata::forAnArray());
		}

		// Objects: extract state and construct payload
		if (\is_object($value)) {
			$this->primitivizeObjectState(
				$this->extractObjectState($value)
			);
		}

		// PHP TYPE CHECKLIST
		// https://www.php.net/manual/en/language.types.php
		//
		// OK (Booleans, Integers, Floating point numbers, Strings)
		// OK NULL
		// OK Arrays
		// OK Objects
		//   OKish Iterables (objects only)
		//   OKish Callbacks / Callables (objects only)
		// Resources --> ERROR

		// for future PHP compatibility, error branch is the default one.
		// For PHP 7.4 this case covers:
		//  - resources
		//  - native iterables
		//  - native closure
		// These types are NOT serializable, thus will throw and exception.
		throw PayloadProcessorException::unexpectedInputType($value);
	}


	private function extractObjectState(object $object): State
	{
		assert(is_object($object));

		// Stateful --> State
		if ($object instanceof Stateful) {
			return $object->_getState();
		}

		// !Stateful --> State
		// type that can be serialized using external serializer:
		$state = $this->externalSerializer->extractState($object);
		if($state !== NULL) {
			return $state;
		}

		throw PayloadProcessorException::objectIsNotSerializable_noSerializerFound(get_class($object));
	}


	private function primitivizeObjectState(State $objectState): array
	{
		// State --> convert to primitive
		$payload = $objectState->getState();
		$className = $objectState->getClassName();

		foreach($payload AS $key => $val) {
			/** @noinspection ReturnNullInspection NULL is valid value here */
			$payload[$key] = $this->_toPayload($val);
		}

		// Normalize payload: to be able to === resulting arrays
		$sortResult = ksort($payload);
		assert($sortResult === TRUE);

		$transferClassName = $this->mapper->toTransferName($className);
		if($transferClassName === NULL) {
			throw PayloadProcessorException::missingNameMappingFor($className);
		}

		return self::addMetaToPayload(
			$payload,
			PayloadMetadata::forAnObject($transferClassName, $objectState->getVersion())
		);
	}

	/** Adds metadata to first key in array */
	private static function addMetaToPayload(array $payload, array $meta): array
	{
		if (isset($payload[PayloadProcessor::META_FIELD])) {
			throw PayloadProcessorException::objectStateCannotContainMetaFieldName();
		}

		return [self::META_FIELD => $meta] + $payload;
	}

	// ------ DESERIALIZATION: -----------

	/**
	 * Convert payload back to object
	 *
	 * Use general {@see \Grifart\Stateful\Exceptions\PayloadParserException} to catch them all!
	 *
	 * @param \Grifart\Stateful\Payload $payload
	 * @return object|array
	 *
	 * @throws \Grifart\Stateful\Exceptions\ClassNameMappingException Cannot covert transfer class name to real runtime class name.
	 * @throws \Grifart\Stateful\Exceptions\ClassNotFoundException Cannot find class name in runtime.
	 * @throws \Grifart\Stateful\Exceptions\MalformedMetadataException If metadata are corrupt.
	 * @throws \Grifart\Stateful\Exceptions\MalformedPayloadException If payload is corrupt
	 * @throws \Grifart\Stateful\Exceptions\NoAppropriateDeserializerFoundException If no deserializer for object type is found.
	 */
	public function fromPayload(Payload $payload)
	{
		$primitives = $payload->getPrimitives();

		if(!is_array($primitives)) {
			throw MalformedPayloadException::payloadRootMustBeAnArray(gettype($primitives));
		}

		// Check payload version:
		$metadata = self::extractMetadata($primitives);
		if (!isset($metadata[self::META_FIELD_SERIALIZATION_VERSION])) {
			throw MalformedMetadataException::missingSerializationVersion();
		}

		$payloadVersion = $metadata[self::META_FIELD_SERIALIZATION_VERSION];
		if($payloadVersion !== 1) {
			throw PayloadProcessorException::unsupportedPayloadVersion($payloadVersion);
		}

		return $this->_fromPayload($primitives);
	}


	/**
	 * @param mixed $data
	 * @return mixed
	 */
	private function _fromPayload($data)
	{
		// SCALARS:
		if (is_scalar($data)) {
			return $data;
		}

		// NULL is not scalar; @see
		if($data === NULL) {
			return NULL;
		}

		// arrays and objects:
		if (!is_array($data)) {
			throw MalformedPayloadException::unexpectedTypeInPayload(gettype($data));
		}

		// parse metadata
		$meta = PayloadMetadata::parse(self::extractMetadata($data));
		unset($data[self::META_FIELD]);

		// ARRAYS:
		if ($meta->getType() === PayloadMetadata::META_TYPE_ARRAY) {
			$arrayData = [];
			foreach ($data AS $key => $val) {
				$arrayData[$key] = $this->_fromPayload($val);
			}
			return $arrayData;
		}

		// OBJECTS:
		assert($meta->getType() === PayloadMetadata::META_TYPE_OBJECT);
		return $this->fromPayload_object($data, $meta);
	}


	/**
	 * @return mixed Doesn't have to be object if deserialized via RemovedClassDeserializer
	 */
	private function fromPayload_object(array $serializedObjectFields, PayloadMetadata $meta)
	{
		$stateVersion = $meta->getVersion();

		$className = $this->mapper->toFullyQualifiedName($meta->getTransferClassName());
		if($className === NULL) {
			throw ClassNameMappingException::cannotConvertTransferNameToClassName($meta->getTransferClassName());
		}

		if ( ! $this->removedClassesDeserializer->canDeserialize($className) && ! class_exists($className)) {
			throw ClassNotFoundException::classNameDeliverFromTransferName($className, $meta->getTransferClassName());
		}

		$isStatefulObject = \is_a($className, Stateful::class, TRUE);

		// reconstruct object state
		// DFS for data: we need to have reconstructed all sub-object to construct their parents
		$stateData = [];
		foreach ($serializedObjectFields AS $key => $val) {
			$stateData[$key] = $this->_fromPayload($val);
		}
		$objectState = new State($className, $stateVersion, $stateData);

		if ($isStatefulObject) {
			/** @var Stateful|string $className */
			$createdObject = $className::_fromState($objectState);
			if(Tools::areAssertsEvaluated()) {
				$this->assertCreatedObject($objectState, $createdObject);
			}
			return $createdObject;
		}

		// non-stateful objects --> use ad-hoc provided unserializers
		if (($createdObject = $this->externalSerializer->reconstructFromState($objectState)) !== NULL) {
			\assert($createdObject !== NULL); // phpstan workaround; handled one line above; https://github.com/phpstan/phpstan/issues/750
			if(Tools::areAssertsEvaluated()) {
				$this->assertCreatedObject($objectState, $createdObject);
			}
			return $createdObject;
		}

		return $this->removedClassesDeserializer->deserialize($objectState);
	}

	private static function extractMetadata(array $data): array
	{
		if (!isset($data[self::META_FIELD])) {
			throw MalformedPayloadException::metadataFieldIsMissing();
		}

		return $data[self::META_FIELD];
	}

	/**
	 * @param State  $objectState
	 * @param object $createdObject
	 */
	private function assertCreatedObject(State $objectState, $createdObject): void
	{
		$originalClassType = $objectState->getClassName();
		$createdClassType = get_class($createdObject);

		// STRICT CHECK: all state properties accessed while deserializing
		if (!$objectState->hasBeenAllPropertiesAccessed()) {
			throw PayloadProcessorException::notAllStatePropertiesDeserialized($objectState);
		}

		if(!$objectState->hasBeenVersionChecked()) {
			throw PayloadProcessorException::unserializerDidNotCheckedObjectStateVersionNumber($originalClassType);
		}

		// create object must be the same type as was original object
		if ($createdClassType !== $originalClassType) {
			throw PayloadProcessorException::createdClassDoesNotMatchWithOriginalClassType($createdClassType, $originalClassType);
		}
	}


	public function withMapper(Mapper $mapper): PayloadProcessor
	{
		$me = clone $this;
		$me->mapper = $mapper;
		return $me;
	}


	/** @internal useful in tests */
	public function getMapper(): Mapper
	{
		return $this->mapper;
	}
}
