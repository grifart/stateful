<?php

/**
 * Do not edit. This is generated file. Modify definition file instead.
 */

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\Stateful\State;
use Grifart\Stateful\StateBuilder;
use Grifart\Stateful\Stateful;

final class GeneratedWithInterface implements Stateful
{
	public function __construct(
		private string $value,
	) {
	}


	#[\Override]
	public function _getState(): State
	{
		return StateBuilder::from($this)
			->version(1)
			->field('value', $this->value)
			->build();
	}


	#[\Override]
	public static function _fromState(State $state): static
	{
		$state->ensureVersion(1);

		/** @var array{value: string} $state */
		return new static(
			$state['value'],
		);
	}
}
