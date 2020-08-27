<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\ArrayParser;
use EnvParser\Parser\ValueParser;
use EnvParser\ParserError;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\ArrayParser */
class ArrayParserTest extends TestCase
{
    /** @test */
    public function matchesOpenParenthesis()
    {
        $parser = new ArrayParser($this->envFile);

        self::assertTrue($parser->match('(', 0));
    }

    /** @test */
    public function throwsForInvalidArguments()
    {
        $parser = new ArrayParser($this->envFile);
        $buffer = 'var=(foo bar)';
        $offset = 5; // should point to the open parenthesis

        self::expectException(\InvalidArgumentException::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function readsValueUsingValueParser()
    {
        $parser = new ArrayParser($this->envFile);
        $buffer = 'var=(foo)';
        $offset = 4;

        $this->envFile->getParser(ValueParser::class);
        $this->parsers[ValueParser::class]->shouldReceive('read')->with($buffer, 5, true)->once()->passthru();
        $this->parsers[ValueParser::class]->shouldReceive('getValue')->once()->andReturn('foo');

        $parser->read($buffer, $offset);

        self::assertSame(['foo'], $parser->getValues());
    }

    /** @test */
    public function throwsWhenClosingParenthesisIsMissing()
    {
        $parser = new ArrayParser($this->envFile);
        $buffer = 'var=(foo';
        $offset = 4;

        self::expectException(ParserError::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function emptyArrayDoesNotContainNull()
    {
        $parser = new ArrayParser($this->envFile);
        $buffer = 'var=()';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame([], $parser->getValues());
    }

    /** @test */
    public function continuesReadingValues()
    {
        $parser = new ArrayParser($this->envFile);
        $buffer = 'var=("foo bar" hello world)';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame(['foo bar', 'hello', 'world'], $parser->getValues());
    }
}
