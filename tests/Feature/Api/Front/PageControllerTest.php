<?php

namespace Tests\Feature\Api\Front;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_pages_ordered_by_sort_order(): void
    {
        Page::create([
            'title' => ['ar' => 'ثان', 'en' => 'Second'],
            'slug' => 'second',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Page::create([
            'title' => ['ar' => 'أول', 'en' => 'First'],
            'slug' => 'first',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Page::create([
            'title' => ['ar' => 'معطل', 'en' => 'Hidden'],
            'slug' => 'hidden',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/pages');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                ['id', 'title', 'slug', 'image', 'sort_order', 'show_in_header', 'show_in_footer'],
            ],
        ]);
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('first', $response->json('data.0.slug'));
    }

    public function test_it_returns_page_details_with_content_and_seo(): void
    {
        app()->setLocale('en');

        $page = Page::create([
            'title' => ['ar' => 'من نحن', 'en' => 'About Us'],
            'slug' => 'about-us',
            'content' => ['ar' => 'محتوى', 'en' => 'English content'],
            'is_active' => true,
        ]);
        $page->seo()->create([
            'meta_title' => ['ar' => 'عن', 'en' => 'About'],
            'meta_description' => ['ar' => 'وصف', 'en' => 'Description'],
        ]);

        $response = $this->getJson('/api/pages/about-us');

        $response->assertOk();
        $response->assertJsonPath('data.slug', 'about-us');
        $response->assertJsonPath('data.title', 'About Us');
        $response->assertJsonPath('data.content', 'English content');
        $response->assertJsonPath('data.seo.meta_title', 'About');
    }

    public function test_it_returns_404_for_unknown_or_inactive_page(): void
    {
        Page::create([
            'title' => ['ar' => 'معطل', 'en' => 'Hidden'],
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $this->getJson('/api/pages/missing')->assertNotFound();
        $this->getJson('/api/pages/hidden')->assertNotFound();
    }
}
