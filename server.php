<?php

declare(strict_types=1);

use OpenSwoole\HTTP\Request;
use OpenSwoole\HTTP\Response;
use OpenSwoole\HTTP\Server;

// port rinha: 9999
$port = 9999;
$server = new Server('0.0.0.0', $port);

$server->set([
    'worker_num' => 1,
    'log_file' => '/dev/null',
]);

$server->on('start', static function (Server $server): void {
    echo "HTTP server listening at http://0.0.0.0:{$server->port}\n";
});

$server->on('request', static function (Request $request, Response $response): void {
    $method = strtoupper($request->server['request_method'] ?? 'GET');
    $path = $request->server['request_uri'] ?? '/';

    $response->header('Content-Type', 'application/json; charset=utf-8');

    if ($method === 'GET' && $path === '/health') {
        $response->status(200);
        $response->end(json_encode([
            'status' => 'ok',
            'service' => 'rinha-api',
            'timestamp' => gmdate(DATE_ATOM),
        ], JSON_UNESCAPED_SLASHES));

        return;
    }

    $response->status(404);
    $response->end(json_encode([
        'message' => 'Not Found',
    ], JSON_UNESCAPED_SLASHES));
});

$server->start();
