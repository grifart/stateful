<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;


function implementedStateful(): ImplementedStateful {
	return new ImplementedStateful();
}

/** @deprecated */
function statefulImplementation(): StatefulImplementation {
	return new StatefulImplementation();
}
