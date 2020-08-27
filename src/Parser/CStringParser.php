<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\ParserError;

class CStringParser extends AbstractParser
{
    protected const ESCAPE_MAPPING = [
        '"' => '"',
        '\'' => '\'',
        '\\' => '\\',
        'a' => "\x7",
        'b' => "\x8",
        'e' => "\e",
        'E' => "\e",
        'f' => "\xC",
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        'v' => "\v",
    ];

    protected const MAX_UNICODE_LENGTH = [
        'x' => 2,
        'u' => 4,
        'U' => 8,
    ];

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
                    $this->readEscapedCharacter($char, $buffer, $offset, $content);
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

    protected function readEscapedCharacter(string $escaped, string $buffer, int &$offset, string &$content)
    {
        if (isset(self::ESCAPE_MAPPING[$escaped])) {
            $content .= self::ESCAPE_MAPPING[$escaped];
            return;
        }

        if (isset(self::MAX_UNICODE_LENGTH[$escaped])) {
            $char = $this->readHexChar(self::MAX_UNICODE_LENGTH[$escaped], $buffer, $offset);
            $content .= $char === false ? '\\' . $escaped : $char;
            return;
        }

        if ($escaped === 'c') {
            $dec = ord(strtoupper($buffer[$offset])) - 64;
            if ($dec >= 0 && $dec < 32) {
                $content .= chr($dec);
            }
            $offset++;
            return;
        }

        if (preg_match('/\G([0-7]{1,3})/', $buffer, $match, 0, $offset - 1)) {
            $content .= chr(octdec($match[1]));
            $offset += strlen($match[1]) - 1;
            return;
        }

        $content .= '\\' . $escaped;
    }

    protected function readHexChar(int $maxLen, string $buffer, int &$offset)
    {
        if (preg_match('/\G([0-9A-F]{1,' . $maxLen . '})/', $buffer, $match, 0, $offset)) {
            $offset += strlen($match[1]);
            if (strlen($match[1]) <= 2) {
                return chr(hexdec($match[1]));
            }

            $char = html_entity_decode('&#x' . $match[1] . ';');
            return substr($char, 0, 3) === '&#x' ? '' : $char;
        }

        return false;
    }
}
