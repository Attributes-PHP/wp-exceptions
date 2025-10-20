<?php

/**
 * Holds logic to register custom WordPress exception handler
 *
 * @author André Gil
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions;

use Exception;
use Throwable;

use function wp_die;

class ExceptionHandler
{
    /**
     * Reserves memory so that errors can be displayed properly on memory exhaustion e.g. out-of-memory errors.
     *
     * @var string|null
     */
    protected static $reservedMemory;

    protected bool $isRegistered = false;

    protected $previousHandler = null;

    /**
     * Set of functions used to handle exceptions
     *
     * @var array<string,callable>
     */
    protected array $onExceptionHandlers = [];

    protected $invoker = null;

    /**
     * Registers the exception handler
     */
    public static function register(bool $force = false): ExceptionHandler
    {
        $exceptionHandler = self::getInstance();
        if ($exceptionHandler->isRegistered && ! $force) {
            return $exceptionHandler;
        }

        $exceptionHandler->isRegistered = true;
        $exceptionHandler::$reservedMemory = str_repeat('x', 32768);
        $previousHandler = set_exception_handler($exceptionHandler);
        if ($previousHandler && $previousHandler != $exceptionHandler) {
            $exceptionHandler->previousHandler = $previousHandler;
        }
        $exceptionHandler->onException(HttpException::class, [$exceptionHandler, 'handleHttpException']);

        return $exceptionHandler;
    }

    /**
     * Adds a handler for a given exception.
     *
     * Handlers will be resolved on the following order: 1) by same exact exception or 2) by a parent class
     *
     * @param  string  $exceptionClass  The exception class to add a handler.
     * @param  callable  $handler  The handler to resolve those types of exceptions.
     * @param  bool  $override  If set, overrides any existent handler. Default value: false.
     */
    public function onException(string $exceptionClass, callable $handler, bool $override = false): ExceptionHandler
    {
        if (isset($this->onExceptionHandlers[$exceptionClass]) && ! $override) {
            return $this;
        }

        $this->onExceptionHandlers[$exceptionClass] = $handler;

        return $this;
    }

    /**
     * Handles non-handled exceptions
     *
     * @internal
     */
    public function __invoke(Throwable $ex): void
    {
        $handler = $this->getExceptionHandler($ex) ?: $this->previousHandler;
        if (! $handler) {
            throw $ex;
        }

        if (! $this->invoker) {
            call_user_func($handler, $ex);

            return;
        }

        call_user_func($this->invoker, $handler, $ex);
    }

    /**
     * Retrieves the exception handler for a given exception, if exists.
     *
     * @param  Throwable  $exception  The exception to be handled.
     * @return ?callable Returns a callable to handle that exception or null if not found
     */
    protected function getExceptionHandler(Throwable $exception): ?callable
    {
        if (isset($this->onExceptionHandlers[$exception::class])) {
            return $this->onExceptionHandlers[$exception::class];
        }

        foreach ($this->onExceptionHandlers as $exceptionClass => $handler) {
            if (is_subclass_of($exception, $exceptionClass)) {
                return $handler;
            }
        }

        return null;
    }

    protected function handleHttpException(HttpException $ex): void
    {
        if (! function_exists('wp_die')) {
            require_once ABSPATH.WPINC.'/functions.php';
        }

        if (! class_exists('WP_Error')) {
            require_once ABSPATH.WPINC.'/class-wp-error.php';
        }

        if (! headers_sent() && (! function_exists('did_action') || ! did_action('admin_head'))) {
            foreach ($ex->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }

        wp_die($ex->toWpError());
    }

    /**
     * Retrieves an instance of this class. If it doesn't exist creates a new one and stores it in a global
     * variable, to be compatible with tools like Mozart or PHP-Scoper which change the namespace.
     */
    public static function getInstance(): ExceptionHandler
    {
        global $attributes_wp_exceptions_exception_handler;

        if ($attributes_wp_exceptions_exception_handler) {
            return $attributes_wp_exceptions_exception_handler;
        }

        $attributes_wp_exceptions_exception_handler = new static;

        return $attributes_wp_exceptions_exception_handler;
    }

    public function setInvoker(callable $invoker): void
    {
        $this->invoker = $invoker;
    }
}
