<?php

namespace EnvParser\Parser;

use EnvParser\EnvFile;
use EnvParser\ParseError;

class SpaceParser extends AbstractParser
{
    public function read(string $buffer, int &$offset)
    {
        preg_match('/\G[' . EnvFile::WHITESPACE_CHARACTERS . ']*/', $buffer, $match, 0, $offset);
        $offset += strlen($match[0]);
    }

    public function match(string $buffer, int $offset)
    {
        return !!preg_match('/\G[' . EnvFile::WHITESPACE_CHARACTERS . ']/', $buffer, $match, 0, $offset);
    }
}
