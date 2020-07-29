<?php declare(strict_types=1);
/**
 * @TestCase
 */

namespace Grifart\Stateful;

use Grifart\Stateful\Exceptions\MalformedPayloadException;
use Grifart\Stateful\ExternalSerializer\SerializerList;
use Grifart\Stateful\Mapper\TrivialMapper;
use Grifart\Stateful\RemovedClassesDeserializer\RemovedClass;
use Grifart\Stateful\RemovedClassesDeserializer\RemovedClassesDeserializer;
use Grifart\Stateful\TestClasses;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/PayloadProcessorTest_testClasses.php';

class PayloadProcessorTest extends TestCase
{

	private function provideProcessor(
		array $serializers = [],
		array $removedClassesDeserializers = []
	): PayloadProcessor
	{
		return new PayloadProcessor(
			new TrivialMapper(),
			SerializerList::from(...$serializers),
			RemovedClassesDeserializer::from($removedClassesDeserializers)
		);
	}

	public function testEmptyClass(): void
	{
		$processor = $this->provideProcessor();
		$testClass = new TestClasses\EmptyClass();
		$payload = $processor->toPayload($testClass);
		Assert::same([
			'@(meta)' => ['type' => 'object', 'name' => TestClasses\EmptyClass::class, 'serializationVersion' => 1],
			// no properties
		], $payload->getPrimitives());
	}

	public function testDateTimeImmutable(): void
	{
		$processor = new PayloadProcessor(
			new TrivialMapper(),
			SerializerList::from(
				// Make stateful externally:

				// Datetime:
				/** @matchSubtypes */
				function(\DateTimeInterface $dateTimeImmutable): State {
					return State::from($dateTimeImmutable, 1, [
						'date' => $dateTimeImmutable->format('c')
					]);
				},

				/** @matchSubtypes */
				function (State $state): \DateTimeInterface {
					switch ($state->getVersion()) {
						case 1:
							$class = $state->getClassName();
							$createdClass = new $class($state['date']);

							return $createdClass;
					}

					throw new \Exception("Version is not supported");
				}
			)
		);
		$testClass = new TestClasses\ObjectWithUnserializableDateTimeImmutable;

		$payload = $processor->toPayload($testClass);

		Assert::same([
			'@(meta)' => ['type' => 'object', 'name' => TestClasses\ObjectWithUnserializableDateTimeImmutable::class, 'serializationVersion' => 1],
			'myDate'  => [
				'@(meta)' => ['type' => 'object', 'name' => \DateTimeImmutable::class],
				'date'    => '2017-02-17T20:20:22+01:00',
			],
		], $payload->getPrimitives());

		$processor->fromPayload($payload);
	}


	public function testOneProperty(): void
	{
		$processor = $this->provideProcessor();

		$testClass = new TestClasses\ComplexObjectWithReferenceToOtherObject(new TestClasses\SimpleObjectWithPrimitiveProperties());
		$payload = $processor->toPayload($testClass);

		Assert::same([
				'@(meta)' => [
					'type' => 'object',
					'name' => TestClasses\ComplexObjectWithReferenceToOtherObject::class,
					'serializationVersion' => 1,
				],
				'bool' => TRUE,
				'float' => 5.5,
				'int' => 5,
				'object' => [
					'@(meta)' => [
						'type' => 'object',
						'name' => TestClasses\SimpleObjectWithPrimitiveProperties::class,
						'stateVersion' => 36713727,
					],
					'float' => 15,
					'string' => 8,
				],
				'string' => 'hello!',
			]
			, $payload->getPrimitives());
	}

	public function testState_oneProperty(): void
	{
		$testClass = new TestClasses\ComplexObjectWithReferenceToOtherObject(new TestClasses\SimpleObjectWithPrimitiveProperties());
		$state = $testClass->_getState();

		Assert::same(TRUE, $state['bool']);
		Assert::same(5.5, $state['float']);
		Assert::same(5  , $state['int']);
		/** @var State $objectState */
		Assert::type(TestClasses\SimpleObjectWithPrimitiveProperties::class, $state['object']);

		Assert::same('hello!', $state['string']);

		Assert::count(5, $state);
	}

	public function test_arrayWithPrimitives(): void
	{
		$processor = $this->provideProcessor();

		$testClass = new TestClasses\ObjectWithAnArrayOfPrimitives;

		$payload = $processor->toPayload($testClass);

		Assert::same(
			[
				'@(meta)' => [
					'type' => 'object',
					'name' => TestClasses\ObjectWithAnArrayOfPrimitives::class,
					'serializationVersion' => 1
				],
				'list' => [
					'@(meta)' => ['type' => 'array'],
					'key-0' => 0,
					0,
					'key-1' => 1,
					1,
					'key-2' => 2,
					2,
					'key-3' => 3,
					3,
					'key-4' => 4,
					4,
					'key-5' => 5,
					5,
					'key-6' => 6,
					6,
					'key-7' => 7,
					7,
					'key-8' => 8,
					8,
					'key-9' => 9,
					9,
				],
			],
			$payload->getPrimitives()
		);
	}

