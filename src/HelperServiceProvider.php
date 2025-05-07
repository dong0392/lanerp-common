<?php

namespace lanerp\common;

use lanerp\common\Providers\PageService;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind('Illuminate\Pagination\LengthAwarePaginator',function ($app,$options){
            return new PageService($options['items'], $options['total'], $options['perPage'], $options['currentPage'] , $options['options']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
