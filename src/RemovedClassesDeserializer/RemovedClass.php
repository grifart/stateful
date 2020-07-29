<?php

declare(strict_types=1);

namespace Grifart\Stateful\RemovedClassesDeserializer;

use Grifart\Stateful\State;

final class RemovedClass extends \stdClass
{
	private function __construct()
	{
	}

	public static function fromState(State $state)
	{
		$self = new self();
		foreach ($state as $key => $value) {
			$self->$key = $value;
		}

		return $self;
	}
}
