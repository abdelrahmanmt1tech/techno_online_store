<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogDetailResource;
use App\Http\Resources\BlogListResource;
use App\Models\Blog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Blog::where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('blog_categories.id', $request->category_id));
        }


        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $request->tag_id));
        }

        if ($search = $request->input('search')) {
            $search = mb_strtolower($search, 'UTF-8');
            $locales = ['ar', 'en'];

            $query->where(function ($q) use ($search, $locales) {
                foreach (['title', 'description'] as $column) {
                    foreach ($locales as $locale) {
                        $q->orWhereRaw(
                            'LOWER(JSON_EXTRACT('.$column.', \'$."'.$locale.'"\')) LIKE ?',
                            ['%'.$search.'%']
                        );
                    }
                }
            });
        }

        $sortField = match ($request->input('sort', 'published_at')) {
            'views' => 'views_count',
            'title' => 'title',
            default => 'published_at',
        };
        $sortDir = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDir);

        $perPage = min((int) ($request->per_page ?? 12), 50);
        $blogs = $query->with('categories', 'tags')->paginate($perPage);

        return $this->paginatedWithExtraResponse(
            $blogs,
            BlogListResource::collection($blogs),
            ['seo' => buildSeo('blogs')]
        );
    }

    public function show(string $slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('categories', 'tags', 'seo', 'faqs')
            ->first();

        if (! $blog) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $blog->increment('views_count');

        $tagIds = $blog->tags->pluck('id');

        $suggested = collect();
        if ($tagIds->isNotEmpty()) {
            $suggested = Blog::where('id', '!=', $blog->id)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->withCount(['tags' => fn ($q) => $q->whereIn('tags.id', $tagIds)])
                ->orderByDesc('tags_count')
                ->orderByDesc('published_at')
                ->with('categories', 'tags')
                ->take(4)
                ->get();
        }

        return $this->successResponse([
            'blog' => BlogDetailResource::make($blog),
            'suggested' => BlogListResource::collection($suggested),
        ]);
    }
}
