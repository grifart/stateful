<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\State;

/** @internal used by PayloadProcessor */
final class ClosureDeserializer
{

	/**
	 * @param \Closure(State $state): object $deserializer
	 */
	public function __construct(
		private \Closure $deserializer,
		private string $forType,
		private bool $matchSubtypes,
	) {}

	public function isUsableFor(string $type): bool
	{
		if($this->matchSubtypes === FALSE) {
			return $type === $this->forType;
		}

		return is_a($type, $this->forType, TRUE);
	}

	public function reconstructFromState(State $object): object
	{
		$createdObject = ($this->deserializer)($object);

		// guaranteed by closure return type
		assert($createdObject instanceof $this->forType);

		return $createdObject;
	}
}

