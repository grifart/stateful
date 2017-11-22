<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\State;

/** @internal used by PayloadProcessor */
final class ClosureSerializer
{

	/** @var callable */
	private $deserializer;

	/** @var bool */
	private $matchSubtypes;

	/** @var string */
	private $forType;

	public function __construct(callable $serializer, string $forType, bool $matchSubtypes)
	{
		$this->deserializer = $serializer;
		$this->matchSubtypes = $matchSubtypes;
		$this->forType = $forType;
	}

	public function isUsableFor($objectInstance) {
		if($this->matchSubtypes === FALSE) {
			return get_class($objectInstance) === $this->forType;
		}

		// match subtypes
		// reflection thing is a little hack to make instanceof working properly
		return $objectInstance instanceof $this->forType;
	}

	/**
	 * @param object $object
	 * @return State
	 */
	public function extractState($object): State
	{
		return call_user_func($this->deserializer, $object);
	}
}

