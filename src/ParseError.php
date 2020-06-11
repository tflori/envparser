<?php

namespace EnvParser;

use Throwable;

class ParseError extends \Exception
{
    /** @var string */
    protected $buffer;

    /** @var int */
    protected $offset;

    /** @var int */
    protected $column;

    public function __construct($message, $path, $buffer, $offset)
    {
        $this->buffer = $buffer;
        $this->offset = $offset;

        $line = substr_count($buffer, "\n", 0, $offset) + 1;
        $lineStart = strrpos(substr($buffer, 0, $offset), "\n") + 1;

        $column = $this->column = $offset - $lineStart;
        $message = sprintf(
            'Parse error in %s on line %d at column %d: %s',
            $path,
            $line,
            $column,
            $message
        );
        parent::__construct($message);
    }

    public function getBufferLine(): string
    {
        $lineStart = strrpos(substr($this->buffer, 0, $this->offset), "\n") + 1;
        $lineEnd = strpos($this->buffer, "\n", $this->offset);

        return substr($this->buffer, $lineStart, $lineEnd - $lineStart);
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getBuffer()
    {
        return $this->buffer;
    }

    public function getOffset()
    {
        return $this->offset;
    }
}
