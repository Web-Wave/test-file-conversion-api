<?php

namespace App\Controller;

use App\Entity\ConversionJob;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/conversions')]
class ConversionController extends AbstractController
{
    private const INPUT_FORMATS = ['csv', 'json', 'xlsx', 'ods'];
    private const OUTPUT_FORMATS = ['json', 'xml'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire(param: 'app.storage_dir')]
        private string $storageDir,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'A valid file is required.'], 400);
        }

        $inputFormat = strtolower($file->getClientOriginalExtension());
        if (!in_array($inputFormat, self::INPUT_FORMATS, true)) {
            return $this->json(['error' => sprintf('Input format must be one of: %s.', implode(', ', self::INPUT_FORMATS))], 400);
        }

        $outputFormat = $request->request->getString('output_format');
        if (!in_array($outputFormat, self::OUTPUT_FORMATS, true)) {
            return $this->json(['error' => sprintf('Output format must be one of: %s.', implode(', ', self::OUTPUT_FORMATS))], 400);
        }

        // Moved to disk, never loaded in memory: the file can be large.
        $inputFile = $file->move($this->storageDir, Uuid::v4().'.'.$inputFormat);

        $job = new ConversionJob($inputFormat, $outputFormat, $file->getClientOriginalName(), $inputFile->getPathname());
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        return $this->json(['id' => $job->getId(), 'status' => $job->getStatus()], 202);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function status(string $id): JsonResponse
    {
        $job = $this->findJob($id);
        if (null === $job) {
            return $this->json(['error' => 'Conversion not found.'], 404);
        }

        return $this->json([
            'id' => $job->getId(),
            'status' => $job->getStatus(),
            'error' => $job->getError(),
        ]);
    }

    private function findJob(string $id): ?ConversionJob
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(ConversionJob::class, $id);
    }
}
