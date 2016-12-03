<?php
/**
 * @author: timbroder
 * Date: 4/25/16
 * @copyright 2015 Kidfund Inc
 */

namespace Kidfund\LaraVault;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Kidfund\ThinTransportVaultClient\TransitClient;
use Kidfund\ThinTransportVaultClient\StringException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    protected $client;

    /**
     * LaraVaultHasher constructor.
     * @param TransitClient $client
     */
    public function __construct(TransitClient $client = null)
    {
        if ($client) {
            $this->client = $client;
        }
    }

    /**
     * @param $modelName
     * @param $field
     * @param $value
     * @return string
     * @throws StringException
     * @internal param Eloquent $model
     */
    public function hashByModelName($modelName, $field, $value)
    {
        if (!is_string($field)) {
            throw new StringException('field must be a string');
        }

        $salt = $this->getSalt($modelName, $field);
        $hashed = crypt($value, $salt);

        return $hashed;
    }

    /**
     * @param Model $model
     * @param $field
     * @param $value
     * @return string
     * @throws StringException
     * @internal param $modelName
     * @internal param Eloquent $model
     */
    public function hash(Model $model, $field, $value)
    {
        if (!is_string($field)) {
            throw new StringException('field must be a string');
        }

        $salt = $this->getSalt(get_class($model), $field);
        $hashed = crypt($value, $salt);

        return $hashed;
    }

    protected function getSalt($modelName, $field)
    {
        $key = $this->generateFieldKey($modelName, $field);

        try {
            $record = $this->getRecord($key);
            if ($this->client) {
                $record->setVaultClient($this->client);
            }

            return $record->value;
        } catch (ModelNotFoundException $e) {
            $record = new LaraVaultHash();
            if ($this->client) {
                $record->setVaultClient($this->client);
            }

            $record->key = $key;
            $record->value = $this->makeSalt();
            $record->save();

            return $record->value;
        }
    }

    /**
     * @param $modelName
     * @param $field
     * @return string
     */
    protected function generateFieldKey($modelName, $field)
    {
        $key = "$modelName-$field";

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
        $salt = '$2y$12$'.$randPortion;

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
