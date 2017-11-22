<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

/**
 * Useful testing / debugging. Wrapping any mapper with this wrapper will not change the behaviour in any way.
 *
 * Tries to translate every name in both directions. Mapping process must result with the same name at the end.
 *
 * Check is done with the following mechanism:
 *
 *    FQN1 -> transfer name -> FQN2
 *    assert(FQN1 === FQN2)
 *
 */
final class ReflexivityCheckingMapper implements Mapper {

	/** @var \Grifart\Stateful\Mapper\Mapper */
	private $mapper;

	public function __construct(Mapper $mapper)
	{
		$this->mapper = $mapper;
	}


	public function toTransferName(string $fullyQualifiedName): ?string
	{
		$transferName = $this->mapper->toTransferName($fullyQualifiedName);

		if($transferName !== NULL) {
			$this->checkConsistency(
				$fullyQualifiedName,
				AssertionFailed::failFactory($fullyQualifiedName, $transferName)
			);
		}

		return $transferName;
	}



	public function toFullyQualifiedName(string $transferName): ?string
	{
		$fullyQualifiedName = $this->mapper->toFullyQualifiedName($transferName);

		if ($fullyQualifiedName !== NULL) {

			$this->checkConsistency(
				$fullyQualifiedName,
				AssertionFailed::failFactory($transferName, $fullyQualifiedName)
			);
		}

		return $fullyQualifiedName;
	}


	private function checkConsistency(string $fullyQualifiedName, callable $fail): void
	{
		// Check the second mapper
		if(($checkedTransferName = $this->mapper->toTransferName($fullyQualifiedName)) === NULL) {
			$fail("Checked mapper does not know how to translate FQN '$fullyQualifiedName' to transfer name.");
		}

		\assert($checkedTransferName !== NULL);
		if(($checkedFQN = $this->mapper->toFullyQualifiedName($checkedTransferName)) === NULL) {
			$fail("Checked mapper does not know how to transfer name '$fullyQualifiedName' to fully qualified name.");
		}

		if ($checkedFQN !== $fullyQualifiedName) {
			$fail("Checked mapper translated FQN -> transfer name -> FQN to different name that was originally. Expected to get '$fullyQualifiedName', got '$checkedFQN'.");
		}
	}
}
