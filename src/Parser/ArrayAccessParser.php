<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;

class ArrayAccessParser extends AbstractParser
{
    /** @var string */
    protected $key;

    public function read(string $buffer, int &$offset)
    {
        if (!preg_match('/\G\[(\d+|\*|@)\]/', $buffer, $match, 0, $offset)) {
            throw new \InvalidArgumentException('Buffer has no array access declaration at offset ' . $offset);
        }

        $offset += strlen($match[0]);
        $this->key = $match[1];
    }

    public function match(string $buffer, int $offset): bool
    {
        return !!preg_match('/\G\[(\d+|\*|@)\]/', $buffer, $match, 0, $offset);
    }

    /** @codeCoverageIgnore */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Get the value from current value
     *
     * Assuming the current value is an array it returns one element from this array.
     *
     * If the key is * or @ then the array get's imploded by spaces.
     *
     * @param $value
     * @return mixed
     */
    public function getValue($value)
    {
        if (in_array($this->key, ['*', '@'])) {
            return implode(' ', (array)$value);
        }
        return ((array)$value)[$this->key] ?? null;
    }
}
