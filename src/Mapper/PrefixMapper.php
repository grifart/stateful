<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

/**
 * Automatically prefixes all transfer names for all mappers in the list.
 */
final class PrefixMapper implements Mapper
{

	/** @var Mapper[] */
	private $mappers = [];

	/** @var string */
	private $prefix;

	/**
	 * @param Mapper[] $mappers
	 */
	public function __construct(string $prefix, array $mappers = [])
	{
		$this->prefix = $prefix;
		$this->mappers = $mappers;
	}


	public static function from(string $prefix, Mapper ...$mappers): self
	{
		return new self($prefix, $mappers);
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
				return $this->prefix . $result;
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
		// prefix was not matched
		if (strpos($transferName, $this->prefix) !== 0) {
			return NULL;
		}
		$transferNameWithoutPrefix = substr($transferName, strlen($this->prefix));

		foreach($this->mappers as $mapper) {
			if (($result = $mapper->toFullyQualifiedName($transferNameWithoutPrefix)) !== NULL) {
				return $result;
			}
		}
		return NULL;
	}
}
