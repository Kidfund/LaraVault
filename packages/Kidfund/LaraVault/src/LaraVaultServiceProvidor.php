<?php
/**
 * @author: timbroder
 * Date: 4/11/16
 * @copyright 2015 Kidfund Inc
 */


namespace Kidfund\LaraVault;


use App\TimModel;
use Illuminate\Support\ServiceProvider;
use Kidfund\ThinTransportVaultClient\TransportClient;

class LaraVaultServiceProvidor extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    public function getRealTransportClient()
    {
        $enabled = config('vault.enabled');

        if (!$enabled) {
            return null;
        }

        $vaultAddr = config('vault.addr');
        $vaultToken = config('vault.token');

        if ($vaultToken == null || $vaultToken == 'none') {
            throw new Exception("Vault token must be configured");
        }

        $_client = new TransportClient($vaultAddr, $vaultToken);
        return $_client;
    }

    /**
     * Register the command.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Kidfund\ThinTransportVaultClient\TransportClient', function ($app) {
            return $this->getRealTransportClient();
        });
    }
}