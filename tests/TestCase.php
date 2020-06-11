<?php

namespace EnvParserTests;

use EnvParser\EnvFile;
use EnvParser\Parser\AbstractParser;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

abstract class TestCase extends MockeryTestCase
{
    /** @var EnvFile|m\MockInterface */
    protected $envFile;

    /** @var AbstractParser[]|m\MockInterface[] */
    protected $parsers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->parsers = [];
        $this->envFile = m::mock(EnvFile::class)->makePartial();
        $this->envFile->shouldReceive('getParser')->with(m::type('string'))
            ->andReturnUsing(function ($class) {
                if (!isset($this->parsers[$class])) {
                    $this->parsers[$class] = m::mock($class)->makePartial();
                    $this->parsers[$class]->__construct($this->envFile);
                }
                return $this->parsers[$class];
            })->byDefault();
    }
}
