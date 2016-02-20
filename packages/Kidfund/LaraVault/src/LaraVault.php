<?php

namespace Kidfund\LaraVault;

use Kidfund\ThinTransportVaultClient\TransportClient;
use Log;
use Exception;

trait LaraVault
{
    protected static $LARAVAULT_PREFIX = 'laravault';
    protected static $VAULT_PREFIX = "vault:v1:";

    /** @var TransportClient */
    protected $client = null;

    /**
     * Tap into model's boot
     * http://www.archybold.com/blog/post/booting-eloquent-model-traits
     */
    public static function bootLaraVault()
    {
        $encryptClosure = function($item) {
            $item->setVaultClient(self::getVaultClient());
            $item->encrypt();
            return true;
        };

        /*
         * Laravel does not provide a "loading" event
         * See notes under decrypt below

        $dencryptClosure = function($item) {
            $item->setVaultClient(self::getVaultClient());
            $item->decrypt();
            return true;
        };
        */

        // Encrypt before writing to disk
        static::saving($encryptClosure);
        static::creating($encryptClosure);
        static::updating($encryptClosure);


    }

    // TODO should this be shared globally?
    /**
     * Share an instance of the TransportClient across all models
     *
     * @return TransportClient|null
     */
    public static function getVaultClient()
    {
        static $_client = null;

        if ($_client == null) {
            $_client = new TransportClient("http://192.168.10.10:8200", "09089548-d7e6-ee34-3119-a1cd31da1967");
        }

        return $_client;
    }

    /**
     * @param TransportClient $client
     */
    public function setVaultClient(TransportClient $client)
    {
        $this->client = $client;
    }

    /**
     * Must have a Client to function
     */
    protected function checkVaultClient()
    {
        if ($this->client === null) {
            $this->client = self::getVaultClient();
        }
    }

    /*
     * Helpers
     */

    /**
     * @param string $value is tested to see if it is already encrypted using the prefix Vault appends to encrypted values
     * @return bool
     */
    protected function isEncrypted($value)
    {
        return strpos((string)$value, self::$VAULT_PREFIX) === 0;
    }

    /**
     * Each col will have it's own vault key. This builds the first part of the key
     * @return string
     */
    protected function getVaultKeyForModel() {
        return self::$LARAVAULT_PREFIX . "-" .  self::getTable();
    }

    /**
     * Is this model using encryption?
     * @return bool
     */
    protected function modelHasEncryptionEnabled()
    {
        // TODO and has len
        return isset($this->encrypts);
    }

    /**
     * Tests whether current attribute is listed as encryptable
     * @param string $attrKey
     * @return bool
     */
    protected function attrIsEncryptable($attrKey)
    {
        return in_array($attrKey, $this->encrypts);
    }

    /*
     * Encryption
     *
     * Is handled before the model is written to disk.
     * Fields that are clear text are encrypted with Vault Transit and stored in Laravel's DB
     * Fields in memory are plain text or encrypted depending on if they have been accessed or set yet
     * Encryption is done on save instead of on set so as to avoid costly calls to Vault on every get
     */

    // TODO track encrypted dirty seperatly
    /**
     * Test:
     * - If attr is set on model
     * - If attr is encryptable
     * - If attr is not already encrypted
     * - If attr is dirty
     * @param string $attrKey
     * @return bool
     * @throws Exception
     */
    protected function shouldEncrypt($attrKey)
    {
        print("shouldEncrypt $attrKey \n");
        if (!isset($this->attributes[$attrKey]) || !$this->attrIsEncryptable($attrKey)) {
            return false;
        }

        $attrVal = $this->attributes[$attrKey];

        if ($this->isEncrypted($attrVal) && $this->isDirty($attrKey)) {
            //throw new Exception("LaraVault: Should not have a dirty encrypted value");
            return true;
        }
        else if (!$this->isEncrypted($attrVal) && $this->isDirty($attrKey)) {
            return true;
        }

        throw new Exception("LaraVault: we shouldn't have gotten here");
    }

    /**
     * @param string $attrKey
     */
    protected function encryptAttribute($attrKey)
    {
        $plaintext = $this->attributes[$attrKey];
        $valutKey = $this->getVaultKeyForModel();

        // TODO figure out how to calculate context
        //$context = $this->id;

        $encrypted = $this->client->encrypt($valutKey, $plaintext, null);
        $this->attributes[$attrKey] = $encrypted;
    }

    /**
     * @throws Exception
     */
    protected function encrypt()
    {
        $this->checkVaultClient();

        // Do we have variables to encrypt?
        if (!$this->modelHasEncryptionEnabled()) {
            return;
        }

        foreach ($this->encrypts as $attrKey) {
            if ($this->shouldEncrypt($attrKey)) {
                $this->encryptAttribute($attrKey);
            }
        }
    }

    /*
     * Decryption
     * Fields are lazy decrypted when accessed.
     * This is different from Encryption where it is handled all at once
     * A field is only decrypted if it is needed.
     * If this was done on load, we would be decrypting every field. In a collection, this would be very costly
     * https://github.com/laravel/framework/issues/1685
     * http://stackoverflow.com/questions/18883859/execute-code-when-eloquent-model-is-retrieved-from-database#answer-20920155
     */

    /**
     * Test:
     * - If attr is set on model
     * - If attr is encryptable
     * - If attr is encrypted
     * @param string $attrKey
     * @return bool
     */
    protected function shouldDecrypt($attrKey) {
        if (!isset($this->attributes[$attrKey]) || !$this->attrIsEncryptable($attrKey)) {
            return false;
        }

        $attrVal = $this->attributes[$attrKey];

        return $this->isEncrypted($attrVal);
    }



    /**
     * Decrypt each attribute in the array as required.
     *
     * @param  array $attributes
     * @return array
     */
    public function decryptAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            $attributes[$key] = $this->decryptAttribute($key, $value);
        }

        return $attributes;
    }

    /**
     * @param string $attrKey
     * @param string $attrVal
     * @return string
     */
    protected function decryptAttribute($attrKey, $attrVal)
    {
        // Do we have variables to decrypt?
        if ($this->modelHasEncryptionEnabled() && $this->shouldDecrypt($attrKey)) {
            $this->checkVaultClient();

            $cipherText = $attrVal;
            $valutKey = $this->getVaultKeyForModel();

            // TODO figure out how to calculate context
            //$context = $this->id;

            $plaintext = $this->client->decrypt($valutKey, $cipherText, null);
            return $plaintext;
        }

        return $attrVal;
    }

    /*
     * Model Overrides
     */

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        return $this->decryptAttribute($key, parent::getAttributeFromArray($key));
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        return $this->decryptAttributes(parent::getArrayableAttributes());
    }

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->decryptAttributes(parent::getAttributes());
    }

}
