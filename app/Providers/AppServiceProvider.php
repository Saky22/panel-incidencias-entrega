<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Shell del framework. Los bindings de negocio NO van aquí:
 * cada bounded context de src/ registra los suyos en su propio ServiceProvider.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
