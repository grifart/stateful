<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\Exceptions\ClosureExternalSerializerException;
use Grifart\Stateful\Exceptions\ExternalSerializerException;
use Grifart\Stateful\State;

/**
 * Creates (de)serializer for given closure
 * contains logic for retrieving closures annotation etc.
 */
final class SerializerList implements Serializer
{

	/** @var ClosureSerializer[] */
	private $serializers = [];

	/** @var ClosureDeserializer[] */
	private $deserializers = [];


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
	 * @param callable[] $externalSerializers List of (de)serializer closures
	 * @return \Grifart\Stateful\ExternalSerializer\SerializerList
	 */
	public static function from(array $externalSerializers): self
	{
		$list = new self;

		foreach ($externalSerializers AS $function) {

			/** @var bool $matchSubtypes */
			/** @var \ReflectionFunction $functionReflection */
			/** @var string $parameterType */
			/** @var string $returnType */
			[$functionReflection, $matchSubtypes, $parameterType, $returnType] = self::checkClosure($function);

			if ($returnType === State::class) {
				// state extractor
				$list->addSerializer(
					new ClosureSerializer(
						$function,
							$parameterType,
						$matchSubtypes
					)
				);

			} elseif ($parameterType === State::class) {
				// object constructor from state
				$list->addDeserializer(
					new ClosureDeserializer(
						$function,
							$returnType,
						$matchSubtypes
					)
				);

			} else {
				ExternalSerializerException::givenFunctionIsNotAValidSerializer($functionReflection);
			}
		}

		return $list;
	}


	/** Is given closure valid (de)serializer? */
	private static function checkClosure(callable $function): array
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
		assert($paramTypeR instanceof \ReflectionType);
		if ($paramTypeR->allowsNull()) {
			throw ClosureExternalSerializerException::allowsNullToBeAnParameter($fnR);
		}
		$parameterType = (string) $paramTypeR;

		// RETURN TYPE:
		if (!$fnR->hasReturnType()) {
			throw ClosureExternalSerializerException::missingReturnType($fnR);
		}

		$returnTypeReflection = $fnR->getReturnType();
		if ($returnTypeReflection->allowsNull()) {
			throw ClosureExternalSerializerException::canReturnNull($fnR);
		}
		$returnType = (string) $fnR->getReturnType();
		return [$fnR, $matchSubtypes, $parameterType, $returnType];
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


	public function extractState($object): ?State
	{
		foreach($this->serializers as $serializer) {
			if(!$serializer->isUsableFor($object)) {
				continue;
			}
			return $serializer->extractState($object);
		}

		return NULL;
	}


	public function reconstructFromState(State $state)
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
