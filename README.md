# LaraVault

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

LaraVault uses Hashicorp [Vault](https://www.vaultproject.io/ "Vault") to encrypt/decrypt specific fields on an Eloquent model, and store the encrypted values in your existing database

[![](http://pocketstudio.jp.s3.amazonaws.com/log3/wp-content/uploads/2015/07/hahsicorp-vaule-header2-670x262.png)](https://www.vaultproject.io/ "Vault Homepage)")

[![](http://tecadmin.net/wp-content/uploads/2014/12/laravel-logo.png)](https://laravel.com/ "Laravel Homepage")

## Install

Via Composer

``` bash
$ composer require kidfund/laravault
```

## Usage

Kidfund uses Hashicorp's [Vault](https://www.vaultproject.io/ "Vault") to encrypt user PII. There are 3 main aspects to this:

1. The Vault Server
2. The Vault Client
3. The Laravel model trait that encrypts/decrypts attributes

The vault server can be run from the command line. If it is [installed](https://www.vaultproject.io/downloads.html "installed") the server can be started with this command, from the root of the Kidfund project:

```
vault server -config ./vendor/kidfund/thin-transit-client/config/vault.hcl.example
```

### Vault Setup

If running vault locally for the first time, it needs to be set up. This is only needed for the first time. After this, Laravel will interact with Vault for you. The only exception to this is unseal. You will need to unseal the vault each time it's started.

1. Leave the window where you started vault open
2. In a new window: ```export VAULT_ADDR=http://192.168.20.20:8200``` *(This is assuming a vagrant/homestead setup. You may be pointing to localhost)*
3. ```vault init``` will give you the master key shards for your instance. Hold on to these
4. Also make note of the initial root token. Take it and run this: ```export VAULT_TOKEN=[YOUR INITIAL ROOT TOKEN]```
5. ```vault unseal``` and put in 3 of the master key shards (keep running the command)
6. ```vault mount transit```
7. Create the access policy that Laravel will use: ```vault policy-write web ./vendor/kidfund/thin-transit-client/config/vault.policy.web.json```
8. Get an access token for Laravel: ```vault token-create -orphan -policy="web"```
9. Add this token to ```VAULT_TOKEN=``` in ```.env```

### Vault Process

If a Laravel Model is encrypting a field,  these are the general steps taken using Vault's [Transit](https://www.vaultproject.io/docs/secrets/transit/index.html "Transit") backend

#### Encryption

1. Model determines if encryption is needed and sends cleartext to Vault Client
2. Vault client talks to Vault Server and gets ciphertext
3. Vault client hands ciphertext to Laravel Model
4. Laravel saves ciphertext in Laravel's data store

#### Decryption:

1. Model retreives ciphertext from Laravel's database
2. Model determines if decryption is needed and sends ciphertext to Vault Client
2. Vault client talks to Vault Server and gets cleartext
3. Vault client hands cleartext to Laravel Model


### Laravel Trait

To enable encryption on a trait: 

```php
use Kidfund\LaraVault\LaraVault;

class User extends Authenticatable
{
    use LaraVault;

    protected $encrypts = [
		'phone_number',
    ];
}
```

**Fields using Vault MUST be larger than normal:**

The ciphertext is a lot longer than the cleartext

```php
$table->string('phone_number', 255)
```

### Looking up by encrupted field

Because the data is encrypted in your databse, you can't use it direcly in a lookup. In our phone number example above, the value of an encrypted phone number will be different each time it's encrypted, so it will never have a known value. Ideally, you wouldn't have to do this kind of lookup, on this kind of data, but you may need to. For instance if you were encrypting your Stripe tokens, you are eventually going to have to look records up by this token.



### Notes

* The master key is unknown to anyone except the operator
* A different encryption key is used for each field that is encrypted. Each key is encrypted with the master key
* Every row gets it's own context in Vault
* Date/Times encrypted by LaraVault **must be strings**

## Testing

### Without a running vault instance

``` bash
$ ./vendor/bin/phpunit --exclude-group EndToEnd
```

### With a running vault instance

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email timothy.broder@gmail.com instead of using the issue tracker.

## Credits

- [@timbroder][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/kidfund/laravault.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/kidfund/laravault/master.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/kidfund/laravault.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/kidfund/laravault
[link-travis]: https://travis-ci.org/kidfund/laravault
[link-downloads]: https://packagist.org/packages/kidfund/laravault
[link-author]: https://github.com/timbroder
