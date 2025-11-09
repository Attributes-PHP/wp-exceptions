# WP Exceptions

<img src="https://raw.githubusercontent.com/Attributes-PHP/wp-exceptions/main/images/wp-exceptions-wallpaper.png" alt="Throw exceptions instead of returning WP_Error">
<p align="center">
    <a href="https://github.com/Attributes-PHP/wp-exceptions/actions"><img alt="GitHub Actions Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/Attributes-PHP/wp-exceptions/tests.yml"></a>
    <a href="https://codecov.io/gh/Attributes-PHP/wp-exceptions" ><img alt="Code Coverage" src="https://codecov.io/gh/Attributes-PHP/wp-exceptions/graph/badge.svg?token=8N7N9NMGLG"/></a>
    <a href="https://packagist.org/packages/Attributes-PHP/wp-exceptions"><img alt="Latest Version" src="https://img.shields.io/packagist/v/Attributes-PHP/wp-exceptions"></a>
    <a href="https://packagist.org/packages/Attributes-PHP/wp-exceptions"><img alt="Supported WordPress Versions" src="https://img.shields.io/badge/6.x-versions?logo=wordpress&label=versions"></a>
    <a href="https://opensource.org/licenses/MIT"><img alt="Software License" src="https://img.shields.io/badge/Licence-MIT-brightgreen"></a>
</p>

------
*WP_Error* was a cool feature in 2007 but today we should throw exceptions.

## Features

- Handles *HttpExceptions* like WP_Error's
- Supports custom handlers for custom exceptions
- Compatible with other exception handlers (e.g. [Whoops](https://github.com/filp/whoops))

## Requirements

- PHP 8.1+
- WordPress 6.x

We aim to support versions that haven't reached their end-of-life.

## Installation

```bash
composer require attributes-php/wp-exceptions
```

## How it works?

Once the ExceptionHandler is registered, you can start throwing exceptions

```php
use Attributes\Wp\Exceptions\ExceptionHandler;

ExceptionHandler::register();
```

<details>
<summary><h4>How <b>HttpExceptions</b> are displayed?</h4></summary>

WordPress itself handles how an <a href="https://github.com/Attributes-PHP/wp-exceptions/blob/main/src/HttpException.php" target="_blank"><b>HttpException</b></a>
is displayed to the user. In a nutshell, those exceptions are converted into a <a href="https://developer.wordpress.org/reference/classes/wp_error/" target="_blank"><b>WP_Error</b></a>
which is then handled by WordPress via <a href="https://developer.wordpress.org/reference/functions/wp_die/" target="_blank"><i>wp_die</i></a> function.

This means, that the following types of requests are supported:

- ✅ Ajax
- ✅ JSON
- ✅ JSONP
- ✅ XMLRPC
- ✅ XML
- ✅ All other types e.g. HTML
</details>

<details>
<summary><h4>How to send custom HTTP headers?</h4></summary>

```php
throw new HttpException(400, 'My message', headers: ['My-Header' => 'Value 123']);
```
</details>

<details>
<summary><h4>How to add custom handlers?</h4></summary>

```php
$exceptionHandler = ExceptionHandler::getInstance();
$exceptionHandler->onException(CustomException::class, fn($ex) => echo "A custom exception has been raised");
```

Ensure to add handlers which supports all types of possible requests e.g. JSON, XML, etc
</details>

<details>
<summary><h4>Sage theme support</h4></summary>

If you are using <a href="https://github.com/roots/sage" target="_blank"><i>Sage</i></a> theme, you would need to register or re-register
this exception handler after the application is configured. Otherwise, this exception handler might be overrided.

```php
// themes/sage/functions.php

Application::configure()
    ->withProviders([
        App\Providers\ThemeServiceProvider::class,
    ])
    ->boot();

ExceptionHandler::register(force: true); // We are using force true in case the ExceptionHandler has been registered before e.g. in a plugin
```
</details>

WP Exceptions was created by **[André Gil](https://www.linkedin.com/in/andre-gil/)** and is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.
