<?php

/**
 * Response used when an http exception is thrown during a regular request.
 *
 * @version 1.0.1
 *
 * @author matapatos
 */

declare(strict_types=1);

namespace Wp\Exceptions\Responses;

use Symfony\Component\HttpFoundation\Response;
use WP_Http;

class HtmlResponse extends Response implements ResponseInterface
{
    /**
     * @version 1.0.0
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct(
        ?string $message = '',
        ?int $status = WP_Http::INTERNAL_SERVER_ERROR,
        array $data = [],
        array $headers = []
    ) {
        $headers = array_merge(['Content-Type' => 'text/html'], $headers);
        $content = $this->getResponseContent($message, $status, $data);
        parent::__construct($content, $status, $headers);
    }

    /**
     * Retrieves a properly formated json response
     *
     * @version 1.0.1
     */
    public function getResponseContent(
        ?string $message = '',
        ?int $status = WP_Http::INTERNAL_SERVER_ERROR,
        array $data = []
    ): ?string {
        $templateView = implode(\DIRECTORY_SEPARATOR, [get_stylesheet_directory(), 'error.php']);
        $templateView = apply_filters('exceptions_html_response_view_name', $templateView);
        if (! $templateView) {
            return $message;
        }

        if (! file_exists($templateView)) {
            wp_die(sprintf(esc_html__('Unable to find error view %s'), $templateView));
        }

        $args = apply_filters('exceptions_html_response_view_args', [
            'title' => 'Something went wrong',
            'message' => $message,
            'code' => $status,
            'data' => $data,
        ]);
        // Avoid issues while rendering http errors in wp-admin requests
        if (is_admin()) {
            $isToShowAdminBar = apply_filters('show_admin_bar', true);
            set_current_screen($isToShowAdminBar ? '' : 'front');
        }

        return $this->renderTemplate($templateView, $args);
    }

    /**
     * Saves the rendered php file into a string variable
     *
     * @version 1.0.1
     */
    protected function renderTemplate(string $viewTemplate, array $args): string
    {
        extract($args);
        ob_start();
        require_once $viewTemplate;
        $view = ob_get_contents();
        ob_end_clean();

        return $view;
    }
}
