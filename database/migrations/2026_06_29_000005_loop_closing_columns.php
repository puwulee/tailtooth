<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('line_user_id')->nullable()->unique()->after('email'); // LINE Login 綁定
            $t->string('avatar_url')->nullable()->after('line_user_id');       // LINE 大頭貼
        });

        Schema::table('registrations', function (Blueprint $t) {
            $t->string('check_in_token', 40)->nullable()->unique()->after('status'); // QR 報到
        });

        Schema::table('battles', function (Blueprint $t) {
            // 淘汰賽下一輪連動：本場勝者要送往的下一場與位置
            $t->foreignId('next_battle_id')->nullable()->after('bracket_slot')->constrained('battles')->nullOnDelete();
            $t->unsignedTinyInteger('next_slot')->nullable()->after('next_battle_id'); // 0=A,1=B
            $t->timestamp('scheduled_at')->nullable()->after('venue_drawn_by');        // 排定時間
        });
    }

    public function down(): void
    {
        Schema::table('battles', function (Blueprint $t) {
            $t->dropConstrainedForeignId('next_battle_id');
            $t->dropColumn(['next_slot', 'scheduled_at']);
        });
        Schema::table('registrations', fn (Blueprint $t) => $t->dropColumn('check_in_token'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['line_user_id', 'avatar_url']));
    }
};
