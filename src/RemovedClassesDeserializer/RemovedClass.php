<?php

declare(strict_types=1);

namespace Grifart\Stateful\RemovedClassesDeserializer;

use Grifart\Stateful\Exceptions\RemovedClassException;
use Grifart\Stateful\State;

final class RemovedClass
{
	/** @var array */
	private $data;

	private function __construct(array $data)
	{
		$this->data = $data;
	}

	public static function fromState(State $state): self
	{
		return new self(\iterator_to_array($state));
	}

	public function __get(string $name)
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

	public function __set(string $name, $value): void
	{
		throw RemovedClassException::removedClassIsImmutable();
	}

	public function __unset(string $name): void
	{
		throw RemovedClassException::removedClassIsImmutable();
	}
}
