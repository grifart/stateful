<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful;

/**
 * Value primitivized by PayloadProcessor
 */
final class Payload implements \JsonSerializable//implements \JsonSerializable, \Traversable, \IteratorAggregate, \ArrayAccess
{

	// Payload implementation:

	/** @var mixed */
	private $data;

	public function __construct($root)
	{
		$this->data = $root;
	}


	/**
	 * @return mixed The payload
	 */
	public function getPrimitives()
	{
		return $this->data;
	}


	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		return $this->getPrimitives();
	}
}
