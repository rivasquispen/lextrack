<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractDocumentStorage
{
    public function storeGeneratedDocument(WordExporter $wordExporter, string $contractCode, int $version, string $html): string
    {
        $directory = $this->versionDirectory($contractCode, $version);
        Storage::makeDirectory($directory);

        $filename = $this->documentFilename($contractCode, $version);
        $path = $directory.'/'.$filename;

        $wordExporter->saveFromHtml($html, $path);

        return $path;
    }

    public function storeUploadedDocument(UploadedFile $file, string $contractCode, int $version): string
    {
        $directory = $this->versionDirectory($contractCode, $version);
        Storage::makeDirectory($directory);

        $filename = $this->documentFilename($contractCode, $version, $this->uploadedDocumentExtension($file));

        return $file->storeAs($directory, $filename);
    }

    public function storeAttachment(UploadedFile $file, string $contractCode, int $version, string $type, ?string $label = null): array
    {
        $directory = $this->versionDirectory($contractCode, $version).'/attachments';
        Storage::makeDirectory($directory);

        $cleanLabel = trim($label ?? $file->getClientOriginalName());
        $baseName = Str::slug(pathinfo($cleanLabel, PATHINFO_FILENAME)) ?: 'adjunto';
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'dat';
        $storedName = $baseName.'-'.Str::random(6).'.'.$extension;

        $path = $file->storeAs($directory, $storedName);

        return [
            'name' => $cleanLabel,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'type' => $type,
        ];
    }

    public function storeSignedDocument(UploadedFile $file, string $contractCode, int $version): string
    {
        $directory = $this->versionDirectory($contractCode, $version);
        Storage::makeDirectory($directory);

        $filename = $this->signedDocumentFilename($contractCode, $version);

        return $file->storeAs($directory, $filename);
    }

    public function copyDocument(string $sourcePath, string $contractCode, int $version): ?string
    {
        if (! Storage::exists($sourcePath)) {
            return null;
        }

        $directory = $this->versionDirectory($contractCode, $version);
        Storage::makeDirectory($directory);

        $filename = $this->documentFilename($contractCode, $version, pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'docx');
        $destination = $directory.'/'.$filename;

        return Storage::copy($sourcePath, $destination) ? $destination : null;
    }

    public function copyAttachment(
        string $sourcePath,
        string $contractCode,
        int $version,
        string $label,
        string $type,
        ?string $originalName = null
    ): ?array {
        if (! Storage::exists($sourcePath)) {
            return null;
        }

        $directory = $this->versionDirectory($contractCode, $version).'/attachments';
        Storage::makeDirectory($directory);

        $baseLabel = trim($originalName ?? $label);
        $baseName = Str::slug(pathinfo($baseLabel, PATHINFO_FILENAME)) ?: 'adjunto';
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'dat';
        $storedName = $baseName.'-'.Str::random(6).'.'.$extension;
        $destination = $directory.'/'.$storedName;

        if (! Storage::copy($sourcePath, $destination)) {
            return null;
        }

        return [
            'name' => $label,
            'path' => $destination,
            'original_name' => $originalName ?? basename($sourcePath),
            'type' => $type,
        ];
    }

    public function versionDirectory(string $contractCode, int $version): string
    {
        return sprintf('contracts/%s/%d', $contractCode, $version);
    }

    public function archiveVersionDocument(string $contractCode, int $version, string $sourcePath): ?string
    {
        if (! Storage::exists($sourcePath)) {
            return null;
        }

        $directory = $this->versionDirectory($contractCode, $version).'/histories';
        Storage::makeDirectory($directory);

        $timestamp = now()->format('YmdHis');
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'docx';
        $filename = sprintf('%s-%d-%s.%s', $contractCode, $version, $timestamp, $extension);
        $historyPath = $directory.'/'.$filename;

        if (Storage::copy($sourcePath, $historyPath)) {
            return $historyPath;
        }

        return null;
    }

    private function documentFilename(string $contractCode, int $version, string $extension = 'docx'): string
    {
        return sprintf('%s-%d.%s', $contractCode, $version, $extension);
    }

    private function signedDocumentFilename(string $contractCode, int $version): string
    {
        return sprintf('%s-%d.pdf', $contractCode, $version);
    }

    private function uploadedDocumentExtension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'docx');

        return in_array($extension, ['doc', 'docx'], true) ? $extension : 'docx';
    }
}
