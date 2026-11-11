<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The bare root URL never renders a page: the captured root
     * index.html is only the offline static entry (a meta-refresh page
     * saying "Opening the offline Sharesub homepage"), so / redirects
     * straight to the homepage instead of flashing that page.
     */
    public function test_the_root_url_redirects_to_the_homepage(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/en/');
        $response->assertStatus(301);
        $this->assertStringNotContainsString('offline', $response->getContent());
    }
}
