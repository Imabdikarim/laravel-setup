<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

/**
 * Read-only data for the public website. No login needed, and drafts are never included.
 */
class PublicProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = Project::query()
            ->published()
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Project $project) => [
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
                'featured' => $project->featured,
            ])
            ->values();

        return response()->json(['data' => $projects]);
    }
}