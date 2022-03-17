<?php declare(strict_types = 1);


namespace Grifart\VatCalculator\VatRate {
	interface IVatRate
	{
		public function getRateClassName(): string;
	}

	final class NumericVatRate implements IVatRate
	{
		private string $rate;

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

		public function isEqualTo(mixed $other): bool
		{
			return $other instanceof self && $other->rate === $this->rate;
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
