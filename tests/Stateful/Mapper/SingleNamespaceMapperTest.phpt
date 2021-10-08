<?php declare(strict_types=1);

namespace Grifart\TaxLibrary\Resolve\Infrastructure\Payload\Mapper;

use Grifart\Stateful\MapperException;
use Grifart\Stateful\Mapper\SimpleMapper;
use Grifart\Stateful\Mapper\SingleNamespaceMapper;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$mapper = new SingleNamespaceMapper('MyApp\\Model\\Domain\\Events', 'Events.');


// To transfer name
Assert::null( $mapper->toTransferName('unknown-prefix') );
Assert::null( $mapper->toTransferName('unknown-prefix\\MyApp\\Model\\Domain\\Events') );
Assert::null( $mapper->toTransferName('MyApp\\Model\\Domain\\Events') );
Assert::exception(
	function () use ($mapper) {
		$mapper->toTransferName('MyApp\\Model\\Domain\\Events\\');
	},
	MapperException::class,
	"Fully qualified name cannot end with namespace separator. 'MyApp\\Model\\Domain\\Events\\' given"
);

Assert::same( 'Events.Bla', $mapper->toTransferName('MyApp\\Model\\Domain\\Events\\Bla') );
Assert::null( $mapper->toTransferName('MyApp\\Model\\Domain\\Events\\Bla\\Bla') );
Assert::null(
	$mapper->toTransferName('MyApp\\Model\\Domain\\Events\\MyApp\\Model\\Domain\\Events')
);

Assert::exception(
	function () use ($mapper) {
		$mapper->toTransferName('MyApp\\Model\\Domain\\Events\\MyApp\\Model\\Domain\\Events\\');
	},
	MapperException::class,
	"Fully qualified name cannot end with namespace separator. 'MyApp\\Model\\Domain\\Events\\MyApp\\Model\\Domain\\Events\\' given"
);

Assert::null( $mapper->toTransferName('MyApp\\Model\\Domain\\EventsMigrator') );


// to qualified name
Assert::null($mapper->toFullyQualifiedName('Bla'));
Assert::null($mapper->toFullyQualifiedName('transfer.My.Class'));
Assert::null($mapper->toFullyQualifiedName('transferNamespace'));

Assert::null(
	$mapper->toFullyQualifiedName('Events.My.Class')
);
Assert::same(
	'MyApp\\Model\\Domain\\Events\\Events',
	$mapper->toFullyQualifiedName('Events.Events')
);

Assert::null( $mapper->toFullyQualifiedName('EventsMigrator') );
