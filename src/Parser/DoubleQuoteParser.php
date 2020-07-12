<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\ParserError;

class DoubleQuoteParser extends AbstractQuoteParser
{
    public const QUOTE = '"';

    protected $escapedCharacters = [self::QUOTE, '\\', '$'];

    public function read(string $buffer, int &$offset)
    {
        if ($buffer[$offset] !== static::QUOTE) {
            throw new \InvalidArgumentException("No double quote at $offset in buffer");
        }

        $offset++;
        $state = self::STATE_READ;
        $length = strlen($buffer);
        $string = '';
        while ($offset < $length) {
            switch ($state) {
                case self::STATE_READ:
                    $result = $this->parse($buffer, $offset);
                    if ($result !== null) {
                        $string .= $result;
                    }

                    if ($buffer[$offset] === '\\') {
                        $state = self::STATE_ESCAPED;
                    } elseif ($buffer[$offset] === static::QUOTE) {
                        $this->string = $string;
                        $offset++;
                        return;
                    } else {
                        $string .= $buffer[$offset];
                    }
                    $offset++;
                    break;

                case self::STATE_ESCAPED:
                    if (in_array($buffer[$offset], $this->escapedCharacters)) {
                        $string .= $buffer[$offset];
                    } else {
                        $string .= '\\' . $buffer[$offset];
                    }
                    $state = self::STATE_READ;
                    $offset++;
                    break;
            }
        }

        throw new ParserError('Unexpected end of file. Expected double quote.');
    }

    /**
     * Check for a var access or other magic inside strings at this position
     *
     * If you find something:
     *   - forward the offset and return the string
     *   - values of boolean should be 'false' and 'true'
     *
     * @param string $buffer
     * @param int    $offset
     * @return string|null
     * @throws ParseError
     */
    protected function parse(string $buffer, int &$offset): ?string
    {
        /** @var VarAccessParser $parser */
        $parser = $this->file->getParser(VarAccessParser::class);
        if ($parser->match($buffer, $offset)) {
            $parser->read($buffer, $offset);
            return (string)$parser->getValue();
        }
        return null;
    }
}
