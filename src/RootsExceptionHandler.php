<?php

/**
 * Holds logic to re-register Roots exception handler
 *
 * @author André Gil
 */

namespace Roots\Acorn\Bootstrap;

use Attributes\Wp\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Roots\Acorn\Bootstrap\HandleExceptions as FoundationHandleExceptionsBootstrapper;

class HandleExceptions extends FoundationHandleExceptionsBootstrapper
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Roots\Acorn\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        parent::bootstrap($app);

        if (! $this->isDebug() || $this->hasHandler()) {
            return;
        }

        ExceptionHandler::register(force: true);
    }
}
