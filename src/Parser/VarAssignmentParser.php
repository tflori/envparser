<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;

class VarAssignmentParser extends AbstractParser
{
    /** @var string */
    protected $var;

    /** @var int */
    protected $key;

    /** @var mixed */
    protected $value = null;

    public function read(string $buffer, int &$offset)
    {
        preg_match('/\G([A-Za-z_][a-zA-Z0-9_]*)(?:\[(\d+)\])?=/', $buffer, $match, 0, $offset);
        $this->var = $match[1];
        $this->key = isset($match[2]) ? (int)$match[2] : null;
        $offset += strlen($match[0]);
        // test array parser and use
        $valueParser = $this->file->getParser(ValueParser::class);
        $valueParser->read($buffer, $offset);
        $this->value = $valueParser->getValue();
    }

    public function match(string $buffer, int $offset): bool
    {
        return !!preg_match('/\G([A-Za-z_][a-zA-Z0-9_]*)(?:\[(\d+)\])?=/', $buffer, $match, 0, $offset);
    }

    /**
     * @return string
     */
    public function getVar(): string
    {
        return $this->var;
    }

    /**
     * @return int|null
     */
    public function getKey(): ?int
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
