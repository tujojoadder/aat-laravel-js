<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
Relation::morphMap([
    'user'=>'App\Models\User',
    'page'=>'App\Models\Pages',
    'group'=>'App\Models\Groups',
    'iaccount'=>'App\Models\IAccount',
    'post'=>'App\Models\Posts',
    'comment'=>'App\Models\Comments',
    'reply'=>'App\Models\Replies',
]);
class AppServiceProvider extends ServiceProvider
{

  
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
