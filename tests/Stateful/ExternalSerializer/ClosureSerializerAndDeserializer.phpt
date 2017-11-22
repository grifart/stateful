<?php declare(strict_types=1);

namespace Grifart\Stateful\ExternalSerializer;

use Grifart\Stateful\State;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/testClasses.php';

$a = new A;
$b = new B;
$c = new C;

// SERIALIZER:

// do NOT match subtypes
$serializer1 = new ClosureSerializer(function() {}, B::class, FALSE);
Assert::false($serializer1->isUsableFor($a));
Assert::true ($serializer1->isUsableFor($b));
Assert::false($serializer1->isUsableFor($c));

// DO match subtypes
$serializer2 = new ClosureSerializer(function() {}, B::class, TRUE);
Assert::false($serializer2->isUsableFor($a));
Assert::true ($serializer2->isUsableFor($b));
Assert::true ($serializer2->isUsableFor($c));


// DESERIALIZER:

// do NOT match subtypes
$deserializer1 = new ClosureDeserializer(function() {}, B::class, FALSE);
Assert::false($deserializer1->isUsableFor(A::class));
Assert::true ($deserializer1->isUsableFor(B::class));
Assert::false($deserializer1->isUsableFor(C::class));

// DO match subtypes
$deserializer2 = new ClosureDeserializer(function() {}, B::class, TRUE);
Assert::false($deserializer2->isUsableFor(A::class));
Assert::true ($deserializer2->isUsableFor(B::class));
Assert::true ($deserializer2->isUsableFor(C::class));


// SERIALIZER: Does it call closure properly?
$objectState3 = NULL;
$serializer3 = new ClosureSerializer(
	function(A $obj) use (&$objectState3) {return ($objectState3 = State::from($obj, 1, []));},
	A::class,
	FALSE
);
$objectState3_returned = $serializer3->extractState(new A);
Assert::type(State::class, $objectState3);
Assert::same($objectState3, $objectState3_returned);


// DESERIALIZER: Does is call closure properly?
$object4 = NULL;
$serializer4 = new ClosureDeserializer(
	function(State $state) use (&$object4): A {assert($state->getVersion() === 1); return ($object4 = new A);},
	A::class,
	FALSE
);
$objectState4_returned = $serializer4->reconstructFromState(
	new State(A::class, 1, [])
);
Assert::type(A::class, $object4);
Assert::same($object4, $objectState4_returned);
