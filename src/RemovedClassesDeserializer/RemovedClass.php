<?php

declare(strict_types=1);

namespace Grifart\Stateful\RemovedClassesDeserializer;

use Grifart\Stateful\RemovedClassException;
use Grifart\Stateful\State;

final class RemovedClass
{
	private function __construct(
		private array $data,
	) {}

	public static function fromState(State $state): self
	{
		return new self(\iterator_to_array($state));
	}

	public function __get(string $name): mixed
	{
		if (\array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}

		throw RemovedClassException::unknownProperty($name);
	}

	public function __isset(string $name): bool
	{
		return \array_key_exists($name, $this->data);
	}

	public function __set(string $name, mixed $value): void
	{
		throw RemovedClassException::removedClassIsImmutable();
	}

	public function __unset(string $name): void
	{
		throw RemovedClassException::removedClassIsImmutable();
	}
}
