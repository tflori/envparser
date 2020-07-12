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

        // find the closing brace if we have an opening brace
        if ($match[0][1] === '{') {
            $length = strlen($buffer);
            while ($offset < $length) {
                if ($buffer[$offset] === '}') {
                    $offset++;
                    $this->value = $value;
                    return;
                } elseif (!$this->parse($buffer, $offset, $value)) {
                    // no parser matched - we got something else
                    throw new ParserError('Unexpected ' . $buffer[$offset]);
                }
            }

            throw new ParserError('Unexpected end of file. Expected closing brace');
        }
    }

    /**
     * Check for a array access or default value at this position
     *
     * If you find something:
     *   - forward the offset and update the value
     *
     * @param string $buffer
     * @param int    $offset
     * @param mixed  $value
     * @return bool|null
     */
    protected function parse(string $buffer, int &$offset, &$value): ?bool
    {
        $parsers = [
            $this->file->getParser(ArrayAccessParser::class),
            $this->file->getParser(DefaultValueParser::class),
        ];

        foreach ($parsers as $parser) {
            if ($parser->match($buffer, $offset)) {
                $parser->read($buffer, $offset);

                if ($parser instanceof ArrayAccessParser) {
                    $value = ((array)$value)[$parser->getKey()] ?? null;
                }

                if ($parser instanceof DefaultValueParser) {
                    $value = empty($value) ? $parser->getDefault() : $value;
                }
                return true;
            }
        }

        return false;
    }

    public function match(string $buffer, int $offset): bool
    {
        return !!preg_match('/\G\$\{?[a-zA-Z][a-zA-Z0-9_]*/', $buffer, $match, 0, $offset);
    }

    /** @codeCoverageIgnore */
    public function getValue()
    {
        return $this->value;
    }
}
