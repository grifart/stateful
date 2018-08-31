<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;


use Grifart\Stateful\Exceptions\MapperException;

final class SimpleMapper implements Mapper
{

	/** @var string */
	private $transferNamespaceSeparator;

	/** @var string */
	private $transferPrefix;

	/** @var string */
	private $namespaceSeparator = '\\';

	/** @var string */
	private $namespacePrefix;


	public function __construct(string $namespacePrefix, string $transferPrefix, string $transferNamespaceSeparator)
	{
		$this->transferNamespaceSeparator = $transferNamespaceSeparator;
		$this->transferPrefix = $transferPrefix;
		$this->namespacePrefix = $namespacePrefix;
	}


	/**
	 * Converts fully qualified class name to name that is used for serialization.
	 *
	 * @param string $fullyQualifiedName
	 * @return string
	 */
	public function toTransferName(string $fullyQualifiedName): ?string
	{
		if (strlen($this->namespaceSeparator) !== 1) {
			throw MapperException::namespaceSeparatorMustHaveOneCharacterOnly();
		}

		if(!$this->startsWith($fullyQualifiedName, $this->namespacePrefix . $this->namespaceSeparator)) {
			return NULL;
		}

		if( $fullyQualifiedName[strlen($fullyQualifiedName) - 1] === $this->namespaceSeparator) {
			throw MapperException::fullyQualifiedNameCannotEndWithNamespaceSeparator($fullyQualifiedName);
		}

		$rest = substr($fullyQualifiedName, strlen($this->namespacePrefix));
		$rest = ltrim($rest, $this->namespaceSeparator);

		if(empty($rest)) {
			return NULL;
		}


		return $this->transferPrefix . str_replace($this->namespaceSeparator, $this->transferNamespaceSeparator, $rest);
	}


	/**
	 * Converts serialization name to fully qualified.
	 *
	 * @param string $transferName
	 * @return string
	 */
	public function toFullyQualifiedName(string $transferName): ?string
	{
		if(!$this->startsWith($transferName, $this->transferPrefix)) {
			return NULL;
		}

		$rest = substr($transferName, strlen($this->transferPrefix));

		return $this->namespacePrefix . $this->namespaceSeparator . str_replace($this->transferNamespaceSeparator, $this->namespaceSeparator, $rest);
	}


	private function startsWith(string $string, string $query): bool
	{
		return $query === '' || strpos($string, $query) === 0;
	}

}
