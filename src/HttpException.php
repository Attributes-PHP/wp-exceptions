<?php

/**
 * Holds exception class which can be used to interrupt the WordPress logic when something
 * goes wrong. It supports all types of WP requests that wp_die function supports.
 *
 * @author André Gil
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions;

use Exception;
use Throwable;
use WP_Error;
use WP_Http;

/**
 * Exception used to stop WordPress execution which mimics when a WP_Error is returned
 */
class HttpException extends Exception
{
    /**
     * Holds the HTTP status error code to be thrown
     */
    protected int $status;

    /**
     * Holds additional data to be passed to the response
     */
    protected array $data;

    /**
     * Holds http headers to be sent in response
     */
    protected array $headers;

    protected ?WP_Error $wpError = null;

    /**
     * @param  int  $status  HTTP status code to be sent
     * @param  string  $message  Short description
     * @param  array<string,mixed>  $data  Additional data to be sent
     * @param  array<string,mixed>  $headers  Headers to be sent
     */
    public function __construct(
        int $status = WP_Http::INTERNAL_SERVER_ERROR,
        string $message = 'Something went wrong',
        array $data = [],
        array $headers = [],
        ?Throwable $previous = null,
    ) {
        $this->status = $status;
        $this->data = $data;
        $this->data['status'] = $this->status;

        $this->headers = $headers;
        parent::__construct($message, $this->status, $previous);
    }

    /**
     * Creates a new instance using a WP_Error class
     *
     * @param  WP_Error  $error  The WP_Error to base the exception from. Nested WP_Error's are also supported.
     * @param  int  $defaultStatus  If the provided error code is a string and no 'data.status' is set, this value is used.
     */
    public static function fromWpError(WP_Error $error, int $defaultStatus = 500): HttpException
    {
        $data = $error->get_error_data();
        $data = $data ?: [];
        $data = is_array($data) ? $data : ['data' => $data];

        $status = isset($data['status']) && is_int($data['status']) ? $data['status'] : $error->get_error_code();
        $status = is_int($status) ? $status : $defaultStatus;

        $httpException = new HttpException(
            $status,
            $error->get_error_message(),
            $data,
        );
        $httpException->wpError = $error;

        return $httpException;
    }

    /**
     * Converts this exception into a WP_Error object
     */
    public function toWpError(): WP_Error
    {
        $this->wpError = $this->wpError ?: new WP_Error($this->status, $this->message, $this->data);

        return $this->wpError;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
