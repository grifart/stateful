<?php declare(strict_types=1);
/**
 * @TestCase
 */

namespace Grifart\Stateful;

use Grifart\Collection\HashMap;
use Grifart\Stateful\ExternalSerializer\SerializerList;
use Grifart\Stateful\Mapper\TrivialMapper;
use Grifart\VatCalculator\VatRate\IVatRate;
use Grifart\VatCalculator\VatRate\NumericVatRate;
use Grifart\VatCalculator\VatRate\VatRate;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/ObjectStorageTest_testClasses.php';

class ObjectStorageTest extends TestCase
{

	public function test_simpleObjectStorage(): void
	{
		$storage = $this->constructSimpleObjectStorage();

		$processor = $this->provideProcessor();
		$payload = $processor->toPayload($storage);

		Assert::equal(
			[
				'@(meta)' =>
					[
						'type' => 'object',
						'name' => MyHashMap::class,
						'serializationVersion' => 1,
					],
				'data' =>
					[
						'@(meta)' =>
							[
								'type' => 'array',
							],
						0 =>
							[
								'@(meta)' =>
									[
										'type' => 'array',
									],
								'key' =>
									[
										'@(meta)' =>
											[
												'type' => 'object',
												'name' => NumericVatRate::class,
											],
										'rate' => '15.00',
									],
								'value' => 8,
							],
						1 =>
							[
								'@(meta)' =>
									[
										'type' => 'array',
									],
								'key' =>
									[
										'@(meta)' =>
											[
												'type' => 'object',
												'name' => NumericVatRate::class,
											],
										'rate' => '18.00',
									],
								'value' => 6,
							],
					],
			],
			$payload->getPrimitives()
		);
	}


	public function test_deserialization_simpleObjectStorage(): void
	{
		$processor = $this->provideProcessor();

		$payload = new Payload(
			[
				'@(meta)' =>
					[
						'type' => 'object',
						'name' => MyHashMap::class,
						'serializationVersion' => 1,
					],
				'data' =>
					[
						'@(meta)' =>
							[
								'type' => 'array',
							],
						0 =>
							[
								'@(meta)' =>
									[
										'type' => 'array',
									],
								'key' =>
									[
										'@(meta)' =>
											[
												'type' => 'object',
												'name' => NumericVatRate::class,
											],
										'rate' => '15.00',
									],
								'value' => 8,
							],
						1 =>
							[
								'@(meta)' =>
									[
										'type' => 'array',
									],
								'key' =>
									[
										'@(meta)' =>
											[
												'type' => 'object',
												'name' => NumericVatRate::class,
											],
										'rate' => '18.00',
									],
								'value' => 6,
							],
					],
			]
		);

		$objectStorage = $processor->fromPayload($payload);

		/** @var HashMap $objectStorage */
		Assert::true($this->constructSimpleObjectStorage()->isEqualTo($objectStorage));
	}


	/**
	 * @return PayloadProcessor
	 */
	private function provideProcessor(): PayloadProcessor
	{
		return new PayloadProcessor(new TrivialMapper(), SerializerList::from(

			/** @matchSubtypes */
			function (IVatRate $rate): State {
				return State::from($rate, 1, [
					'rate' => VatRate::serialize($rate)
				]);
			},

			/** @matchSubtypes */
			function (State $state): IVatRate {
				assert($state->getVersion() === 1);

				return VatRate::unserialize($state['rate']);
			}

		));
	}


	/**
	 * @return HashMap
	 */
	private function constructSimpleObjectStorage(): HashMap
	{
		$storage = new MyHashMap;
		$storage->offsetSet(VatRate::numeric(15), 8);
		$storage->offsetSet(VatRate::numeric(18), 6);

		return $storage;
	}
}

class MyHashMap extends HashMap
{

	/**
	 * @inheritdoc
	 */
	protected function isValueValid($value): bool
	{
		return true;
	}


	/**
	 * @inheritdoc
	 */
	public function getKeyHash($key): string
	{
		\assert($key instanceof IVatRate);

		return $key->getRateClassName();
	}
}

(new ObjectStorageTest)->run();
