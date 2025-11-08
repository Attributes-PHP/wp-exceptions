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
*WP_Error* was a cool feature in 2007 but today, we should throw exceptions instead.

## Features

- Stops WordPress execution by converting HTTP exceptions into WP_Error's
- Support for handling custom exceptions
- Compatible with other exception handlers (e.g. [Whoops](https://github.com/filp/whoops))

## Requirements

- PHP 8.1+
- WordPress 6.x

We aim to support versions that haven't reached their end-of-life.

## Installation

```bash
composer require attributes-php/wp-exceptions
```

WP FastEndpoints was created by **[André Gil](https://www.linkedin.com/in/andre-gil/)** and is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.
