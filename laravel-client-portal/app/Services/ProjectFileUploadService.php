<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded tender/deliverable documents on a PRIVATE disk and
 * records them against a project. Files are never saved under a public
 * path or served by a guessable URL — downloads always go through
 * ProjectFileController, which checks the requester owns the project
 * (or is staff) before streaming the file back.
 */
class ProjectFileUploadService
{
    public const DISK = 'projects';

    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip',
    ];

    private const MAX_BYTES = 25 * 1024 * 1024; // 25 MB per file

    /**
     * @param  UploadedFile[]  $files
     * @return ProjectFile[]
     */
    public function storeMany(Project $project, array $files, int $uploadedBy, string $source): array
    {
        return array_map(
            fn (UploadedFile $file) => $this->storeOne($project, $file, $uploadedBy, $source),
            $files
        );
    }

    public function storeOne(Project $project, UploadedFile $file, int $uploadedBy, string $source): ProjectFile
    {
        $this->assertAllowed($file);

        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid() . '.' . $extension;
        $path = $file->storeAs("projects/{$project->id}", $storedName, self::DISK);

        return $project->files()->create([
            'uploaded_by'   => $uploadedBy,
            'source'        => $source,
            'original_name' => $file->getClientOriginalName(),
            'stored_path'   => $path,
            'disk'          => self::DISK,
            'mime_type'     => $file->getClientMimeType(),
            'size_bytes'    => $file->getSize(),
        ]);
    }

    private function assertAllowed(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        abort_unless(
            in_array($extension, self::ALLOWED_EXTENSIONS, true),
            422,
            "File type .{$extension} is not allowed."
        );

        abort_unless(
            $file->getSize() <= self::MAX_BYTES,
            422,
            'File exceeds the 25 MB per-file limit.'
        );
    }

    public function delete(ProjectFile $file): void
    {
        Storage::disk($file->disk)->delete($file->stored_path);
        $file->delete();
    }
}
