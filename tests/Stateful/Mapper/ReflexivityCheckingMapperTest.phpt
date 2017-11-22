<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

// There is no problem if there two names for single object in transfer names as they can be canonicalized.
// However there must be only one possible translation of transfer name to FQN.
// Lets check it out...

// todo: If the have something like prefix mapper, we can actually proof that the will be no such conflict.

$original = MapperList::from(
	new SimpleMapper('A\\a', 'a', '.'),
	new SimpleMapper('A\\b', 'b', '.'),
	new SimpleMapper('A\\c', 'a', '.')
);

$uut = new ReflexivityCheckingMapper($original);

Assert::same('a.c', $uut->toTransferName('A\\a\\c'));
Assert::same('b.c', $uut->toTransferName('A\\b\\c'));
Assert::exception(
	function() use ($uut) {
		$uut->toTransferName('A\\c\\c');
	},
	\AssertionError::class,
	"Checked mapper translated FQN -> transfer name -> FQN to different name that was originally. Expected to get 'A\c\c', got 'A\a\c'."
);
Assert::same(NULL, $uut->toTransferName('A\\d\\c'));

Assert::same('A\\a\\c', $uut->toFullyQualifiedName('a.c'));
Assert::same('A\\b\\c', $uut->toFullyQualifiedName('b.c'));
Assert::same(NULL, $uut->toFullyQualifiedName('c.c'));
