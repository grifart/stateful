<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\Capabilities\ImplementedInterface;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Grifart\Stateful\Stateful;

/**
 * Composite capability for {@see ImplementedInterface}({@see Stateful}) and {@see ImplementedStateful}
 */
final class ImplementedStatefulAndInterface implements Capability
{
	private ImplementedInterface $implementedInterface;
	private ImplementedStateful $implementedStateful;

	public function __construct(
		FromStateMethod|null $fromStateMethod = null,
	) {
		$this->implementedInterface = new ImplementedInterface(Stateful::class);
		$this->implementedStateful = new ImplementedStateful($fromStateMethod);
	}

	public function applyTo(
		ClassDefinition $definition,
		ClassInNamespace $draft,
		?ClassInNamespace $current,
	): void
	{
		$this->implementedInterface->applyTo($definition, $draft, $current);
		$this->implementedStateful->applyTo($definition, $draft, $current);
	}
}
