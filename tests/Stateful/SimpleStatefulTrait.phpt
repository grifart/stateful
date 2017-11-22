<?php declare(strict_types=1);

namespace Grifart\Stateful;

use Grifart\Stateful\ExternalSerializer\NullSerializer;
use Grifart\Stateful\Mapper\TrivialMapper;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// TEST CLASS:
final class TestObjectUsingTrait implements Stateful
{
	use SimpleStatefulTrait;

	private $theNumber = 42;
	private $theBool = FALSE;
	private $theNull = NULL;
	private $theUndefined;
	private $theString = 'Hello!';
}


// SERIALIZATION:
$processor = new PayloadProcessor(new TrivialMapper(), NullSerializer::getInstance());
$payload = $processor->toPayload(new TestObjectUsingTrait());

Assert::same([
	'@(meta)' => [
		'type' => 'object',
		'name' => TestObjectUsingTrait::class,
		'stateVersion' => 53622271,
		'serializationVersion' => 1,
	],
	'theBool' => FALSE,
	'theNull' => NULL,
	'theNumber' => 42,
	'theString' => 'Hello!',
	'theUndefined' => NULL,
], $payload->getPrimitives());


// DESERIALIZATION:
$object = $processor->fromPayload($payload);

Assert::equal(new TestObjectUsingTrait(), $object);
