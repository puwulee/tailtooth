<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 後台可輸入的設定（API 金鑰等），secret 值加密儲存
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('group')->default('general'); // linepay/invoice/line/bg/...
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_secret')->default(false);
            $t->timestamps();
        });

        // 通知發送紀錄（LINE / Email）
        Schema::create('notification_logs', function (Blueprint $t) {
            $t->id();
            $t->string('channel');            // line | email
            $t->string('to')->nullable();
            $t->string('subject')->nullable();
            $t->text('body')->nullable();
            $t->string('status')->default('queued'); // queued/sent/failed
            $t->text('error')->nullable();
            $t->nullableMorphs('related');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('settings');
    }
};
