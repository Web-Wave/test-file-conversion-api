# File conversion API

A small Symfony API to run a long file conversion job (more than 2 minutes) in the background.

The client uploads a file (CSV, JSON, XLSX or ODS) and picks an output format (JSON or XML). The API answers right away with a job id, a worker runs the conversion, and the client polls the job status until it can download the result.

The conversion itself is a dummy: it waits 150 seconds, then writes a placeholder file.

## How it works

1. `POST /api/conversions` stores the file on disk, saves a `ConversionJob` (`pending`) and sends a `ConvertFile` message to the queue. The input format comes from the file extension. Response: `202` with the job id.
2. The worker handles the message: the job goes to `processing`, then `completed`, or `failed` with the error message.
3. `GET /api/conversions/{id}` returns the job status.
4. `GET /api/conversions/{id}/download` returns the converted file once the job is `completed`.

Errors are returned as JSON (`{"error": "..."}`): `400` for an invalid file or format, `404` for an unknown job, `409` for a download before completion.

Where to look:

- `src/Controller/ConversionController.php`: the three endpoints
- `src/MessageHandler/ConvertFileHandler.php`: the background job
- `src/Converter/`: the converter interface and its dummy implementation
- `src/Entity/ConversionJob.php`: the job and its statuses
- `tests/ConversionWorkflowTest.php`: the full workflow, from upload to download

## Getting started

Requirements: PHP 8.3+ with `pdo_sqlite`, Composer.

```bash
composer install
php bin/console doctrine:migrations:migrate -n
```

Run the API and the worker in two terminals:

```bash
php -S 127.0.0.1:8000 -t public
php bin/console messenger:consume async -vv
```

The conversion lasts 150 seconds. To change it, set `CONVERSION_DURATION` (in seconds) in `.env.local`.

The upload size is limited by PHP (`upload_max_filesize` and `post_max_size`): raise them to send big files.

Run the tests (the test database must be migrated once):

```bash
php bin/console doctrine:migrations:migrate -n --env=test
php bin/phpunit
```

## Usage

```bash
curl -F file=@data.csv -F output_format=xml http://127.0.0.1:8000/api/conversions
# {"id":"0199...","status":"pending"}

curl http://127.0.0.1:8000/api/conversions/0199...
# {"id":"0199...","status":"processing","error":null}

curl -OJ http://127.0.0.1:8000/api/conversions/0199.../download
# saves data.xml
```

## Technical choices

- **Asynchronous job with Symfony Messenger**: a job of several minutes cannot run inside an HTTP request. The API only accepts the job, and the client polls for the result.
- **Doctrine transport on SQLite**: nothing else to install to run the project. Moving to RabbitMQ or Redis only needs the matching transport package and a new `MESSENGER_TRANSPORT_DSN`.
- **Unknown file size**: the upload is moved to disk and the download is streamed, the file is never loaded in memory.
- **Failures**: the job is marked as `failed` with the error message and is not retried. The client sees it in the status.
- **Tests**: the workflow test uses the same Doctrine transport as in production and runs the real worker command.
