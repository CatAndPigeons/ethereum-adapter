<?php

namespace Daikon\Ethereum\Migration;

use Daikon\Dbal\Migration\MigrationTrait;
use Daikon\Ethereum\Exception\EthereumException;

trait EthereumMigrationTrait
{
    use MigrationTrait;

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

    private function getCoinbase(): string
    {
        return $this->call('eth_coinbase');
    }

    private function unlockAccount(string $address, string $password, int $duration = 30): void
    {
        $this->call('personal_unlockAccount', [$address, $password, $duration]);
    }

    private function createMigrationsContract(string $from, string $contractByteCode, string $namespace)
    {
        $offset = str_pad(dechex(32), 64, '0', STR_PAD_LEFT);
        $length = str_pad(dechex(strlen($namespace)), 64, '0', STR_PAD_LEFT);
        $argument = str_pad($this->strhex($namespace), 64, '0');
        $payload = [
            'from' => $from,
            'data' => '0x'.$contractByteCode.$offset.$length.$argument,
        ];
        // @todo fix gas estimation - currently 0x47e7c4 is the max
        $payload['gas'] = '0xf4240'; //$this->estimateGas($payload);
        return $this->call('eth_sendTransaction', [$payload]);
    }

    private function getTransactionReceipt(string $transactionHash): array
    {
        return $this->call('eth_getTransactionReceipt', [$transactionHash]);
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
}
