<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;

final readonly class ImplementedStateful implements Capability
{
	public function __construct(
		private FromStateMethod $fromStateMethod = new FromStateMethodUsingAssignment(),
	) {}

	public function applyTo(
		ClassDefinition $definition,
		ClassInNamespace $draft,
		?ClassInNamespace $current,
	): void
	{
		(new GetStateMethod())->applyTo($definition, $draft, $current);
		$this->fromStateMethod->applyTo($definition, $draft, $current);
	}
}
