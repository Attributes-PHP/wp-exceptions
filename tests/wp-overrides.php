<?php

/**
 * Holds class overrides from WordPress
 *
 * @since 0.9.0
 */

use Wp\Exceptions\Tests\Helpers\Helpers;

if (Helpers::isIntegrationTest()) {
    return;
}
