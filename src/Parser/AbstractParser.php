<?php

namespace EnvParser\Parser;

use EnvParser\EnvFile;
use EnvParser\ParseError;
use function PHPUnit\Framework\assertSame;

abstract class AbstractParser
{
    /** @var EnvFile */
    protected $file;

    /**
     * AbstractParser constructor.
     *
     * @param EnvFile $file
     */
    public function __construct(EnvFile $file)
    {
        $this->file = $file;
    }

    /**
     * @param string $buffer
     * @param int    $offset
     * @return mixed
     * @throws ParseError
     */
    abstract public function read(string $buffer, int &$offset);

    /**
     * @param string $buffer
     * @param int    $offset
     * @return mixed
     */
    abstract public function match(string $buffer, int $offset);

    protected function interpolate(string $value)
    {
        $varPattern = '([a-zA-Z_][a-zA-Z0-9_]*)';
        return preg_replace_callback(
            '/\$(?|\{' . $varPattern . '}|' . $varPattern . ')/',
            function ($match) {
                return getenv($match[1]);
            },
            $value
        );
    }

    protected function currentLine($buffer, $offset)
    {
        $nextLineFeed = strpos($buffer, "\n", $offset);
        if ($nextLineFeed === false) {
            return substr($buffer, $offset);
        }

        return substr($buffer, $offset, $nextLineFeed - $offset);
    }
}
