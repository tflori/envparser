<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\ParserError;

class CStringParser extends AbstractParser
{

    /** @var string */
    protected $string;

    public function read(string $buffer, int &$offset)
    {
        if (substr($buffer, $offset, 2) !== '$\'') {
            throw new \InvalidArgumentException('No c-like string starting at offset ' . $offset);
        }

        $offset += 2;
        $length = strlen($buffer);
        $state = 'unescaped';
        $content = '';
        while ($offset < $length) {
            $char = $buffer[$offset];
            $offset++;

            switch ($state) {
                case 'unescaped':
                    if ($char === '\\') {
                        $state = 'escaped';
                    } elseif ($char === '\'') {
                        $this->string = $content;
                        return;
                    } else {
                        $content .= $char;
                    }
                    break;

                case 'escaped':
                    switch ($char) {
                        case '"':
                            $content .= '"';
                            break;

                        case '\'':
                            $content .= '\'';
                            break;

                        case '\\':
                            $content .= '\\';
                            break;

                        case 'a':
                            $content .= "\x7";
                            break;

                        case 'b':
                            $content .= "\x8";
                            break;

                        case 'e':
                        case 'E':
                            $content .= "\e";
                            break;

                        case 'f':
                            $content .= "\xC";
                            break;

                        case 'n':
                            $content .= "\n";
                            break;

                        case 'r':
                            $content .= "\r";
                            break;

                        case 't':
                            $content .= "\t";
                            break;

                        case 'v':
                            $content .= "\v";
                            break;

                        case 'x': // hexadecimal
                            if (!preg_match('/\G([0-9A-F]{1,2})/', $buffer, $match, 0, $offset)) {
                                $content .= '\\x';
                            } else {
                                $content .= chr(hexdec($match[1]));
                                $offset += strlen($match[1]);
                            }
                            break;

                        case 'u': // unicode
                            if (!preg_match('/\G([0-9A-F]{1,4})/', $buffer, $match, 0, $offset)) {
                                $content .= '\\u';
                            } else {
                                $content .= html_entity_decode('&#x' . $match[1] . ';');
                                $offset += strlen($match[1]);
                            }
                            break;

                        case 'U': // unicode with up to 8 digits
                            if (!preg_match('/\G([0-9A-F]{1,8})/', $buffer, $match, 0, $offset)) {
                                $content .= '\\U';
                            } else {
                                $content .= html_entity_decode('&#x' . $match[1] . ';');
                                $offset += strlen($match[1]);
                            }
                            break;

                        case 'c':
                            $dec = ord(strtoupper($buffer[$offset])) - 64;
                            if ($dec >= 0 && $dec < 32) {
                                $content .= chr($dec);
                            }
                            $offset++;
                            break;

                        default:
                            if (preg_match('/\G([0-7]{1,3})/', $buffer, $match, 0, $offset - 1)) {
                                $content .= chr(octdec($match[1]));
                                $offset += strlen($match[1]) - 1;
                            } else {
                                $content .= '\\' . $char;
                            }
                    }
                    $state = 'unescaped';
                    break;
            }
        }

        throw new ParserError('Unexpected end of file. Expected single quote');
    }

    public function match(string $buffer, int $offset): bool
    {
        return substr($buffer, $offset, 2) === '$\'';
    }

    /** @codeCoverageIgnore */
    public function getString(): string
    {
        return $this->string;
    }
}
