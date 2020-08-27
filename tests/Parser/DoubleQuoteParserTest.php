<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\DoubleQuoteParser;
use EnvParser\Parser\VarAccessParser;
use EnvParser\ParserError;
use EnvParserTests\TestCase;
use Mockery as m;

/** @covers \EnvParser\Parser\DoubleQuoteParser */
class DoubleQuoteParserTest extends TestCase
{
    /** @test */
    public function matchesAStartingDoubleQuote()
    {
        $parser = new DoubleQuoteParser($this->envFile);

        self::assertTrue($parser->match('var="', 4));
    }

    /** @test */
    public function endsWithDoubleQuote()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = '"foo bar" # bla bla';
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame('foo bar', $parser->getString());
        self::assertSame(9, $offset);
    }

    /** @test */
    public function backslashesEscapeDoubleQuotes()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = '"\""';
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame('"', $parser->getString());
    }

    /** @test */
    public function backslashEscapesBackslash()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = '"\\\\"';
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame('\\', $parser->getString());
    }

    /** @test */
    public function backslashStaysForAnythingElse()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = '"\a\xa1\\\'"';
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame('\a\xa1\\\'', $parser->getString());
    }

    /** @test */
    public function parsesVarAccesses()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = '"$user"';
        $offset = 0;

        $varAccessParser = m::mock(VarAccessParser::class, [$this->envFile])->makePartial();
        $varAccessParser->shouldReceive('getValue')->andReturn('john doe');
        $this->envFile->shouldReceive('getParser')->with(VarAccessParser::class)
            ->once()->andReturn($varAccessParser);

        $parser->read($buffer, $offset);

        self::assertSame('john doe', $parser->getString());
    }

    /** @test */
    public function backslashEscapesVarAccess()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = '"\$user"';
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame('$user', $parser->getString());
    }

    /** @test */
    public function throwsWhenNoEndIsFound()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = '"foo bar';
        $offset = 0;

        self::expectException(ParserError::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function throwsWhenCalledForWrongOffset()
    {
        $parser = new DoubleQuoteParser($this->envFile);
        $buffer = 'foo bar';
        $offset = 0;

        self::expectException(\InvalidArgumentException::class);

        $parser->read($buffer, $offset);
    }
}
