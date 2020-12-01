<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\Exceptions\ClosureExternalSerializerException;
use Grifart\Stateful\Exceptions\ExternalSerializerException;
use Grifart\Stateful\Exceptions\UsageException;
use Grifart\Stateful\State;

/**
 * Creates (de)serializer for given closure
 * contains logic for retrieving closures annotation etc.
 */
final class SerializerList implements Serializer
{

	/** @var ClosureSerializer[] */
	private array $serializers = [];

	/** @var ClosureDeserializer[] */
	private array $deserializers = [];


	/**
	 * Build {@see SerializerList} from list of given external (de)serializers (closures).
	 *
	 * Please provide closures with following signatures:
	 *
	 * Deserializer:
	 * ````php
	 * <?php
	 * function(State $state): MyObject {
	 *      assert($state->getVersion() === 1);
	 *      $object = $state->makeAnEmptyObject();
	 *      $object->myProperty = $state['serializedNameForProperty'];
	 *      return $object;
	 * }
	 * ````
	 *
	 * Serializer:
	 * ````php
	 * <?php
	 * function(MyObject $object): State {
	 *      return StateBuilder($object)
	 *          ->version(1)
	 *          ->field('serializedNameForProperty', $object->myProperty)
	 *          ->build();
	 * }
	 * ````
	 *
	 * Additionally you can use `@matchSubtypes` to force external serializer
	 * to match also subtypes of (de)serialized class. Read docs before using this.
	 * This can easily become tricky and counterintuitive.
	 *
	 * @param \Closure ...$externalSerializers List of (de)serializer closures
	 * @return \Grifart\Stateful\ExternalSerializer\SerializerList
	 */
	public static function from(\Closure ...$externalSerializers): self
	{
		$list = new self;

		foreach ($externalSerializers AS $function) {

			$convertedClosure = self::checkClosure($function);

			if ($convertedClosure instanceof ClosureSerializer) {
				$list->addSerializer($convertedClosure);
				continue;

			}

			if ($convertedClosure instanceof ClosureDeserializer) {
				$list->addDeserializer($convertedClosure);
				continue;

			}
		}

		return $list;
	}


	/**
	 * Is given closure valid (de)serializer?
	 * @return ClosureSerializer|ClosureDeserializer
	 */
	private static function checkClosure(\Closure $function)
	{
		try {
			$fnR = new \ReflectionFunction($function);
		} catch (\ReflectionException $e) {
			throw ExternalSerializerException::serializerIsNotAValidFunction($e);
		}

		$matchSubtypes = $fnR->getDocComment() !== false /* has doc block */
			? stripos($fnR->getDocComment(), '@matchSubtypes') !== false /* docblock contains @matchSubtypes */
			: false;

		// PARAMETERS CHECK:
		$numberOfParameters = $fnR->getNumberOfParameters();
		if ($numberOfParameters !== 1) {
			throw ClosureExternalSerializerException::wrongNumberOrArguments($fnR, $numberOfParameters, 1);
		}
		$param = $fnR->getParameters()[0];
		assert($param instanceof \ReflectionParameter);

		$paramTypeR = $param->getType();
		assert($paramTypeR instanceof \ReflectionNamedType);
		if ($paramTypeR->allowsNull()) {
			throw ClosureExternalSerializerException::allowsNullToBeAnParameter($fnR);
		}
		/** @var class-string<mixed> $parameterType */
		$parameterType = $paramTypeR->getName();

		// RETURN TYPE:
		if (!$fnR->hasReturnType()) {
			throw ClosureExternalSerializerException::missingReturnType($fnR);
		}

		$returnTypeReflection = $fnR->getReturnType();
		if ($returnTypeReflection === NULL) {
			throw ClosureExternalSerializerException::doesNotSpecifyReturnType($fnR);
		}
		if ($returnTypeReflection->allowsNull()) {
			throw ClosureExternalSerializerException::canReturnNull($fnR);
		}
		assert($returnTypeReflection instanceof \ReflectionNamedType);
		/** @var class-string<mixed> $returnType */
		$returnType = $returnTypeReflection->getName();

		$isSerializer = $returnType === State::class;
		$isDeserializer = $parameterType === State::class;
		\assert($isSerializer xor $isDeserializer);

		if ( ! $matchSubtypes) {
			// It does not make sense to register abstract class or interface for (de)serialization
			// As by default we use precise type match.
			self::checkInterfacesAndAbstractClasses(
				$isSerializer
					? $parameterType
					: $returnType
			);
		}


		return
			$isSerializer
				? new ClosureSerializer(
					$function,
					$parameterType,
					$matchSubtypes
				)
				: new ClosureDeserializer(
					$function,
					$returnType,
					$matchSubtypes
				);
	}

	/**
	 * @param class-string<mixed> $typeToValidate
	 * @throw ExternalSerializerException
	 */
	private static function checkInterfacesAndAbstractClasses(string $typeToValidate): void
	{
		$typeReflection = new \ReflectionClass($typeToValidate);
		$isConcreteClass = !$typeReflection->isInterface() && !$typeReflection->isAbstract();
		if ( ! $isConcreteClass) {
			throw ExternalSerializerException::serializerForInterfaceDoesNotMakeSense($typeToValidate);
		}
	}


	/** @internal use {@see from} instead */
	public function __construct()
	{
	}


	/** @internal use {@see from} instead */
	public function addSerializer(ClosureSerializer $serializer): void
	{
		$this->serializers[] = $serializer;
	}


	/** @internal use {@see from} instead */
	public function addDeserializer(ClosureDeserializer $deserializer): void
	{
		$this->deserializers[] = $deserializer;
	}


	public function extractState(object $object): ?State
	{
		foreach($this->serializers as $serializer) {
			if(!$serializer->isUsableFor($object)) {
				continue;
			}
			return $serializer->extractState($object);
		}

		return NULL;
	}


	public function reconstructFromState(State $state): ?object
	{
		foreach($this->deserializers as  $deserializer) {
			if(!$deserializer->isUsableFor($state->getClassName())) {
				continue;
			}

			return $deserializer->reconstructFromState($state);
		}

		return NULL;
	}

}
