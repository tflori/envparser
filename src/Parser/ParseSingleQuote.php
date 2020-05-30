<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\StringValue;

class ParseSingleQuote extends AbstractParser
{
    public function read(string $buffer, int &$offset): StringValue
    {
        $from = $offset;
        $to = strpos($buffer, "'", $offset);
        if ($from === $to) {
            return new StringValue('');
        }

        if ($to === false) {
            throw new ParseError('Unexpected end of file. Missing closing single quote', $buffer, strlen($buffer) - 1);
        }

        $offset = $to+1;
        return new StringValue(substr($buffer, $from, $to - $from));
    }
}
