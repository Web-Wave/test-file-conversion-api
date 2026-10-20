<?php

namespace App\Tests\MessageHandler;

use App\Converter\FileConverterInterface;
use App\Entity\ConversionJob;
use App\Enum\ConversionStatus;
use App\Message\ConvertFile;
use App\MessageHandler\ConvertFileHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ConvertFileHandlerTest extends TestCase
{
    private ConversionJob $job;

    protected function setUp(): void
    {
        $this->job = new ConversionJob('csv', 'xml', 'data.csv', '/storage/input.csv');
    }

    public function testCompletesTheJob(): void
    {
        $converter = $this->createMock(FileConverterInterface::class);
        $converter->expects($this->once())
            ->method('convert')
            ->with('/storage/input.csv', '/storage/'.$this->job->getId().'.xml', 'xml')
            ->willReturnCallback(fn () => $this->assertSame(ConversionStatus::Processing, $this->job->getStatus()));

        $this->handle($converter);

        $this->assertSame(ConversionStatus::Completed, $this->job->getStatus());
        $this->assertSame('/storage/'.$this->job->getId().'.xml', $this->job->getOutputPath());
    }

    public function testFailsTheJobWhenConversionFails(): void
    {
        $converter = $this->createStub(FileConverterInterface::class);
        $converter->method('convert')->willThrowException(new \RuntimeException('Conversion error.'));

        $this->handle($converter);

        $this->assertSame(ConversionStatus::Failed, $this->job->getStatus());
        $this->assertSame('Conversion error.', $this->job->getError());
        $this->assertNull($this->job->getOutputPath());
    }

    private function handle(FileConverterInterface $converter): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturn($this->job);
        $entityManager->expects($this->exactly(2))->method('flush');

        $handler = new ConvertFileHandler($entityManager, $converter, '/storage');
        $handler(new ConvertFile((string) $this->job->getId()));
    }
}
