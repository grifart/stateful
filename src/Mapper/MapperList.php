<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

/**
 * The mapper broker.
 */
final class MapperList implements Mapper
{

	/**
	 * @param Mapper[] $mappers
	 */
	public function __construct(
		private array $mappers = [],
	) {}


	public static function from(Mapper ...$mappers): self
	{
		return new self($mappers);
	}


	public function addMapper(Mapper $mapper): void
	{
		$this->mappers[] = $mapper;
	}


	/**
	 * Converts fully qualified name to transfer version
	 *
	 * @param string $fullyQualifiedName
	 * @return string
	 */
	public function toTransferName(string $fullyQualifiedName): ?string
	{
		foreach($this->mappers as $mapper) {
			if (($result = $mapper->toTransferName($fullyQualifiedName)) !== NULL) {
				return $result;
			}
		}
		return NULL;
	}


	/**
	 * Converts transfer name to fully qualified version.
	 *
	 * @param string $transferName
	 * @return string
	 */
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
