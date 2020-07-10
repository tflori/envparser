<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\ParserError;
use EnvParser\StringValue;

class ValueParser extends AbstractParser
{
    public const PREFIX_JSON = 'json:';
    public const PREFIX_JSON_ARRAY = 'jsonArray:';
    public const PREFIX_BASE_64 = 'base64:';

    protected const STATE_END = 'end';
    protected const STATE_READ = 'read';
    protected const STATE_ESCAPED = 'escaped';

    /** @var mixed */
    protected $value;

    public function read(string $buffer, int &$offset, bool $inArray = false)
    {
        $this->value = null;
        $length = strlen($buffer);

        $state = self::STATE_READ;
        $value = '';
        $bare = true;
        while ($offset < $length) {
            switch ($state) {
                case self::STATE_END:
                    break 2;

                case self::STATE_ESCAPED:
                    $value .= $buffer[$offset];
                    $offset++;
                    $state = self::STATE_READ;
                    break;

                case self::STATE_READ:
                    $state = $this->parse($buffer, $offset, $inArray, $value, $bare);

                    if ($state === '') {
                        if ($buffer[$offset] === '\\') {
                            $state = self::STATE_ESCAPED;
                        } elseif ($buffer[$offset] === ')') {
                            // The ) ends the value and still needs to get read from something
                            break 2;
                        } else {
                            $state = self::STATE_READ;
                            $value .= $buffer[$offset];
                        }
                        $offset++;
                    }

                    break;
            }
        }

        // prepare the value ?
        if ($bare) {
            $value = $this->file::string2Var($value);
        }

        $this->value = is_string($value) ? $this->decode($value) : $value;
    }

    protected function parse(string $buffer, int &$offset, bool $inArray, &$value, &$bare)
    {
        /** @var AbstractParser[] $parsers */
        $parsers = [
            'parseSpace' => $this->file->getParser(SpaceParser::class),
            'parseComment' => $this->file->getParser(CommentParser::class),
            'parseVarAccess' => $this->file->getParser(VarAccessParser::class),
            'parseDoubleQuote' => $this->file->getParser(DoubleQuoteParser::class),
            'parseSingleQuote' => $this->file->getParser(SingleQuoteParser::class),
            'parseCString' => $this->file->getParser(CStringParser::class),
            'parseArray' => $this->file->getParser(ArrayParser::class),
        ];

        foreach ($parsers as $method => $parser) {
            if ($parser->match($buffer, $offset)) {
                $parser->read($buffer, $offset);
                return $this->$method($parser, $value, $bare, $inArray);
            }
        }

        return '';
    }

    protected function parseSpace(SpaceParser $parser)
    {
        return self::STATE_END;
    }

    protected function parseComment(CommentParser $parser)
    {
        return self::STATE_END;
    }

    protected function parseVarAccess(VarAccessParser $parser, &$value, &$bare)
    {
        $bare = false;
        (strlen($value) > 0) ?
            $value .= $parser->getValue() :
            $value = $parser->getValue();
        return self::STATE_READ;
    }

    protected function parseDoubleQuote(DoubleQuoteParser $parser, &$value, &$bare)
    {
        if (strlen($value) > 0) {
            $bare = false;
        }

        $value .= $parser->getString();
        return self::STATE_READ;
    }

    protected function parseSingleQuote(SingleQuoteParser $parser, &$value, &$bare)
    {
        $bare = false;
        $value .= $parser->getString();
        return self::STATE_READ;
    }

    protected function parseCString(CStringParser $parser, &$value, &$bare)
    {
        $bare = false;
        $value .= $parser->getString();
        return self::STATE_READ;
    }

    protected function parseArray(ArrayParser $parser, &$value, &$bare, $inArray)
    {
        if ($inArray) {
            throw new ParserError('Array inside array is not allowed');
        }
        $bare = false;
        $value = $parser->getValues();
        return self::STATE_END;
    }

    /**
     * Decode a string if it is prefix with one of PREFIX_ constants
     *
     * @param string $value
     * @return mixed
     */
    protected function decode(string $value)
    {
        if (substr($value, 0, 5) === self::PREFIX_JSON) {
            return json_decode(substr($value, 5));
        }

        if (substr($value, 0, 10) === self::PREFIX_JSON_ARRAY) {
            return json_decode(substr($value, 10), true);
        }

        if (substr($value, 0, 7) === self::PREFIX_BASE_64) {
            return base64_decode(substr($value, 7));
        }

        return $value;
    }

    public function match(string $buffer, int $offset): bool
    {
        return !!preg_match('/\G[^' . $this->file::WHITESPACE_CHARACTERS . ']/', $buffer, $match, 0, $offset);
    }

    /** @codeCoverageIgnore */
    public function getValue()
    {
        return $this->value;
    }
}
