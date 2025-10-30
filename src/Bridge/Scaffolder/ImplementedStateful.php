<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Nette\PhpGenerator\ClassType;

final readonly class ImplementedStateful implements Capability
{
	public function __construct(
		private FromStateMethod|null $fromStateMethod = null,
	) {}

	public function applyTo(
		ClassDefinition $definition,
		ClassInNamespace $draft,
		?ClassInNamespace $current,
	): void
	{
		(new GetStateMethod())->applyTo($definition, $draft, $current);

		$fromStateMethod = $this->fromStateMethod ?? $this->getDefaultFromStateMethod($draft->getClassType());
		$fromStateMethod->applyTo($definition, $draft, $current);
	}

	private function getDefaultFromStateMethod(ClassType $classType): FromStateMethod
	{
		return $classType->isReadOnly() && $classType->hasMethod('__construct')
			? new FromStateMethodUsingConstructor()
			: new FromStateMethodUsingAssignment();
	}
}
