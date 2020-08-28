<?php

namespace EnvParser;

use EnvParser\Parser\AbstractParser;
use EnvParser\Parser\CommentParser;
use EnvParser\Parser\SpaceParser;
use EnvParser\Parser\VarAssignmentParser;

class EnvFile extends \ArrayObject
{
    public const WHITESPACE_CHARACTERS = ' \t\n';

    /** @var array */
    protected $context = [];

    /** @var callable */
    protected $resolver;

    /** @var AbstractParser[] */
    protected $resolved = [];

    public function __construct(array $context = null, callable $resolver = null)
    {
        $this->context = $context ?? getenv();
        $this->resolver = $resolver ?? function (string $class, ...$args) {
            if (!isset($this->resolved[$class])) {
                $this->resolved[$class] = new $class($this, ...$args);
            }
            return $this->resolved[$class];
        };
        parent::__construct([], self::ARRAY_AS_PROPS);
    }

    /** @codeCoverageIgnore trivial */
    public function setContext(array $context = null)
    {
        $this->context = $context ?? getenv();
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

    public function getArrayCopy()
    {
        return array_merge(parent::getArrayCopy(), array_map([$this, 'string2Var'], $this->context));
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
        $buffer = @file_get_contents($path); // error is fetched in next line
        if ($buffer === false) {
            throw new \InvalidArgumentException('Unable to read file ' . $path);
        }

        try {
            $parsers = [
                $this->getParser(VarAssignmentParser::class),
                $this->getParser(SpaceParser::class),
                $this->getParser(CommentParser::class),
            ];

            $size = strlen($buffer);
            $offset = 0;
            while ($offset < $size) {
                $parser = $this->matchParser($parsers, $buffer, $offset);
                if (!$parser) {
                    preg_match('/\G(.*?)([' . self::WHITESPACE_CHARACTERS . ']|$)/', $buffer, $match, 0, $offset);
                    throw new ParserError(sprintf('Unexpected %s', $match[1]));
                }

                $parser->read($buffer, $offset);
                if ($parser instanceof VarAssignmentParser) {
                    $var = $parser->getVar();
                    $key = $parser->getKey();
                    if ($key) {
                        $current = (array)($this[$var] ?? []);
                        $current[$key] = $parser->getValue();
                        $this[$var] = $current;
                    } else {
                        $this[$var] = $parser->getValue();
                    }
                }
            }
        } catch (ParserError $parserError) {
            throw new ParseError($parserError, $path, $buffer, $offset);
        }
    }

    public function matchParser(array $parsers, string $buffer, int $offset): ?AbstractParser
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
        return call_user_func($this->resolver, $class);
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

    /** @codeCoverageIgnore only executed in php <7.4 */
    public function serialize()
    {
        return serialize(parent::getArrayCopy());
    }

    /** @codeCoverageIgnore only executed in php >=7.4 */
    public function __serialize(): array
    {
        return parent::getArrayCopy();
    }

    /** @codeCoverageIgnore only executed in php <7.4 */
    public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized));
    }

    /**
     * @param array $data
     * @noinspection PhpHierarchyChecksInspection
     */
    public function __unserialize($data)
    {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }
        $this->context = getenv();
    }
}
