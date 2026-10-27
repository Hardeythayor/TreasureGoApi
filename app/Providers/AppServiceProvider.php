<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // Some production MySQL/MariaDB versions (anything older than
        // MySQL 5.7.7 / MariaDB 10.2.2, or one configured without
        // innodb_large_prefix) can't index a full VARCHAR(255) column
        // under utf8mb4 (255 * 4 bytes = 1020, over their key-length
        // limit) — e.g. password_reset_tokens.email as a primary key.
        // Capping the default string length keeps every future indexed/
        // unique/primary string column safely under that limit
        // (191 * 4 = 764 bytes) regardless of the server's exact threshold.
        Schema::defaultStringLength(191);
    }
}
