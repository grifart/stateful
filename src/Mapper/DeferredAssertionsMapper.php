<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

use Grifart\Stateful\UsageException;

/**
 * Useful testing / debugging.
 *
 * Wrap any mapper into {@see DeferredAssertionsMapper} to get all failed assertions
 * at once at the end of script.
 *
 */
final class DeferredAssertionsMapper implements Mapper {

	private Mapper $mapper;

	/** @var AssertionFailed[] */
	private array $failedAssertions = [];

	public function __construct(Mapper $mapper)
	{
		$this->mapper = $mapper;
	}


	public function toTransferName(string $fullyQualifiedName): ?string
	{
		try {
			return $this->mapper->toTransferName($fullyQualifiedName);
		} catch (AssertionFailed $failure) {
			$this->failedAssertions[] = $failure;
			return $failure->getOutput();
		}
	}


	public function toFullyQualifiedName(string $transferName): ?string
	{
		try {
			return $this->mapper->toFullyQualifiedName($transferName);
		} catch (AssertionFailed $failure) {
			$this->failedAssertions[] = $failure;
			return $failure->getOutput();
		}
	}


	/**
	 * @return AssertionFailed[]
	 */
	public function getFailedAssertions(): array
	{
		return $this->failedAssertions;
	}

	public function clearFailedAssertions(): void
	{
		$this->failedAssertions = [];
	}


	public function __destruct()
	{
		if (empty($this->failedAssertions)) {
			return;
		}

		$message = "Some of stateful mapper assertions failed: \n\n" . \implode("\n\n", $this->failedAssertions);
		throw new UsageException($message);

	}

}
