<?php

namespace App\Tests\Converter;

use App\Converter\DummyFileConverter;
use PHPUnit\Framework\TestCase;

class DummyFileConverterTest extends TestCase
{
    private string $inputPath;
    private string $outputPath;

    protected function setUp(): void
    {
        $this->inputPath = tempnam(sys_get_temp_dir(), 'input');
        $this->outputPath = tempnam(sys_get_temp_dir(), 'output');
    }

    protected function tearDown(): void
    {
        unlink($this->inputPath);
        unlink($this->outputPath);
    }

    public function testConvertsToJson(): void
    {
        (new DummyFileConverter(0))->convert($this->inputPath, $this->outputPath, 'json');

        $this->assertJson(file_get_contents($this->outputPath));
    }

    public function testConvertsToXml(): void
    {
        (new DummyFileConverter(0))->convert($this->inputPath, $this->outputPath, 'xml');

        $this->assertNotFalse(simplexml_load_file($this->outputPath));
    }

    public function testFailsWhenInputFileIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);

        (new DummyFileConverter(0))->convert('/missing/file.csv', $this->outputPath, 'json');
    }
}
