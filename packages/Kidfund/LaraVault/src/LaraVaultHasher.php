<?php
/**
 * @author: timbroder
 * Date: 4/25/16
 * @copyright 2015 Kidfund Inc
 */

namespace Kidfund\LaraVault;

use Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Kidfund\ThinTransportVaultClient\StringException;
use Kidfund\ThinTransportVaultClient\TransitClient;

/**
 * Kidfund\LaraVault\LaraVaultHasher
 *
 * @property integer $id
 * @property string $key
 * @property string $value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\Kidfund\LaraVault\LaraVaultHasher whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Kidfund\LaraVault\LaraVaultHasher whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\Kidfund\LaraVault\LaraVaultHasher whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|\Kidfund\LaraVault\LaraVaultHasher whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Kidfund\LaraVault\LaraVaultHasher whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LaraVaultHasher
{
    /**
     * LaraVaultHasher constructor.
     * @param TransitClient $client
     */
    public function __construct(TransitClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param Eloquent $model
     * @param $field
     * @return string
     * @throws StringException
     */
    public function hash(Eloquent $model, $field) {
        if (!is_string($field)) {
            throw new StringException("field must be a string");
        }

        $key = $this->generateFieldKey($model, $field);

        try {
            $record = $this->getRecord($key);
            return $record->value;
        } catch (ModelNotFoundException $e) {
            $record = new LaraVaultHash();
            $record->key = $key;
            $record->value = $this->makeSalt();
            $record->save();

            return $record->value;
        }
    }

    /**
     * @param Eloquent $model
     * @param $field
     * @return string
     */
    protected function generateFieldKey(Eloquent $model, $field)
    {
        $modelString = get_class($model);
        $key = "$modelString-$field";

        return $key;
    }

    /**
     * @param $key
     * @return LaraVaultHash
     * @throws ModelNotFoundException
     */
    protected function getRecord($key)
    {
        $record = LaraVaultHash::whereKey($key)->firstOrFail();
        return $record;
    }

    /**
     * @return string
     */
    protected function makeSalt()
    {
        $randPortion = $this->getRandForSalt();
        $salt = "$2y$12$" . $randPortion;

        return $salt;
    }

    /**
     * @return string
     */
    protected function getRandForSalt()
    {
        // salt for bcrypt needs to be 22 base64 characters (but just [./0-9A-Za-z]), see http://php.net/crypt
        $rand = substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
        return $rand;
    }
}