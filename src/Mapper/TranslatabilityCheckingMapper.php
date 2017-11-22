<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

/**
 * Useful for testing.
 *
 * Checks that:
 *   If mapper1 knows how to translate => mapper2 also must know.
 *
 * There are two mappers given in this mapper.
 * mapper1 - the one that is really used for translation
 * mapper2 - the one just called and is checked if it also knows the answer
 *
 * Especially useful with {@see ReflexivityCheckingMapper}
 */
final class TranslatabilityCheckingMapper implements Mapper {

	/** @var \Grifart\Stateful\Mapper\Mapper */
	private $mapper1;

	/** @var \Grifart\Stateful\Mapper\Mapper */
	private $mapper2;


	public function __construct(Mapper $mapper, Mapper $checkedMapper)
	{
		$this->mapper1 = $mapper;
		$this->mapper2 = $checkedMapper;
	}


	public function toTransferName(string $fullyQualifiedName): ?string
	{
		$transferName1 = $this->mapper1->toTransferName($fullyQualifiedName);

		$fail = AssertionFailed::failFactory($fullyQualifiedName, $transferName1);

		// if mapper1 knows -> mapper2 must also know
		$transferName2 = $this->mapper2->toTransferName($fullyQualifiedName);
		if ($transferName1 !== NULL && $transferName2 === NULL) {
			$fail(
				'Mapper consistency check failed (FQN --X--> transfer). ' .
				"First mapper knows the answer and second does NOT, for then name '$fullyQualifiedName'."
			);
		}

		return $transferName1;
	}


	public function toFullyQualifiedName(string $transferName): ?string
	{
		$FQN1 = $this->mapper1->toFullyQualifiedName($transferName);
		if ($FQN1 === NULL) {
			return NULL;
		}

		$fail = AssertionFailed::failFactory($transferName, $FQN1);

		// because transfer name passed to premise mapper can differ from consequence mapper name
		// we must do the following: (1=mapper1; 2=mapper2)
		// transfer1 -> FQN1 -> transfer2 -> FQN2
		$transferName2 = $this->mapper2->toTransferName($FQN1);
		if ($transferName2 === NULL) {
			$fail(
				'Mapper consistency check failed (transfer1->FQN1 --X--> transfer2->FQN2). ' .
				"Intermediate check FQN1->transfer2 failed. Mapper2 cannot translate '$FQN1' to transfer name."
			);
		}

		\assert($transferName2 !== NULL);
		$FQN2 = $this->mapper2->toFullyQualifiedName($transferName2);
		if ($FQN2 === NULL) {
			$fail(
				'Mapper consistency check failed (transfer1->FQN1->transfer2 --X--> FQN2). ' .
				"First mapper knows how to translate '$transferName', second does not know hot to translate '$transferName2' to FQN."
			);
		}

		return $FQN1;
	}

}
