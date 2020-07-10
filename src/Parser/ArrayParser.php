<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\ParserError;

class ArrayParser extends AbstractParser
{

    /**
     * @var array
     */
    protected $values = [];

    public function read(string $buffer, int &$offset)
    {
        if ($buffer[$offset] !== '(') {
            throw new \InvalidArgumentException('No opening parenthesis at offset ' . $offset);
        }

        $offset++;
        $this->values = [];
        /** @var ValueParser $parser */
        $parser = $this->file->getParser(ValueParser::class);
        $length = strlen($buffer);
        while ($offset < $length) {
            if ($buffer[$offset] === ')') {
                $offset++;
                return;
            }
            $parser->read($buffer, $offset, true);
            $this->values[] = $parser->getValue();
        }

        throw new ParserError('Unexpected end of file. Expected closing parenthesis');
    }

    public function match(string $buffer, int $offset): bool
    {
        return $buffer[$offset] === '(';
    }

    /** @codeCoverageIgnore */
    public function getValues(): array
    {
        return $this->values;
    }
}
