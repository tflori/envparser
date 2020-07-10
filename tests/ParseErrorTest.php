<?php

namespace EnvParserTests;

use EnvParser\ParseError;
use EnvParser\ParserError;

class ParseErrorTest extends TestCase
{
    /** @test */
    public function returnsTheLineAtCurrentOffset()
    {
        $content = 'this is a text file' . PHP_EOL .
                   'with more than one line' . PHP_EOL .
                   'and maybe event more';

        $parserError = new ParserError('Foo? Bar!');
        $parseError = new ParseError($parserError, '/path/to/.env', $content, 25);

        $bufferLine = $parseError->getBufferLine();

        self::assertSame('with more than one line', $bufferLine);
    }
}
