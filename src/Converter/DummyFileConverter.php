<?php

namespace App\Converter;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fake converter: waits to simulate a long job, then writes a placeholder file.
 */
class DummyFileConverter implements FileConverterInterface
{
    public function __construct(
        #[Autowire(env: 'int:CONVERSION_DURATION')]
        private int $duration,
    ) {
    }

    public function convert(string $inputPath, string $outputPath, string $outputFormat): void
    {
        if (!is_file($inputPath)) {
            throw new \RuntimeException('Input file not found.');
        }

        sleep($this->duration);

        $content = match ($outputFormat) {
            'json' => '{"converted": true}',
            'xml' => '<?xml version="1.0" encoding="UTF-8"?><converted>true</converted>',
        };

        file_put_contents($outputPath, $content);
    }
}
