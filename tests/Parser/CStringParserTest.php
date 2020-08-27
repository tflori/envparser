<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\CStringParser;
use EnvParser\ParserError;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\CStringParser */
class CStringParserTest extends TestCase
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

    /** @test
     * @param string $content
     * @param string $expected
     * @dataProvider provideEscapeSequences*/
    public function escapeSequencesWorkLikeInBash(string $content, string $expected)
    {
        $parser = new CStringParser($this->envFile);
        $buffer = 'var=$\'' . $content . '\'';
        $offset = 4;

        $parser->read($buffer, $offset);

        self::assertSame($expected, $parser->getString());
    }

    public function provideEscapeSequences()
    {
        return [
            // single character replacement
            ['\\\'', '\''], // \' => '
            ['\\"', '"'],   // \" => "
            ['\\\\', '\\'], // \\ => \
            ['\\n', "\n"], // \n => <new line>
            ['\\r', "\r"], // \r => <carriage return>
            ['\\t', "\t"], // \t => <tab>
            ['\\t', "\t"], // \t => <horizontal tab>
            ['\\v', "\v"], // \v => <vertical tab>
            ['\\e', "\e"], // \e => <escape>
            ['\\f', "\f"], // \f => <form feed>
            ['\\a', "\x7"], // \a => <bell>
            ['\\b', "\x8"], // \b => <backspace>

            // modifier for the next character(s)
            ['\xA', "\n"], // HEX 0A => DEC 10 => <new line>
            ['\x45F', 'EF'], // HEX 45 => "E" + "F"
            ['\xN', '\\xN'], // N has to be between 0 and F otherwise it is not replaced
            ['\u45', 'E'], // unicode charaters < FF are equal with \x
            ['\u0045', 'E'], // but they accept 4 digits
            ['\u7', "\x7"], // in bash this will return the ascii character bell
            ['\u30C4', 'ツ'],
            ['\uN', '\\uN'], // N has to be between 0 and F otherwise it is not replaced
            ['\U00000045', 'E'], // uppercase U allows up to 8 digits
            ['\U1F600', '😀'],
            ['\UN', '\\UN'], // N has to be between 0 and F otherwise it is not replaced
            ['\777', "\xff"], // out of bound equal to \377 => HEX FF
            ['\cg', "\x7"], // control characters (e. g. ctrl-g or ^G - a bell)

            // anything else
            ['\\j', '\\j'], // \<any> => \<any>
        ];
    }
}
