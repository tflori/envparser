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
        ];

        $value = '';
        $bare = true;
        while ($offset < $length) {
            foreach ($parsers as $parser) {
                if ($parser->match($buffer, $offset)) {
                    $parser->read($buffer, $offset);

                    // spaces and coments end the value part
                    if ($parser instanceof SpaceParser || $parser instanceof CommentParser) {
                        break 2; // stop reading
                    }

                    if ($parser instanceof VarAccessParser) {
                        $bare = false;

                        if (strlen($value) > 0) {
                            $value .= $parser->getValue();
                            continue 2;
                        }

                        $value = $parser->getValue();
                        continue 2;
                    }
                }
            }

            // other characters are all handled as value
            $value .= $buffer[$offset];
            $offset++;
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

    public function match(string $buffer, int $offset)
    {
        return !!preg_match('/\G[^' . $this->file::WHITESPACE_CHARACTERS . ']/', $buffer, $match, 0, $offset);
    }

    public function getValue()
    {
        return $this->value;
    }
}
