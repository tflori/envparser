<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;

abstract class AbstractParser
{
    /**
     * @param string $buffer
     * @param int    $offset
     * @return mixed
     * @throws ParseError
     */
    abstract public function read(string $buffer, int &$offset);

    protected function string2Var(string $value)
    {
        $lower = strtolower($value);
        if (strlen($value) === 0 || $lower === 'null') {
            return null;
        }

        if ($lower === 'true' || $lower === 'false') {
            return $lower === 'true';
        }

        if (is_numeric($value)) {
            $int = (int)$value;
            $float = (double)$value;
            return $int == $float ? $int : $float;
        }

        return json_decode($value) ?? $value;
    }

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
}
