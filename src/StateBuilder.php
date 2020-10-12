<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful;

use Grifart\Stateful\Exceptions\ObjectStateBuilderException;

final class StateBuilder
{

	public static function from(object $theInstance): self
	{
		assert(is_object($theInstance));

		return new self($theInstance);
	}


	/** @internal use ::from() instead */
	private function __construct(object $instance)
	{
		$this->instance = $instance;
	}


	/** @var object */
	private $instance;

	/** @var int */
	private $version;

	/** @var array */
	private $state = [];

	/** @var array */
	private $ignore = [];


	/** Sets serialized state version */
	public function version(int $version): self
	{
		if($this->version !== NULL) {
			throw ObjectStateBuilderException::versionHasAlreadyBeenSet($this->version, $version);
		}

		$this->version = $version;
		return clone $this;
	}


	/**
	 * Sets object state field from given value.
	 *
	 * @param int|string $name must be scalar value
	 * @param mixed $value
	 * @return self
	 */
	public function field($name, $value): self
	{
		if (!is_scalar($name)) {
			throw ObjectStateBuilderException::onlyScalarsAreAllowedAsStateFiledNames(gettype($name));
		}

		if (array_key_exists($name, $this->state)) { // intentionally not used isset()
			throw ObjectStateBuilderException::fieldIsAlreadySet($name, $value);
		}

		$this->state[$name] = $value;

		return clone $this;
	}


	/**
	 * Ignores object field
	 *
	 * @param int|string $fieldName must be scalar value
	 * @return self
	 */
	public function ignore($fieldName): self
	{
		if (!is_scalar($fieldName)) {
			throw ObjectStateBuilderException::onlyScalarsAreAllowedAsStateFiledNames(gettype($fieldName));
		}

		// todo: make this idempotent?
		if (in_array($fieldName, $this->ignore, true)) {
			throw ObjectStateBuilderException::fieldHasAlreadyBeenIgnored($fieldName);
		}

		$this->ignore[] = $fieldName;

		return clone $this;
	}


	public function build(): State
	{
		if($this->version === NULL) {
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
