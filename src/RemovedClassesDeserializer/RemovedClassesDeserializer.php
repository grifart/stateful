<?php

declare(strict_types=1);

namespace Grifart\Stateful\RemovedClassesDeserializer;

use Grifart\Stateful\Exceptions\NoAppropriateDeserializerFoundException;
use Grifart\Stateful\State;
use Grifart\Stateful\Stateful;


final class RemovedClassesDeserializer
{
	/** @var array<string, \Closure(State $state): mixed> */
	private $deserializers = [];

	/**
	 * @param array<string, \Closure(State $state): mixed> $deserializers
	 */
	private function __construct(array $deserializers)
	{
		foreach (\array_keys($deserializers) as $className) {
			\assert( ! \class_exists($className));
		}

		$this->deserializers = $deserializers;
	}

	/**
	 * @param array<int|string, string|\Closure(State $state): mixed> $deserializers
	 */
	public static function from(array $deserializers): self
	{
		/** @var array<string, \Closure(State $state): mixed> $normalizedDeserializers */
		$normalizedDeserializers = [];

		foreach ($deserializers as $className => $deserializer) {
			if (\is_callable($deserializer)) {
				\assert(\is_string($className));
				$normalizedDeserializers[$className] = \Closure::fromCallable($deserializer);
			} else {
				\assert(\is_int($className));
				\assert(\is_string($deserializer));
				$normalizedDeserializers[$deserializer] = self::defaultDeserializer();
			}
		}

		return new self($normalizedDeserializers);
	}

	public function canDeserialize(string $className): bool
	{
		return \in_array($className, \array_keys($this->deserializers), true);
	}

	/**
	 * @return mixed
	 */
	public function deserialize(State $state)
	{
		$className = $state->getClassName();
		if ( ! isset($this->deserializers[$className])) {
			throw NoAppropriateDeserializerFoundException::for($className);
		}

		$deserializer = $this->deserializers[$className];

		$result = $deserializer($state);
		\assert( ! \is_object($result) || ! $result instanceof Stateful);

		return $result;
	}

	/**
	 * @return \Closure(State $state): RemovedClass
	 */
	public static function defaultDeserializer(): \Closure
	{
		return static function (State $state): RemovedClass {
			return RemovedClass::fromState($state);
		};
	}
}
