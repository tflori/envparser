<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\CStringParser;
use EnvParser\ParserError;
use EnvParserTests\TestCase;

class CstringParserTest extends TestCase
{
    /** @test */
    public function matchesDollarFollowedBySingleQuote()
    {
        $parser = new CStringParser($this->envFile);
        $buffer = 'var=$\'';
        $offset = 4;

        self::assertTrue($parser->match($buffer, $offset));
    }

    /** @test */
    public function throwsWhenNoCStringStartsAtOffset()
    {
        $parser = new CStringParser($this->envFile);
        $buffer = 'var=\'foo bar\'';
        $offset = 4;

        self::expectException(\InvalidArgumentException::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function throwsWhenNoEndIsFound()
    {
        $parser = new CStringParser($this->envFile);
        $buffer = 'var=$\'foo bar';
        $offset = 4;

        self::expectException(ParserError::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function endsWithSingleQuote()
    {
        $parser = new CStringParser($this->envFile);
        $buffer = 'A=$\'foo bar\' B=42';
        $offset = 2;

        $parser->read($buffer, $offset);

        self::assertSame(12, $offset);
        self::assertSame('foo bar', $parser->getString());
    }

    /** @test */
    public function escapedSingleQuotesDontEndTheString()
    {
        $parser = new CStringParser($this->envFile);
        $buffer = 'A=$\'foo bar\\\' B=42\'';
        $offset = 2;

        $parser->read($buffer, $offset);

        self::assertSame(19, $offset);
        self::assertSame('foo bar\' B=42', $parser->getString());
    }
}
