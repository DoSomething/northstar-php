# Northstar PHP [![Packagist](https://img.shields.io/packagist/v/dosomething/northstar.svg)](https://packagist.org/packages/dosomething/northstar)
This is a simple PHP API client for [Northstar](https://www.github.com/dosomething/northstar), the DoSomething.org
identity API. It supports authorization and resource requests from Northstar, and includes the tools necessary for
building other API clients that authorize against Northstar.

It also includes [built-in support for Laravel 5](https://github.com/DoSomething/northstar-php#laravel-usage) and can
be used for [authentication](#authentication) via OpenID Connect.

### Installation
Install with Composer:
```json
"require": {
    "dosomething/northstar": "^1.0.0"
}
```

To require the latest release candidate add it to the suffix of the version specified, such as `1.0.0-rc10`.

### Usage
In vanilla PHP, you can create a new `Northstar` client with your credentials to make API requests. You'll need
to implement your own version of the `\DoSomething\Northstar\Contracts\OAuthRepositoryContract` class to handle storing
and retrieving tokens.

```php
use DoSomething\Northstar\Northstar;

$northstar = new Northstar([
    'grant' => 'client_credentials', // Default OAuth grant to use: either 'password' or 'client_credentials'
    'url' => 'https://northstar.dosomething.org', // the environment you want to connect to
    'repository' => \YourApp\OAuthRepository::class, // class which handles saving/retrieving tokens
    
    // Then, configure client ID, client secret, and scopes per grant.
    'client_credentials' => [
        'client_id' => 'example',
        'client_secret' => 'xxxxxxxxxxxxx',
        'scope' => ['user'],
    ],
    'password' => [
        'client_id' => 'example',
        'client_secret' => 'xxxxxxxxxxxxx',
        'scope' => ['user'],
    ],
]);

// And go!
$northstar->getAllUsers();
$northstar->getUser('email', 'test@dosomething.org');
$northstar->updateUser('5480c950bffebc651c8b4570', ['first_name' => 'Puppet']);
$northstar->deleteUser('5480c950bffebc651c8b4570');

// You can override the default grant (or provide a token) per request like so:
$northstar->asClient()->get('v1/users');
$northstar->asUser()->get('v1/profile');
$northstar->withToken($accessToken)->get('v1/profile');

// and so on...

```

### Laravel Usage
Laravel support is built-in. First, add a service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    DoSomething\Northstar\Laravel\NorthstarServiceProvider::class,
],

'aliases' => [
   // ...
   'Northstar' => DoSomething\Northstar\Laravel\Facades\Northstar::class,
]
```

Then, set your environment & key in `config/services.php`:

```php
'northstar' => [
    'grant' => 'client_credentials', // Default OAuth grant to use: either 'password' or 'client_credentials'
    'url' => 'https://northstar.dosomething.org', // the environment you want to connect to
    
    // Then, configure client ID, client secret, and scopes per grant.
    'client_credentials' => [
        'client_id' => 'example',
        'client_secret' => 'xxxxxxxxxxxxx',
        'scope' => ['user'],
    ],
    'password' => [
        'client_id' => 'example',
        'client_secret' => 'xxxxxxxxxxxxx',
        'scope' => ['user'],
    ],
]
```

Publish the included migrations (and customize as needed) to add the required client & user database columns.

```
php artisan vendor:publish
```

You can now use the `Northstar` facade anywhere in your app:
```php
class Inspire
{
    public function doSomething()
    {
        $users = app('northstar')->getAllUsers();
    }
}
```

### Authentication
You can use the `authorize` and `logout` methods on the client to let users log in using Northstar's single-sign on
functionality. This can be implemented anywhere using a custom framework bridge, but it's super easy in Laravel:

First, set up the `login` and `logout` routes in your `routes.php`:

```php
// Authentication
Router::get('login', 'AuthController@getLogin');
Router::get('logout', 'AuthController@getLogout');
```

And forward those requests to the Northstar client in your authentication controller:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends Controller
{
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/users';

    /**
     * Where to redirect users after logout.
     *
     * @var string
     */
    protected $redirectAfterLogout = '/';

    /**
     * Handle a login request to the application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getLogin(ServerRequestInterface $request, ResponseInterface $response)
    {
        return app('northstar')->authorize($request, $response, $this->redirectTo);
    }

    /**
     * Handle a logout request to the application.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getLogout(ResponseInterface $response)
    {
        return app('northstar')->logout($response, $this->redirectAfterLogout);
    }
}
```

Finally, add the Northstar contract & trait to your app's User model:
```php
<?php

namespace App\Models;

// ...
use DoSomething\Northstar\Contracts\NorthstarUserContract;
use DoSomething\Northstar\Laravel\HasNorthstarToken;

class User extends Model implements NorthstarUserContract, /* ... */
{
    use HasNorthstarToken, /* ... */;
    // ...
}

```

Now, Laravel will redirect to Northstar for user login and automatically create a new model in your local database
with the appropriate `northstar_id` and `role` columns. The user's access and refresh tokens will be stored so they
can make authorized requests to other DoSomething.org services.

### License
&copy;2016 DoSomething.org. The Northstar PHP client is free software, and may be redistributed under the terms
specified in the [LICENSE](https://github.com/DoSomething/northstar-php/blob/master/LICENSE) file.
