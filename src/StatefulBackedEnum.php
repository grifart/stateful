<?php declare(strict_types = 1);

namespace Grifart\Stateful;

/**
 * @phpstan-require-implements \BackedEnum
 */
trait StatefulBackedEnum
{
	public function _getState(): State
	{
		return StateBuilder::from($this)
			->version(1)
			->field('value', $this->value)
			->ignore('name')
			->build();
	}

	public static function _fromState(State $state): static
	{
		$state->ensureVersion(1);
		$state->ignoreProperty(['name']);
		/** @var array{value: int|string} $state */
		return self::from($state['value']);
	}
}
