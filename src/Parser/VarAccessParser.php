<?php

namespace EnvParser\Parser;

use EnvParser\ParserError;

class VarAccessParser extends AbstractParser
{
    /** @var mixed */
    protected $value;

    public function read(string $buffer, int &$offset)
    {
        preg_match('/\G\$\{?([a-zA-Z][a-zA-Z0-9_]*)/', $buffer, $match, 0, $offset);
        $var = $match[1];
        $value = $this->value = $this->file->get($var);
        $length = strlen($match[0]);
        $offset += $length;

        if ($match[0][1] === '{') {
            $parsers = [
                // ArrayAccessParser::class,
                // DefaultValueParser::class,
            ];

            while ($offset < $length) {
                if ($buffer[$offset] === '}') {
                    $offset++;
                    break;
                }

                foreach ($parsers as $parser) {
                    if ($parser->match($buffer, $offset)) {
                        $parser->read($buffer, $offset);

                        // if ($parser instanceof ArrayAccessParser) then change value to item of array or null
                        // if ($parser instanceof DefaultValueParser) then change value if value is empty
                        continue 2;
                    }
                }

                // no parser matched - we got something else
                throw new ParserError('Unexpected ' . $buffer[$offset]);
            }

            $this->value = $value;
        }
    }

    public function match(string $buffer, int $offset)
    {
        return !!preg_match('/\G\$\{?[a-zA-Z][a-zA-Z0-9_]*/', $buffer, $match, 0, $offset);
    }

    public function getValue()
    {
        return $this->value;
    }
}
