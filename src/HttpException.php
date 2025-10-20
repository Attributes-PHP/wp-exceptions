<?php

/**
 * Holds main exception class that is used to interrupt the WordPress logic when something
 * goes wrong. It supports JSON and HTML response.
 *
 * @author André Gil
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions;

use Exception;
use Throwable;
use WP_Error;
use WP_Http;

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

    public function __construct(
        int $status = WP_Http::INTERNAL_SERVER_ERROR,
        string $message = 'Something went wrong',
        array $data = [],
        array $headers = [],
        ?Throwable $previous = null,
        ?WP_Error $wpError = null,
    ) {
        $this->data = $data;
        $this->data['status'] = $status;

        $this->headers = $headers;
        $this->status = $status;
        $this->wpError = $wpError;
        parent::__construct($message, $this->status, $previous);
    }

    /**
     * Creates a new instance using a WP_Error class
     */
    public static function fromWpError(WP_Error $error, int $defaultStatus = 500): HttpException
    {
        $data = $error->get_error_data();
        $data = $data ?: [];
        $data = is_array($data) ? $data : ['data' => $data];

        $status = $error->get_error_code();
        if (! is_int($status)) {
            $status = isset($data['status']) && is_int($data['status']) ? $data['status'] : $defaultStatus;
        }

        return new HttpException(
            $status,
            $error->get_error_message(),
            $data,
            [],
            null,
            $error,
        );
    }

    public function toWpError(): WP_Error
    {
        if ($this->wpError) {
            return $this->wpError;
        }

        $this->wpError = new WP_Error($this->status, $this->message, $this->data);

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
