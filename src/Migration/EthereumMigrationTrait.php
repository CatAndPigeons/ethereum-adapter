<?php

namespace Daikon\Ethereum\Migration;

use Daikon\Dbal\Migration\MigrationTrait;
use Daikon\Ethereum\Connector\EthereumRpcServiceTrait;

trait EthereumMigrationTrait
{
    use MigrationTrait;

    use EthereumRpcServiceTrait;

    private function createMigrationsList(string $from, string $contractByteCode, string $namespace): string
    {
        $offset = str_pad(dechex(32), 64, '0', STR_PAD_LEFT);
        $length = str_pad(dechex(strlen($namespace)), 64, '0', STR_PAD_LEFT);
        $argument = str_pad($this->strhex($namespace), 64, '0');
        $payload = [
            'from' => $from,
            'data' => '0x'.$contractByteCode.$offset.$length.$argument,
            'gas' => '0x3d0900' //@todo proper gas estimation
        ];
        return $this->call('eth_sendTransaction', [$payload]);
    }

    private function deployContract(string $from, string $contractByteCode): string
    {
        $payload = [
            'from' => $from,
            'data' => '0x'.$contractByteCode,
            'gas' => '0x3d0900',
            'value' => '0x0' //required for empty constructor
        ];
        return $this->call('eth_sendTransaction', [$payload]);
    }
}
