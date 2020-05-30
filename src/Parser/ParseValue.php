<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\StringValue;

class ParseValue extends AbstractParser
{
    public function read(string $buffer, int &$offset)
    {
        $value = [];
        $current = '';
        $length = strlen($buffer);
        while ($offset < $length) {
            $char = $buffer[$offset];
            switch ($char) {
                case '"':
                    $offset++;
                    // empty the current buffer and append the value
                    if (!empty($current)) {
                        $value[] = $this->interpolate($current);
                        $current = '';
                    }

                    $parser = new ParseDoubleQuote();
                    $value[] = $parser->read($buffer, $offset);
                    break;

                case "'":
                    $offset++;
                    // empty the current buffer and append the value
                    if (!empty($current)) {
                        $value[] = $this->interpolate($current);
                        $current = '';
                    }

                    $parser = new ParseSingleQuote();
                    $value[] = $parser->read($buffer, $offset);
                    break;

                case ' ':
                    if (preg_match('/^[ \t]*(#|$)/', substr($buffer, $offset))) {
                        // set the offset after the next line feed
                        $offset = strpos($buffer, "\n", $offset) + 1;
                        break 2; // stop our loop and return the value
                    }

                    throw new ParseError('spaces have to be enclosed in quotes', $buffer, $offset);

                case ';':
                case '#':
                    // set the offset after the next line feed
                    $offset = strpos($buffer, "\n", $offset) + 1;
                    break 2; // stop our loop and return the value

                case "\n":
                    $offset++;
                    break 2; // stop our loop and return the value

                default:
                    $offset++;
                    // other characters are all handled as value
                    $current .= $char;
                    break;
            }
        }

        if (!empty($current)) {
            $value[] = $this->interpolate($current);
        }

        if (count($value) === 0) {
            return null;
        }

        if (count($value) === 1) {
            $value = $value[0] instanceof StringValue ? (string)$value[0] : $this->string2Var($value[0]);
        } else {
            $value = implode('', $value);
        }

        return is_string($value) ? $this->decode($value) : $value;
    }

    protected function decode(string $value)
    {
        if (substr($value, 0, 5) === 'json:') {
            return json_decode(substr($value, 5));
        }

        if (substr($value, 0, 10) === 'jsonArray:') {
            return json_decode(substr($value, 10), true);
        }

        if (substr($value, 0, 7) === 'base64:') {
            return base64_decode(substr($value, 7));
        }

        return $value;
    }
}
