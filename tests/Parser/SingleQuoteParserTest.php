<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\SingleQuoteParser;
use EnvParser\ParserError;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\SingleQuoteParser */
class SingleQuoteParserTest extends TestCase
{
    /** @test */
    public function matchesSingleQuote()
    {
        $parser = new SingleQuoteParser($this->envFile);
        $buffer = "var='";
        $offset = 4;

        self::assertTrue($parser->match($buffer, $offset));
    }

    /** @test */
    public function throwsWhenNotClosed()
    {
        $parser = new SingleQuoteParser($this->envFile);
        $buffer = "var='foo";
        $offset = 4;

        self::expectException(ParserError::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function throwsWithoutSingleQuoteAtOffset()
    {
        $parser = new SingleQuoteParser($this->envFile);
        $buffer = "var=foo";
        $offset = 4;

        self::expectException(\InvalidArgumentException::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function endsWithSingleQuote()
    {
        $parser = new SingleQuoteParser($this->envFile);
        $buffer = "var='foo bar'faf";
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame(13, $offset);
    }

    /** @test */
    public function storesTheString()
    {
        $parser = new SingleQuoteParser($this->envFile);
        $buffer = "var='foo bar'";
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame('foo bar', $parser->getString());
    }

    /** @test */
    public function singleQuotesCantBeEscaped()
    {
        $parser = new SingleQuoteParser($this->envFile);
        $buffer = "var='foo\\'s bar'";
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame('foo\\', $parser->getString());
        self::assertSame(10, $offset);
    }
}
