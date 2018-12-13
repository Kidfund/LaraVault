<?php
use Illuminate\Database\Eloquent\Model;
use Kidfund\LaraVault\LaraVault;

/**
 * @author: timbroder
 * Date: 4/11/16
 * @copyright 2015 Kidfund Inc
 */
class DummyModelIntegrating extends Model
{
    use LaraVault {
        encrypt as traitEncrypt;
        decryptAttribute as traitDecryptAttribute;
    }

    protected $table = 'dummy';
    protected $encrypts = [
        'phone',
        'cell'
    ];

    public $encryptedCount = 0;
    public $decryptedCount = 0;

    protected function encrypt()
    {
        $this->encryptedCount++;
        return $this->traitEncrypt();
    }

    protected function decryptAttribute($attrKey, $attrVal)
    {
        $this->decryptedCount++;
        return $this->traitDecryptAttribute($attrKey, $attrVal);
    }
}
