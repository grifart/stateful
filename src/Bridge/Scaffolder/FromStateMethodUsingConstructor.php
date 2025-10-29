<?php

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Closure;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;

final readonly class FromStateMethodUsingConstructor extends FromStateMethod
{
	#[\Override]
	protected function addFromStateReconstitution(
		Method $fromState,
		ClassDefinition $definition,
		Closure $addStateArrayShape,
	): void
	{
		$addStateArrayShape();

		$fromState->addBody('return new static(');

		foreach ($definition->getFields() as $field) {
			$fieldName = $field->getName();
			$fromState->addBody('\t?,', [new Literal('$' . $fieldName)]);
		}

		$fromState->addBody(");");
	}
}
