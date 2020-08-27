<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\ValueParser;
use EnvParser\ParserError;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\ValueParser */
class ValueParserTest extends TestCase
{
    /** @test */
    public function matchesEveryNonSpaceCharacter()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo';
        $offset = 0;

        self::assertTrue($parser->match($buffer, $offset));
    }

    /** @test */
    public function endOfFileResultsInNull()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertnull($parser->getValue());
    }

    /** @dataProvider provideWhiteSpaceCharacters
     * @param string $whiteSpace
     * @test */
    public function whiteSpaceEndsReading(string $whiteSpace)
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=bar' . $whiteSpace;
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame('bar', $parser->getValue());
    }

    public function provideWhiteSpaceCharacters()
    {
        return [
            'space' => [' '],
            'tab' => ["\t"],
            'newLine' => ["\n"],
        ];
    }

    /** @test */
    public function commentsEndReading()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=bar# because of';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame('bar', $parser->getValue());
    }

    /** @dataProvider provideInterpretedValues
     * @param string $value
     * @param mixed  $expected
     * @test */
    public function interpretsNonConcatenatedValues(string $value, $expected)
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=' . $value;
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame($expected, $parser->getValue());
    }

    public function provideInterpretedValues()
    {
        return [
            'integer' => ['42', 42],
            'float' => ['23.2', 23.2],
            'null' => ['null', null],
            'true' => ['true', true],
            'false' => ['false', false],
        ];
    }

    /** @test */
    public function usesTheValueFromVarAccess()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=$bar';
        $offset = 4;

        $this->envFile->shouldReceive('get')->with('bar')->once()->andReturn(42);

        $parser->read($buffer, $offset);

        self::assertSame(42, $parser->getValue());
    }

    /** @test */
    public function doesNotInterpretConcatenatedValues()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=tr$ue';
        $offset = 4;

        $this->envFile->shouldReceive('get')->with('ue')->once()->andReturn('ue');

        $parser->read($buffer, $offset);

        self::assertSame('true', $parser->getValue());
    }

    /** @test */
    public function doesNotReinterpretValueFromVarAccess()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=$true';
        $offset = 4;

        $this->envFile->shouldReceive('get')->with('true')->once()->andReturn('true');

        $parser->read($buffer, $offset);

        self::assertSame('true', $parser->getValue());
    }

    /** @dataProvider provideEncodedStrings
     * @param string $string
     * @param mixed  $expected
     * @test */
    public function decodesStrings(string $string, $expected)
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=$string';
        $offset = 4;

        $this->envFile->shouldReceive('get')->with('string')->once()->andReturn($string);

        $parser->read($buffer, $offset);

        self::assertEquals($expected, $parser->getValue());
    }

    public function provideEncodedStrings()
    {
        return [
            'json' => ['json:{"foo":"bar"}', (object)['foo' => 'bar']],
            'jsonArray' => ['jsonArray:{"foo":"bar"}', ['foo' => 'bar']],
            'base64' => ['base64:Zm9vQmFyCg==', "fooBar\n"],
        ];
    }

    /** @test */
    public function everyCharacterCanBeEscaped()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=\h\e\l\l\o\ w\0\"\\\'\\....';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertEquals('hello w0"\'....', $parser->getValue());
    }

    /** @test */
    public function stringConcatenationPreventsInterpretation()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=nu"ll"';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame('null', $parser->getValue());
    }

    /** @test */
    public function doubleQuotesAreStillInterpreted()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo="null"';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame(null, $parser->getValue());
    }

    /** @test */
    public function singleQuotesAreNeverInterpreted()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=\'null\'';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame('null', $parser->getValue());
    }

    /** @test */
    public function cStringsAreNeverInterpreted()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=$\'null\'';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame('null', $parser->getValue());
    }

    /** @test */
    public function throwsWhenFindingAnArrayInsideAnArray()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo[]=(bar)';
        $offset = 6;

        self::expectException(ParserError::class);

        $parser->read($buffer, $offset, true);
    }

    /** @test */
    public function stopsReadingAfterAnArray()
    {
        $parser = new ValueParser($this->envFile);
        $buffer = 'foo=(bar)asdf';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame(['bar'], $parser->getValue());
    }
}
