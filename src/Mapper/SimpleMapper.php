<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;


use Grifart\Stateful\MapperException;

final class SimpleMapper implements Mapper
{

	private const NAMESPACE_SEPARATOR = '\\';

	public function __construct(
		private string $namespacePrefix,
		private string $transferPrefix,
		private string $transferNamespaceSeparator
	) {}


	/**
	 * Converts fully qualified class name to name that is used for serialization.
	 *
	 * @param string $fullyQualifiedName
	 * @return string
	 */
	public function toTransferName(string $fullyQualifiedName): ?string
	{
		if(!$this->startsWith($fullyQualifiedName, $this->namespacePrefix . self::NAMESPACE_SEPARATOR)) {
			return NULL;
		}

		if( $fullyQualifiedName[strlen($fullyQualifiedName) - 1] === self::NAMESPACE_SEPARATOR) {
			throw MapperException::fullyQualifiedNameCannotEndWithNamespaceSeparator($fullyQualifiedName);
		}

		$rest = substr($fullyQualifiedName, strlen($this->namespacePrefix));
		$rest = ltrim($rest, self::NAMESPACE_SEPARATOR);

		if(empty($rest)) {
			return NULL;
		}


		return $this->transferPrefix . str_replace(self::NAMESPACE_SEPARATOR, $this->transferNamespaceSeparator, $rest);
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

		return $this->namespacePrefix . self::NAMESPACE_SEPARATOR . str_replace($this->transferNamespaceSeparator, self::NAMESPACE_SEPARATOR, $rest);
	}


	private function startsWith(string $string, string $query): bool
	{
		return $query === '' || strpos($string, $query) === 0;
	}

}
