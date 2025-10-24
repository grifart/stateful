<?php

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\Capabilities\CapabilityTools;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Grifart\ClassScaffolder\Definition\Field;
use Grifart\Stateful\State;
use Nette\PhpGenerator\Literal;

final readonly class FromStateMethodUsingConstructor implements Capability
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
		$fromState->addBody('$state->ensureVersion(1);\n');
		$fromState->addAttribute(\Override::class);

		$fromState->addBody(sprintf(
			'/** @var array{%s} $state */',
			implode(', ', array_map(
				static fn(Field $field) => sprintf('%s: %s', $field->getName(), $field->getType()->getDocCommentType($namespace)),
				$definition->getFields(),
			)),
		));

		$fromState->addBody('return new static(');

		foreach ($definition->getFields() as $field) {
			$fieldName = $field->getName();
			$fromState->addBody('\t?,', [new Literal('$' . $fieldName)]);
		}

		$fromState->addBody(");");
	}
}
