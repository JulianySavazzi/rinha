<?php

declare(strict_types=1);

require_once __DIR__ . '/FraudScoreRequest.php';
require_once __DIR__ . '/VectorSearch.php';

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
    $path = __DIR__ . '/resources/references.json.gz';
    VectorSearch::loadDataset($path);
    echo "HTTP server listening at http://0.0.0.0:{$server->port}\n";
});

$server->on('request', static function (Request $request, Response $response): void {
    $method = strtoupper($request->server['request_method'] ?? 'GET');
    $path = $request->server['request_uri'] ?? '/';

    $response->header('Content-Type', 'application/json; charset=utf-8');

    if ($method === 'GET' && $path === '/ready') {
        $response->status(200);
        $response->end(json_encode([
            'status' => 'ok',
            'service' => 'rinha-api',
            'timestamp' => gmdate(DATE_ATOM),
        ], JSON_UNESCAPED_SLASHES));
        return;
    }

    if ($method === 'POST' && $path === '/fraud-score') {
        $payload = $request->rawContent() ?: '';
        $payload = FraudScoreRequest::validateAndCreate($payload);

        if (!$payload) {
            $response->status(422);
            $response->end(json_encode([
                'message' => 'Invalid request',
            ]));
            return;
        }

        $vector = FraudScoreRequest::toVector($payload);
        var_dump($vector);
        // TODO
        $response->status(200);
        $response->end(json_encode([
            'approved' => true,
            'fraud_score' => 0.0,
        ]));
        return;
    }

    $response->status(404);
    $response->end(json_encode([
        'message' => 'Not Found',
        'requested_method' => $method,
        'requested_path' => $path
    ], JSON_UNESCAPED_SLASHES));
});

$server->start();
