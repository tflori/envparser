<?php

namespace EnvParserTests\Parser;

use EnvParser\Parser\ValueParser;
use EnvParser\Parser\VarAssignmentParser;
use EnvParserTests\TestCase;
use Mockery as m;

class VarAssignmentParserTest extends TestCase
{
    /** @dataProvider provideValidVarNames
     * @test  */
    public function matchesValidVarNames($var)
    {
        $parser = new VarAssignmentParser($this->envFile);

        self::assertTrue($parser->match($var . '=', 0));
    }

    public function provideValidVarNames()
    {
        return [
            ['a'],
            ['VAR'],
            ['true'],
            ['SNAKE_CASE'],
        ];
    }

    /** @test */
    public function matchesArrayAssignment()
    {
        $parser = new VarAssignmentParser($this->envFile);

        self::assertTrue($parser->match('VAR[0]=', 0));
    }

    /** @test */
    public function parsesTheValueWithParseValue()
    {
        $parser = new VarAssignmentParser($this->envFile);
        $valueParser = m::mock(ValueParser::class);
        $buffer = 'a=';
        $offset = 0;

        $this->envFile->shouldReceive('getParser')->with(ValueParser::class)->once()->andReturn($valueParser);
        $valueParser->shouldReceive('read')->with('a=', 2)->once();
        $valueParser->shouldReceive('getValue')->with()->once()->andReturn('foobar');

        $parser->read($buffer, $offset);
    }
}
