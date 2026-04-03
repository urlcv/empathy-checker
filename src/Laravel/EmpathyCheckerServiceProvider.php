<?php

declare(strict_types=1);

namespace URLCV\EmpathyChecker\Laravel;

use Illuminate\Support\ServiceProvider;

/**
 * Loads Blade views for the Empathy Checker frontend tool.
 */
class EmpathyCheckerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'empathy-checker');
    }
}
