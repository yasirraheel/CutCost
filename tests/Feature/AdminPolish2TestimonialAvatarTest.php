<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPolish2TestimonialAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_sits_in_icon_slot_and_missing_avatar_keeps_chat_icon(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Testimonial::create([
            'author_name' => 'Avatar Author',
            'role_text' => 'Subscriber',
            'quote' => 'Avatar quote.',
            'rating' => 5,
            'avatar_path' => '/img/dashboard/testimonials/avatar-1.png',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Testimonial::create([
            'author_name' => 'No Avatar Author',
            'role_text' => 'Subscriber',
            'quote' => 'No avatar quote.',
            'rating' => 4,
            'avatar_path' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get(route('admin.testimonials.index'));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString(
            '<div class="col-1 text-right"><img src="'.asset('/img/dashboard/testimonials/avatar-1.png').'"',
            $html
        );
        $this->assertSame(1, substr_count($html, 'avatar-1.png'));
        $this->assertStringContainsString('<div class="col-1 text-right">💬</div>', $html);
        $this->assertSame(1, substr_count($html, '💬'));
        $this->assertStringNotContainsString('middle;"><strong>', $html);
    }
}
