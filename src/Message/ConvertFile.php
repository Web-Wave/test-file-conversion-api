<?php

namespace App\Message;

class ConvertFile
{
    public function __construct(
        public readonly string $jobId,
    ) {
    }
}
