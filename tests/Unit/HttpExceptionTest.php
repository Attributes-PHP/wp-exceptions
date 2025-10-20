<?php

/**
 * Holds tests for the Endpoint class.
 *
 * @since 1.0.0
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions\Tests\Unit;

use Attributes\Wp\Exceptions\Tests\Helpers\Helpers;
use Attributes\Wp\FastEndpoints\Endpoint;
use Brain\Monkey;

beforeEach(function () {
    Monkey\setUp();
});

afterEach(function () {
    Monkey\tearDown();
});

// Constructor

test('Creating Endpoint instance', function () {
    $endpoint = new Endpoint('GET', '/my-endpoint', '__return_false', ['my-args'], false);
    expect($endpoint)
        ->toBeInstanceOf(Endpoint::class)
        ->and(Helpers::getNonPublicClassProperty($endpoint, 'method'))->toBe('GET')
        ->and(Helpers::getNonPublicClassProperty($endpoint, 'route'))->toBe('/my-endpoint')
        ->and(Helpers::getNonPublicClassProperty($endpoint, 'handler'))->toBe('__return_false')
        ->and(Helpers::getNonPublicClassProperty($endpoint, 'args'))->toEqual(['my-args'])
        ->and(Helpers::getNonPublicClassProperty($endpoint, 'override'))->toBeFalse();
})->group('endpoint', 'constructor');
