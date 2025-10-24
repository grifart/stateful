<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\Capabilities\ConstructorWithPromotedProperties;
use Grifart\ClassScaffolder\ClassGenerator;
use Tester\Assert;
use function Grifart\ClassScaffolder\Definition\definitionOf;

require __DIR__ . '/../../../bootstrap.php';

$classGenerator = new ClassGenerator();

Assert::matchFile(
	__DIR__ . '/ImplementedStatefulAndInterface.expected.phps',
	(string) $classGenerator->generateClass(
		definitionOf(GeneratedWithInterface::class)
			->with(new ConstructorWithPromotedProperties())
			->with(new ImplementedStatefulAndInterface())
			->withField('value', 'string'),
	),
);
