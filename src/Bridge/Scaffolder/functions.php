<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;


use Grifart\ClassScaffolder\Capabilities\Capability;

function implementedStateful(
	Capability $fromStateMethod = new FromStateMethodUsingAssignment(),
): ImplementedStateful {
	return new ImplementedStateful($fromStateMethod);
}

function implementedStatefulAndInterface(
	Capability $fromStateMethod = new FromStateMethodUsingAssignment(),
): ImplementedStatefulAndInterface {
	return new ImplementedStatefulAndInterface($fromStateMethod);
}

/** @deprecated */
function statefulImplementation(): StatefulImplementation {
	return new StatefulImplementation();
}
