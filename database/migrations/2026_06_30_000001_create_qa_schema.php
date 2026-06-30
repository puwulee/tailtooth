<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 活動問答（Slido 風現場互動提問）核心資料表。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 活動：每場活動有獨立的加入代碼與提問牆
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // 主辦者
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('code', 12)->unique();           // 觀眾加入用的代碼
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->boolean('require_approval')->default(false); // 提問需審核才上牆
            $table->boolean('allow_anonymous')->default(true);   // 允許匿名提問
            $table->timestamp('starts_at')->nullable();
            $table->timestamps();
        });

        // 提問
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('author_name')->nullable();      // 匿名時為 null
            $table->string('author_token', 64)->nullable(); // 辨識送出者瀏覽器
            $table->text('body');
            $table->enum('status', ['pending', 'published', 'archived'])->default('published');
            $table->text('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        // 按讚投票（每個瀏覽器每題限投一次）
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('voter_token', 64);
            $table->timestamps();

            $table->unique(['question_id', 'voter_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('events');
    }
};
