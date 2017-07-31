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
            throw new EthereumException(sprintf('Ethereum client error: %s', $rawResponse['error']['message']));
        }

        return $rawResponse['result'];
    }

    public function createAccount(string $password): string
    {
        return $this->call('personal_newAccount', [$password]);
    }

    public function getEtherBalance(string $address): float
    {
        return hexdec($this->call('eth_getBalance', [$address, 'latest']))/(10**18);
    }

    public function getTokenBalance(string $contract, string $address): int
    {
        $signature = $this->getFunctionSignature('balanceOf(address)');
        return hexdec($this->call('eth_call', [[
            'to' => $contract,
            'data' => $signature.str_pad(substr($address, 2), 64, '0', STR_PAD_LEFT)
        ], 'latest']));
    }

    public function getCoinbase(): string
    {
        return $this->call('eth_coinbase');
    }

    public function sendEther(string $from, string $to, float $value)
    {
        return $this->call('eth_sendTransaction', [[
            'from' => $from,
            'to' => $to,
            'value' => '0x'.dechex(number_format($value*(10**18), 0, '.', ''))
        ]]);
    }

    public function transferToken(string $contract, string $from, string $to, int $value)
    {
        $signature = $this->getFunctionSignature('transfer(address,uint256)');
        $to = str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT);
        $value = str_pad(dechex($value), 64, '0', STR_PAD_LEFT);

        return $this->call('eth_sendTransaction', [[
            'from' => $from,
            'to' => $contract,
            'data' => $signature.$to.$value
        ]]);
    }

    public function unlockAccount(string $address, string $password, int $duration = 30)
    {
        return $this->call('personal_unlockAccount', [$address, $password], $duration);
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

    private function strhex(string $string): string
    {
        $hexstr = unpack('H*', $string);
        return array_shift($hexstr);
    }
}
