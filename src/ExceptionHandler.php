<?php

/**
 * Holds logic to register custom WordPress exception handler
 *
 * @author André Gil
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions;

use Throwable;
use WP_Error;

use function wp_die;

class ExceptionHandler
{
    protected static ?self $instance = null;

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

    /**
     * If set, it's used to call the exception handlers. Otherwise, the handlers will be called directly.
     */
    protected $invoker = null;

    /**
     * Registers the exception handler
     */
    public static function register(bool $force = false): self
    {
        $exceptionHandler = self::getOrCreate();
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
    public function onException(string $exceptionClass, callable $handler, bool $override = false): self
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
        if (! class_exists('WP_Error')) {
            require_once ABSPATH.WPINC.'/class-wp-error.php';
        }

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

    /**
     * @param  HttpException  $ex  - The exception to be handled. We purposely didn't type-hint in case Mozart or PHP-Scoper is used
     */
    protected function handleHttpException($ex): void
    {
        if (! headers_sent() && (! function_exists('did_action') || ! did_action('admin_head'))) {
            foreach ($ex->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }

        static::die($ex->toWpError());
    }

    /**
     * Stops WordPress execution by calling wp_die
     */
    public static function die(WP_Error $wpError): void
    {
        if (! function_exists('wp_die')) {
            require_once ABSPATH.WPINC.'/functions.php';
        }

        wp_die($wpError);
    }

    public static function getOrCreate(): self
    {
        static::$instance = static::$instance ?: new static;

        return static::$instance;
    }

    public function setInvoker(callable $invoker): void
    {
        $this->invoker = $invoker;
    }
}
