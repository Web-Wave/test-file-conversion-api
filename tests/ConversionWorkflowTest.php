<?php

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConversionWorkflowTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Other tests leave messages in the queue: the worker must only see the one sent here.
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM messenger_messages');
    }

    public function testConvertsAFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, "name,price\nbook,12\n");
        $file = new UploadedFile($path, 'data.csv', null, null, true);

        $this->client->request('POST', '/api/conversions', ['output_format' => 'json'], ['file' => $file]);
        $this->assertResponseStatusCodeSame(202);
        $id = $this->getJson()['id'];
        $this->assertSame(1, static::getContainer()->get('messenger.transport.async')->getMessageCount());

        $this->client->request('GET', '/api/conversions/'.$id);
        $this->assertSame('pending', $this->getJson()['status']);

        $this->runWorker();

        $this->client->request('GET', '/api/conversions/'.$id);
        $this->assertSame(['id' => $id, 'status' => 'completed', 'error' => null], $this->getJson());

        $this->client->request('GET', '/api/conversions/'.$id.'/download');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Disposition', 'attachment; filename=data.json');
        $this->assertSame('{"converted": true}', $this->client->getInternalResponse()->getContent());
    }

    private function runWorker(): void
    {
        $command = (new Application(self::$kernel))->find('messenger:consume');
        $tester = new CommandTester($command);
        $tester->execute(['receivers' => ['async'], '--limit' => 1]);
        $tester->assertCommandIsSuccessful();
    }

    private function getJson(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
