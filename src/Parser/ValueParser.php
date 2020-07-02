<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
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

    public function read(string $buffer, int &$offset)
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
                    $state = $this->parse($buffer, $offset, $value, $bare);

                    if ($state === '') {
                        if ($buffer[$offset] === '\\') {
                            $state = self::STATE_ESCAPED;
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

    protected function parse(string $buffer, int &$offset, &$value, &$bare)
    {
        /** @var AbstractParser[] $parsers */
        $parsers = [
            $this->file->getParser(SpaceParser::class),
            $this->file->getParser(CommentParser::class),
            $this->file->getParser(VarAccessParser::class),
            $this->file->getParser(DoubleQuoteParser::class),
            $this->file->getParser(SingleQuoteParser::class),
        ];

        foreach ($parsers as $parser) {
            if ($parser->match($buffer, $offset)) {
                $parser->read($buffer, $offset);

                // spaces and comments end the value part
                if ($parser instanceof SpaceParser || $parser instanceof CommentParser) {
                    return self::STATE_END;
                }

                if ($parser instanceof VarAccessParser) {
                    $bare = false;

                    (strlen($value) > 0) ?
                        $value .= $parser->getValue() :
                        $value = $parser->getValue();
                    return self::STATE_READ;
                }

                if ($parser instanceof DoubleQuoteParser) {
                    if (strlen($value) > 0) {
                        $bare = false;
                    }

                    $value .= $parser->getString();
                    return self::STATE_READ;
                }

                if ($parser instanceof SingleQuoteParser) {
                    $bare = false;
                    $value .= $parser->getString();
                    return self::STATE_READ;
                }
            }
        }

        return '';
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

    public function getValue()
    {
        return $this->value;
    }
}
