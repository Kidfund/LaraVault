<?php
/**
 * @author: timbroder
 * Date: 4/25/16
 * @copyright 2015 Kidfund Inc
 */

namespace Kidfund\LaraVault;

use Eloquent;

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
class LaraVaultHash extends Eloquent
{
    use LaraVault;

    protected $table = 'laravault_hash';

    protected $encrypts = ['value'];
}
