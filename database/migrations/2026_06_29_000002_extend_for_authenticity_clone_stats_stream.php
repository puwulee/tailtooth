<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beyblades', function (Blueprint $t) {
            // 正版/非正版
            $t->string('authenticity')->default('official')->after('generation');
            // 零件組合簽章：用於跨選手聚合「同一種陀螺組合」的統計
            $t->string('combo_signature')->nullable()->after('parts');
            // 影像處理狀態：pending/processing/done/failed
            $t->string('photo_status')->default('pending')->after('watermarked_path');
            $t->index(['generation', 'authenticity', 'combo_signature']);
        });

        Schema::table('tournaments', function (Blueprint $t) {
            // 賽事影印（複製範本以快速開新活動、推廣優惠）
            $t->foreignId('cloned_from_id')->nullable()->after('id')
                ->constrained('tournaments')->nullOnDelete();
            $t->boolean('is_template')->default(false)->after('status');
            // 允許的正版性政策
            $t->string('authenticity_policy')->default('both')->after('generation');
            // 活動直播訊號（整場賽事層級）
            $t->string('stream_url')->nullable()->after('authenticity_policy');
            // 推廣優惠資訊
            $t->json('promo')->nullable()->after('prizes');
        });

        Schema::table('battles', function (Blueprint $t) {
            // 各場次可塞入獨立直播訊號
            $t->string('stream_url')->nullable()->after('venue_drawn_by');
            $t->string('stream_status')->default('offline')->after('stream_url'); // offline/live/ended
        });

        Schema::table('players', function (Blueprint $t) {
            // 大頭貼（排行榜/冠軍展示）
            $t->string('avatar_path')->nullable()->after('nickname');
        });
    }

    public function down(): void
    {
        Schema::table('players', fn (Blueprint $t) => $t->dropColumn('avatar_path'));
        Schema::table('battles', fn (Blueprint $t) => $t->dropColumn(['stream_url', 'stream_status']));
        Schema::table('tournaments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('cloned_from_id');
            $t->dropColumn(['is_template', 'authenticity_policy', 'stream_url', 'promo']);
        });
        Schema::table('beyblades', function (Blueprint $t) {
            $t->dropIndex(['generation', 'authenticity', 'combo_signature']);
            $t->dropColumn(['authenticity', 'combo_signature', 'photo_status']);
        });
    }
};
