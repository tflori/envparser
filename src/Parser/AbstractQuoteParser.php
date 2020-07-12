<?php

namespace EnvParser\Parser;

use EnvParser\ParserError;

abstract class AbstractQuoteParser extends AbstractParser
{
    protected const STATE_READ = 1;
    protected const STATE_ESCAPED = 2;

    public const QUOTE = '"';

    protected $escapedCharacters = [self::QUOTE, '\\'];

    /** @var string */
    protected $string;

    public function match(string $buffer, int $offset): bool
    {
        return $buffer[$offset] === static::QUOTE;
    }

    /** @codeCoverageIgnore */
    public function getString(): string
    {
        return $this->string;
    }
}
