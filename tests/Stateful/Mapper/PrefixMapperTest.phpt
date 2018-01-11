<?php declare(strict_types=1);

namespace Grifart\TaxLibrary\Resolve\Infrastructure\Payload\Mapper;

use Grifart\Stateful\Exceptions\MapperException;
use Grifart\Stateful\Mapper\PrefixMapper;
use Grifart\Stateful\Mapper\SimpleMapper;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$mapper = new PrefixMapper("Prefix.", [
	new SimpleMapper('MyApp\\Model\\Domain\\Events', 'Events', '.')
]);



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

Assert::same( 'Prefix.Events.Bla', $mapper->toTransferName('MyApp\\Model\\Domain\\Events\\Bla') );
Assert::same( 'Prefix.Events.Bla.Bla', $mapper->toTransferName('MyApp\\Model\\Domain\\Events\\Bla\\Bla') );
Assert::same(
	'Prefix.Events.MyApp.Model.Domain.Events',
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
Assert::null($mapper->toFullyQualifiedName('PrefixEvents.My.Class'));
Assert::null($mapper->toFullyQualifiedName('Events.My.Class'));

Assert::same(
	'MyApp\\Model\\Domain\\Events\\My\\Class',
	$mapper->toFullyQualifiedName('Prefix.Events.My.Class')
);
Assert::same(
	'MyApp\\Model\\Domain\\Events\\Events',
	$mapper->toFullyQualifiedName('Prefix.Events.Events')
);

Assert::null( $mapper->toFullyQualifiedName('EventsMigrator') );
