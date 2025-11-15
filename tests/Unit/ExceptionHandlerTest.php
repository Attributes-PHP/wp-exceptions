<?php

/**
 * Holds tests for the ExceptionHandler class.
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions\Tests\Unit;

use Attributes\Wp\Exceptions\ExceptionHandler;
use Attributes\Wp\Exceptions\HttpException;
use Attributes\Wp\Exceptions\Tests\Helpers\Helpers;
use Brain\Monkey\Functions;
use Exception;
use Mockery;
use WP_Error;

$previousHandler = fn () => true;

beforeEach(fn () => set_exception_handler($previousHandler));

afterEach(function () {
    Helpers::setProperty(ExceptionHandler::getOrCreate(), 'instance', null);
});

function set(string $name, mixed $value): mixed
{
    $exceptionHandler = ExceptionHandler::getOrCreate();

    return Helpers::setProperty($exceptionHandler, $name, $value);
}

function get(string $name): mixed
{
    $exceptionHandler = ExceptionHandler::getOrCreate();

    return Helpers::getProperty($exceptionHandler, $name);
}

function invoke(string $name, ...$args): mixed
{
    $exceptionHandler = ExceptionHandler::getOrCreate();

    return Helpers::invokeMethod($exceptionHandler, $name, ...$args);
}

if (! function_exists('get_exception_handler')) {
    function get_exception_handler()
    {
        $currentHandler = set_exception_handler(fn () => true);
        set_exception_handler($currentHandler);

        return $currentHandler;
    }
}

// getOrCreate

test('Creates/retrieves an instance of an exception handler', function () {
    $exceptionHandler = ExceptionHandler::getOrCreate();
    expect($exceptionHandler)
        ->toBeInstanceOf(ExceptionHandler::class)
        ->and(ExceptionHandler::getOrCreate())
        ->toBe($exceptionHandler);
})->group('exception', 'handler', 'getOrCreate');

// register

test('Registers exception handler', function () use ($previousHandler) {
    $exceptionHandler = ExceptionHandler::register();
    expect($exceptionHandler)
        ->toBeInstanceOf(ExceptionHandler::class)
        ->and(get('isRegistered'))->toBeTrue()
        ->and(get('reservedMemory'))->toBeString()
        ->and(get('previousHandler'))->toBe($previousHandler)
        ->and(get('onExceptionHandlers'))->toBeArray()->not->toBeEmpty()
        ->and(get_exception_handler())->toBe($exceptionHandler);
})
    ->group('exception', 'handler', 'register');

test('Forces registration', function () use ($previousHandler) {
    $originalExceptionHandler = ExceptionHandler::register();
    Helpers::setProperty($originalExceptionHandler, 'reservedMemory', 'Hello world');

    $exceptionHandler = ExceptionHandler::register(force: true);
    expect($exceptionHandler)
        ->toBe($originalExceptionHandler)
        ->and(get('reservedMemory'))->toBeString()->not->toBe('Hello world')
        ->and(get('previousHandler'))->toBe($previousHandler);
})
    ->group('exception', 'handler', 'register', 'hey');

test('Already registered', function () {
    $exceptionHandler = ExceptionHandler::register();

    ExceptionHandler::register();
    expect(get('previousHandler'))->not->toBe($exceptionHandler);
})
    ->group('exception', 'handler', 'register');

// onException

test('Default handler added', function () {
    $exceptionHandler = ExceptionHandler::register();
    $onExceptionHandlers = get('onExceptionHandlers');
    expect($onExceptionHandlers)
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey(HttpException::class)
        ->and($onExceptionHandlers[HttpException::class])
        ->toMatchArray([$exceptionHandler, 'handleHttpException']);
})
    ->group('exception', 'handler', 'onException');

test('Ignores adding handler due to existent one', function () {
    $exceptionHandler = ExceptionHandler::register();
    $handler = fn () => true;
    $exceptionHandler->onException(HttpException::class, $handler);
    expect(get('onExceptionHandlers')[HttpException::class])->not->toBe($handler);
})
    ->group('exception', 'handler', 'onException');

test('Overrides existent handler', function () {
    $exceptionHandler = ExceptionHandler::register();
    $handler = fn () => true;
    $exceptionHandler->onException(HttpException::class, $handler, override: true);
    expect(get('onExceptionHandlers')[HttpException::class])->toBe($handler);
})
    ->group('exception', 'handler', 'onException');

test('Adds new handler', function () {
    $exceptionHandler = ExceptionHandler::register();
    $handler = fn () => true;
    $exceptionHandler->onException(Exception::class, $handler);
    $onExceptionHandlers = get('onExceptionHandlers');
    expect($onExceptionHandlers)
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKeys([Exception::class, HttpException::class])
        ->and($onExceptionHandlers[Exception::class])->toBe($handler);
})
    ->group('exception', 'handler', 'onException');

// getExceptionHandler

test('Retrieves specific exception handler', function () {
    $exceptionHandler = ExceptionHandler::register();
    $exceptionHandler->onException(Exception::class, fn () => true);

    $retrievedHandler = invoke('getExceptionHandler', new HttpException(500));
    expect($retrievedHandler)->toBeArray()->toMatchArray([$exceptionHandler, 'handleHttpException']);
})
    ->group('exception', 'handler', 'getExceptionHandler');

test('Retrieves broad exception handler', function () {
    $exceptionHandler = ExceptionHandler::register();
    $handler = fn () => true;
    $exceptionHandler->onException(Exception::class, $handler);

    class CustomException extends Exception {}
    $retrievedHandler = invoke('getExceptionHandler', new CustomException);
    expect($retrievedHandler)->toBe($handler);
})
    ->group('exception', 'handler', 'getExceptionHandler');

test('No exception handler found', function () {
    $exceptionHandler = ExceptionHandler::register();
    $handler = invoke('getExceptionHandler', new Exception);
    expect($handler)->toBeNull();
})
    ->group('exception', 'handler', 'getExceptionHandler');

// __invoke

test('Invokes custom handler', function () {
    $exceptionHandler = Mockery::mock(ExceptionHandler::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $ex = new Exception('Ignore message');
    $exceptionHandler->shouldReceive('getExceptionHandler')
        ->with($ex)
        ->once()
        ->andReturn(fn ($ex) => throw new Exception('Working'));

    call_user_func($exceptionHandler, $ex);
})
    ->throws(Exception::class, 'Working')
    ->group('exception', 'handler', '__invoke');

test('Invokes previous exception handler', function () {
    $exceptionHandler = ExceptionHandler::register();
    set('previousHandler', fn ($ex) => throw new Exception('Working'));
    $ex = new Exception('Ignore message');
    call_user_func($exceptionHandler, $ex);
})
    ->throws(Exception::class, 'Working')
    ->group('exception', 'handler', '__invoke');

test('No handler to invoke', function () {
    $exceptionHandler = ExceptionHandler::register();
    set('previousHandler', null);
    $ex = new Exception('Working');
    call_user_func($exceptionHandler, $ex);
})
    ->throws(Exception::class, 'Working')
    ->group('exception', 'handler', '__invoke');

test('Custom invoker', function () {
    $exceptionHandler = ExceptionHandler::register();
    $exceptionHandler->setInvoker(fn ($handler, $ex) => throw new Exception('Working'));
    $ex = new HttpException(500, 'Ignore message');
    call_user_func($exceptionHandler, $ex);
})
    ->throws(Exception::class, 'Working')
    ->group('exception', 'handler', '__invoke');

// handleHttpException

test('Handles HTTP exceptions', function () {
    Functions\expect('wp_die')->once()->andReturnUsing(fn ($wpError) => expect($wpError)
        ->toBeInstanceOf(WP_Error::class)
        ->and($wpError->get_error_code())->toBe(501)
        ->and($wpError->get_error_message())->toBe('My custom error message')
        ->and($wpError->get_error_data())->toMatchArray(['custom' => true, 'status' => 501])
    );
    $exceptionHandler = ExceptionHandler::register();
    $ex = new HttpException(501, 'My custom error message', data: ['custom' => true], headers: ['key' => 'value']);
    call_user_func($exceptionHandler, $ex);
})
    ->group('exception', 'handler', 'handleHttpException');
