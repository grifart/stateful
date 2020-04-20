<?php declare(strict_types = 1);


namespace Grifart\Stateful\Mapper;


final class CompositeMapper implements Mapper
{
	/** @var Mapper[] */
	private $mappers = [];

	/**
	 * @param Mapper[] $mappers
	 */
	private function __construct(array $mappers = [])
	{
		$this->mappers = $mappers;
	}


	public static function from(Mapper ...$mappers): self
	{
		return new self($mappers);
	}


	public function toTransferName(string $fullyQualifiedName): ?string
	{
		foreach($this->mappers as $mapper) {
			if (($result = $mapper->toTransferName($fullyQualifiedName)) !== NULL) {
				return $result;
			}
		}
		return NULL;
	}


	public function toFullyQualifiedName(string $transferName): ?string
	{
		foreach($this->mappers as $mapper) {
			if (($result = $mapper->toFullyQualifiedName($transferName)) !== NULL) {
				return $result;
			}
		}
		return NULL;
	}
}
