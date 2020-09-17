<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\State;

/**
 * Serializer that never (de)serializes anything
 */
final class NullSerializer implements Serializer
{
	public static function getInstance(): NullSerializer
	{
		static $instance;
		if($instance !== NULL) {
			return $instance;
		}
		return ($instance = new self());
	}

	public function extractState($object): ?State
	{
		return NULL;
	}

	public function reconstructFromState(State $state)
	{
		return NULL;
	}
}
