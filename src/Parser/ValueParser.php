<?php

namespace EnvParser\Parser;

use EnvParser\ParseError;
use EnvParser\StringValue;

class ValueParser extends AbstractParser
{
    /** @var mixed */
    protected $value;

    public function read(string $buffer, int &$offset)
    {
        $this->value = null;
        $length = strlen($buffer);

        /** @var AbstractParser[] $parsers */
        $parsers = [
            $this->file->getParser(SpaceParser::class),
            $this->file->getParser(CommentParser::class),
            $this->file->getParser(VarAccessParser::class),
            $this->file->getParser(DoubleQuoteParser::class),
            $this->file->getParser(SingleQuoteParser::class),
        ];

        $state = 'read';
        $value = '';
        $bare = true;
        while ($offset < $length) {
            switch ($state) {
                case 'escaped':
                    $value .= $buffer[$offset];
                    $offset++;
                    $state = 'read';
                    break;

                case 'read':
                    foreach ($parsers as $parser) {
                        if ($parser->match($buffer, $offset)) {
                            $parser->read($buffer, $offset);

                            // spaces and coments end the value part
                            if ($parser instanceof SpaceParser || $parser instanceof CommentParser) {
                                break 3; // stop reading
                            }

                            if ($parser instanceof VarAccessParser) {
                                $bare = false;

                                if (strlen($value) > 0) {
                                    $value .= $parser->getValue();
                                    continue 3;
                                }

                                $value = $parser->getValue();
                                continue 3;
                            }

                            if ($parser instanceof DoubleQuoteParser) {
                                if (strlen($value) > 0) {
                                    $bare = false;
                                }

                                $value .= $parser->getString();
                                continue 3;
                            }

                            if ($parser instanceof SingleQuoteParser) {
                                $bare = false;
                                $value .= $parser->getString();
                                continue 3;
                            }
                        }
                    }

                    if ($buffer[$offset] === '\\') {
                        $state = 'escaped';
                    } else {
                        $value .= $buffer[$offset];
                    }
                    $offset++;
                    break;
            }
        }

        // prepare the value ?
        if ($bare) {
            $value = $this->file::string2Var($value);
        }

        $this->value = is_string($value) ? $this->decode($value) : $value;
    }


    protected function decode(string $value)
    {
        if (substr($value, 0, 5) === 'json:') {
            return json_decode(substr($value, 5));
        }

        if (substr($value, 0, 10) === 'jsonArray:') {
            return json_decode(substr($value, 10), true);
        }

        if (substr($value, 0, 7) === 'base64:') {
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
