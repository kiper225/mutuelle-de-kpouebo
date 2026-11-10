<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Models\Fundraiser;
use App\Models\Project;
use App\Models\Publication;
use App\Services\ContentNotifications;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\PaymentGateway::class, function () {
        return config('mutuelle.paystack.secret_key')
            ? app(\App\Services\PaystackGateway::class)
            : app(\App\Services\NullPaymentGateway::class);
        });

        $current = getenv('OPENSSL_CONF');

        if (PHP_OS_FAMILY === 'Windows' && (! $current || ! is_file($current)) && is_file(base_path('openssl.cnf'))) {
            putenv('OPENSSL_CONF='.base_path('openssl.cnf'));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        // Notifications : à chaque enregistrement, on vérifie si le contenu vient d'être mis en ligne.
        Publication::saved(fn (Publication $p) => app(ContentNotifications::class)->publication($p));
        Project::saved(fn (Project $p) => app(ContentNotifications::class)->project($p));
        Fundraiser::saved(fn (Fundraiser $f) => app(ContentNotifications::class)->fundraiser($f));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
