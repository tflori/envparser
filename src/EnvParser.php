<?php

namespace EnvParser;

use EnvParser\Parser\ParseValue;
use EnvParser\State\AbstractState;
use EnvParser\State\NoneState;

class EnvParser
{
    /** @var array */
    protected $states = [];

    public function read(string $path): array
    {
        $buffer = file_get_contents($path);
        if ($buffer === false) {
            throw new \InvalidArgumentException('Unable to read file ' . $path);
        }

        $data = [];
        try {
            $offset = 0;
            $length = strlen($buffer);
            while ($offset < $length) {
                $line = substr($buffer, $offset, strpos($buffer, "\n", $offset) - $offset);

                if (strlen($line) === 0) {
                    // empty line - skip
                    $offset++;
                    continue;
                }

                if (preg_match('/^[ \t]*#/', $line)) {
                    // comment line - skip
                    $offset += strlen($line) + 1;
                    continue;
                }

                if (preg_match('/^([A-Za-z_][a-zA-Z0-9_]*)(?:\[(\d+)\])?=/', $line, $match)) {
                    // defining variable
                    $offset += strlen($match[0]);
                    $value = (new ParseValue())->read($buffer, $offset);
                    $var = $match[1];
                    if (isset($match[2])) {
                        $key = $match[2];
                        if (isset($data[$var]) && !is_array($data[$var])) {
                            $data[$var] = [];
                        }
                        $data[$var][$key] = $value;
                    } else {
                        $data[$var] = $value;
                    }
                }

            }
        } catch (ParseError $error) {
            $error->setFile($path);
            // @todo throw or warn?
            throw $error;
        }

        return $data;
    }
}
