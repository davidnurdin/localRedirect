#!/usr/local/bin/php
<?php

use React\Dns\Query\CoopExecutor;
use React\Dns\Query\RetryExecutor;
use React\Dns\Query\TimeoutExecutor;
use React\Dns\Query\UdpTransportExecutor;

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

$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) use ($dns)  {

    // On crée un connector qui va utilisé notre resolver dns ET qui va ignoré les certifs HTTPS
    $connector = new React\Socket\Connector(array(
        'dns' => $dns,
        'tcp' => array(
        ),
        'tls' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    ));

    // On crée notre client
    $client = new React\Http\Browser($connector);
    $promise = $client->get($request->getUri())->then(function (Psr\Http\Message\ResponseInterface $response) use ($request) {
        return $response->getBody() ;
    }, function (Exception $e) use ($request) {
        echo 'Error: ' . $e->getMessage() . " => " . $request->getUri() .  PHP_EOL;
    }) ;

    try {
        $response = React\Async\await($promise);
        $response = React\Http\Message\Response::plaintext(
            $response
        );

    } catch (Exception $e) {
        echo "E2:" . $e->getMessage() . PHP_EOL ;

        $response = new React\Http\Message\Response(
            React\Http\Message\Response::STATUS_NOT_FOUND,
            array(
                'Content-Type' => 'text/html'
            ),
            "<html>Error 404</html>\n"
        );

    }


    return $response ;


});
$socket = new React\Socket\TcpServer('127.0.0.1:80');
$http->listen($socket);
echo "Server running" . PHP_EOL;