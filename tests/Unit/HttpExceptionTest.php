<?php

/**
 * Holds tests for the HttpException class.
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions\Tests\Unit;

use Attributes\Wp\Exceptions\HttpException;
use Mockery;
use WP_Error;

// Constructor

test('Creating HTTP exception', function () {
    $httpException = new HttpException(404, 'My custom message', ['data' => true], ['header' => true]);
    expect($httpException->getCode())
        ->toBe(404)
        ->and($httpException->getMessage())
        ->toBe('My custom message')
        ->and($httpException->getData())
        ->toMatchArray(['data' => true])
        ->and($httpException->getHeaders())
        ->toMatchArray(['header' => true]);
})->group('exception', 'constructor');

// fromWpError

test('Convert WP_Error into an exception', function (mixed $code, mixed $message, mixed $data) {
    $mockError = Mockery::mock(WP_Error::class);
    $mockError->shouldReceive('get_error_code')
        ->once()
        ->andReturn($code);

    $mockError->shouldReceive('get_error_message')
        ->once()
        ->andReturn($message);

    $mockError->shouldReceive('get_error_data')
        ->once()
        ->andReturn($data);

    $httpException = HttpException::fromWpError($mockError);
    expect($httpException->getCode())
        ->toBe(is_int($code) ? $code : 500)
        ->and($httpException->getMessage())
        ->toBe($message)
        ->and($httpException->getData())
        ->toMatchArray(is_array($data) ? $data : ['data' => $data])
        ->and($httpException->getHeaders())
        ->toBeArray()
        ->toBeEmpty();
})
    ->with([
        ['edit_users', 'Unable to edit user', 'My data'],
        [400, 'Unable to edit user', 'My data'],
    ])
    ->group('exception', 'fromWpError');

test('Convert WP_Error with nested errors into an exception', function () {
    $wpError = new WP_Error('edit_users', 'message 1', ['status' => 400]);
    $wpError->add(404, 'message 2', 'data 2');
    $wpError->add(405, 'message 3', 'data 3');

    $httpException = HttpException::fromWpError($wpError);
    expect($httpException->getCode())
        ->toBe(400)
        ->and($httpException->getMessage())
        ->toBe('message 1')
        ->and($httpException->getData())
        ->toMatchArray(['status' => 400]);
})
    ->group('exception', 'fromWpError');

// toWpError

test('Convert exception into WP_Error', function () {
    $httpException = new HttpException(404, 'My error message', ['data' => true]);
    $wpError = $httpException->toWpError();
    expect($wpError)
        ->toBeInstanceOf(WP_Error::class)
        ->and($wpError->get_error_code())
        ->toBe(404)
        ->and($wpError->get_error_data())
        ->toMatchArray(['data' => true])
        ->and($wpError->get_error_message())
        ->toBe('My error message');
})
    ->group('exception', 'toWpError');

test('Create exception from WP_Error and convert it back to WP_Error', function (string|int $code, string $message, string|array $data) {
    $originalWpError = new WP_Error($code, $message, $data);
    $httpException = HttpException::fromWpError($originalWpError);
    $wpError = $httpException->toWpError();
    expect($wpError)->toBe($originalWpError);
})
    ->with([
        ['edit_users', 'Unable to edit user', 'My data'],
        [404, 'My error message', ['data' => true]],
    ])
    ->group('exception', 'toWpError');
