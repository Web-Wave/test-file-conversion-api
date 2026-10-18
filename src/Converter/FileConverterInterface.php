<?php

namespace App\Converter;

interface FileConverterInterface
{
    public function convert(string $inputPath, string $outputPath, string $outputFormat): void;
}
