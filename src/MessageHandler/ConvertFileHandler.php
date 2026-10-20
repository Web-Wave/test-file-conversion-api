<?php

namespace App\MessageHandler;

use App\Converter\FileConverterInterface;
use App\Entity\ConversionJob;
use App\Message\ConvertFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ConvertFileHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileConverterInterface $converter,
        #[Autowire(param: 'app.storage_dir')]
        private string $storageDir,
    ) {
    }

    public function __invoke(ConvertFile $message): void
    {
        $job = $this->entityManager->find(ConversionJob::class, $message->jobId);

        // Saved right away so the status endpoint shows the job as processing during the conversion.
        $job->start();
        $this->entityManager->flush();

        $outputPath = $this->storageDir.'/'.$job->getId().'.'.$job->getOutputFormat();

        // Not rethrown: the job is marked as failed and not retried.
        try {
            $this->converter->convert($job->getInputPath(), $outputPath, $job->getOutputFormat());
            $job->complete($outputPath);
        } catch (\Throwable $e) {
            $job->fail($e->getMessage());
        }

        $this->entityManager->flush();
    }
}
