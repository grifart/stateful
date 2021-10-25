<?php declare(strict_types=1);

namespace Grifart\Stateful\ExternalSerializer\__tests;

require __DIR__ . '/../../bootstrap.php';

use Grifart\Stateful\ExternalSerializerException;
use Grifart\Stateful\ExternalSerializer\SerializerList;
use Grifart\Stateful\State;
use Tester\Assert;


interface testInterface {}
abstract class abstractClass {}

// serializer
Assert::exception(function () {
	SerializerList::from(
		function (testInterface $test): State {}
	);
}, ExternalSerializerException::class);

Assert::exception(function () {
	SerializerList::from(
		function (abstractClass $test): State {}
	);
}, ExternalSerializerException::class);

// deserializer
Assert::exception(function () {
	SerializerList::from(
		function (State $test): testInterface {}
	);
}, ExternalSerializerException::class);
Assert::exception(function () {
	SerializerList::from(
		function (State $test): abstractClass {}
	);
}, ExternalSerializerException::class);



// allows serializer when using @matchSubtypes
Assert::type(SerializerList::class, SerializerList::from(
	/** @matchSubtypes */
	function (testInterface $test): State {},
	/** @matchSubtypes */
	function (State $test): testInterface {},

	/** @matchSubtypes */
	function (abstractClass $test): State {},
	/** @matchSubtypes */
	function (State $test): abstractClass {}
));
