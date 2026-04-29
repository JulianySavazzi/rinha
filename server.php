<?php

declare(strict_types=1);

require_once __DIR__ . '/FraudScoreRequest.php';
require_once __DIR__ . '/VectorSearch.php';
require_once __DIR__ . '/FraudScoreResponse.php';

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
    $method = $request->server['request_method'] ?? 'GET';
    $path = $request->server['request_uri'] ?? '/';

    $response->header('Content-Type', 'application/json; charset=utf-8');

    if ($method === 'GET' && $path === '/ready') {
        $response->status(200);
        $response->end('{"status": "ok", "service": "rinha-api"}');
        return;
    }

    if ($method === 'POST' && $path === '/fraud-score') {
        $payload = $request->rawContent() ?: '';
        $payload = FraudScoreRequest::validateAndCreate($payload);
        $json = '{"message": "Invalid request"}';
        if (!$payload) {
            $response->status(422);
            $response->end($json);
            return;
        }

        $vector = FraudScoreRequest::toVector($payload);
        $neighbors = VectorSearch::search($vector, 5, 9500); // 9500 or 4000 are the best numbers
        $decisionJson = FraudScoreResponse::makeResponse($neighbors);

        $response->status(200);
        $response->end($decisionJson);
        return;
    }

    $response->status(404);
    $response->end('{"message": "Not Found"}');
});

$path = __DIR__ . '/resources/references.json.gz';
VectorSearch::loadDataset($path); // for load data by all workers
$server->start();
