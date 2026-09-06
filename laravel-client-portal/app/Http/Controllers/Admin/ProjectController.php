<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\ProjectFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff-facing side of the portal. Routes are protected by the 'auth'
 * and 'admin' middleware (see routes/web.php).
 */
class ProjectController extends Controller
{
    public function __construct(private ProjectFileUploadService $uploads)
    {
    }

    public function index(Request $request): View
    {
        $projects = Project::with('client')
            ->status($request->query('status'))
            ->latest()
            ->paginate(20);

        return view('admin.projects.index', [
            'projects'     => $projects,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function show(Project $project): View
    {
        $project->load(['client', 'clientFiles', 'deliverables']);

        return view('admin.projects.show', ['project' => $project]);
    }

    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'status'     => ['required', 'in:submitted,in_progress,needs_info,completed'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $project->update($data);

        return back()->with('status', "Project #{$project->id} marked as {$data['status']}.");
    }

    /**
     * Staff uploads the finished documents for the client to download.
     */
    public function storeDeliverables(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file'],
        ]);

        $this->uploads->storeMany(
            $project,
            $request->file('files'),
            $request->user()->id,
            ProjectFile::SOURCE_ADMIN
        );

        return back()->with('status', 'Deliverables uploaded.');
    }
}
