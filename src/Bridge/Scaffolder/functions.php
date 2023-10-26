<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;


function implementedStateful(): ImplementedStateful {
	return new ImplementedStateful();
}

function implementedStatefulAndInterface(): ImplementedStatefulAndInterface {
	return new ImplementedStatefulAndInterface();
}

/** @deprecated */
function statefulImplementation(): StatefulImplementation {
	return new StatefulImplementation();
}
