<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;


final class MatchMapper implements Mapper
{
	public function __construct(
		private string $localName,
		private string $transferName,
	) {}


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
