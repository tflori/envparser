<?php

namespace EnvParser;

use Throwable;

class ParseError extends \Exception
{
    public function __construct($message, $buffer, $offset)
    {
        $line = substr_count($buffer, "\n", 0, $offset) + 1;
        $column = $offset - strrpos(substr($buffer, 0, $offset), "\n");
        $message = sprintf('Parse error on line ' . $line . ' at column ' . $column . ': ' . $message);
        parent::__construct($message);
    }

    public function setFile(string $path)
    {
        $this->message = sprintf('Parse error in ' . $path . substr($this->message, 11));
    }
}
