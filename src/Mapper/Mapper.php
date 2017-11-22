<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

/**
 * Provides mapping between transfer names and fully qualified names.
 *
 * Requirements:
 * - toTransferName(): There must be only one transfer name for each FQN.
 * - toFullyQualifiedName(): There can be more transfer names that translates
 *     to the same FQN. This is useful for backward compatibility.
 *
 * This means that it may happen that transfer name 1 and 2 can differ.
 *   transfer name 1 -> fqn -> transfer name 2
 */
interface Mapper
{
	/**
	 * Converts fully qualified class name to name that is used for serialization.
	 *
	 * @param string $fullyQualifiedName
	 * @return string
	 */
	public function toTransferName(string $fullyQualifiedName): ?string;


	/**
	 * Converts serialization name to fully qualified.
	 *
	 * @param string $transferName
	 * @return string
	 */
	public function toFullyQualifiedName(string $transferName): ?string;

}
