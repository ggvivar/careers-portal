<?php
namespace App\Repositories;

use App\Services\CareersSchema;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class DocumentRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function listForApplicant(int $applicantId): array
    {
        $table = $this->schema->table('documents', false);
        $applicantField = $this->schema->field('documents', 'applicant_id', false);
        if (! $table || ! $applicantField) return [];

        $order = $this->schema->field('documents', 'created_at', false)
            ?: $this->schema->field('documents', 'id');

        $rows = $this->schema->db()->table($table)
            ->where($applicantField, $applicantId)
            ->orderBy($order, 'DESC')
            ->get()
            ->getResultArray();

        return array_map([$this, 'normalize'], $rows);
    }

    public function store(UploadedFile $file, int $applicantId, string $type, ?int $applicationId = null): int
    {
        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('The uploaded file is invalid.');
        }

        $base = rtrim($this->schema->config()->storagePath, '\\/');
        $relativeDirectory = 'applicants/' . $applicantId
            . ($applicationId ? '/applications/' . $applicationId : '/profile');
        $directory = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the protected upload directory.');
        }

        $stored = bin2hex(random_bytes(18)) . '.' . strtolower($file->getExtension());
        $original = $file->getClientName();
        $mime = $file->getClientMimeType();
        $size = $file->getSize();
        $file->move($directory, $stored);

        $payload = [];
        $this->put($payload, 'applicant_id', $applicantId, true);
        $this->put($payload, 'application_id', $applicationId);
        $this->put($payload, 'type', $type);
        $this->put($payload, 'original_name', $original);
        $this->put($payload, 'stored_name', $stored);
        $this->put($payload, 'path', $relativeDirectory . '/' . $stored, true);
        $this->put($payload, 'mime_type', $mime);
        $this->put($payload, 'size', $size);
        $payload += $this->schema->nowPayload('documents');

        if (! $this->schema->db()->table($this->schema->table('documents'))->insert($payload)) {
            @unlink($directory . DIRECTORY_SEPARATOR . $stored);
            throw new RuntimeException('Unable to save the uploaded document record.');
        }

        return (int) $this->schema->db()->insertID();
    }

    public function findOwned(int $id, int $applicantId): ?array
    {
        $row = $this->schema->db()->table($this->schema->table('documents'))
            ->where($this->schema->field('documents', 'id'), $id)
            ->where($this->schema->field('documents', 'applicant_id'), $applicantId)
            ->get()
            ->getRowArray();

        return $row ? $this->normalize($row) : null;
    }

    public function deleteOwned(int $id, int $applicantId): void
    {
        $document = $this->findOwned($id, $applicantId);
        if (! $document) return;

        $this->schema->db()->table($this->schema->table('documents'))
            ->where($this->schema->field('documents', 'id'), $id)
            ->where($this->schema->field('documents', 'applicant_id'), $applicantId)
            ->delete();

        $path = $this->absolutePath($document);
        if (is_file($path)) @unlink($path);
    }

    public function absolutePath(array $document): string
    {
        return rtrim($this->schema->config()->storagePath, '\\/')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim((string) $document['path'], '/'));
    }

    private function normalize(array $row): array
    {
        $result = ['raw' => $row];
        foreach (array_keys($this->schema->config()->fields['documents']) as $key) {
            $field = $this->schema->field('documents', $key, false);
            $result[$key] = $field ? ($row[$field] ?? null) : null;
        }
        return $result;
    }

    private function put(array &$payload, string $key, mixed $value, bool $required = false): void
    {
        $field = $this->schema->field('documents', $key, $required);
        if ($field && $value !== null) $payload[$field] = $value;
    }
}