	public function test_arrayWithObjects(): void
	{
		$processor = $this->provideProcessor([

			/** @matchSubtypes */
			function(\DateTimeInterface $date): State {
				return State::from($date, 1, [
					'date' => $date->format('c')
				]);
			}
		]);
		$testClass = new TestClasses\ObjectWithAnArrayOfObjects();
		$payload = $processor->toPayload($testClass);

		Assert::same(
			[
				'@(meta)' => [
					'type' => 'object',
					'name' => TestClasses\ObjectWithAnArrayOfObjects::class,
					'serializationVersion' => 1
				],
				'list' => [
					'@(meta)' => ['type' => 'array'],
					[
						'@(meta)' => [
							'type' => 'object',
							'name' => TestClasses\ComplexObjectWithReferenceToOtherObject::class,
						],
						'bool' => TRUE,
						'float' => 5.5,
						'int' => 5,
						'object' => [
							'@(meta)' => [
								'type' => 'object',
								'name' => TestClasses\SimpleObjectWithPrimitiveProperties::class,
								'stateVersion' => 36713727,
							],
							'float' => 15,
							'string' => 8,
						],
						'string' => 'hello!',
					],
					'cislo' => 42,
					[
						'@(meta)' => [
							'type' => 'object',
							'name' => TestClasses\ObjectWithUnserializableDateTimeImmutable::class,
						],
						'myDate' => [
							'@(meta)' => ['type' => 'object', 'name' => \DateTimeImmutable::class],
							'date' => '2017-02-17T20:20:22+01:00',
						],
					],
				],
			]
			, $payload->getPrimitives()
		);
	}

	public function test_deserialization_scalar(): void
	{
		$processor = $this->provideProcessor();

		Assert::exception(function () use ($processor) {
			$processor->fromPayload(new Payload('test'));
		}, MalformedPayloadException::class);

		Assert::exception(function () use ($processor) {
			$processor->fromPayload(new Payload(5));
		}, MalformedPayloadException::class);

		Assert::exception(function () use ($processor) {
			$processor->fromPayload(new Payload(5.5));
		}, MalformedPayloadException::class);
	}


	public function test_deserialization_array(): void
	{
		$processor = $this->provideProcessor();
		Assert::same(['test'],
			$processor->fromPayload(new Payload(['@(meta)' => ['type' =>'array', 'serializationVersion' => 1], 0 => 'test']))
		);
		Assert::same([10 => '1', 20 => '2'],
			$processor->fromPayload(new Payload(['@(meta)' => ['type' =>'array', 'serializationVersion' => 1], 10 => '1', 20 => '2']))
		);
		Assert::same([20 => '2', 10 => '1'],
			$processor->fromPayload(new Payload(['@(meta)' => ['type' =>'array', 'serializationVersion' => 1], 20 => '2', 10 => '1']))
		);
	}

	public function test_deserialization_simple_object(): void
	{
		$processor = $this->provideProcessor();

		Assert::equal( // ==
			TestClasses\ComplexObjectWithReferenceToOtherObject::noObject(),
			$processor->fromPayload(new Payload(
				[
					'@(meta)' => [
						'type' => 'object',
						'name' => TestClasses\ComplexObjectWithReferenceToOtherObject::class,
						'serializationVersion' => 1
					],
				    'bool' => TRUE,
				    'float' => 5.5,
				    'int' => 5,
				    'object' => NULL,
				    'string' => 'hello!',
				]
			))
		);
	}

	public function test_deserialization_complex_object(): void
	{
		$processor = $this->provideProcessor();

		Assert::equal( // ==
			new TestClasses\ComplexObjectWithReferenceToOtherObject(new TestClasses\SimpleObjectWithPrimitiveProperties()),
			$processor->fromPayload(new Payload(
				[
					'@(meta)' => [
						'type' => 'object',
						'name' => TestClasses\ComplexObjectWithReferenceToOtherObject::class,
						'serializationVersion' => 1,
					],
				    'bool' => TRUE,
				    'float' => 5.5,
				    'int' => 5,
				    'object' => [
				    	'@(meta)' => [
					        'type' => 'object',
					        'name' => TestClasses\SimpleObjectWithPrimitiveProperties::class,
					        'stateVersion' => 36713727, // auto generated by trait from object properties
					    ],
				        'float' => 15,
	                    'string' => 8,
				    ],
				    'string' => 'hello!',
				]
			))
		);
	}

	public function test_deserialization_removedClass(): void
	{
		$processor = $this->provideProcessor([], [
			'RemovedClassWithDefaultDeserializer',
			'RemovedClassWithCustomDeserializer' => static function (State $state) {
				return $state['value'];
			},
		]);

		Assert::same(
			42,
			$processor->fromPayload(new Payload(
				[
					'@(meta)' => [
						'type' => 'object',
						'name' => 'RemovedClassWithCustomDeserializer',
						'serializationVersion' => 1,
					],
					'value' => 42,
				]
			))
		);

		$removedClassWithDefaultDeserializer = $processor->fromPayload(new Payload(
			[
				'@(meta)' => [
					'type' => 'object',
					'name' => 'RemovedClassWithDefaultDeserializer',
					'serializationVersion' => 1,
				],
				'value' => 42,
			]
		));
		Assert::type(RemovedClass::class, $removedClassWithDefaultDeserializer);
		Assert::same(42, $removedClassWithDefaultDeserializer->value);
	}
}

(new PayloadProcessorTest)->run();
