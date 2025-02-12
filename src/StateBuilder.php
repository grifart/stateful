<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful;

use Grifart\Stateful\ObjectStateBuilderException;

final class StateBuilder
{

	public static function from(object $theInstance): self
	{
		return new self($theInstance);
	}


	/** @internal use ::from() instead */
	private function __construct(object $instance)
	{
		$this->instance = $instance;
	}


	private object $instance;

	private int $version;

	private array $state = [];

	/** @var (int|string)[] */
	private array $ignore = [];


	/** Sets serialized state version */
	public function version(int $version): self
	{
		if (isset($this->version)) {
			throw ObjectStateBuilderException::versionHasAlreadyBeenSet($this->version, $version);
		}

		$this->version = $version;
		return clone $this;
	}


	/**
	 * Sets object state field from given value.
	 */
	public function field(int|string $name, mixed $value): self
	{
		if (array_key_exists($name, $this->state)) { // intentionally not used isset()
			throw ObjectStateBuilderException::fieldIsAlreadySet($name, $value);
		}

		$this->state[$name] = $value;

		return clone $this;
	}


	/**
	 * Ignores object field
	 */
	public function ignore(int|string $fieldName): self
	{
		// todo: make this idempotent?
		if (in_array($fieldName, $this->ignore, true)) {
			throw ObjectStateBuilderException::fieldHasAlreadyBeenIgnored($fieldName);
		}

		$this->ignore[] = $fieldName;

		return clone $this;
	}


	public function build(): State
	{
		if ( ! isset($this->version)) {
			throw ObjectStateBuilderException::versionHasNotBeenProvided();
		}

		return State::from(
			$this->instance,
			$this->version,
			$this->state,
			$this->ignore
		);
	}
}
