<?php declare(strict_types = 1);

namespace Grifart\Stateful\RemovedClassesDeserializer;

use Grifart\Stateful\Exceptions\NoAppropriateDeserializerFoundException;
use Grifart\Stateful\State;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$removedClassesDeserializer = RemovedClassesDeserializer::from([
	SomeRemovedClass::class,
	AnotherRemovedClass::class => static function (State $state) {
		return $state['anotherField'];
	},
]);

$someClassState = new State(SomeRemovedClass::class, 1, ['field' => 'value']);
$deserializedSomeClass = $removedClassesDeserializer->deserialize($someClassState);
Assert::type(RemovedClass::class, $deserializedSomeClass);
Assert::same('value', $deserializedSomeClass->field);

$anotherClassState = new State(AnotherRemovedClass::class, 1, ['anotherField' => 'anotherValue']);
$deserializedAnotherClass = $removedClassesDeserializer->deserialize($anotherClassState);
Assert::type('string', $deserializedAnotherClass);
Assert::same('anotherValue', $deserializedAnotherClass);

$unconfiguredClassState = new State(UnconfiguredRemovedClass::class, 1, ['whatever' => 42]);
Assert::throws(static function () use ($removedClassesDeserializer, $unconfiguredClassState): void {
	$removedClassesDeserializer->deserialize($unconfiguredClassState);
}, NoAppropriateDeserializerFoundException::class);
