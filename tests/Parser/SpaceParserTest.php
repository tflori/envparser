<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\SpaceParser;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\SpaceParser */
class SpaceParserTest extends TestCase
{
    /** @test */
    public function spacesMatch()
    {
        $parser = new SpaceParser($this->envFile);

        self::assertTrue($parser->match(' ', 0));
    }

    /** @test */
    public function tabsMatch()
    {
        $parser = new SpaceParser($this->envFile);

        self::assertTrue($parser->match("\t", 0));
    }

    /** @test */
    public function newLinesMatch()
    {
        $parser = new SpaceParser($this->envFile);

        self::assertTrue($parser->match("\n", 0));
    }

    /** @test */
    public function readSeeksOverTheSpace()
    {
        $parser = new SpaceParser($this->envFile);
        $buffer = ' foo bar';
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame(1, $offset);
    }

    /** @test */
    public function readSeeksToNextNonMatchingCharacter()
    {
        $parser = new SpaceParser($this->envFile);
        $buffer = " \t\nfoo=bar";
        $offset = 0;

        $parser->read($buffer, $offset);

        self::assertSame(3, $offset);
    }

    /** @test */
    public function matchesSpacesAtOffset()
    {
        $parser = new SpaceParser($this->envFile);
        $buffer = "foo=bar\n bar=foo";
        $offset = 7;

        self::assertTrue($parser->match($buffer, $offset));
    }

    /** @test */
    public function seeksToNextNonSpaceFromOffset()
    {
        $parser = new SpaceParser($this->envFile);
        $buffer = "foo=bar\n bar=foo";
        $offset = 7;

        $parser->read($buffer, $offset);

        self::assertSame(9, $offset);
    }
}
