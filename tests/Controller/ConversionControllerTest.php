<?php

namespace App\Tests\Controller;

use App\Entity\ConversionJob;
use App\Enum\ConversionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConversionControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testSubmitsAConversion(): void
    {
        $this->submit($this->createFile('data.csv'), 'json');

        $this->assertResponseStatusCodeSame(202);
        $data = $this->getJson();
        $this->assertSame('pending', $data['status']);

        $job = static::getContainer()->get(EntityManagerInterface::class)->find(ConversionJob::class, $data['id']);
        $this->assertSame(ConversionStatus::Pending, $job->getStatus());
        $this->assertSame('csv', $job->getInputFormat());
        $this->assertSame('json', $job->getOutputFormat());
        $this->assertSame('data.csv', $job->getOriginalName());
        $this->assertFileExists($job->getInputPath());
    }

    public function testRejectsMissingFile(): void
    {
        $this->client->request('POST', '/api/conversions', ['output_format' => 'json']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('A valid file is required.', $this->getJson()['error']);
    }

    public function testRejectsUnsupportedInputFormat(): void
    {
        $this->submit($this->createFile('document.pdf'), 'json');

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('Input format must be one of: csv, json, xlsx, ods.', $this->getJson()['error']);
    }

    public function testRejectsUnsupportedOutputFormat(): void
    {
        $this->submit($this->createFile('data.csv'), 'csv');

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('Output format must be one of: json, xml.', $this->getJson()['error']);
    }

    private function submit(UploadedFile $file, string $outputFormat): void
    {
        $this->client->request('POST', '/api/conversions', ['output_format' => $outputFormat], ['file' => $file]);
    }

    private function createFile(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, "name,price\nbook,12\n");

        return new UploadedFile($path, $name, null, null, true);
    }

    private function getJson(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
