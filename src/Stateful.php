<?php declare(strict_types = 1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful;

/**
 * Stateful object can be anytime reconstructed from {@see State}.
 * Every stateful object must provide method for retrieving internal state
 * and must be able to reconstruct itself from given state.
 */
interface Stateful
{
	/**
	 * Method must return all properties that are needed for state reconstruction
	 * @internal this method should be used only for serialization purposes
	 */
	public function _getState(): State;

	/**
	 * Reconstruct object from given state.
	 * Only state that has been generated by getObjectState() can be passed.
	 * Use array access to receive object state
	 *
	 * @param State $state state of your object before serialization
	 * @return static
	 *
	 * @internal this method should be used for deserialization purposes
	 */
	public static function _fromState(State $state)/*: static todo when PHP ^8.0 */;

}
