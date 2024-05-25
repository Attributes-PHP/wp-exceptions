<?php

/**
 * Response interface used to create custom http exception responses.
 *
 * @version 1.0.0
 *
 * @author matapatos
 */

declare(strict_types=1);

namespace Wp\Exceptions\Responses;

use WP_Http;

interface ResponseInterface
{
    /**
     * It retrieves the contents of a response in a string format
     *
     * @version 1.0.0
     *
     * @param  ?string  $message  - UI client message (e.g. Something went wrong).
     * @param  ?int  $status  - HTTP status code.
     * @param  ?array  $status  - Additional data to be passed to the response.
     */
    public function getResponseContent(
        ?string $message = '',
        ?int $status = WP_Http::INTERNAL_SERVER_ERROR,
        array $data = [],
    );
}
