<?php

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Closure;
use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\Capabilities\CapabilityTools;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Grifart\ClassScaffolder\Definition\Field;
use Grifart\Stateful\State;
use Nette\PhpGenerator\Method;

abstract readonly class FromStateMethod implements Capability
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
		$fromState->addAttribute(\Override::class);

		$this->addFromStateReconstitution(
			$fromState,
			$definition,
			fn() => $this->addStateArrayShape($fromState, $definition, $draft),
		);
	}

	/**
	 * @param Closure(): void $addStateArrayShape
	 */
	abstract protected function addFromStateReconstitution(
		Method $fromState,
		ClassDefinition $definition,
		Closure $addStateArrayShape,
	): void;

	private function addStateArrayShape(
		Method $fromState,
		ClassDefinition $definition,
		ClassInNamespace $draft,
	): void
	{
		$fromState->addBody(sprintf(
			'/** @var array{%s} $state */',
			implode(', ', array_map(
				static fn(Field $field) => sprintf('%s: %s', $field->getName(), $field->getType()->getDocCommentType($draft->getNamespace())),
				$definition->getFields(),
			)),
		));
	}
}
