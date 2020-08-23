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
                        // To be honest: I'm not sure that this is not a security risk
                        // otherwise we had to implement all the different escape sequences:
                        // https://wiki.bash-hackers.org/syntax/quoting#ansi_c_like_strings
                        exec('echo $\'' . $content . '\'', $output);
                        $this->string = implode(PHP_EOL, $output);
                        return;
                    } else {
                        $content .= $char;
                    }
                    break;

                case 'escaped':
                    $content .= '\\' . $char;
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
