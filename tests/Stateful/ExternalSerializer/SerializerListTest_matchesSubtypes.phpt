<?php declare(strict_types=1);

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\State;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/testClasses.php';

$a = new A;
$b = new B;
$c = new C;

/** @matchSubtypes */
$serializer = function(\DateTimeInterface $dateTimeImmutable): State {
	return State::from($dateTimeImmutable, 1, [
		'date' => $dateTimeImmutable->format('c')
	]);
};

/** @matchSubtypes */
$unserializer = function (State $state): \DateTimeInterface {
	switch ($state->getVersion()) {
		case 1:
			$class = $state->getClassName();
			$createdClass = new $class($state['date']);

			return $createdClass;
	}

	throw new \Exception("Version is not supported");
};


// SERIALIZER:
$list1 = SerializerList::from($serializer);
Assert::type(State::class, $list1->extractState(new \DateTime()));
Assert::type(State::class, $list1->extractState(new \DateTimeImmutable()));

// UNSERIALIZER:
$list2 = SerializerList::from($unserializer);
$objectState_dateTime = new State(\DateTime::class, 1, ['date' => '2017-02-22T14:28:05+00:00']);
$objectState_dateTimeImmutable = new State(\DateTimeImmutable::class, 1, ['date' => '2017-02-22T14:28:05+00:00']);
Assert::type(\DateTime::class, $list2->reconstructFromState($objectState_dateTime));
Assert::type(\DateTimeImmutable::class, $list2->reconstructFromState($objectState_dateTimeImmutable));

// wrong serialized type
$objectState_dateTimeInterface = new State(\DateTimeInterface::class, 1, ['date' => '2017-02-22T14:28:05+00:00']);
Assert::exception(function() use ($objectState_dateTimeInterface, $list2) {
	$list2->reconstructFromState($objectState_dateTimeInterface);
}, \Error::class);
