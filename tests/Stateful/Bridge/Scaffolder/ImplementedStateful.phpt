<?php declare(strict_types = 1);

namespace Grifart\Stateful\Bridge\Scaffolder;

use Grifart\ClassScaffolder\ClassGenerator;
use Tester\Assert;
use function Grifart\ClassScaffolder\Definition\definitionOf;

require __DIR__ . '/../../../bootstrap.php';

$classGenerator = new ClassGenerator();

Assert::matchFile(
	__DIR__ . '/ImplementedStateful.expected.phps',
	(string) $classGenerator->generateClass(
		definitionOf(Generated::class)
			->with(new ImplementedStateful()))
);
