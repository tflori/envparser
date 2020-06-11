<?php

namespace EnvParser;

use EnvParser\Parser\AbstractParser;
use EnvParser\Parser\CommentParser;
use EnvParser\Parser\SpaceParser;
use EnvParser\Parser\VarAssignmentParser;

class EnvFile extends \ArrayObject
{
    const WHITESPACE_CHARACTERS = ' \t\n';

    /** @var string */
    protected $path;

    /** @var array */
    protected $context = [];

    public function __construct(array $context = null)
    {
        $this->context = $context ?? getenv();
        parent::__construct([], self::ARRAY_AS_PROPS);
    }

    public function get($var, $default = null)
    {
        if (!isset($this->context[$var]) && !isset($this[$var])) {
            return $default;
        }

        if (isset($this->context[$var])) {
            return $this->string2Var($this->context[$var]);
        }

        return parent::offsetGet($var);
    }

    public function offsetGet($index)
    {
        return $this->get($index);
    }

    /**
     * @param string $path
     * @throws ParseError
     */
    public function read(string $path)
    {
        $buffer = file_get_contents($path);
        if ($buffer === false) {
            throw new \InvalidArgumentException('Unable to read file ' . $path);
        }

        $this->path = $path;
        $offset = 0;

        $size = strlen($buffer);
        while ($offset < $size) {
            $parser = $this->nextParser([
                $this->getParser(VarAssignmentParser::class),
                $this->getParser(SpaceParser::class),
                $this->getParser(CommentParser::class),
            ], $buffer, $offset);

            if ($parser instanceof VarAssignmentParser) {
                $parser->read($buffer, $offset);
                $var = $parser->getVar();
                $key = $parser->getKey();
                if ($key) {
                    $current = (array)($this[$var] ?? []);
                    $current[$key] = $parser->getValue();
                    $this[$var] = $current;
                } else {
                    $this[$var] = $parser->getValue();
                }
            } elseif ($parser instanceof SpaceParser || $parser instanceof CommentParser) {
                $parser->read($buffer, $offset);
            } else {
                preg_match('/\G(.*?)([' . self::WHITESPACE_CHARACTERS . ']|$)/', $buffer, $match, 0, $offset);
                throw $this->createParseError(sprintf('Unexpected %s', $match[1]), $buffer, $offset);
            }
        }
    }

    /**
     * @param AbstractParser[] $parsers
     * @param string           $buffer
     * @param int              $offset
     */
    protected function nextParser(array $parsers, string $buffer, int &$offset): ?AbstractParser
    {
        foreach ($parsers as $parser) {
            if ($parser->match($buffer, $offset)) {
                return $parser;
            }
        }
        return null;
    }

    public function getParser($class): AbstractParser
    {
        return new $class($this);
    }

    public function createParseError(string $message, string $buffer, int $offset)
    {
        return new ParseError($message, $this->path, $buffer, $offset);
    }

    public static function string2Var($value)
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

        return $value;
    }
}
