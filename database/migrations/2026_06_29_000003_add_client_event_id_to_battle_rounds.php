<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battle_rounds', function (Blueprint $t) {
            // 離線補送的冪等鍵：同一 client_event_id 只計分一次
            $t->string('client_event_id', 64)->nullable()->unique()->after('battle_id');
        });
    }

    public function down(): void
    {
        Schema::table('battle_rounds', function (Blueprint $t) {
            $t->dropUnique(['client_event_id']);
            $t->dropColumn('client_event_id');
        });
    }
};
