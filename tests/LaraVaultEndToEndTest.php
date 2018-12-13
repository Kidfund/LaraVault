<?php

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Kidfund\ThinTransportVaultClient\TransitClient;
use Orchestra\Testbench\TestCase;
use Psr\Log\NullLogger;

/**
 * @author: timbroder
 * Date: 4/15/16
 * @copyright 2015 Kidfund Inc
 */
class LaraVaultEndToEndTest extends TestCase
{
    // TODO provide setup instructions to run vault

    const ENCRYPTED_VALUE = "vault:v1:UEhQVW5pdF9GcmFtZXdvcmtfTW9ja09iamVjdF9Nb2NrT2JqZWN0";
    const VAULT_MODEL_KEY = "laravault-dummy";
    const VALID_PHONE = '1231231234';
    const VAULT_ADDR='http://kidfund-dev-web.app:8200';
    const VAULT_TOKEN='6a4a2fd1-0d72-40a3-74f1-0b303e943fda';
    const VAULT_ROOT_TOKEN = 'ec25daef14e-3bfb81d9-c695-8a8b-2d27';
    const VAULTTEST_PREFIX = 'thingtransport_test';
    const VALID_STRING = 'the quick brown fox';
    const VAULT_PREFIX = 'vault:v1:';

    public function setUp()
    {
        parent::setUp();

        $app = new Container();
        $app->singleton('app', Container::class);
        $app->bind('log', function($app)
        {
            return new NullLogger();
        });

        Facade::setFacadeApplication($app);

        $this->artisan('migrate:reset');
        $this->artisan('migrate', [
            '--path' => $this->getDummyMigrationsDir()
        ]);
    }

    public function tearDown()
    {
        $this->artisan('migrate:reset');
        parent::tearDown();
    }

    protected function getDummyMigrationsDir()
    {
        $path = __DIR__ . "/migrations";

        return $path;
    }

    public function getRealDummyObject()
    {
        $model = new DummyModelIntegrating();
        $client = $this->getRealVaultClient();
        $model->setVaultClient($client, true);

        return $model;
    }

    /**
     * @param bool $root
     * @param null $addr
     * @return TransitClient
     */
    public function getRealVaultClient($root = false, $addr = null)
    {
        if ($root) {
            $token = self::VAULT_ROOT_TOKEN;
        } else {
            $token = self::VAULT_TOKEN;
        }

        if ($addr == null) {
            $addr = self::VAULT_ADDR;
        }
        return new TransitClient($addr, $token);
    }

    /**
     * @test
     * @group VaultEndToEnd
     * @group EndToEnd
     */
    public function it_really_encrypts()
    {
        $dummy = $this->getRealDummyObject();
        $dummy->phone = $this::VALID_PHONE;
        $dummy->save();

        $this->seeLikeInDatabase('dummy', 'phone', 'LIKE', "%" . $this::VAULT_PREFIX . "%");
    }

    /**
     * @test
     * @group VaultEndToEnd
     * @group EndToEnd
     */
    public function it_really_decrypts()
    {
        $dummy = $this->getRealDummyObject();
        $dummy->phone = $this::VALID_PHONE;
        $dummy->save();


        $real = DummyModelIntegrating::all()->first();

        $this->assertNotNull($real->id);
        $this->assertEquals($this::VALID_PHONE, $real->phone);


    }
}
