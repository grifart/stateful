<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;


final class MatchMapper implements Mapper
{
	/**
	 * @var string
	 */
	private $transferName;

	/**
	 * @var string
	 */
	private $localName;


	public function __construct(string $localName, string $transferName)
	{
		$this->transferName = $transferName;
		$this->localName = $localName;
	}


	public function toTransferName(string $fullyQualifiedName): ?string
	{
		if ($this->localName === $fullyQualifiedName) {
			return $this->transferName;
		}

		return NULL;
	}


	public function toFullyQualifiedName(string $transferName): ?string
	{
		if ($transferName === $this->transferName) {
			return $this->localName;
		}

		return NULL;
	}

}
