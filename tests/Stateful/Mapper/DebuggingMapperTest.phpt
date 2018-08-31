<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$mapper = new DebuggingMapper(
	new SingleNamespaceMapper('Namespace', 'namespace.'),
	FALSE
);

$mapper->toFullyQualifiedName('namespace.Test');
$mapper->toTransferName('Namespace\\Test2');

Assert::same(
	[
		'namespace.Test' => 'Namespace\Test',
		'namespace.Test2' => 'Namespace\Test2'
	],
	$mapper->getTranslatedNames()
);


