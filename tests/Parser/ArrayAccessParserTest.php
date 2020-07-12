<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\ArrayAccessParser;
use EnvParserTests\TestCase;

class ArrayAccessParserTest extends TestCase
{
    /** @test */
    public function matchesNumbersInBrackets()
    {
        $parser = new ArrayAccessParser($this->envFile);

        self::assertTrue($parser->match('[0]', 0));
    }

    /** @test */
    public function matchesAsterixInBrackets()
    {
        $parser = new ArrayAccessParser($this->envFile);

        self::assertTrue($parser->match('[*]', 0));
    }

    /** @test */
    public function matchesAtSignInBrackets()
    {
        $parser = new ArrayAccessParser($this->envFile);

        self::assertTrue($parser->match('[@]', 0));
    }

    /** @test */
    public function doesNotMatchStrings()
    {
        $parser = new ArrayAccessParser($this->envFile);

        self::assertFalse($parser->match('[foo]', 0));
    }

    /** @test */
    public function replacesValueWithTheCorrespondingKey()
    {
        $parser = new ArrayAccessParser($this->envFile);
        $buffer = '[2]';
        $offset = 0;

        $parser->read($buffer, $offset);
        $value = $parser->getValue(['a', 'b', 'c']);

        self::assertSame('c', $value);
    }

    /** @test */
    public function returnsNullWhenOutOfBound()
    {
        $parser = new ArrayAccessParser($this->envFile);
        $buffer = '[3]';
        $offset = 0;

        $parser->read($buffer, $offset);
        $value = $parser->getValue(['a', 'b', 'c']);

        self::assertNull($value);
    }

    /** @test */
    public function returnTheStringWith0()
    {
        $parser = new ArrayAccessParser($this->envFile);
        $buffer = '[0]';
        $offset = 0;

        $parser->read($buffer, $offset);
        $value = $parser->getValue('foo bar');

        self::assertSame('foo bar', $value);
    }

    /** @test */
    public function returnsTheImplodedArrayWithAsterix()
    {
        $parser = new ArrayAccessParser($this->envFile);
        $buffer = '[*]';
        $offset = 0;

        $parser->read($buffer, $offset);
        $value = $parser->getValue(['foo', 'bar']);

        self::assertSame('foo bar', $value);
    }

    /** @test */
    public function throwsForInvalidArguments()
    {
        $parser = new ArrayAccessParser($this->envFile);
        $buffer = '${a[0]}';
        $offset = 0; // wrong offset

        self::expectException(\InvalidArgumentException::class);

        $parser->read($buffer, $offset);
    }
}
