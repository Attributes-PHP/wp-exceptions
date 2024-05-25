<?php

/**
 * Holds main exception class that is used to interrupt the WordPress logic when something
 * goes wrong. It supports JSON and HTML response.
 *
 * @version 1.0.1
 *
 * @author matapatos
 */

declare(strict_types=1);

namespace Wp\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Throwable;
use Wp\Exceptions\Responses\HtmlResponse;
use Wp\Exceptions\Responses\JsonResponse;
use WP_Error;
use WP_Http;
use WP_Rewrite;

class HttpException extends Exception implements Responsable
{
    /**
     * Holds the HTTP status error code to be thrown
     */
    protected $code = WP_Http::INTERNAL_SERVER_ERROR;

    /**
     * Holds the HTTP status error code to be thrown
     */
    protected $message = 'Something went wrong';

    /**
     * Holds additional data to be passed to the response
     */
    protected array $data = [];

    /**
     * Holds http headers to be sent in response
     */
    protected array $headers = [];

    public function __construct(
        ?string $message = null,
        ?int $code = null,
        array $data = [],
        array $headers = [],
        ?Throwable $previous = null
    ) {
        $this->data = $data;
        $this->headers = $headers;
        $this->message = $message ?: $this->message;
        $this->code = $code ?: $this->code;
        parent::__construct($this->message, $this->code, $previous);
    }

    /**
     * Creates a new instance using a WP_Error class
     *
     * @version 1.0.0
     */
    public static function fromWpError(WP_Error $error): HttpException
    {
        $status = $error->get_error_code();
        if (! is_int($status)) {
            $status = null;
        }

        return new HttpException(
            $error->get_error_message(),
            $status,
            $error->get_error_data() ?: [],
        );
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @version 1.0.0
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $isRestRequest = $this->isRestRequest();
        $class = $isRestRequest ? JsonResponse::class : HtmlResponse::class;
        $class = apply_filters('exceptions_response_class', $class, $this);

        return new $class($this->message, $this->code, $this->data, $this->headers);
    }

    /**
     * @version 1.0.0
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @version 1.0.0
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Checks if a given request is a REST request.
     *
     * @link https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist#answer-317041
     *
     * @author matzeeable
     */
    protected function isRestRequest(): bool
    {
        if (defined('REST_REQUEST') && \REST_REQUEST) {
            return true;
        }

        if (isset($_GET['rest_route']) && strpos($_GET['rest_route'], '/', 0) === 0) {
            return true;
        }

        global $wp_rewrite;
        if ($wp_rewrite === null) {
            $wp_rewrite = new WP_Rewrite();
        }

        $restUrl = wp_parse_url(trailingslashit(rest_url()));
        $currentUrl = wp_parse_url(add_query_arg([]));

        return strpos($currentUrl['path'] ?? '/', $restUrl['path'], 0) === 0;
    }
}
