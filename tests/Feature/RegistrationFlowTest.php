<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function division(array $attrs = []): Division
    {
        $t = Tournament::create([
            'name' => 'Cup', 'event_date' => now()->addDays(30)->toDateString(),
            'generation' => 'beyblade_x', 'rule_version' => '1',
        ]);

        return Division::create(array_merge([
            'tournament_id' => $t->id, 'name' => 'D', 'age_group' => 'adult', 'fee' => 500,
        ], $attrs));
    }

    public function test_register_pay_and_issue_invoice(): void
    {
        $division = $this->division();
        $player = Player::create(['real_name' => 'Aoi']);
        $svc = app(RegistrationService::class);

        $reg = $svc->register($division, $player, '/ABC1234');
        $this->assertSame(RegistrationStatus::PendingPayment, $reg->status);

        $svc->startPayment($reg);
        $reg = $svc->confirmPayment($reg, ['transaction_id' => 'TXN-1']);

        $this->assertSame(RegistrationStatus::Paid, $reg->status);
        $this->assertSame('TXN-1', $reg->payment_ref);
        $this->assertNotNull($reg->invoice_number); // 已開立電子發票
        $this->assertNotNull($reg->paid_at);
    }

    public function test_kids_division_requires_guardian_consent(): void
    {
        $division = $this->division(['age_group' => 'kids']);
        $player = Player::create(['real_name' => 'Kid', 'guardian_consent' => false]);

        $this->expectException(ValidationException::class);
        app(RegistrationService::class)->register($division, $player);
    }

    public function test_full_division_goes_to_waitlist(): void
    {
        $division = $this->division(['capacity' => 1]);
        $svc = app(RegistrationService::class);

        $p1 = Player::create(['real_name' => 'P1']);
        $reg1 = $svc->register($division, $p1);
        $svc->confirmPayment($reg1, ['transaction_id' => 'T1']); // 佔用名額

        $p2 = Player::create(['real_name' => 'P2']);
        $reg2 = $svc->register($division, $p2);
        $this->assertSame(RegistrationStatus::Waitlisted, $reg2->status);
    }

    public function test_refund_issues_allowance(): void
    {
        $division = $this->division();
        $player = Player::create(['real_name' => 'Aoi']);
        $svc = app(RegistrationService::class);

        $reg = $svc->register($division, $player);
        $reg = $svc->confirmPayment($reg, ['transaction_id' => 'TXN-9']);
        $reg = $svc->refund($reg);

        $this->assertSame(RegistrationStatus::Refunded, $reg->status);
    }

    public function test_compete_blockers_require_registered_beyblades(): void
    {
        $division = $this->division(['deck_mode' => 'deck', 'deck_size' => 3]);
        $player = Player::create(['real_name' => 'Aoi']);

        // 尚未登錄陀螺 → 不可出戰
        $this->assertNotEmpty($player->competeBlockers($division));

        // 登錄 3 顆且照片處理完成 → 通過
        for ($i = 0; $i < 3; $i++) {
            Beyblade::create([
                'player_id' => $player->id, 'name' => "b$i", 'generation' => 'beyblade_x',
                'photo_status' => 'done',
            ]);
        }
        $this->assertEmpty($player->fresh()->competeBlockers($division));
    }
}
