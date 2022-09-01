<?php

namespace Hwacom\EIPLogin;

use Illuminate\Support\ServiceProvider;

class EIPLoginServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPublishables();
    }

    private function registerPublishables()
    {
        $basePath = __DIR__;

        $arrPublishable = [
            'config' => [
                "$basePath/publishable/config/eip.php" => config_path('eip.php'),
            ],
            'resources' => [
                "$basePath/publishable/resources/lang/tw/eip_login.php" => resource_path('/lang/tw/eip_login.php'),
            ],
        ];

        foreach ($arrPublishable as $group => $paths) {
            $this->publishes($paths, $group);
        }
    }
}
