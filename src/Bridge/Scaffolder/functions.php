<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;


function implementedStateful(
	FromStateMethod|null $fromStateMethod = null,
): ImplementedStateful {
	return new ImplementedStateful($fromStateMethod);
}

function implementedStatefulAndInterface(
	FromStateMethod|null $fromStateMethod = null,
): ImplementedStatefulAndInterface {
	return new ImplementedStatefulAndInterface($fromStateMethod);
}

/** @deprecated */
function statefulImplementation(): StatefulImplementation {
	return new StatefulImplementation();
}
