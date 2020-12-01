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

	private mixed $data;


	public function __construct(mixed $root)
	{
		$this->data = $root;
	}


	public function getPrimitives(): mixed
	{
		return $this->data;
	}


	public function jsonSerialize(): mixed
	{
		return $this->getPrimitives();
	}
}
