<?php

namespace Daikon\Ethereum\Connector;

use Daikon\Ethereum\Exception\EthereumException;

trait EthereumRpcServiceTrait
{
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
            throw new EthereumException(sprintf('Ethereum client error: %s', $rawResponse['error']['message']));
        }

        return $rawResponse['result'];
    }

    public function unlockAccount(string $address, string $password, int $duration = 30): void
    {
        $this->call('personal_unlockAccount', [$address, $password, $duration]);
    }

    private function getCoinbase(): string
    {
        return $this->call('eth_coinbase');
    }

    private function getTransactionReceipt(string $txHash): ?array
    {
        return $this->call('eth_getTransactionReceipt', [$txHash]);
    }

    private function getFunctionSignature(string $function): string
    {
        $signature = $this->getSha3($function);
        return substr($signature, 0, 10);
    }

    private function getSha3(string $string): string
    {
        return $this->call('web3_sha3', ['0x'.$this->strhex($string)]);
    }

    private function estimateGas(array $payload): string
    {
        return $this->call('eth_estimateGas', [$payload]);
    }

    private function strhex(string $string): string
    {
        $hexstr = unpack('H*', $string);
        return array_shift($hexstr);
    }

    private function hexstr(string $string): string
    {
        return pack('H*', $string);
    }
}
