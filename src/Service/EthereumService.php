<?php

namespace Daikon\Ethereum\Service;

use Daikon\Ethereum\Connector\EthereumRpcConnector;

final class EthereumService implements EthereumServiceInterface
{
    private $connector;

    public function __construct(EthereumRpcConnector $connector)
    {
        $this->connector = $connector;
    }

    public function call(string $method, array $parameters = []): array
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $parameters,
            'id' => $id = time()
        ];

        $client = $this->connector->getConnection();
        $response = $client->post('/', ['body' => json_encode($body)]);
        //@todo better error handling
        return json_decode($response->getBody()->getContents(), true)['result'];
    }
}
