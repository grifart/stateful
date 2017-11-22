<?php
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\State;

/**
 * Any object implementing this interface na act as external (de)serializer
 */
interface Serializer
{
	/**
	 * Extract object state from given object.
	 *
	 * There must be enough data to {@see reconstructFromState} be able to reconstruct original object.
	 *
	 * @param $object
	 * @return \Grifart\Stateful\State|null
	 */
	public function extractState($object): ?State;


	/**
	 * Reconstruct object from object state.
	 *
	 * @param State $state
	 * @return object|null null means cannot deserialize; try to use another one
	 */
	public function reconstructFromState(State $state);
}