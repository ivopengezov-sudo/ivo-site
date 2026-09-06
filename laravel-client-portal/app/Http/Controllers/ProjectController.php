<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private ProjectFileUploadService $uploads)
    {
    }

    /**
     * The client's own project list — never another client's.
     */
    public function index(Request $request): View
    {
        $projects = Project::forClient($request->user()->id)
            ->latest()
            ->paginate(15);

        return view('projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'files'       => ['required', 'array', 'min:1', 'max:20'],
            'files.*'     => ['file'],
        ]);

        $project = Project::create([
            'user_id'     => $request->user()->id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => Project::STATUS_SUBMITTED,
        ]);

        $this->uploads->storeMany(
            $project,
            $request->file('files'),
            $request->user()->id,
            \App\Models\ProjectFile::SOURCE_CLIENT
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project submitted — our team will review it shortly.');
    }

    public function show(Request $request, Project $project): View
    {
        $this->authorizeAccess($request, $project);

        $project->load(['clientFiles', 'deliverables']);

        return view('projects.show', ['project' => $project]);
    }

    /**
     * Client adds more documents to an existing project — most commonly
     * used after the project was marked "needs_info" by staff.
     */
    public function storeFiles(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAccess($request, $project);

        $data = $request->validate([
            'files'   => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file'],
        ]);

        $this->uploads->storeMany(
            $project,
            $request->file('files'),
            $request->user()->id,
            \App\Models\ProjectFile::SOURCE_CLIENT
        );

        if ($project->status === Project::STATUS_NEEDS_INFO) {
            $project->update(['status' => Project::STATUS_SUBMITTED]);
        }

        return back()->with('status', 'Files uploaded.');
    }

    private function authorizeAccess(Request $request, Project $project): void
    {
        abort_unless(
            $project->user_id === $request->user()->id || $request->user()->is_admin,
            403
        );
    }
}
