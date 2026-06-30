<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Event;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function gatedEvent(): array
    {
        $host = User::factory()->create();
        $event = Event::factory()->for($host)->create(['require_company' => true]);
        $company = Company::factory()->for($host)->create(['tax_id' => '12345675', 'name' => '示範公司']);

        return [$host, $event, $company];
    }

    public function test_verify_returns_company_name_for_listed_tax_id(): void
    {
        [, $event] = $this->gatedEvent();

        $this->postJson(route('events.verify', $event), ['tax_id' => '12345675'])
            ->assertOk()
            ->assertJson(['ok' => true, 'company_name' => '示範公司']);
    }

    public function test_verify_rejects_unlisted_tax_id(): void
    {
        [, $event] = $this->gatedEvent();

        $this->postJson(route('events.verify', $event), ['tax_id' => '99999999'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_question_blocked_without_valid_tax_id_when_gated(): void
    {
        [, $event] = $this->gatedEvent();

        $this->postJson(route('events.questions.store', $event), [
            'body' => '沒有統編能問嗎？',
            'token' => 'tok-1',
        ])->assertStatus(422)->assertJsonValidationErrors('tax_id');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_question_accepted_with_valid_tax_id_and_records_company(): void
    {
        [, $event] = $this->gatedEvent();

        $this->postJson(route('events.questions.store', $event), [
            'body' => '以公司身分提問',
            'token' => 'tok-1',
            'tax_id' => '12345675',
        ])->assertCreated();

        $this->assertDatabaseHas('questions', [
            'tax_id' => '12345675',
            'company_name' => '示範公司',
        ]);
    }

    public function test_vote_blocked_without_valid_tax_id_when_gated(): void
    {
        [, $event] = $this->gatedEvent();
        $question = Question::factory()->for($event)->create();

        $this->postJson(route('events.questions.vote', [$event, $question]), ['token' => 'voter-1'])
            ->assertStatus(422);

        $this->assertSame(0, $question->fresh()->upvotes_count);
    }

    public function test_inactive_company_is_rejected(): void
    {
        $host = User::factory()->create();
        $event = Event::factory()->for($host)->create(['require_company' => true]);
        Company::factory()->for($host)->inactive()->create(['tax_id' => '12345675']);

        $this->postJson(route('events.verify', $event), ['tax_id' => '12345675'])
            ->assertStatus(422);
    }

    public function test_company_list_is_scoped_to_event_host(): void
    {
        $hostA = User::factory()->create();
        $hostB = User::factory()->create();
        $event = Event::factory()->for($hostA)->create(['require_company' => true]);
        // 同一統編只在 hostB 的名單中
        Company::factory()->for($hostB)->create(['tax_id' => '12345675']);

        $this->postJson(route('events.verify', $event), ['tax_id' => '12345675'])
            ->assertStatus(422);
    }

    public function test_host_can_create_company(): void
    {
        $host = User::factory()->create();

        $this->actingAs($host)
            ->post(route('admin.companies.store'), ['tax_id' => '12345675', 'name' => '新公司'])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'user_id' => $host->id, 'tax_id' => '12345675', 'name' => '新公司',
        ]);
    }

    public function test_duplicate_tax_id_rejected_per_host(): void
    {
        $host = User::factory()->create();
        Company::factory()->for($host)->create(['tax_id' => '12345675']);

        $this->actingAs($host)
            ->post(route('admin.companies.store'), ['tax_id' => '12345675', 'name' => '重複'])
            ->assertSessionHasErrors('tax_id');
    }

    public function test_batch_import_creates_and_updates(): void
    {
        $host = User::factory()->create();
        Company::factory()->for($host)->create(['tax_id' => '12345675', 'name' => '舊名']);

        $rows = "12345675,新名稱\n53212539\t原創科技\n壞資料,沒有統編\n00000000";

        $this->actingAs($host)
            ->post(route('admin.companies.import'), ['rows' => $rows])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', ['tax_id' => '12345675', 'name' => '新名稱']);
        $this->assertDatabaseHas('companies', ['tax_id' => '53212539', 'name' => '原創科技']);
        $this->assertDatabaseCount('companies', 2); // 兩筆無效略過
    }

    public function test_host_cannot_edit_other_hosts_company(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $company = Company::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->put(route('admin.companies.update', $company), ['tax_id' => '12345675', 'name' => 'x'])
            ->assertForbidden();
    }
}
