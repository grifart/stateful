<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

/**
 * Maps every name to the same name
 */
final class TrivialMapper implements Mapper
{
	/**
	 * Converts fully qualified class name to name that is used for serialization.
	 *
	 * @param string $fullyQualifiedName
	 * @return string
	 */
	public function toTransferName(string $fullyQualifiedName): string
	{
		return $fullyQualifiedName;
	}


	/**
	 * Converts serialization name to fully qualified.
	 *
	 * @param string $transferName
	 * @return string
	 */
	public function toFullyQualifiedName(string $transferName): string
	{
		return $transferName;
	}
}
