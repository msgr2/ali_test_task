<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Schema;

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
        Schema::defaultStringLength(191);
//        not working..
//        DB::connection('clickhouse')->listen(function($query) {
//            Log::info(
//                $query->sql,
//                $query->bindings,
//                $query->time
//            );
//        });

        DB::listen(function ($query) {
//            Log::info(
//                $query->sql,
//                $query->bindings,
//                $query->time
//            );
        });
    }
}
