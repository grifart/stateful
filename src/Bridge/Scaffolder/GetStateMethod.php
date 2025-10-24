<?php

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\Capabilities\CapabilityTools;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Grifart\Stateful\State;
use Grifart\Stateful\StateBuilder;

final readonly class GetStateMethod implements Capability
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

		$namespace->addUse(StateBuilder::class);
		$namespace->addUse(State::class);

		$getState = $classType->addMethod('_getState');
		$getState->setVisibility('public');
		$getState->setReturnType(State::class);
		$getState->addBody('return StateBuilder::from($this)');
		$getState->addBody("\t->version(1)");
		$getState->addAttribute(\Override::class);

		foreach ($definition->getFields() as $field) {
			$fieldName = $field->getName();

			// add Stateful::_getState()
			$getState->addBody("\t->field(?, \$this->?)", [
				$fieldName,
				$fieldName,
			]);
		}

		$getState->addBody("\t->build();");
	}
}
