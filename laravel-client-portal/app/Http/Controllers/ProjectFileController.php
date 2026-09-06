<?php

namespace App\Http\Controllers;

use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFileController extends Controller
{
    /**
     * Every download is authorization-checked here — files are never
     * reachable by a bare public URL. A client can only pull files that
     * belong to one of their own projects; staff can pull any file.
     */
    public function download(Request $request, ProjectFile $file): StreamedResponse
    {
        $project = $file->project;

        abort_unless(
            $project->user_id === $request->user()->id || $request->user()->is_admin,
            403
        );

        abort_unless(
            Storage::disk($file->disk)->exists($file->stored_path),
            404,
            'File is missing from storage.'
        );

        return Storage::disk($file->disk)->download($file->stored_path, $file->original_name);
    }
}
