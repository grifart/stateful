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

	public function isUsableFor(string $type): bool
	{
		if($this->matchSubtypes === FALSE) {
			return $type === $this->forType;
		}

		return is_a($type, $this->forType, TRUE);
	}

	public function reconstructFromState(State $object): object
	{
		$createdObject = call_user_func($this->deserializer, $object);

		// guaranteed by closure return type
		assert($createdObject instanceof $this->forType);

		return $createdObject;
	}
}

