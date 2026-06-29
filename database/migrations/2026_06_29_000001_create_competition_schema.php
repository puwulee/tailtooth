<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 使用者角色（一個帳號可有多角色）
        Schema::create('user_roles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role'); // App\Enums\UserRole
            $t->timestamps();
            $t->unique(['user_id', 'role']);
        });

        // 選手檔案
        Schema::create('players', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('real_name');
            $t->string('nickname')->nullable();
            $t->date('birthdate')->nullable();
            $t->string('phone')->nullable();
            $t->string('guardian_name')->nullable();   // 監護人（兒童組）
            $t->string('guardian_phone')->nullable();
            $t->boolean('guardian_consent')->default(false); // 監護人同意書
            $t->boolean('portrait_consent')->default(false); // 肖像權授權
            $t->timestamps();
        });

        // 陀螺登錄
        Schema::create('beyblades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->string('name');                 // 陀螺暱稱
            $t->string('generation');           // App\Enums\Generation
            $t->json('parts')->nullable();      // {blade, ratchet, bit} (Beyblade X)
            $t->string('photo_path')->nullable();      // 原始照片
            $t->string('cutout_path')->nullable();     // 去背圖
            $t->string('watermarked_path')->nullable();// 浮水印圖
            $t->timestamps();
        });

        // 場地
        Schema::create('venues', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('address')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->string('google_place_id')->nullable();
            $t->json('photos')->nullable();
            $t->unsignedInteger('capacity')->nullable();
            $t->boolean('is_sponsored')->default(false);
            $t->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('approval_status')->default('pending'); // pending/approved/rejected
            $t->timestamps();
        });

        // 贊助方
        Schema::create('sponsors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('tier')->nullable();      // 贊助等級
            $t->string('logo_path')->nullable();
            $t->timestamps();
        });

        // 賽事
        Schema::create('tournaments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->date('event_date');
            $t->string('generation');            // 預設世代
            $t->string('rule_version');          // 規則版本（綁定，避免成績爭議）
            $t->json('prizes')->nullable();      // 獎品/贈品
            $t->string('status')->default('draft'); // draft/open/ongoing/finished
            $t->timestamps();
        });

        // 組別
        Schema::create('divisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('age_group');             // App\Enums\AgeGroup
            $t->string('deck_mode')->default('deck');  // App\Enums\DeckMode
            $t->unsignedTinyInteger('deck_size')->default(3);
            $t->unsignedTinyInteger('points_to_win')->default(4); // 先達 N 點獲勝
            $t->unsignedSmallInteger('round_time_seconds')->default(180);
            $t->unsignedSmallInteger('capacity')->nullable();
            $t->unsignedSmallInteger('min_players')->default(4);
            $t->decimal('fee', 8, 2)->default(0);
            $t->timestamps();
        });

        // 計分規則（finish → 點數），可依賽事覆寫預設
        Schema::create('score_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('division_id')->constrained()->cascadeOnDelete();
            $t->string('finish_type');
            $t->unsignedTinyInteger('points');
            $t->timestamps();
            $t->unique(['division_id', 'finish_type']);
        });

        // 報名
        Schema::create('registrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('division_id')->constrained()->cascadeOnDelete();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->string('status')->default('pending_payment'); // RegistrationStatus
            $t->string('payment_ref')->nullable();   // LINE Pay transaction id
            $t->string('invoice_number')->nullable(); // 光貿電子發票號碼
            $t->string('invoice_carrier')->nullable();// 載具/統編/捐贈碼
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('checked_in_at')->nullable();
            $t->timestamps();
            $t->unique(['division_id', 'player_id']);
        });

        // 賽段
        Schema::create('stages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('division_id')->constrained()->cascadeOnDelete();
            $t->string('type');                  // App\Enums\StageType
            $t->string('format');                // App\Enums\BracketFormat
            $t->unsignedTinyInteger('sequence')->default(1);
            $t->timestamps();
        });

        // 小組
        Schema::create('groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        // 對戰（避免保留字，使用 battles）
        Schema::create('battles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $t->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('player_a_id')->nullable()->constrained('players')->nullOnDelete();
            $t->foreignId('player_b_id')->nullable()->constrained('players')->nullOnDelete();
            $t->foreignId('referee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('winner_id')->nullable()->constrained('players')->nullOnDelete();
            $t->unsignedSmallInteger('score_a')->default(0);
            $t->unsignedSmallInteger('score_b')->default(0);
            $t->unsignedSmallInteger('bracket_slot')->nullable(); // 淘汰賽位置
            $t->string('status')->default('pending'); // BattleStatus
            $t->boolean('venue_drawn')->default(false); // 場地是否為抽選
            $t->foreignId('venue_drawn_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });

        // 每一回合（一場由多回合組成）
        Schema::create('battle_rounds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('battle_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('sequence');
            $t->foreignId('winner_player_id')->nullable()->constrained('players')->nullOnDelete();
            $t->foreignId('winner_beyblade_id')->nullable()->constrained('beyblades')->nullOnDelete();
            $t->foreignId('loser_beyblade_id')->nullable()->constrained('beyblades')->nullOnDelete();
            $t->string('finish_type')->nullable(); // App\Enums\FinishType
            $t->unsignedTinyInteger('points')->default(0);
            $t->boolean('is_draw')->default(false);
            $t->timestamps();
        });

        // 裝備驗規紀錄
        Schema::create('equipment_checks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $t->foreignId('beyblade_id')->constrained()->cascadeOnDelete();
            $t->foreignId('referee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('passed')->default(false);
            $t->text('note')->nullable();
            $t->timestamps();
        });

        // 賽季積分
        Schema::create('ranking_points', function (Blueprint $t) {
            $t->id();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $t->foreignId('division_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('final_rank')->nullable();
            $t->integer('points')->default(0);
            $t->timestamps();
        });

        // 申訴
        Schema::create('appeals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('battle_id')->constrained()->cascadeOnDelete();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->text('reason');
            $t->string('status')->default('open'); // open/upheld/rejected
            $t->foreignId('ruled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('ruling')->nullable();
            $t->timestamps();
        });

        // 不可竄改的賽務操作存證
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');           // e.g. score.update, venue.draw, appeal.rule
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('payload')->nullable();
            $t->string('hash')->nullable(); // 串接前一筆 hash 防竄改
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'appeals', 'ranking_points', 'equipment_checks',
            'battle_rounds', 'battles', 'groups', 'stages', 'registrations',
            'score_rules', 'divisions', 'tournaments', 'sponsors', 'venues',
            'beyblades', 'players', 'user_roles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
