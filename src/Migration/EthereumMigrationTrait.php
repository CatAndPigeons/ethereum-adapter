<?php

namespace Daikon\Ethereum\Migration;

use Daikon\Dbal\Migration\MigrationTrait;
use Daikon\Ethereum\Connector\EthereumRpcServiceTrait;

trait EthereumMigrationTrait
{
    use MigrationTrait;

    use EthereumRpcServiceTrait;

    private function createMigrationsList(string $from, string $contractByteCode, string $namespace)
    {
        $offset = str_pad(dechex(32), 64, '0', STR_PAD_LEFT);
        $length = str_pad(dechex(strlen($namespace)), 64, '0', STR_PAD_LEFT);
        $argument = str_pad($this->strhex($namespace), 64, '0');
        $payload = [
            'from' => $from,
            'data' => '0x'.$contractByteCode.$offset.$length.$argument,
        ];
        // @todo fix gas estimation
        $payload['gas'] = '0xf4240'; //$this->estimateGas($payload);
        return $this->call('eth_sendTransaction', [$payload]);
    }
}
