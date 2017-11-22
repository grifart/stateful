<?php declare(strict_types=1);

namespace Grifart\Stateful;

/** @internal */
final class Tools
{

	/**
	 * @return bool if PHP asserts are evaluated
	 */
	public static function areAssertsEvaluated(): bool
	{
		assert($assertsEnabled = TRUE); // will be evaluated when asserts enabled
		return isset($assertsEnabled);
	}

}
