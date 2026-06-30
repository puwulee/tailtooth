<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 統編驗證：活動可要求觀眾輸入統一編號，對照主辦者的公司名單才能參與。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 活動開關：是否需統編驗證
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('require_company')->default(false)->after('allow_anonymous');
        });

        // 主辦者共用的公司名單（統編 ↔ 公司名）
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 名單擁有者（主辦者）
            $table->string('tax_id', 8);    // 統一編號（8 碼）
            $table->string('name');         // 公司名稱
            $table->boolean('active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tax_id']);
        });

        // 提問者的公司身分（驗證通過時記錄）
        Schema::table('questions', function (Blueprint $table) {
            $table->string('tax_id', 8)->nullable()->after('author_token');
            $table->string('company_name')->nullable()->after('tax_id');
        });

        // 投票者的公司身分（驗證通過時記錄，去重仍以 voter_token 為準）
        Schema::table('votes', function (Blueprint $table) {
            $table->string('tax_id', 8)->nullable()->after('voter_token');
        });
    }

    public function down(): void
    {
        Schema::table('votes', fn (Blueprint $t) => $t->dropColumn('tax_id'));
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn(['tax_id', 'company_name']));
        Schema::dropIfExists('companies');
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('require_company'));
    }
};
