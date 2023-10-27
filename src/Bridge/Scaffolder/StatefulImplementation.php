<?php

declare(strict_types=1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\Capability;
use Grifart\ClassScaffolder\ClassInNamespace;
use Grifart\ClassScaffolder\Definition\ClassDefinition;

/** @deprecated use {@see ImplementedStateful} instead */
final class StatefulImplementation implements Capability
{
	private ImplementedStateful $replacement;

	public function __construct()
	{
		$this->replacement = new ImplementedStateful();
	}

	public function applyTo(
		ClassDefinition $definition,
		ClassInNamespace $draft,
		?ClassInNamespace $current,
	): void
	{
		$this->replacement->applyTo($definition, $draft, $current);
	}
}
