<?php

namespace Daikon\Ethereum\Connector;

use Daikon\Dbal\Connector\ConnectorInterface;
use Daikon\Dbal\Connector\ConnectorTrait;
use GuzzleHttp\Client;

final class EthereumRpcConnector implements ConnectorInterface
{
    use ConnectorTrait;

    private function connect()
    {
        $clientOptions = [
            'base_uri' => sprintf(
                '%s://%s:%s',
                $this->settings['scheme'],
                $this->settings['host'],
                $this->settings['port']
            )
        ];

        if (isset($this->settings['debug'])) {
            $clientOptions['debug'] = $this->settings['debug'] === true;
        }

        if (isset($this->settings['user']) && !empty($this->settings['user'])
            && isset($this->settings['password']) && !empty($this->settings['password'])
        ) {
            $clientOptions['auth'] = [
                $this->settings['user'],
                $this->settings['password'],
                $this->settings['authentication'] ?? 'basic'
            ];
        }

        $clientOptions['headers'] = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        return new Client($clientOptions);
    }
}
