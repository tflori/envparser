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
        $offset += strlen($match[0]);

        if ($match[0][1] === '{') {
            $parsers = [
                 $this->file->getParser(ArrayAccessParser::class),
                 $this->file->getParser(DefaultValueParser::class),
            ];

            $length = strlen($buffer);
            while ($offset < $length) {
                if ($buffer[$offset] === '}') {
                    $offset++;
                    break;
                }

                foreach ($parsers as $parser) {
                    if ($parser->match($buffer, $offset)) {
                        $parser->read($buffer, $offset);

                        if ($parser instanceof ArrayAccessParser) {
                            $value = $value[$parser->getKey()] ?? null;
                        }

                        if ($parser instanceof DefaultValueParser) {
                            $value = empty($value) ? $parser->getDefault() : $value;
                        }
                        continue 2;
                    }
                }

                // no parser matched - we got something else
                throw new ParserError('Unexpected ' . $buffer[$offset]);
            }

            $this->value = $value;
        }
    }

    public function match(string $buffer, int $offset): bool
    {
        return !!preg_match('/\G\$\{?[a-zA-Z][a-zA-Z0-9_]*/', $buffer, $match, 0, $offset);
    }

    public function getValue()
    {
        return $this->value;
    }
}
