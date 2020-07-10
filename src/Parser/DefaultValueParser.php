<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;

class DefaultValueParser extends AbstractParser
{
    /** @var string */
    protected $default;

    public function read(string $buffer, int &$offset)
    {
        if (!preg_match('/\G-([^}]*)}/', $buffer, $match, 0, $offset)) {
            throw new \InvalidArgumentException('Buffer has no default value declaration at offset ' . $offset);
        }

        $offset += strlen($match[0]) - 1;
        $this->default = $match[1];
    }

    public function match(string $buffer, int $offset): bool
    {
        return $buffer[$offset] === '-';
    }

    /** @codeCoverageIgnore */
    public function getDefault(): ?string
    {
        return $this->default;
    }
}
