<?php

namespace App\Providers;

use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\User;
use App\Observers\InstructorExamObserver;
use App\Observers\InstructorExamQuestionObserver;
use App\Services\AI\AiConfigurationManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        InstructorExam::observe(InstructorExamObserver::class);
        InstructorExamQuestion::observe(InstructorExamQuestionObserver::class);
        app(AiConfigurationManager::class)->apply();

        Gate::before(function (User $user, string $ability): ?bool {
            if (app()->environment('testing') && (bool) config('auth.testing_bypass_permissions', true)) {
                return true;
            }

             if ($user->isRootSuperAdmin()) {
                return true;
            }

            if ($ability === 'grant_super_admin') {
                return false;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
