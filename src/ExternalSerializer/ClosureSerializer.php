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

	/**
	 * @param \Closure(object $instance): State $serializer
	 */
	public function __construct(
		private \Closure $serializer,
		private string $forType,
		private bool $matchSubtypes,
	) {}

	public function isUsableFor(object $objectInstance): bool
	{
		if($this->matchSubtypes === FALSE) {
			return get_class($objectInstance) === $this->forType;
		}

		// match subtypes
		return $objectInstance instanceof $this->forType;
	}

	public function extractState(object $object): State
	{
		return ($this->serializer)($object);
	}
}

