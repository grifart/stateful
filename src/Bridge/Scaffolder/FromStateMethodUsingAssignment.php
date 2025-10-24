<?php

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\Capabilities\CapabilityTools;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Grifart\ClassScaffolder\Definition\Field;
use Grifart\Stateful\State;

final readonly class FromStateMethodUsingAssignment implements Capability
{
	public function applyTo(
		ClassDefinition $definition,
		ClassInNamespace $draft,
		?ClassInNamespace $current,
	): void
	{
		$namespace = $draft->getNamespace();
		$classType = $draft->getClassType();
		CapabilityTools::checkIfAllFieldsArePresent($definition, $classType);

		$namespace->addUse(State::class);

		$fromState = $classType->addMethod('_fromState');
		$fromState->setVisibility('public');
		$fromState->setStatic(TRUE);
		$fromState->setReturnType('static');
		$fromState->addParameter('state')->setType(State::class);
		$fromState->addBody('$state->ensureVersion(1);');
		$fromState->addBody('$self = $state->makeAnEmptyObject(self::class);');
		$fromState->addBody("\assert(\$self instanceof static);\n");
		$fromState->addAttribute(\Override::class);

		$fromState->addBody(sprintf(
			'/** @var array{%s} $state */',
			implode(', ', array_map(
				static fn(Field $field) => sprintf('%s: %s', $field->getName(), $field->getType()->getDocCommentType($namespace)),
				$definition->getFields(),
			)),
		));

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
