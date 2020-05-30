<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;

class ParseDoubleQuote extends AbstractParser
{
    public function read(string $buffer, int &$offset): string
    {
        $value = '';
        $length = strlen($buffer);
        $ended = false;
        $escaped = false;
        while ($offset < $length) {
            $char = $buffer[$offset];
            switch ($char) {
                case '\\':
                    $offset++;

                    // escaped backslash
                    if ($escaped) {
                        $value .= '\\';
                        $escaped = false;
                    }

                    $escaped = true;
                    break;

                case '"':
                    $offset++;

                    // escaped double quote
                    if ($escaped) {
                        $value .= '"';
                        $escaped = false;
                        break;
                    }

                    $ended = true;
                    break 2;

                default:
                    $offset++;

                    if ($escaped) {
                        // in bash the escape sign is not required to be escaped
                        $value .= '\\';
                        $escaped = false;
                    }

                    $value .= $char;
                    break;
            }
        }

        if (!$ended) {
            throw new ParseError('Unexpected end of file. Missing closing double quote', $buffer, $offset);
        }

        return $this->interpolate($value);
    }
}
