<?php declare(strict_types=1);

namespace Grifart\Stateful;

use Grifart\Stateful\VersionMismatchException;


/**
 * @phpstan-require-implements Stateful
 */
trait SimpleStatefulTrait
{
	/**
	 * Provides a list of properties used to create a serializable state
	 *
	 * WARNING: Every time the list of properties changes, a version number changes as well. If the state derived
	 * from old list of properties is already stored and will need to be deserialized in the future,
	 * the {@see _fromState()} must be able to handle old and new version of state!
	 *
	 * @return string[] List of properties used to create a serializable state
	 */
	protected static function getStatefulProperties(): array
	{
		$reflection = new \ReflectionClass(static::class);

		$props = [];
		foreach($reflection->getProperties() AS $property) {
			$props[] = $property->getName();
		}
		return $props;
	}


	/** @internal used only for serialization */
	public function _getState(): State
	{
		$properties = self::getStatefulProperties();

		$state = [];
		$props = [];
		foreach ($properties AS $property) {
			$state[$property] = $this->$property;
			$props[] = $property;
		}

		return State::from($this, self::generateVersionNumberFrom($props), $state);
	}


	/** @internal used only for deserialization */
	public static function _fromState(State $state): static
	{
		$reflection = new \ReflectionClass(static::class);

		/** @var static $instance */
		$instance = $reflection->newInstanceWithoutConstructor();

		$props = [];
		foreach (self::getStatefulProperties() as $propertyName) {
			$instance->$propertyName = $state[$propertyName];
			$props[] = $propertyName;
		}

		// version check
		$requiredVersion = self::generateVersionNumberFrom($props);
		$providedVersion = $state->getVersion();

		if($providedVersion !== $requiredVersion) {
			throw VersionMismatchException::versionDoesNotMatch($state, [$requiredVersion]);
		}

		return $instance;
	}


	/** This should work on 32 and 64-bit platforms the same */
	private static function generateVersionNumberFrom(array $objectPropertyList): int
	{
		$PHP_SIGNED_INT_MAX_32BIT = 0x7FFFFFF;

		$sortResult = \sort($objectPropertyList);
		assert($sortResult === TRUE);

		$versionString = implode(',', $objectPropertyList);

		// @see http://php.net/manual/en/function.pack.php
		// l = signed long (always 32 bit, machine byte order)
		$unpack = unpack('l', substr(sha1($versionString),0,4));

		// strip first signing bit
		// this prevents on 32-bit platforms to render negative integers; on 64-bit this is arbitrary bit
		// use decbin() for debugging
		// 0xFF prevent from making version number < 255; these are usually used manually
		return ( $PHP_SIGNED_INT_MAX_32BIT & $unpack[1] ) | 0xFF;
	}

}
