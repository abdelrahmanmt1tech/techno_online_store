<?php

namespace Tests\Feature\Api\Front;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_active_blogs(): void
    {
        Blog::create([
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'slug' => 'test-blog',
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'content' => ['ar' => 'محتوى', 'en' => 'Content'],
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blogs');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'items' => [
                ['id', 'title', 'slug', 'description', 'image', 'published_at', 'views_count'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_it_filters_by_category(): void
    {
        $cat = BlogCategory::create(['name' => ['ar' => 'قسم', 'en' => 'Cat'], 'slug' => 'cat']);

        $blog = Blog::create([
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'slug' => 'test-blog',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Cont'],
            'is_active' => true,
            'published_at' => now(),
        ]);
        $blog->categories()->attach($cat);

        Blog::create([
            'title' => ['ar' => 'آخر', 'en' => 'Other'],
            'slug' => 'other-blog',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Cont'],
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blogs?category_slug=cat');

        $response->assertOk();
        $this->assertCount(1, $response->json('items'));
        $this->assertEquals('test-blog', $response->json('items.0.slug'));
    }

    public function test_it_search_by_keyword(): void
    {
        app()->setLocale('en');

        Blog::create([
            'title' => ['ar' => 'عنوان عشوائي', 'en' => 'Unique Blog Post'],
            'slug' => 'unique-blog',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Cont'],
            'is_active' => true,
            'published_at' => now(),
        ]);

        Blog::create([
            'title' => ['ar' => 'شيء آخر', 'en' => 'Other Thing'],
            'slug' => 'other-thing',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Cont'],
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blogs?search=unique');

        $response->assertOk();
        $this->assertCount(1, $response->json('items'));
    }

    public function test_it_returns_blog_detail_with_suggested(): void
    {
        app()->setLocale('en');

        $tag1 = Tag::create(['name' => ['ar' => 'وسم1', 'en' => 'Tag1'], 'slug' => 'tag1']);
        $tag2 = Tag::create(['name' => ['ar' => 'وسم2', 'en' => 'Tag2'], 'slug' => 'tag2']);

        $blog = Blog::create([
            'title' => ['ar' => 'عنوان', 'en' => 'Main Blog'],
            'slug' => 'main-blog',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Content'],
            'is_active' => true,
            'published_at' => now(),
        ]);
        $blog->tags()->attach([$tag1->id, $tag2->id]);

        $suggested = Blog::create([
            'title' => ['ar' => 'مقترح', 'en' => 'Suggested Blog'],
            'slug' => 'suggested-blog',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Content'],
            'is_active' => true,
            'published_at' => now(),
        ]);
        $suggested->tags()->attach($tag1->id);

        $response = $this->getJson('/api/blogs/main-blog');

        $response->assertOk();
        $response->assertJsonPath('data.blog.slug', 'main-blog');
        $response->assertJsonPath('data.blog.content', 'Content');
        $this->assertCount(1, $response->json('data.suggested'));
    }

    public function test_it_returns_404_for_inactive_blog(): void
    {
        Blog::create([
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'slug' => 'inactive',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Cont'],
            'is_active' => false,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blogs/inactive');

        $response->assertNotFound();
    }

    public function test_it_excludes_unpublished_blogs(): void
    {
        Blog::create([
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'slug' => 'future',
            'description' => ['ar' => 'وصف', 'en' => 'Desc'],
            'content' => ['ar' => 'محتوى', 'en' => 'Cont'],
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/blogs');

        $response->assertOk();
        $this->assertCount(0, $response->json('items'));
    }
}
