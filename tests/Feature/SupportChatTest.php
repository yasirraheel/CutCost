<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\SupportChatController;
use App\Http\Middleware\RedirectAdminFromUserPanel;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Support chat: user widget endpoints plus the admin inbox.
 *
 * The routes are registered here, mirroring the exact route lines
 * the coordinator applies to routes/web.php, because this worker
 * does not edit that file. The controllers, the table and the auth
 * rules are what these tests verify.
 */
class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The 'web' group is what routes/web.php applies to the whole
        // file: without it there is no SubstituteBindings, so the
        // {user} parameter would not resolve to a model.
        Route::middleware(['web', 'auth', RedirectAdminFromUserPanel::class])->group(function (): void {
            Route::get('/en/support-chat/messages', [SupportChatController::class, 'messages']);
            Route::get('/en/support-chat/unread', [SupportChatController::class, 'unread']);
            Route::post('/en/support-chat/message', [SupportChatController::class, 'send']);
        });

        Route::middleware(['web', 'auth', 'role:admin'])->prefix('en/admin')->group(function (): void {
            Route::get('/support', [AdminSupportController::class, 'index'])->name('admin.support.index');
            Route::get('/support/{user}', [AdminSupportController::class, 'show'])->name('admin.support.show');
            Route::post('/support/{user}/message', [AdminSupportController::class, 'reply'])->name('admin.support.reply');
        });

        // The app ends routes/web.php with a catch-all GET /{path}
        // fallback, so routes registered here land after it and the
        // GET endpoints would 404. Move the support routes ahead of
        // it in the collection. The coordinator must do the same in
        // routes/web.php: add the support routes BEFORE the final
        // fallback line, not after it.
        $router = $this->app['router'];
        $mine = [];
        $others = [];
        foreach ($router->getRoutes()->getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'en/support-chat') || str_starts_with($route->uri(), 'en/admin/support')) {
                $mine[] = $route;
            } else {
                $others[] = $route;
            }
        }
        $reordered = new RouteCollection();
        foreach ([...$mine, ...$others] as $route) {
            $reordered->add($route);
        }
        // Names are only looked up when the collection is refreshed
        // (boot does this); routes added in a test need the same, or
        // route('admin.support.show') cannot find them. The URL
        // generator may already hold the old collection, so point it
        // at the reordered one too.
        $reordered->refreshNameLookups();
        $reordered->refreshActionLookups();
        $router->setRoutes($reordered);
        $this->app['url']->setRoutes($reordered);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'first_name' => 'Admin', 'last_name' => 'User']);
    }

    protected function user(): User
    {
        return User::factory()->create(['role' => 'user', 'first_name' => 'Demo', 'last_name' => 'User']);
    }

    public function test_user_can_send_a_support_message(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $response = $this->postJson('/en/support-chat/message', ['message' => 'Hello, I need help with my subscription.']);

        $response->assertCreated()
            ->assertJsonPath('crypted', 'Hello, I need help with my subscription.')
            ->assertJsonPath('is_mine', true);

        $this->assertDatabaseHas('chat_messages', [
            'support_user_id' => $user->id,
            'sender_id' => $user->id,
            'body' => 'Hello, I need help with my subscription.',
            'offer_id' => null,
            'member_id' => null,
        ]);
    }

    public function test_user_sees_their_thread_and_admin_reply(): void
    {
        $user = $this->user();
        $admin = $this->admin();
        $this->actingAs($user);

        $this->postJson('/en/support-chat/message', ['message' => 'My payment failed.']);
        ChatMessage::forceCreate([
            'offer_id' => null,
            'member_id' => null,
            'support_user_id' => $user->id,
            'sender_id' => $admin->id,
            'body' => 'We have refunded you, sorry about that.',
            'is_new' => true,
            'is_read' => false,
            'is_notified' => false,
            'is_moderated' => false,
            'waiting_moderation' => 0,
            'admin_override' => false,
        ]);

        $response = $this->getJson('/en/support-chat/messages');

        $response->assertOk()->assertJsonPath('done', true);
        $messages = $response->json('messages');
        $this->assertCount(2, $messages);
        $this->assertSame('My payment failed.', $messages[0]['crypted']);
        $this->assertTrue($messages[0]['is_mine']);
        $this->assertSame('We have refunded you, sorry about that.', $messages[1]['crypted']);
        $this->assertFalse($messages[1]['is_mine']);

        // Fetching the thread marks the admin reply read.
        $this->assertSame(0, (int) $this->get('/en/support-chat/unread')->getContent());
    }

    public function test_admin_can_see_the_message_in_the_inbox_and_reply(): void
    {
        $user = $this->user();
        $admin = $this->admin();
        $this->actingAs($user);
        $this->postJson('/en/support-chat/message', ['message' => 'Please help, my seat is gone.']);

        $this->actingAs($admin);

        $this->get('/en/admin/support')
            ->assertOk()
            ->assertSee('Demo User')
            ->assertSee('Please help, my seat is gone.');

        $this->post('/en/admin/support/'.$user->id.'/message', ['message' => 'Your seat is back now.'])
            ->assertRedirect(route('admin.support.show', ['user' => $user->id]));

        $this->get('/en/admin/support/'.$user->id)
            ->assertOk()
            ->assertSee('Please help, my seat is gone.')
            ->assertSee('Your seat is back now.');

        $this->assertDatabaseHas('chat_messages', [
            'support_user_id' => $user->id,
            'sender_id' => $admin->id,
            'body' => 'Your seat is back now.',
        ]);
    }

    public function test_user_cannot_see_another_users_support_thread(): void
    {
        $user = $this->user();
        $other = $this->user();
        $this->actingAs($other);
        $this->postJson('/en/support-chat/message', ['message' => 'This is private.']);

        $this->actingAs($user);

        $response = $this->getJson('/en/support-chat/messages');
        $response->assertOk();
        $this->assertSame([], $response->json('messages'));
        $this->assertSame('', $this->get('/en/support-chat/unread')->getContent());
    }

    public function test_non_admin_cannot_open_the_support_inbox(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->get('/en/admin/support')->assertForbidden();
        $this->post('/en/admin/support/'.$user->id.'/message', ['message' => 'Hi'])
            ->assertForbidden();
    }

    public function test_guests_cannot_use_the_support_endpoints(): void
    {
        $this->getJson('/en/support-chat/messages')->assertUnauthorized();
        $this->postJson('/en/support-chat/message', ['message' => 'Hi'])->assertUnauthorized();
    }
}
