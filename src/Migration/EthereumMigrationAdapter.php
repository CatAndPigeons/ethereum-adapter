<?php

namespace Daikon\Ethereum\Migration;

use Daikon\Dbal\Connector\ConnectorInterface;
use Daikon\Dbal\Migration\MigrationAdapterInterface;
use Daikon\Dbal\Migration\MigrationList;
use Daikon\Ethereum\Connector\EthereumRpcConnector;
use Daikon\Ethereum\Exception\EthereumException;

final class EthereumMigrationAdapter implements MigrationAdapterInterface
{
    private $connector;

    private $settings;

    public function __construct(EthereumRpcConnector $connector, array $settings = [])
    {
        $this->connector = $connector;
        $this->settings = $settings;
    }

    public function read(string $identifier): MigrationList
    {
        $migrationsAddress = $this->getMigrations($identifier)['address'];
        $currentVersion = $this->getCurrentVersion($migrationsAddress);
        if (!$currentVersion) {
            return new MigrationList;
        }
        return new MigrationList;
    }

    public function write(string $identifier, MigrationList $executedMigrations): void
    {
        $migrationsAddress = $this->getMigrations($identifier)['address'];
        $transactionHash = $this->updateMigrationsVersion(
            $migrationsAddress,
            $executedMigrations->getLast()->getVersion()
        );
    }

    public function getConnector(): ConnectorInterface
    {
        return $this->connector;
    }

    private function call(string $method, array $parameters = [])
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

    private function getMigrations(string $identifier)
    {
        return end($this->call('eth_getLogs', [[
            'fromBlock' => '0x0',
            'toBlock' => 'latest',
            'topics'=> [
                $this->getSha3('Migration(address,string,uint256)'),
                '0x'.str_pad(substr($this->settings['coinbase'], 2), 64, '0', STR_PAD_LEFT),
                $this->getSha3($identifier)
            ]
        ]]));
    }

    private function updateMigrationsVersion(string $address, int $version)
    {
        $signature = $this->getFunctionSignature('setCompleted(uint256)');
        $version = str_pad(dechex($version), 64, '0', STR_PAD_LEFT);

        return $this->call('eth_sendTransaction', [[
            'from' => $this->settings['coinbase'],
            'to' => $address,
            'data' => $signature.$version
        ]]);
    }

    private function getCurrentVersion(string $address)
    {
        $signature = $this->getFunctionSignature('version()');
        return hexdec($this->call('eth_call', [[
            'to' => $address,
            'data' => $signature
        ], 'latest']));
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
