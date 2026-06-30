<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 場次資訊（日期/主講人/主題）＋ AI 生成題目來源；
 * 統編改為「選填身分」（非會員仍可參與），require_company → company_identity。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('description');     // 場次日期
            $table->string('speaker')->nullable()->after('event_date');        // 主講人
            $table->string('topic')->nullable()->after('speaker');             // 主題（AI 出題依據）
            // 統編改為選填身分：開啟時提供公司身分對照，非會員仍可匿名參與
            $table->renameColumn('require_company', 'company_identity');
        });

        Schema::table('questions', function (Blueprint $table) {
            // 題目來源：audience（觀眾提問）或 ai（主辦用 AI 生成）
            $table->enum('source', ['audience', 'ai'])->default('audience')->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn('source'));

        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('company_identity', 'require_company');
            $table->dropColumn(['event_date', 'speaker', 'topic']);
        });
    }
};
