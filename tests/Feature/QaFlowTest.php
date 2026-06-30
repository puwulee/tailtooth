<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_gets_slug_and_code_on_create(): void
    {
        $event = Event::factory()->create(['title' => '年度大會']);

        $this->assertNotEmpty($event->slug);
        $this->assertNotEmpty($event->code);
        $this->assertSame(6, strlen($event->code));
    }

    public function test_join_by_code_redirects_to_event(): void
    {
        $event = Event::factory()->create();

        $this->post('/join', ['code' => strtolower($event->code)])
            ->assertRedirect(route('events.show', $event));
    }

    public function test_join_with_unknown_code_shows_error(): void
    {
        $this->post('/join', ['code' => 'NOPE99'])
            ->assertSessionHasErrors('code');
    }

    public function test_visitor_can_submit_question(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('events.questions.store', $event), [
            'body' => '請問報名截止日？',
            'author_name' => '小明',
            'token' => 'tok-123',
        ])->assertCreated()->assertJson(['ok' => true, 'pending' => false]);

        $this->assertDatabaseHas('questions', [
            'event_id' => $event->id,
            'body' => '請問報名截止日？',
            'status' => 'published',
        ]);
    }

    public function test_question_is_pending_when_event_requires_approval(): void
    {
        $event = Event::factory()->moderated()->create();

        $this->postJson(route('events.questions.store', $event), [
            'body' => '需要審核的問題',
            'token' => 'tok-1',
        ])->assertCreated()->assertJson(['pending' => true]);

        $this->assertDatabaseHas('questions', ['status' => 'pending']);

        // 待審核不出現在公開 JSON
        $this->getJson(route('events.questions', $event).'?token=tok-1')
            ->assertJsonCount(0, 'questions');
    }

    public function test_cannot_submit_when_event_closed(): void
    {
        $event = Event::factory()->closed()->create();

        $this->postJson(route('events.questions.store', $event), [
            'body' => '關閉後還能問嗎？',
            'token' => 'tok-1',
        ])->assertForbidden();
    }

    public function test_upvote_toggles_and_counts(): void
    {
        $event = Event::factory()->create();
        $question = Question::factory()->for($event)->create();

        // 第一次按讚 +1
        $this->postJson(route('events.questions.vote', [$event, $question]), ['token' => 'voter-1'])
            ->assertOk()->assertJson(['voted' => true, 'upvotes' => 1]);

        // 同一人不重複，再按一次取消 -1
        $this->postJson(route('events.questions.vote', [$event, $question]), ['token' => 'voter-1'])
            ->assertOk()->assertJson(['voted' => false, 'upvotes' => 0]);

        $this->assertSame(0, $question->fresh()->upvotes_count);
    }

    public function test_questions_sorted_by_votes_for_top(): void
    {
        $event = Event::factory()->create();
        $low = Question::factory()->for($event)->create(['upvotes_count' => 1]);
        $high = Question::factory()->for($event)->create(['upvotes_count' => 9]);

        $ids = $this->getJson(route('events.questions', $event).'?sort=top')
            ->json('questions.*.id');

        $this->assertSame([$high->id, $low->id], $ids);
    }

    public function test_admin_routes_require_login(): void
    {
        $this->get(route('admin.events.index'))->assertRedirect(route('login'));
    }

    public function test_host_can_answer_question(): void
    {
        $host = User::factory()->create();
        $event = Event::factory()->for($host)->create();
        $question = Question::factory()->for($event)->create();

        $this->actingAs($host)
            ->post(route('admin.questions.answer', [$event, $question]), ['answer' => '截止日為 6/30。'])
            ->assertRedirect();

        $question->refresh();
        $this->assertSame('截止日為 6/30。', $question->answer);
        $this->assertNotNull($question->answered_at);
    }

    public function test_host_cannot_manage_other_hosts_event(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $event = Event::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->get(route('admin.events.show', $event))
            ->assertForbidden();
    }
}
