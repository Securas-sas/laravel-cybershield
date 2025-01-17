# CyberShield WAF for Laravel

This is a WAF (Web Application Firewall) using CyberShield API (Laravel only)

**Requirement : CyberShield API key.

## Install
```
composer require securas-sas/laravel-cybershield
```

## Using

### Edit routing
Set "`cybershield`" as the middleware for routing.

```php
Route::group(['middleware' => 'cybershield'], function () {
  // Protect area
});

```

# Configuration
Now is time to provide required parameters. First of all we need to publish cybershield configuration file:
php artisan vendor:publish --provider="Securas\LaravelCyberShield\CyberShieldServiceProvider" --tag=config
go to config/cybershield.php and set the parameters
#### parameters
| Param             | Description                                        |
| `enabled`         | CyberShield enabled or disabled.                   |
| `api_key`         | CyberShield API key.                               |
| `email_address`   | CyberShield Email Adress .                         |
| `sabdbox`         | Enable/ Disable CyberShield middleware true/false. |

ex.
```
enabled=true
api_key=****************
email_address=contact@*****.com
sabdbox=false
```



