<?php

namespace EnvParserTests;

use EnvParser\EnvFile;
use EnvParser\ParseError;

class EnvFileTest extends TestCase
{
    /** @var resource */
    protected $tmpFile;

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->tmpFile) {
            fclose($this->tmpFile);
            $this->tmpFile = null;
        }
    }


    /** @test */
    public function returnsTheExitingEnvValuesFromContext()
    {
        $envFile = new EnvFile(['foo' => 'bar']);

        $foo = $envFile['foo'];

        self::assertSame('bar', $foo);
    }

    /** @test
     * @param string $value
     * @param mixed $expected
     * @dataProvider provideScalarValues */
    public function interpretsScalarValuesFromContext($value, $expected)
    {
        $envFile = new EnvFile(['foo' => $value]);

        $foo = $envFile['foo'];

        self::assertSame($expected, $foo);
    }

    public function provideScalarValues()
    {
        return [
            ['42', 42],
            ['23.7', 23.7],
            ['0.5E4', 5000],
            ['-17', -17],
            ['3E-1', 0.3],
            ['null', null],
            ['', null],
            ['NULL', null],
            ['true', true],
            ['false', false],
            ['TRUE', true],
            ['FALSE', false],
        ];
    }

    /** @test */
    public function returnsTheDefaultValueIfUnknown()
    {
        $envFile = new EnvFile([]); // empty context

        $foo = $envFile->get('foo', 23);

        self::assertSame(23, $foo);
    }

    /** @test */
    public function ignoresCommentedVarAssignments()
    {
        $path = $this->createEnvFile("#foo=bar\n");
        $envFile = new EnvFile([]);

        $envFile->read($path);

        self::assertNull($envFile->get('foo'));
    }

    /** @test */
    public function readsVarAssignments()
    {
        $path = $this->createEnvFile("foo=bar\n");
        $envFile = new EnvFile([]);

        $envFile->read($path);

        self::assertSame('bar', $envFile->get('foo'));
    }

    /** @test */
    public function readsArrayAssignments()
    {
        $path = $this->createEnvFile("foo[0]=bar\nfoo[1]=baz\n");
        $envFile = new EnvFile([]);

        $envFile->read($path);

        self::assertSame(['bar', 'baz'], $envFile->get('foo'));
    }

    /** @test */
    public function throwsForUnexpectedContent()
    {
        $path = $this->createEnvFile("foo=bar command\n");
        $envFile = new EnvFile([]);

        self::expectException(ParseError::class);

        $envFile->read($path);
    }

    /** @test */
    public function throwsWhenFileIsNotReadable()
    {
        $envFile = new EnvFile();

        self::expectException(\InvalidArgumentException::class);

        $envFile->read('/foo/bar');
    }

    protected function createEnvFile(string $content): string
    {
        $this->tmpFile = tmpfile();
        fwrite($this->tmpFile, $content);
        fseek($this->tmpFile, 0);
        return stream_get_meta_data($this->tmpFile)['uri'];
    }
}
