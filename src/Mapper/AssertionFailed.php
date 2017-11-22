<?php declare(strict_types=1);

namespace Grifart\Stateful\Mapper;

use Throwable;

final class AssertionFailed extends \AssertionError
{
	/** @var string */
	private $input;

	/** @var ?string */
	private $output;


	/** @internal use {@see failFactory} instead */
	public function __construct($message, string $input, ?string $output)
	{
		parent::__construct($message, 0, NULL);
		$this->input = $input;
		$this->output = $output;
	}


	public static function failFactory(string $input, ?string $output): callable
	{
		return function(string $message) use ($input, $output) {
			throw new self($message, $input, $output);
		};
	}


	public function getInput(): string
	{
		return $this->input;
	}


	public function getOutput(): ?string
	{
		return $this->output;
	}

}
