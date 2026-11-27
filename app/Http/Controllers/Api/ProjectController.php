<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Admin CRUD for projects. Routes live in routes/api.php (admins only).
 */
class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = Project::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Project $project) => $this->present($project))
            ->values();

        return response()->json(['data' => $projects]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(), $this->messages());

        $project = Project::create($this->attributes($data) + [
            'slug' => Project::uniqueSlug($data['title']),
        ]);

        return response()->json(['data' => $this->present($project)], 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate($this->rules(), $this->messages());

        // The slug stays the same after it is created, so public links don't break.
        $project->update($this->attributes($data));

        return response()->json(['data' => $this->present($project)]);
    }

    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:240'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['string', 'max:30'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'repo_url' => ['nullable', 'url', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', Rule::in(Project::ICONS)],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    private function messages(): array
    {
        return [
            'color.regex' => 'Use a colour like #2f8be6.',
        ];
    }

    /** Clean the validated input before saving. */
    private function attributes(array $data): array
    {
        $tags = collect($data['tags'] ?? [])
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'title' => $data['title'],
            'summary' => $data['summary'],
            'description' => $data['description'] ?? null,
            'tags' => $tags,
            'live_url' => $data['live_url'] ?? null,
            'repo_url' => $data['repo_url'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'color' => isset($data['color']) ? strtolower($data['color']) : null,
            'icon' => $data['icon'] ?? null,
            'status' => $data['status'],
            'featured' => (bool) ($data['featured'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function present(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'slug' => $project->slug,
            'summary' => $project->summary,
            'description' => $project->description,
            'tags' => $project->tags ?? [],
            'live_url' => $project->live_url,
            'repo_url' => $project->repo_url,
            'image_url' => $project->image_url,
            'color' => $project->color,
            'icon' => $project->icon,
            'status' => $project->status,
            'featured' => $project->featured,
            'sort_order' => $project->sort_order,
            'created_at' => $project->created_at?->toIso8601String(),
            'updated_at' => $project->updated_at?->toIso8601String(),
        ];
    }
}