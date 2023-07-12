#!/usr/local/bin/php
<?php

use Psr\Http\Message\ResponseInterface;
use React\Dns\Query\CoopExecutor;
use React\Dns\Query\RetryExecutor;
use React\Dns\Query\TimeoutExecutor;
use React\Dns\Query\UdpTransportExecutor;use React\Stream\CompositeStream;use React\Stream\ThroughStream;

error_reporting(E_ERROR);
require __DIR__ . '/vendor/autoload.php';

/* On crée un resolver DNS , qui va utilisé google */
$executor = new CoopExecutor(
    new RetryExecutor(
        new TimeoutExecutor(
            new UdpTransportExecutor("8.8.8.8"),
            3.0
        )
    )
);
$dns = new React\Dns\Resolver\Resolver($executor);
$connector = new React\Socket\Connector(array(
    'dns' => $dns,
    'tcp' => array(),
    'tls' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
));

$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) use ($connector) {
    return new React\Promise\Promise(function ($resolve, $reject) use ($connector,$request) {

/*        var_dump($request->getMethod());
        var_dump($request->getUri());
        var_dump($request->getHeaders());
        var_dump($request->getBody());*/

        var_dump($request->getBody());

        (new React\Http\Browser($connector))->request($request->getMethod(),$request->getUri(),$request->getHeaders(),$request->getBody())->then(function (ResponseInterface $response) use ($resolve) {
            $resolve(new React\Http\Message\Response(
                React\Http\Message\Response::STATUS_OK,
                $response->getHeaders(),
                $response->getBody()
            )) ;

        }, function (Exception $e) use ($resolve) {
            // bad
            $resolve(new React\Http\Message\Response(
                React\Http\Message\Response::STATUS_BAD_GATEWAY,
                array(
                    'Content-Type' => 'text/plain'
                ),
                $e->getMessage()
            ));
        });
    });
});
$socket = new React\Socket\TcpServer('0.0.0.0:80');
$http->listen($socket);
echo "Server running" . PHP_EOL;