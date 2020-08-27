<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\ArrayAccessParser;
use EnvParser\Parser\DefaultValueParser;
use EnvParser\Parser\VarAccessParser;
use EnvParser\ParserError;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\VarAccessParser */
class VarAccessParserTest extends TestCase
{
    /** @test */
    public function matchesSimpleVarNames()
    {
        $parser = new VarAccessParser($this->envFile);

        self::assertTrue($parser->match('$fooBar', 0));
    }

    /** @test */
    public function matchesVarNamesWithDigitsAndUnderscores()
    {
        $parser = new VarAccessParser($this->envFile);

        self::assertTrue($parser->match('$FOO_BAR23', 0));
    }

    /** @test */
    public function varNamesCanNotStartWithNumbers()
    {
        $parser = new VarAccessParser($this->envFile);

        self::assertFalse($parser->match('$2asdf', 0));
    }

    /** @test */
    public function matchesVarAccessWithBraces()
    {
        $parser = new VarAccessParser($this->envFile);

        self::assertTrue($parser->match('${foo', 0));
    }

    /** @test */
    public function throwsWhenOpenBraceIsNotClosed()
    {
        $parser = new VarAccessParser($this->envFile);
        $buffer = '${foo';
        $offset = 0;

        self::expectException(ParserError::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function storesTheValueFromVar()
    {
        $parser = new VarAccessParser($this->envFile);
        $buffer = 'var=$foo';
        $offset = 4;

        $this->envFile->shouldReceive('get')->with('foo')->once()->andReturn('bar');

        $parser->read($buffer, $offset);

        self::assertSame('bar', $parser->getValue());
    }

    /** @test */
    public function endsWithAnythingElse()
    {
        $parser = new VarAccessParser($this->envFile);
        $buffer = 'var=$foo[]';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame(8, $offset);
    }

    /** @test */
    public function parsesDefaultValues()
    {
        $parser = new VarAccessParser($this->envFile);
        $buffer = '${foo-bar}';
        $offset = 0;

        $this->envFile->getParser(DefaultValueParser::class);
        $this->parsers[DefaultValueParser::class]
            ->shouldReceive('match')->andReturn(false)->byDefault();
        $this->parsers[DefaultValueParser::class]
            ->shouldReceive('match')->with($buffer, 5)->once()->andReturnTrue();
        $this->parsers[DefaultValueParser::class]
            ->shouldReceive('read')->with($buffer, 5)->once()->passthru();
        $this->parsers[DefaultValueParser::class]
            ->shouldReceive('getDefault')->once()->andReturn('bar');

        $parser->read($buffer, $offset);

        self::assertSame('bar', $parser->getValue());
    }

    /** @test */
    public function parsesSimpleArrayAccess()
    {
        $parser = new VarAccessParser($this->envFile);
        $buffer = '${foo[1]}';
        $offset = 0;

        $this->envFile->shouldReceive('get')->with('foo')->once()->andReturn(['bar', 'baz']);
        $this->envFile->getParser(ArrayAccessParser::class);
        $this->parsers[ArrayAccessParser::class]
            ->shouldReceive('match')->andReturn(false)->byDefault();
        $this->parsers[ArrayAccessParser::class]
            ->shouldReceive('match')->with($buffer, 5)->once()->andReturnTrue();
        $this->parsers[ArrayAccessParser::class]
            ->shouldReceive('read')->with($buffer, 5)->once()->passthru();
        $this->parsers[ArrayAccessParser::class]
            ->shouldReceive('getKey')->once()->andReturn(1);

        $parser->read($buffer, $offset);

        self::assertSame('baz', $parser->getValue());
    }

    /** @test */
    public function throwsWhenSomethingElseComes()
    {
        $parser = new VarAccessParser($this->envFile);
        $buffer = '${foo#}';
        $offset = 0;

        self::expectException(ParserError::class);

        $parser->read($buffer, $offset);
    }
}
