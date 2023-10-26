<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\Capabilities\CapabilityTools;
use Grifart\ClassScaffolder\Capabilities\ImplementedInterface;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;
use Grifart\ClassScaffolder\Definition\Field;
use Grifart\Stateful\State;
use Grifart\Stateful\StateBuilder;
use Grifart\Stateful\Stateful;
use function array_map;
use function implode;
use function sprintf;


/**
 * Composite capability for {@see ImplementedInterface}({@see Stateful}) and {@see ImplementedStateful}
 */
final class ImplementedStatefulAndInterface implements Capability
{
	private ImplementedInterface $implementedInterface;
	private ImplementedStateful $implementedStateful;

	public function __construct()
	{
		$this->implementedInterface = new ImplementedInterface(Stateful::class);
		$this->implementedStateful = new ImplementedStateful();
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
