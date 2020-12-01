<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;


use Grifart\Stateful\Exceptions\MapperException;

final class SingleNamespaceMapper implements Mapper
{

	private const NAMESPACE_SEPARATOR = '\\';


	public function __construct(
		private string $namespaceForMapping,
		private string $transferPrefix,
	) {}


	/**
	 * Converts fully qualified class name to name that is used for serialization.
	 *
	 * @param string $fullyQualifiedName
	 * @return string
	 */
	public function toTransferName(string $fullyQualifiedName): ?string
	{
		// https://regex101.com/r/IWD9wD/4
		if (!\preg_match('#^(|(.*?)\\\\)([^\\\\]*)$#', $fullyQualifiedName, $matches)) {
			throw MapperException::invalidClassNameGiven($fullyQualifiedName);
		}
		$namespace = $matches[2] ?? ''; // because 2nd matching group can be skipped when there is no namespace
		$className = $matches[3];
		if ($className === '') {
			throw MapperException::fullyQualifiedNameCannotEndWithNamespaceSeparator($fullyQualifiedName);
		}

		if ($namespace !== $this->namespaceForMapping) {
			return NULL;
		}

		return $this->transferPrefix . $className;
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
		// regexp is from: http://php.net/manual/en/language.oop5.basic.php
		$classNameValid = \preg_match('#^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$#', $rest) === 1;
		$fqn = $this->namespaceForMapping . self::NAMESPACE_SEPARATOR . $rest;

		return $classNameValid ? $fqn : null;
	}


	private function startsWith(string $string, string $query): bool
	{
		return $query === '' || strpos($string, $query) === 0;
	}

}
