<?php declare(strict_types=1);


namespace Grifart\Stateful\Mapper;


/**
 * Mapper that prints all tested translations
 */
final class DebuggingMapper implements Mapper {

	/** @var Mapper */
	private $inner;

	/** @var array<string, string> */
	private $translatedNames = [];

	/** @var bool */
	private $printOnDestruct;

	public function __construct(Mapper $inner, bool $printOnDestruct = false)
	{
		$this->inner = $inner;
		$this->printOnDestruct = $printOnDestruct;
	}

	public function toTransferName(string $fullyQualifiedName): ?string
	{
		$transferName = $this->inner->toTransferName($fullyQualifiedName);
		$this->translatedNames[$transferName ?? "unknown translation for $fullyQualifiedName"] = $fullyQualifiedName;

		return $transferName;
	}

	public function toFullyQualifiedName(string $transferName): ?string
	{
		$fqn = $this->inner->toFullyQualifiedName($transferName);
		$this->translatedNames[$transferName] = $fqn ?? "unknown translation for $transferName";
		return $fqn;
	}

	/** @return array<string, string> */
	public function getTranslatedNames(): array
	{
		\ksort($this->translatedNames);
		return $this->translatedNames;
	}

	public function __destruct()
	{
		if (!$this->printOnDestruct) {
			return;
		}
		foreach ($this->getTranslatedNames() as $transferName => $fqn) {
			echo \sprintf("%-60s\t%s\n", $transferName, $fqn);
		}
	}
}