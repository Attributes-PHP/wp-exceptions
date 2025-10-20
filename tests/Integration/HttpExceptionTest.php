<?php

/**
 * Holds tests for registering a single FastEndpoints router
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Attributes\Wp\Exceptions\Tests\Integration;

use Attributes\Wp\Exceptions\Tests\Helpers\Helpers;
use WP_REST_Server;
use Yoast\WPTestUtils\WPIntegration\TestCase;

if (! Helpers::isIntegrationTest()) {
    return;
}

/*
 * We need to provide the base test class to every integration test.
 * This will enable us to use all the WordPress test goodies, such as
 * factories and proper test cleanup.
 */
uses(TestCase::class);

beforeEach(function () {
    parent::setUp();

    // Set up a REST server instance.
    global $wp_rest_server;

    $this->server = $wp_rest_server = new WP_REST_Server;
    $router = Helpers::getRouter('PostsRouter.php');
    $router->register();
    do_action('rest_api_init', $this->server);
});

afterEach(function () {
    global $wp_rest_server;
    $wp_rest_server = null;

    parent::tearDown();
});

test('REST API endpoints registered', function () {
    $routes = $this->server->get_routes();

    expect($routes)
        ->toBeArray()
        ->toHaveKeys([
            '/my-posts/v1',
            '/my-posts/v1/(?P<ID>[\\d]+)',
        ])
        ->and($routes['/my-posts/v1/(?P<ID>[\\d]+)'])
        ->toBeArray()
        ->toHaveCount(3);
})->group('single');

test('Retrieving a post by id', function () {
    $userId = $this::factory()->user->create();
    $postId = $this::factory()->post->create(['post_author' => $userId]);
    wp_set_current_user($userId);
    $response = $this->server->dispatch(
        new \WP_REST_Request('GET', "/my-posts/v1/{$postId}")
    );
    expect($response->get_status())->toBe(200);
    $data = $response->get_data();
    expect($data)
        ->toHaveProperty('ID', $postId)
        ->toHaveProperty('post_author', $userId);
})->group('single');
