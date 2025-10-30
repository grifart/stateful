<?php

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Closure;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Nette\PhpGenerator\Method;

final readonly class FromStateMethodUsingAssignment extends FromStateMethod
{
	#[\Override]
	protected function addFromStateReconstitution(
		Method $fromState,
		ClassDefinition $definition,
		Closure $addStateArrayShape,
	): void
	{
		$fromState->addBody('$self = $state->makeAnEmptyObject(self::class);');
		$fromState->addBody("\assert(\$self instanceof static);\n");

		$addStateArrayShape();

		foreach ($definition->getFields() as $field) {
			$fieldName = $field->getName();

			// add Stateful::_fromState()
			$fromState->addBody('$self->? = $state[?];', [
				$fieldName,
				$fieldName,
			]);
		}

		$fromState->addBody("\nreturn \$self;");
	}
}
