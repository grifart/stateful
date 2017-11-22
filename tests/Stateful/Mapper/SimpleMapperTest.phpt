<?php declare(strict_types=1);

namespace Grifart\TaxLibrary\Resolve\Infrastructure\Payload\Mapper;

use Grifart\Stateful\Exceptions\MapperException;
use Grifart\Stateful\Mapper\SimpleMapper;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$mapper = new SimpleMapper('MyApp\\Model\\Domain\\Events', 'Events', '.');


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
Assert::same( 'Events.Bla.Bla', $mapper->toTransferName('MyApp\\Model\\Domain\\Events\\Bla\\Bla') );
Assert::same(
	'Events.MyApp.Model.Domain.Events',
	$mapper->toTransferName('MyApp\\Model\\Domain\\Events\\MyApp\\Model\\Domain\\Events')
);

Assert::exception(
	function () use ($mapper) {
		$mapper->toTransferName('MyApp\\Model\\Domain\\Events\\MyApp\\Model\\Domain\\Events\\');
	},
	MapperException::class,
	"Fully qualified name cannot end with namespace separator. 'MyApp\\Model\\Domain\\Events\\MyApp\\Model\\Domain\\Events\\' given"
);


// to qualified name
Assert::null($mapper->toFullyQualifiedName('Bla'));
Assert::null($mapper->toFullyQualifiedName('transfer.My.Class'));
Assert::null($mapper->toFullyQualifiedName('transferNamespace'));

Assert::same(
	'MyApp\\Model\\Domain\\Events\\My\\Class',
	$mapper->toFullyQualifiedName('Events.My.Class')
);
Assert::same(
	'MyApp\\Model\\Domain\\Events\\Events',
	$mapper->toFullyQualifiedName('Events.Events')
);

