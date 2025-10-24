<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\Capabilities\CapabilityTools;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Grifart\ClassScaffolder\Definition\Field;
use Grifart\Stateful\State;
use Grifart\Stateful\StateBuilder;
use function array_map;
use function implode;
use function sprintf;


final readonly class ImplementedStateful implements Capability
{
	public function __construct(
		private Capability $fromStateMethod = new FromStateMethodUsingAssignment(),
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
