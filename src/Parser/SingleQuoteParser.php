<?php

namespace EnvParser\Parser;

use EnvParser\ParserError;

class SingleQuoteParser extends AbstractQuoteParser
{
    public const QUOTE = "'";

    public function read(string $buffer, int &$offset)
    {
        if ($buffer[$offset] !== static::QUOTE) {
            throw new \InvalidArgumentException("No single quote at $offset in buffer");
        }

        $offset++;
        $length = strlen($buffer);
        $string = '';
        while ($offset < $length) {
            if ($buffer[$offset] === static::QUOTE) {
                $this->string = $string;
                $offset++;
                return;
            } else {
                $string .= $buffer[$offset];
            }
            $offset++;
        }

        throw new ParserError('Unexpected end of file. Expected single quote.');
    }

    public function match(string $buffer, int $offset): bool
    {
        return $buffer[$offset] === static::QUOTE;
    }
}
