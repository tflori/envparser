<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\CommentParser;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\CommentParser */
class CommentParserTest extends TestCase
{
    /** @test */
    public function matchesHash()
    {
        $parser = new CommentParser($this->envFile);
        $buffer = '# foo=bar';
        $offset = 0;

        self::assertTrue($parser->match($buffer, $offset));
    }

    /** @test */
    public function readsToEndOfBuffer()
    {
        $parser = new CommentParser($this->envFile);
        $buffer = '# foo=bar';
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame(9, $offset);
    }

    /** @test */
    public function readsToEndOfLine()
    {
        $parser = new CommentParser($this->envFile);
        $buffer = "# foo=bar\nfoo=42";
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame(10, $offset);
    }
}
