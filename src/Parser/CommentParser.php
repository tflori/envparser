<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;

class CommentParser extends AbstractParser
{

    public function read(string $buffer, int &$offset)
    {
        // move position over next line feed or end of buffer
        $nextLf = strpos($buffer, "\n", $offset);
        $offset = $nextLf !== false ? $nextLf + 1 : strlen($buffer);
    }

    public function match(string $buffer, int $offset): bool
    {
        return $buffer[$offset] === '#';
    }
}
