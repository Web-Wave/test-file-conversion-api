<?php

namespace App\Entity;

use App\Enum\ConversionStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class ConversionJob
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(enumType: ConversionStatus::class)]
    private ConversionStatus $status = ConversionStatus::Pending;

    #[ORM\Column(length: 10)]
    private string $inputFormat;

    #[ORM\Column(length: 10)]
    private string $outputFormat;

    #[ORM\Column]
    private string $originalName;

    #[ORM\Column]
    private string $inputPath;

    #[ORM\Column(nullable: true)]
    private ?string $outputPath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $inputFormat, string $outputFormat, string $originalName, string $inputPath)
    {
        $this->id = Uuid::v7();
        $this->inputFormat = $inputFormat;
        $this->outputFormat = $outputFormat;
        $this->originalName = $originalName;
        $this->inputPath = $inputPath;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function start(): void
    {
        $this->status = ConversionStatus::Processing;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function complete(string $outputPath): void
    {
        $this->status = ConversionStatus::Completed;
        $this->outputPath = $outputPath;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function fail(string $error): void
    {
        $this->status = ConversionStatus::Failed;
        $this->error = $error;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStatus(): ConversionStatus
    {
        return $this->status;
    }

    public function getInputFormat(): string
    {
        return $this->inputFormat;
    }

    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getInputPath(): string
    {
        return $this->inputPath;
    }

    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
