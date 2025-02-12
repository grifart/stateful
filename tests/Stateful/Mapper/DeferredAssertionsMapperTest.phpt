<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

use Grifart\Stateful\UsageException;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$fakeMapper = new class implements Mapper {
	public function toTransferName(string $fullyQualifiedName): ?string
	{
		$transferName = 'transfer name';
		if ($fullyQualifiedName === 'assertion error') {
			throw new AssertionFailed('to transfer name faked message', $fullyQualifiedName, $transferName);
		}
		return $transferName;
	}


	public function toFullyQualifiedName(string $transferName): ?string
	{
		$fqn = 'fqn';

		if ($transferName === 'assertion error') {
			throw new AssertionFailed('to fqn faked message', $transferName, $fqn);
		}
		return $fqn;
	}
};

$deferredMapper = new DeferredAssertionsMapper($fakeMapper);

// check that it returns proper data
Assert::same('transfer name', $deferredMapper->toTransferName('something valid'));
Assert::same('fqn', $deferredMapper->toFullyQualifiedName('exception'));

// check that faked mapper really throws exceptions for given input values
Assert::exception(function () use ($fakeMapper) {
	$fakeMapper->toFullyQualifiedName('assertion error');
}, \AssertionError::class);
Assert::exception(function () use ($fakeMapper) {
	$fakeMapper->toTransferName('assertion error');
}, \AssertionError::class);

// check deferring functionality
Assert::same('transfer name', $deferredMapper->toTransferName('assertion error'));
Assert::same('fqn', $deferredMapper->toFullyQualifiedName('assertion error'));

// throws exception in destructor
Assert::throws(function () use ($deferredMapper) {
	/** @noinspection ImplicitMagicMethodCallInspection */
	$deferredMapper->__destruct();
}, UsageException::class);

// Check that it catches assertions in proper order
[$toTransferNameError, $toFqnError] = $deferredMapper->getFailedAssertions();
Assert::same('to transfer name faked message', $toTransferNameError->getMessage());
Assert::same('assertion error', $toTransferNameError->getInput());
Assert::same('transfer name', $toTransferNameError->getOutput());

Assert::same('to fqn faked message', $toFqnError->getMessage());
Assert::same('assertion error', $toFqnError->getInput());
Assert::same('fqn', $toFqnError->getOutput());

// otherwise will throw error again when destructor will be called again
$deferredMapper->clearFailedAssertions();
