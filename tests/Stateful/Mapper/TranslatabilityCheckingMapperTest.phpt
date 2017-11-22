<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

use AssertionError;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$neverKnowsTheAnswer = new class implements Mapper {
	public function toTransferName(string $fullyQualifiedName): ?string { return NULL; }
	public function toFullyQualifiedName(string $transferName): ?string { return NULL; }
};

$alwaysKnowsTheAnswer = new class implements Mapper {
	public function toTransferName(string $fullyQualifiedName): ?string { return 'something fqn->transfer'; }
	public function toFullyQualifiedName(string $transferName): ?string { return 'something transfer->fqn'; }
};


// FALSE => FALSE
$uutFalseFalse = new TranslatabilityCheckingMapper($neverKnowsTheAnswer, $neverKnowsTheAnswer);
Assert::same(NULL, $uutFalseFalse->toTransferName('whatever'));
Assert::same(NULL, $uutFalseFalse->toFullyQualifiedName('whatever'));


// FALSE => TRUE
$uutFalseTrue = new TranslatabilityCheckingMapper($neverKnowsTheAnswer, $alwaysKnowsTheAnswer);
Assert::same(NULL, $uutFalseTrue->toTransferName('whatever'));
Assert::same(NULL, $uutFalseTrue->toFullyQualifiedName('whatever'));


// TRUE => FALSE
$uutTrueFalse = new TranslatabilityCheckingMapper($alwaysKnowsTheAnswer, $neverKnowsTheAnswer);
Assert::exception(function () use ($uutTrueFalse) {
	$uutTrueFalse->toTransferName('whatever');
},AssertionError::class, "Mapper consistency check failed (FQN --X--> transfer). First mapper knows the answer and second does NOT, for then name 'whatever'.");

Assert::exception(function () use ($uutTrueFalse) {
	$uutTrueFalse->toFullyQualifiedName('whatever');
},AssertionError::class, "Mapper consistency check failed (transfer1->FQN1 --X--> transfer2->FQN2). Intermediate check FQN1->transfer2 failed. Mapper2 cannot translate 'something transfer->fqn' to transfer name.");

Assert::exception(function () use ($alwaysKnowsTheAnswer) {
	$uut = new TranslatabilityCheckingMapper($alwaysKnowsTheAnswer, new class implements Mapper {
		public function toTransferName(string $fullyQualifiedName): ?string
		{
			return 'something fqn->transfer';
		}
		public function toFullyQualifiedName(string $transferName): ?string
		{
			return NULL;
		}
	});
	$uut->toFullyQualifiedName('whatever');
},AssertionError::class, "Mapper consistency check failed (transfer1->FQN1->transfer2 --X--> FQN2). First mapper knows how to translate 'whatever', second does not know hot to translate 'something fqn->transfer' to FQN.");


// TRUE => TRUE
$uutTrueTrue = new TranslatabilityCheckingMapper($alwaysKnowsTheAnswer, $alwaysKnowsTheAnswer);
Assert::same('something fqn->transfer', $uutTrueTrue->toTransferName('whatever'));
Assert::same('something transfer->fqn', $uutTrueTrue->toFullyQualifiedName('whatever'));

