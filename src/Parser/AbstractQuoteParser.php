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

    /**
     * Check for a var access or other magic inside strings at this position
     *
     * If you find something:
     *   - forward the offset and return the string
     *   - values of boolean should be 'false' and 'true'
     *
     * @param string $buffer
     * @param int    $offset
     * @return string|null
     */
    protected function parse(string $buffer, int &$offset): ?string
    {
        return null;
    }

    /** @codeCoverageIgnore */
    public function getString(): string
    {
        return $this->string;
    }
}
