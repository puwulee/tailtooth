<?php

namespace App\Providers;

use Anthropic\Client;
use App\Contracts\QuestionGenerator;
use App\Services\ClaudeQuestionGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, fn () => new Client(
            apiKey: (string) config('services.anthropic.key'),
        ));

        $this->app->bind(QuestionGenerator::class, ClaudeQuestionGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
