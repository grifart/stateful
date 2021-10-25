<?php declare(strict_types = 1);

namespace Grifart\Collection {

	use Grifart\Stateful\NoAppropriateDeserializerFoundException;
	use Grifart\Stateful\State;
	use Grifart\Stateful\StateBuilder;
	use Grifart\Stateful\Stateful;
	use function Interop\EqualableUtils\equals;


	final class HashMapIterator implements \Iterator
	{
		/** @var \Grifart\Collection\HashMap */
		private $storage;

		/** @var array */
		private $keys;

		/** @var \ArrayIterator */
		private $keysIterator;

		public function __construct(HashMap $objectStorage)
		{
			$this->storage = $objectStorage;
			$this->keys = $objectStorage->keys();
			$this->keysIterator = new \ArrayIterator($this->keys);
		}

		/** Return the current element */
		public function current()
		{
			$key = $this->key();
			assert($key !== NULL);
			return $this->storage[$key];
		}

		/** Move forward to next element */
		public function next(): void
		{
			$this->keysIterator->next();
		}

		/**
		 * Return the key of the current element
		 * @return mixed|null
		 */
		public function key()
		{
			return $this->keysIterator->current();
		}

		/** Checks if current position is valid */
		public function valid(): bool
		{
			return $this->keysIterator->valid();
		}

		/** Rewind the Iterator to the first element */
		public function rewind(): void
		{
			$this->keysIterator->rewind();
		}
	}

	abstract class HashMap implements \Countable, \IteratorAggregate, \Serializable, \ArrayAccess, Stateful, \Ds\Collection
	{
		/** @var array */
		private $keys = [];

		/** @var array */
		private $values = [];


		public function __construct()
		{

		}


		/**
		 * Calculates hash - a string representation for given key.
		 *
		 * All objects with equal hash are considered as equal.
		 *
		 * @param $key
		 * @return string A hash.
		 * @throws \Throwable If it is not possible to generate hash
		 */
		abstract public function getKeyHash($key): string;


		/**
		 * Check weather given object could be used as value.
		 *
		 * @param $value
		 * @return bool
		 */
		abstract protected function isValueValid($value): bool;



		public function getIterator(): \Iterator
		{
			return new HashMapIterator($this);
		}


		public function __clone()
		{
			// $this->keys is primitive array --> copies automatically
			// $this->values is primitive array --> copies automatically
		}


		/**
		 * Analogous method to {@see \array_keys()}
		 * @return array with types allowed by {@see isAllowedMethod()}
		 */
		public function keys(): array
		{
			return array_values($this->keys);
		}


		public function attach($key, $value): void
		{
			assert($this->isValueValid($value));

			$hash = $this->getKeyHash($key);
			$this->keys[$hash] = $key;
			$this->values[$hash] = $value;
		}


		public function detach($key): void
		{
			$hash = $this->getKeyHash($key);
			assert(isset($this->keys[$hash]));

			unset($this->keys[$hash], $this->values[$hash]);
		}


		public function contains($key): bool
		{
			return isset($this->keys[$this->getKeyHash($key)]);
		}


		public function count(): int
		{
			assert(count($this->keys) === count($this->values));

			return count($this->keys);
		}


		public function unserialize($serialized): void
		{
			/** @noinspection UnserializeExploitsInspection There is now way how to make this safe (too general code) */
			[$values, $keys] = \unserialize($serialized);
			$this->values = $values;
			$this->keys = $keys;
		}


		public function serialize(): string
		{
			return \serialize([
				$this->values,
				$this->keys
			]);
		}


		//<editor-fold desc="\ArrayAccess interface">
		public function offsetExists($key): bool
		{
			return $this->contains($key);
		}


		public function offsetSet($key, $value): void
		{
			$this->attach($key, $value);
		}


		public function offsetUnset($key): void
		{
			$this->detach($key);
		}

		/**
		 * @param mixed $key
		 * @return mixed
		 * @throws \UnexpectedValueException If key is missing
		 */
		public function offsetGet($key)
		{
			$hash = $this->getKeyHash($key);

			if(!isset($this->values[$hash])) {
				throw new \UnexpectedValueException('Cannot find element with given key.');
			}
			return $this->values[$hash];
		}
		//</editor-fold>



		public function isEqualTo($other): bool
		{
			assert($other instanceof self); // including children

			if($this->count() !== $other->count()) {
				return FALSE;
			}

			foreach($this as $key => $value) {
				if(
					!isset($this[$key], $other[$key])
					|| !equals($this[$key], $other[$key])
				) {
					return FALSE;
				}
			}

			return TRUE;
		}


		protected function toArray_v1(): array
		{
			$data = [];
			/** @var object $key */
			/** @var object $value */
			foreach($this as $key => $value) {
				$data[] = [
					'key' => $key,
					'value' => $value,
				];
			}
			return $data;
		}


		/** Apply data from {@see toArray_v1()} */
		protected function _applyData_v1(array $data)
		{
			/** @var array $data {@see _getStateBuilder()} */
			foreach ($data as $item) {
				assert(isset($item['key']) && is_object($item['key']));
				assert(isset($item['value']));
				$this[$item['key']] = $item['value'];
			}
		}


		public function _getState(): State
		{
			return StateBuilder::from($this)
				->version(1)
				->field('data', $this->toArray_v1())
				->ignore('splObjectStorage')
				->build();
		}


		public static function _fromState(State $state): static
		{
			$self = new static;

			switch($state->getVersion()) {
				case 1:
					$self->_applyData_v1($state['data']);
					return $self;
			}

			throw NoAppropriateDeserializerFoundException::unknownSerializationVersion();
		}


		// \JsonSerializable interface
		public function jsonSerialize(): array
		{
			return $this->toArray();
		}


		//<editor-fold desc="\DS\Collection interface">
		public function clear(): void
		{
			$this->keys = [];
			$this->values = [];
		}


		public function copy(): \Ds\Collection
		{
			return clone $this;
		}


		public function isEmpty(): bool
		{
			return count($this->keys) === 0;
		}


		public function toArray(): array
		{
			$data = [];
			foreach($this as $key => $val) {
				$data[] = [
					'key' => $key,
					'value' => $val
				];
			}
			return $data;
		}
		//</editor-fold>
	}
}


namespace Grifart\VatCalculator\VatRate {
	interface IVatRate
	{
		public function getRateClassName(): string;
	}

	final class NumericVatRate implements IVatRate
	{
		/** @var string */
		private $rate;

		public function __construct(int $rate)
		{
			$this->rate = \sprintf('%.2f', $rate);
		}

		public function getRateClassName(): string
		{
			return $this->getRate();
		}

		public function getRate(): string
		{
			return $this->rate;
		}
	}

	final class VatRate
	{
		public static function numeric(int $rate): NumericVatRate
		{
			return new NumericVatRate($rate);
		}

		public static function serialize(NumericVatRate $vatRate): string
		{
			return $vatRate->getRate();
		}

		public static function unserialize(string $vatRate): IVatRate
		{
			return new NumericVatRate((int) $vatRate);
		}
	}
}
