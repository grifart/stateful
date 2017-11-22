<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

use Mockery;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$mapper = new MapperList();

// Mapper always return NULL
$mapper->addMapper($mapper1 = Mockery::mock(Mapper::class));
$mapper1->shouldReceive([
	'toTransferName' => NULL,
	'toFullyQualifiedName' => NULL
]);

Assert::null($mapper->toFullyQualifiedName('whatever'));
Assert::null($mapper->toTransferName('whatever'));



// Mapper accepts some of given classes
$mapper->addMapper(new class implements Mapper {

	public function toTransferName(string $fullyQualifiedName): ?string
	{
		return $fullyQualifiedName === 'qualified' ? 'transfer' : NULL;
	}
	public function toFullyQualifiedName(string $transferName): ?string
	{
		return $transferName === 'transfer' ? 'qualified' : NULL;
	}

});


Assert::same( 'qualified', $mapper->toFullyQualifiedName('transfer'));
Assert::same('transfer', $mapper->toTransferName('qualified'));

Assert::null($mapper->toFullyQualifiedName('whatever'));
Assert::null($mapper->toTransferName('whatever'));



// Accept everything

$mapper->addMapper(new class implements Mapper {

	public function toTransferName(string $fullyQualifiedName): ?string
	{
		return $fullyQualifiedName;
	}
	public function toFullyQualifiedName(string $transferName): ?string
	{
		return $transferName;
	}

});


Assert::same( 'qualified', $mapper->toFullyQualifiedName('transfer'));
Assert::same('transfer', $mapper->toTransferName('qualified'));

Assert::same('whatever', $mapper->toFullyQualifiedName('whatever'));
Assert::same('whatever', $mapper->toTransferName('whatever'));

Mockery::close();
