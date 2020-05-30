<?php

namespace EnvParser;

class StringValue
{
    /** @var string */
    protected $string;

    /**
     * StringValue constructor.
     *
     * @param string $string
     */
    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}
