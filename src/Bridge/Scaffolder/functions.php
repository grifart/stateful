<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;


function implementedStateful(
	FromStateMethod $fromStateMethod = new FromStateMethodUsingAssignment(),
): ImplementedStateful {
	return new ImplementedStateful($fromStateMethod);
}

function implementedStatefulAndInterface(
	FromStateMethod $fromStateMethod = new FromStateMethodUsingAssignment(),
): ImplementedStatefulAndInterface {
	return new ImplementedStatefulAndInterface($fromStateMethod);
}

/** @deprecated */
function statefulImplementation(): StatefulImplementation {
	return new StatefulImplementation();
}
