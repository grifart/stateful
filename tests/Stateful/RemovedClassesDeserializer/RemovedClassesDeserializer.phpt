<?php declare(strict_types = 1);

namespace Grifart\Stateful\RemovedClassesDeserializer;

use Grifart\Stateful\Exceptions\NoAppropriateDeserializerFoundException;
use Grifart\Stateful\Exceptions\RemovedClassesDeserializerException;
use Grifart\Stateful\Exceptions\RemovedClassException;
use Grifart\Stateful\State;
use Grifart\Stateful\Stateful;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

class TestClass implements Stateful
{
	public function _getState(): State
	{
		return State::from($this, 1, []);
	}

	public static function _fromState(State $state): static
	{
		$state->ensureVersion(1);
		$self = $state->makeAnEmptyObject(self::class);
		return $self;
	}
}

$removedClassesDeserializer = RemovedClassesDeserializer::from([
	SomeRemovedClass::class,
	AnotherRemovedClass::class => static function (State $state) {
		return $state['anotherField'];
	},
	InvalidRemovedClass::class => static function () {
		return new TestClass;
	},
]);

$someClassState = new State(SomeRemovedClass::class, 1, ['field' => 'value']);
$deserializedSomeClass = $removedClassesDeserializer->deserialize($someClassState);
Assert::type(RemovedClass::class, $deserializedSomeClass);
Assert::same('value', $deserializedSomeClass->field);
Assert::throws(function () use ($deserializedSomeClass): void {
	$deserializedSomeClass->bar;
}, RemovedClassException::class, 'Cannot access unknown property RemovedClass::$bar.');

$anotherClassState = new State(AnotherRemovedClass::class, 1, ['anotherField' => 'anotherValue']);
$deserializedAnotherClass = $removedClassesDeserializer->deserialize($anotherClassState);
Assert::type('string', $deserializedAnotherClass);
Assert::same('anotherValue', $deserializedAnotherClass);

$invalidClassState = new State(InvalidRemovedClass::class, 1, []);
Assert::throws(static function () use ($removedClassesDeserializer, $invalidClassState): void {
	$removedClassesDeserializer->deserialize($invalidClassState);
}, RemovedClassesDeserializerException::class);

$unconfiguredClassState = new State(UnconfiguredRemovedClass::class, 1, ['whatever' => 42]);
Assert::throws(static function () use ($removedClassesDeserializer, $unconfiguredClassState): void {
	$removedClassesDeserializer->deserialize($unconfiguredClassState);
}, NoAppropriateDeserializerFoundException::class);

Assert::throws(static function (): void {
	RemovedClassesDeserializer::from([TestClass::class]);
}, RemovedClassesDeserializerException::class);
