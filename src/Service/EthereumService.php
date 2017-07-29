<?php

namespace Daikon\Ethereum\Service;

use Daikon\Ethereum\Connector\EthereumRpcConnector;
use Daikon\Ethereum\Exception\EthereumException;

final class EthereumService implements EthereumServiceInterface
{
    private $connector;

    public function __construct(EthereumRpcConnector $connector)
    {
        $this->connector = $connector;
    }

    public function call(string $method, array $parameters = [])
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $parameters,
            'id' => $id = time()
        ];

        $client = $this->connector->getConnection();
        $response = $client->post('/', ['body' => json_encode($body)]);
        $rawResponse = json_decode($response->getBody()->getContents(), true);

        if (isset($rawResponse['error'])) {
            throw new EthereumException(sprintf('Ethereum client error %s', $rawResponse['error']['message']));
        }

        return $rawResponse['result'];
    }

    public function createAccount(string $password): string
    {
        return $this->call('personal_newAccount', [$password]);
    }

    public function getEtherBalance(string $address): int
    {
        return hexdec($this->call('eth_getBalance', [$address, 'latest']))/(10**18);
    }
}
