<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;

class ArrayAccessParser extends AbstractParser
{
    /** @var string */
    protected $key;

    public function read(string $buffer, int &$offset)
    {
        if (!preg_match('/\G\[([a-z0-9A-Z_]+)\]/', $buffer, $match, 0, $offset)) {
            throw new \InvalidArgumentException('Buffer has no array access declaration at offset ' . $offset);
        }

        $offset += strlen($match[0]);
        $this->key = $match[1];
    }

    public function match(string $buffer, int $offset): bool
    {
        return !!preg_match('/\G\[[a-z0-9A-Z_]+\]/', $buffer, $match, 0, $offset);
    }

    /** @codeCoverageIgnore */
    public function getKey(): ?string
    {
        return $this->key;
    }
}
