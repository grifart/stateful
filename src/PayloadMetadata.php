<?php declare(strict_types=1);
/**
 * This file is part of the resolving.lib.
 * Copyright (c) 2017 Grifart spol. s r.o. (https://grifart.cz)
 */

namespace Grifart\Stateful;
use Grifart\Stateful\MalformedMetadataException;

/** @internal used by {@see PayloadProcessor} */
final class PayloadMetadata
{
	// META fields:
	const META_FIELD_STATE_VERSION = 'stateVersion';
	const META_FIELD_TRANSFER_CLASS_NAME = 'name';
	const META_FIELD_TYPE = 'type';

	const META_TYPE_ARRAY = 'array';
	const META_TYPE_OBJECT = 'object';
	const META_TYPE_POSSIBLE_VALUE = [self::META_TYPE_OBJECT, self::META_TYPE_ARRAY];

	public static function forAnObject(string $classTransferName, int $version): array
	{
		return [
				/* string */
				self::META_FIELD_TYPE                => self::META_TYPE_OBJECT,

				// Logic object class name
				self::META_FIELD_TRANSFER_CLASS_NAME => $classTransferName,

				// FQ class name in time of serialization (can be used as a backup)
//				/* string */ 'class' => $objectClass, // todo: remove?
			] +
			(
			$version !== 1 ? [
				// Value Object can be serialized in more ways that can be incompatible
				// This can be used as an identifier. 1 is default
				/* int */
				self::META_FIELD_STATE_VERSION => $version,
			] : []
			);
	}

	public static function forAnArray(): array
	{
		return [
			self::META_FIELD_TYPE => self::META_TYPE_ARRAY
		];
	}

	/**
	 * @param array $metaArray
	 * @return \Grifart\Stateful\PayloadMetadata
	 * @throws \Grifart\Stateful\MalformedMetadataException if given metadata are not valid
	 */
	public static function parse($metaArray): self
	{
		if (!is_array($metaArray)) {
			throw MalformedMetadataException::metadataMustBeAnArray(gettype($metaArray));
		}

		if (!isset($metaArray[PayloadMetadata::META_FIELD_TYPE])) {
			throw MalformedMetadataException::typeFieldIsMissing();
		}
		/** @var string $type */
		$type = $metaArray[PayloadMetadata::META_FIELD_TYPE];

		if (in_array($type, self::META_TYPE_POSSIBLE_VALUE, true) === FALSE) {
			throw MalformedMetadataException::wrongTypeValue();
		}

		if ($type === self::META_TYPE_ARRAY) {
			return self::buildForArray();
		}

		// OBJECT:
		if (!array_key_exists(PayloadMetadata::META_FIELD_TRANSFER_CLASS_NAME, $metaArray))
		{
			throw MalformedMetadataException::missingClassName();
		}
		$transferClassName = $metaArray[PayloadMetadata::META_FIELD_TRANSFER_CLASS_NAME];

		$version = $metaArray[PayloadMetadata::META_FIELD_STATE_VERSION] ?? 1; // if not set, default version is 1;

		return self::buildForObject($transferClassName, $version);
	}

	private function __construct()
	{
	}

	private string $type;

	private ?string $transferClassName = null;

	private ?int $version = null;

	private static function buildForArray(): self
	{
		$me = new self();
		$me->type = self::META_TYPE_ARRAY;
		return $me;
	}

	private static function buildForObject(string $transferClassName, int $version): self
	{
		$me = new self();
		$me->type = self::META_TYPE_OBJECT;
		$me->transferClassName = $transferClassName;
		$me->version = $version;
		return $me;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getTransferClassName(): string
	{
		assert($this->type === self::META_TYPE_OBJECT);
		assert($this->transferClassName !== NULL);
		return $this->transferClassName;
	}

	/**
	 * @return int
	 */
	public function getVersion(): int
	{
		assert($this->type === self::META_TYPE_OBJECT);
		assert($this->version !== NULL);
		return $this->version;
	}


}
