<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
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
        $this->logLocalUrlsOnServe();

        Gate::define('admin', fn (User $user): bool => $user->role === 'admin');
    }

    /**
     * Display the local application and tooling URLs when the dev server starts.
     */
    protected function logLocalUrlsOnServe(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        Event::listen(function (CommandStarting $event): void {
            if ($event->command !== 'serve') {
                return;
            }

            $event->output->writeln('');
            $event->output->writeln('  <fg=yellow;options=bold>URLs locales</>');
            $event->output->writeln('  Application   '.config('app.url'));
            $event->output->writeln('  phpMyAdmin    http://localhost:8081');
            $event->output->writeln('');
        });
    }
}
