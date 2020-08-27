<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\DefaultValueParser;
use EnvParserTests\TestCase;

/** @covers \EnvParser\Parser\DefaultValueParser */
class DefaultValueParserTest extends TestCase
{
    /** @test */
    public function matchesMinusSign()
    {
        $parser = new DefaultValueParser($this->envFile);
        $buffer = 'var=${val-false}';
        $offset = 9;

        self::assertTrue($parser->match($buffer, $offset));
    }

    /** @test */
    public function throwsWithoutMinusSignAtOffset()
    {
        $parser = new DefaultValueParser($this->envFile);
        $buffer = 'var=${val[1]}';
        $offset = 9;

        self::expectException(\InvalidArgumentException::class);

        $parser->read($buffer, $offset);
    }

    /** @test */
    public function storesTheDefaultValue()
    {
        $parser = new DefaultValueParser($this->envFile);
        $buffer = 'var=${val-false}';
        $offset = 9;

        $parser->read($buffer, $offset);

        self::assertSame('false', $parser->getDefault());
    }

    /** @test */
    public function endsAfterTheDefaultValue()
    {
        $parser = new DefaultValueParser($this->envFile);
        $buffer = 'var=${val-false}';
        $offset = 9;

        $parser->read($buffer, $offset);

        self::assertSame(15, $offset);
    }
}
