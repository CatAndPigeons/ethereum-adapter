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

        if ($method === 'eth_sendTransaction') {
            do {
                /*
                 * Handling concurrency is difficult with Ethereum/Geth. Sending transactions without waiting for
                 * receipts is a recipe for losing your transactions, and nonces won't help you. This impl is not a
                 * scalable solution but is suitable for the purposes of a single node demo. Nonces based on
                 * transaction count could be used if you can be certain that getTransactionCount() is reliable, which
                 * it may not be because of concurrency limitations, esp. under load.
                 *
                 * A scalable solution would be to mirror the asynchronous architecture of the web3 backend by
                 * funneling all FE/BE events into a message queue for subsequent handling. The downside is that the
                 * UI and web2 bridge really has to be eventually consistent but that's relatively easy to design
                 * around CQRS/ES and a javascript/native UI.
                 */
                sleep(1);
                $txReceipt = $this->getTransactionReceipt($rawResponse['result']);
            } while (!$txReceipt);
            return $txReceipt;
        }

        return $rawResponse['result'];
    }

    public function unlockAccount(string $address, string $password, int $duration = 30): void
    {
        $this->call('personal_unlockAccount', [$address, $password, $duration]);
    }

    public function getTransactionReceipt(string $txHash): ?array
    {
        return $this->call('eth_getTransactionReceipt', [$txHash]);
    }

    private function getCoinbase(): string
    {
        return $this->call('eth_coinbase');
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

    // Here be Dragons...

    private function strhex(string $string): string
    {
        $hexstr = unpack('H*', $string);
        return array_shift($hexstr);
    }

    private function hexstr(string $string): string
    {
        return pack('H*', $string);
    }

    private function toWei(float $value, int $decimals = 18): string
    {
        $brokenNumber = explode('.', $value);
        return number_format($brokenNumber[0]).''.str_pad($brokenNumber[1] ?? '0', $decimals, '0');
    }

    private function bcdechex(string $dec): string
    {
        $hex = '';
        do {
            $last = bcmod($dec, 16);
            $hex = dechex($last).$hex;
            $dec = bcdiv(bcsub($dec, $last), 16);
        } while($dec > 0);
        return $hex;
    }
}
