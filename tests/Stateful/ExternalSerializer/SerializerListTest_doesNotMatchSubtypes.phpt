<?php declare(strict_types=1);

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\State;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/testClasses.php';

$a = new A;
$b = new B;
$c = new C;

$serializer = function(\DateTime $dateTime): State {
	return State::from($dateTime, 1, [
		'date' => $dateTime->format('c')
	]);
};

$unserializer = function (State $state): \DateTime {
	assert($state->getVersion() === 1);
	return new \DateTime($state['date']);
};

// SERIALIZER:
$list1 = SerializerList::from([$serializer]);
Assert::type(State::class, $list1->extractState(new \DateTime()));
Assert::null($list1->extractState(new \DateTimeImmutable()));

// UNSERIALIZER:
$list2 = SerializerList::from([$unserializer]);
$objectState_dateTime = new State(\DateTime::class, 1, ['date' => '2017-02-22T14:28:05+00:00']);
$objectState_dateTimeImmutable = new State(\DateTimeImmutable::class, 1, ['date' => '2017-02-22T14:28:05+00:00']);
Assert::type(\DateTime::class, $list2->reconstructFromState($objectState_dateTime));
Assert::null($list2->reconstructFromState($objectState_dateTimeImmutable));
