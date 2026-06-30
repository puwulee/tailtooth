<?php

namespace Tests\Feature;

use App\Contracts\QuestionGenerator;
use App\Models\Event;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionGenerationTest extends TestCase
{
    use RefreshDatabase;

    /** 綁定一個假的生成器，回傳固定題目，避免呼叫真實 API。 */
    private function fakeGenerator(array $questions): void
    {
        $this->app->bind(QuestionGenerator::class, fn () => new class($questions) implements QuestionGenerator
        {
            public function __construct(private array $questions) {}

            public function generate(Event $event, int $count = 10): array
            {
                return array_slice($this->questions, 0, $count);
            }
        });
    }

    public function test_creating_event_requires_date_speaker_topic(): void
    {
        $host = User::factory()->create();

        $this->actingAs($host)
            ->post(route('admin.events.store'), ['title' => '只有標題'])
            ->assertSessionHasErrors(['event_date', 'speaker', 'topic']);
    }

    public function test_event_creates_with_session_details(): void
    {
        $host = User::factory()->create();

        $this->actingAs($host)->post(route('admin.events.store'), [
            'title' => '年度大會',
            'event_date' => '2026-07-01',
            'speaker' => '王理事長',
            'topic' => '會員權益',
        ])->assertRedirect();

        $this->assertDatabaseHas('events', [
            'title' => '年度大會',
            'speaker' => '王理事長',
            'topic' => '會員權益',
        ]);
    }

    public function test_host_can_generate_ai_questions(): void
    {
        $this->fakeGenerator(['Q1', 'Q2', 'Q3']);

        $host = User::factory()->create();
        $event = Event::factory()->for($host)->create(['topic' => '會員權益']);

        $this->actingAs($host)
            ->post(route('admin.events.generate', $event))
            ->assertRedirect();

        $this->assertSame(3, $event->questions()->where('source', 'ai')->count());
        $this->assertDatabaseHas('questions', ['body' => 'Q1', 'source' => 'ai', 'status' => 'published']);
    }

    public function test_generate_replaces_existing_ai_questions_but_keeps_audience(): void
    {
        $this->fakeGenerator(['New1', 'New2']);

        $host = User::factory()->create();
        $event = Event::factory()->for($host)->create();
        Question::factory()->for($event)->create(['source' => 'ai', 'body' => 'Old AI']);
        Question::factory()->for($event)->create(['source' => 'audience', 'body' => 'Audience Q']);

        $this->actingAs($host)->post(route('admin.events.generate', $event))->assertRedirect();

        $this->assertDatabaseMissing('questions', ['body' => 'Old AI']);
        $this->assertDatabaseHas('questions', ['body' => 'Audience Q', 'source' => 'audience']);
        $this->assertSame(2, $event->questions()->where('source', 'ai')->count());
    }

    public function test_generate_requires_topic(): void
    {
        $this->fakeGenerator(['Q1']);

        $host = User::factory()->create();
        $event = Event::factory()->for($host)->create(['topic' => null]);

        $this->actingAs($host)
            ->post(route('admin.events.generate', $event))
            ->assertSessionHasErrors('topic');

        $this->assertSame(0, $event->questions()->count());
    }

    public function test_host_cannot_generate_for_other_hosts_event(): void
    {
        $this->fakeGenerator(['Q1']);

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $event = Event::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->post(route('admin.events.generate', $event))
            ->assertForbidden();
    }
}
