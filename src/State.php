<?php declare(strict_types = 1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful;

use Ds\Hashable;
use Grifart\Stateful\Exceptions\ObjectStateException;
use Grifart\Stateful\Exceptions\PayloadException;
use Grifart\Stateful\Exceptions\PayloadProcessorException;
use function Interop\EqualableUtils\equals;


/**
 * Represent internal object state of given object.
 *
 * This state container MUST NEVER be used outside original object that generated it.
 * (otherwise this will break encapsulation of source object)
 *
 * @implements \ArrayAccess<string, mixed>
 * @implements \Iterator<string, mixed>
 */
final class State implements \ArrayAccess, \Countable, \Iterator, Hashable
{

	//<editor-fold desc="Named constructors">

	public static function from(object $theInstance, int $version, array $state, array $ignore = []): self
	{
		if(Tools::areAssertsEvaluated()) {
			self::assertAllPropertiesHaveBeenSerialized($theInstance, $state, $ignore);
		}

		return new self(get_class($theInstance), $version, $state);
	}


	/**
	 * This check should be executed:
	 * - in dev env every time
	 * - on CI for all serializable classes
	 * - at container build time (?)
	 */
	private static function assertAllPropertiesHaveBeenSerialized(
		object $theInstance,
		array $primitivesForSerialization,
		array $ignore
	): void {
		// 1.1) collect data: serialized keys
		$serializedKeys = array_keys($primitivesForSerialization);

		// 1.2) collect data: all class properties
		$classPropertyNames = self::getPropertyNamesForInstance($theInstance);

		// 1.3) subtract ignored
		$classPropertyNames = array_filter($classPropertyNames, function ($val) use ($ignore) {
			return !in_array($val, $ignore, TRUE);
		});

		// 2) What is missing in $serializedKeys?
		$difference = array_diff($classPropertyNames, $serializedKeys);
		if (count($difference) > 0) {
			throw ObjectStateException::forgottenProperty($difference, get_class($theInstance));
		}

	}

	/** @return \ReflectionProperty[] */
	private static function getPropertiesForInstance(object $theInstance): array
	{
		return (new \ReflectionClass($theInstance))->getProperties();
	}

	/** @return string[] names */
	private static function getPropertyNamesForInstance(object $theInstance): array
	{
		$classPropertyNames = [];
		foreach (self::getPropertiesForInstance($theInstance) AS $property) {
			$classPropertyNames[] = $property->getName();
		}
		return $classPropertyNames;
	}
	//</editor-fold>



	/** @var array */
	private $state;

	/** @var string FQ class name */
	private $className;

	/** @var int */
	private $version;

	/** @var bool */
	private $wasVersionRetrieved = FALSE;

	/** @internal */
	public function __construct(string $className, int $version, array $state)
	{
		$this->state = $state;
		$this->className = $className;
		$this->version = $version;

		// Traversable implementation:
		$this->iterator = new \ArrayIterator($this->state);
	}


	/** @internal Used by PayloadProcessor; use ArrayAccess instead */
	public function getState(): array
	{
		return $this->state;
	}


	public function getClassName(): string
	{
		return $this->className;
	}


	public function keys(): array
	{
		return array_keys($this->state);
	}

	public function getVersion(): int
	{
		$this->wasVersionRetrieved = TRUE;
		return $this->version;
	}

	/**
	 * Use when there is only one state version supported in from state method.
	 *
	 * This is stricter version of
	 * ```php
	 * \assert($state->getVersion() === 1);
	 * ```
	 * because asserts are not evaluated in production environment.
	 *
	 * ```php
	 * $state->ensureVersion(1);
	 * ```
	 *
	 * is evaluated even on production servers.
	 */
	public function ensureVersion(int $expectedVersion): void
	{
		if($this->getVersion() !== $expectedVersion) {
			throw PayloadProcessorException::unsupportedPayloadVersion($this->version, $expectedVersion);
		}
	}

	/**
	 * Shorthand method when using switch statement as in example bellow:
	 *
	 * ```php
	 * switch($state->getVersion()) {
	 *   case 1: ...; break;
	 *   default: $state->throwIncompatibleVersion()
	 * }
	 * ```
	 */
	public function throwIncompatibleVersion(): void
	{
		// do not call getter getVersion(), not treated as accessing version
		throw PayloadProcessorException::unsupportedPayloadVersion($this->version);
	}



	//<editor-fold desc="Property access checks">

	/** @var array<string, bool> */
	private $accessedProperties = [];

	/**
	 * Resets which properties has been accessed
	 * @internal used by {@see PayloadProcessor}
	 */
	public function resetAccessedProperties(): void
	{
		$this->wasVersionRetrieved = FALSE;
		$this->accessedProperties = [];
	}

	/**
	 * TRUE if all properties of object state has been accessed
	 * @return bool
	 * @internal used by {@see PayloadProcessor}
	 */
	public function hasBeenAllPropertiesAccessed(): bool
	{
		foreach ($this->state as $key => $val) {
			if (!isset($this->accessedProperties[$key])) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Returns all properties that has not been used for reconstructing state
	 * @return array
	 * @internal used by {@see PayloadProcessor} and exceptions
	 */
	public function getUnusedProperties(): array
	{
		return array_diff(
			array_keys($this->state),
			array_keys($this->accessedProperties)
		);
	}


	/** Mark property as used */
	private function setUsed(string $propertyName): void
	{
		$this->accessedProperties[$propertyName] = true;
	}

	/**
	 * Ignores state property
	 * This is used to suppress unused property errors when object reconstruction is done.
	 *
	 * @param array $properties
	 */
	public function ignoreProperty(array $properties): void
	{
		foreach ($properties AS $property) {
			$this->setUsed($property);
		}
	}

	//</editor-fold>




	//<editor-fold desc="Traversable, Countable, ArrayAccess interfaces implementation">
	public function offsetExists($offset): bool
	{
		$this->setUsed($offset);
		return isset($this->state[$offset]);
	}


	public function offsetGet($offset)
	{
		if(!array_key_exists($offset, $this->state)) { // intentionally not isset() which returns FALSE for NULL values
			throw ObjectStateException::accessedStatePropertyThatDoesNotExists((string) $offset, $this->getClassName());
		}

		$this->setUsed($offset);
		return $this->state[$offset];
	}

	public function offsetSet($offset, $value): void
	{
		throw ObjectStateException::notAllowedToModifyObjectState($this->getClassName(), (string) $offset);
	}

	public function offsetUnset($offset): void
	{
		throw ObjectStateException::notAllowedToModifyObjectState($this->getClassName(), (string) $offset);
	}

	// Countable interface:

	public function count(): int
	{
		return count($this->state);
	}




	// Traversable interface:
	// Proxy iterator manually to automatically setUsed for properties

	/** @var \ArrayIterator<string, mixed> */
	private $iterator;

	public function current()
	{
		$this->setUsed($this->key());

		return $this->iterator->current();
	}

	public function next(): void
	{
		$this->iterator->next();
	}

	public function key()
	{
		return $this->iterator->key();
	}

	public function valid(): bool
	{
		return $this->iterator->valid();
	}

	public function rewind(): void
	{
		$this->iterator->rewind();
	}


	//</editor-fold>



	// OBJECT CONSTRUCTION HELPERS:

	/** @var ?\ReflectionClass<object> */
	private $_reflectionClass;


	/**
	 * @return \ReflectionClass<object>
	 */
	private function getReflectionClass(): \ReflectionClass
	{
		if ($this->_reflectionClass !== null) {
			return $this->_reflectionClass;
		}

		try {
			/** @var class-string<object> $className */
			$className = $this->className;
			$reflection = new \ReflectionClass($className);

		} catch (\ReflectionException $e) {
			throw ObjectStateException::cannotCreateClass_classNotFound($this->getClassName());
		}

		return $this->_reflectionClass = $reflection;
	}

	/**
	 * Create an empty instance of original object
	 * @param string $class A class to be created
	 * @return object
	 */
	public function makeAnEmptyObject(string $class): object
	{
		if($this->className !== $class) {
			throw ObjectStateException::mustCreateSameObjectTypeAsWasOriginalObject($this->className, $class);
		}
		return $this->getReflectionClass()->newInstanceWithoutConstructor();
	}

	/**
	 * Create an instance of object
	 * @param string $class A class to be created.
	 * @return object
	 */
	public function makeObjectWithProperties(string $class): object
	{
		$object = $this->makeAnEmptyObject($class);

		foreach ($this->getReflectionClass()->getProperties() AS $property) {
			$propName = $property->getName();
			$object->$propName = $this[$propName];
		}

		if(!$this->hasBeenAllPropertiesAccessed()) {
			throw PayloadException::state_cannotCreateNewInstanceWithProperties_objectWasChanged($this->getClassName());
		}

		return $object;
	}

	public function hasBeenVersionChecked(): bool
	{
		return $this->wasVersionRetrieved;
	}


	public function equals($other): bool
	{
		if (!$other instanceof self) {
			return false;
		}

		if ($this->getClassName() !== $other->getClassName()) {
			return false;
		}

		// if structure of state is not the same
		/** @noinspection TypeUnsafeComparisonInspection comparing array without order of elements */
		if($this->keys() != $other->keys()) {
			return false;
		}

		if($this->getVersion() !== $other->getVersion()) {
			return false;
		}

		foreach ($this as $key => $val) {
			if (!equals($val, $other[$key])) {
				return false;
			}
		}

		return true;
	}


	public function hash()
	{
		throw new \LogicException('Object state cannot act as an hash key');
	}
}
