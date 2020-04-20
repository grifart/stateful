<?php declare(strict_types = 1);


namespace Grifart\Stateful\ExternalSerializer;


use Grifart\Stateful\State;

final class CompositeSerializer implements Serializer
{

	/** @var Serializer[] */
	private $serializers;

	/** @param Serializer[] $serializers */
	public function __construct(array $serializers)
	{
		$this->serializers = $serializers;
	}

	public static function from(Serializer ...$serializers): self
	{
		return new self($serializers);
	}

	/**
	 * @inheritDoc
	 */
	public function extractState($object): ?State
	{
		foreach ($this->serializers as $serializer) {
			if (($state = $serializer->extractState($object)) !== NULL) {
				return $state;
			}
		}
		return NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function reconstructFromState(State $state)
	{
		foreach ($this->serializers as $serializer) {
			if (($object = $serializer->reconstructFromState($state)) !== NULL) {
				return $object;
			}
		}
		return NULL;
	}
}
