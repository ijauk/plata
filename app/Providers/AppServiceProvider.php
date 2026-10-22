<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Decimalni;
use Illuminate\Support\Facades\Blade;
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
        Gate::define('admin', fn (User $user): bool => $user->is_admin);

        Blade::directive('eur', fn (string $iznos): string => "<?php echo \\Illuminate\\Support\\Number::format({$iznos}, precision: 2, locale: 'hr'); ?>");
        Blade::directive('unos', fn (string $vrijednost): string => '<?php echo e(\\'.Decimalni::class."::zaUnos({$vrijednost})); ?>");
        Blade::directive('broj', fn (string $broj): string => "<?php echo \\Illuminate\\Support\\Number::format({$broj}, maxPrecision: 4, locale: 'hr'); ?>");
    }
}
