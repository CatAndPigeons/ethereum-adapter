<?php

namespace Daikon\Ethereum\Migration;

use Daikon\Dbal\Connector\ConnectorInterface;
use Daikon\Dbal\Exception\MigrationException;
use Daikon\Dbal\Migration\MigrationAdapterInterface;
use Daikon\Dbal\Migration\MigrationInterface;
use Daikon\Dbal\Migration\MigrationList;
use Daikon\Ethereum\Connector\EthereumRpcConnector;
use Daikon\Ethereum\Connector\EthereumRpcServiceTrait;

final class EthereumMigrationAdapter implements MigrationAdapterInterface
{
    use EthereumRpcServiceTrait;

    private $connector;

    private $settings;

    public function __construct(EthereumRpcConnector $connector, array $settings = [])
    {
        $this->connector = $connector;
        $this->settings = $settings;
    }

    public function read(string $identifier): MigrationList
    {
        $migrationListAddress = $this->getMigrationListAddress($identifier);
        if (!$migrationListAddress) {
            return new MigrationList;
        }
        $migrationEvents = $this->getMigrationEvents($migrationListAddress);
        return $this->createMigrationList($migrationEvents);
    }

    public function write(string $identifier, MigrationList $executedMigrations): void
    {
        $migrationListAddress = $this->getMigrationListAddress($identifier);
        if (!$migrationListAddress) {
            throw new MigrationException('MigrationList contract was not found for '.$identifier);
        }

        $migrationEvents = $this->getMigrationEvents($migrationListAddress);
        $completedMigrations = $this->createMigrationList($migrationEvents);
        $newMigrations = $executedMigrations->diff($completedMigrations);
        foreach ($newMigrations as $newMigration) {
            $this->addMigration($migrationListAddress, $newMigration);
        }
    }

    public function getConnector(): ConnectorInterface
    {
        return $this->connector;
    }

    private function getMigrationListAddress(string $identifier): ?string
    {
        $migrationList = end($this->call('eth_getLogs', [[
            'fromBlock' => '0x0',
            'toBlock' => 'latest',
            'topics'=> [
                $this->getSha3('MigrationListWasCreated(address,string)'),
                '0x'.str_pad(substr($this->settings['coinbase'], 2), 64, '0', STR_PAD_LEFT),
                $this->getSha3($identifier)
            ]
        ]]));

        return $migrationList['address'] ?? null;
    }

    private function getMigrationEvents(string $address): array
    {
        return $this->call('eth_getLogs', [[
            'address' => $address,
            'topics'=> [
                $this->getSha3('MigrationWasAdded(string)')
            ]
        ]]);
    }

    private function addMigration(string $address, MigrationInterface $migration)
    {
        $signature = $this->getFunctionSignature('addMigration(string)');
        $offset = str_pad(dechex(32), 64, '0', STR_PAD_LEFT);
        $packed = $this->strhex(serialize($migration->toArray()));
        $length = str_pad(dechex(strlen($packed)), 64, '0', STR_PAD_LEFT);
        return $this->call('eth_sendTransaction', [[
            'from' => $this->settings['coinbase'],
            'to' => $address,
            'data' => $signature.$offset.$length.$packed
        ]]);
    }

    private function createMigrationList(array $migrationEvents)
    {
        $migrations = [];
        foreach ($migrationEvents as $migrationEvent) {
            $migrationData = unserialize($this->hexstr(substr($migrationEvent['data'], 2+64+64)));
            $migrationClass = $migrationData['@type'];
            $migrations[] = new $migrationClass(new \DateTimeImmutable($migrationData['executedAt']));
        }
        return (new MigrationList($migrations))->sortByVersion();
    }
}
