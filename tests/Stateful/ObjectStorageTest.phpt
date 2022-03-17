<?php declare(strict_types=1);
/**
 * @TestCase
 */

namespace Grifart\Stateful;

use Grifart\Stateful\ExternalSerializer\SerializerList;
use Grifart\Stateful\Mapper\TrivialMapper;
use Grifart\VatCalculator\VatRate\IVatRate;
use Grifart\VatCalculator\VatRate\VatRate;
use Tester\Assert;
use Tester\TestCase;
use function Interop\EqualableUtils\equals;

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
				'@(meta)' => [
					'type' => 'object',
					'name' => 'Grifart\\Stateful\\MyHashMap',
					'serializationVersion' => 1,
				],
				'keys' => [
					'@(meta)' => ['type' => 'array'],
					'15.00' => [
						'@(meta)' => [
							'type' => 'object',
							'name' => 'Grifart\\VatCalculator\\VatRate\\NumericVatRate',
						],
						'rate' => '15.00',
					],
					'18.00' => [
						'@(meta)' => [
							'type' => 'object',
							'name' => 'Grifart\\VatCalculator\\VatRate\\NumericVatRate',
						],
						'rate' => '18.00',
					],
				],
				'values' => ['@(meta)' => ['type' => 'array'], '15.00' => 8, '18.00' => 6],
			],
			$payload->getPrimitives()
		);
	}


	public function test_deserialization_simpleObjectStorage(): void
	{
		$processor = $this->provideProcessor();

		$payload = new Payload(
			[
				'@(meta)' => [
					'type' => 'object',
					'name' => 'Grifart\\Stateful\\MyHashMap',
					'serializationVersion' => 1,
				],
				'keys' => [
					'@(meta)' => ['type' => 'array'],
					'15.00' => [
						'@(meta)' => [
							'type' => 'object',
							'name' => 'Grifart\\VatCalculator\\VatRate\\NumericVatRate',
						],
						'rate' => '15.00',
					],
					'18.00' => [
						'@(meta)' => [
							'type' => 'object',
							'name' => 'Grifart\\VatCalculator\\VatRate\\NumericVatRate',
						],
						'rate' => '18.00',
					],
				],
				'values' => ['@(meta)' => ['type' => 'array'], '15.00' => 8, '18.00' => 6],
			]
		);

		$objectStorage = $processor->fromPayload($payload);

		/** @var MyHashMap $objectStorage */
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


	private function constructSimpleObjectStorage(): MyHashMap
	{
		$storage = new MyHashMap;
		$storage->attach(VatRate::numeric(15), 8);
		$storage->attach(VatRate::numeric(18), 6);

		return $storage;
	}
}


/**
 * Sample implementation of map, that can have objects as keys
 */
class MyHashMap implements Stateful
{
	/** @var array<string, mixed> */
	private array $keys = [];

	/** @var array<string, mixed> */
	private array $values = [];

	public function attach($key, $value): void
	{
		$hash = $this->getKeyHash($key);
		$this->keys[$hash] = $key;
		$this->values[$hash] = $value;
	}

	public function isEqualTo($other): bool
	{
		return $other instanceof self && equals($this->toArray(), $other->toArray());
	}

	public function _getState(): State
	{
		return StateBuilder::from($this)
			->version(1)
			// this is NOT a best practise; in production code you should always produce code as deterministic-as-possible.
			// in case toArray() changes -> state will produce different results, which can cause troubles
			->field('keys', $this->keys)
			->field('values', $this->values)
			->ignore('keys')
			->build();
	}


	public static function _fromState(State $state): static
	{
		switch($state->getVersion()) {
			case 1:
				/** @var self $self */
				/** @var array{keys: <string, mixed>, values: array<string, mixed>} $state */
				$self = $state->makeAnEmptyObject(self::class);
				$self->keys = $state['keys'];
				$self->values = $state['values'];
				return $self;
			default:
				$state->throwIncompatibleVersion();
		}
	}

	private function toArray(): array
	{
		$data = [];
		foreach($this->keys as $idx => $key) {
			$data[] = [
				'key' => $key,
				'value' => $this->values[$idx]
			];
		}
		return $data;
	}

	/**
	 * @inheritdoc
	 */
	private function getKeyHash($key): string
	{
		\assert($key instanceof IVatRate);

		return $key->getRateClassName();
	}
}

(new ObjectStorageTest)->run();
