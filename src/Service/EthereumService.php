<?php

namespace Daikon\Ethereum\Service;

use Daikon\Ethereum\Connector\EthereumRpcConnector;
use Daikon\Ethereum\Connector\EthereumRpcServiceTrait;

final class EthereumService implements EthereumServiceInterface
{
    use EthereumRpcServiceTrait;

    private $connector;

    public function __construct(EthereumRpcConnector $connector)
    {
        $this->connector = $connector;
    }

    public function createAccount(string $password): string
    {
        return $this->call('personal_newAccount', [$password]);
    }

    public function getEtherBalance(string $address): float
    {
        return hexdec($this->call('eth_getBalance', [$address, 'latest']))/(10**18);
    }

    public function getTokenBalance(string $contract, string $address): float
    {
        $signature = $this->getFunctionSignature('balanceOf(address)');
        return hexdec($this->call('eth_call', [[
            'to' => $contract,
            'data' => $signature.str_pad(substr($address, 2), 64, '0', STR_PAD_LEFT)
        ], 'latest']))/(10**18);
    }

    public function sendEther(string $from, string $to, float $value): string
    {
        return $this->call('eth_sendTransaction', [[
            'from' => $from,
            'to' => $to,
            'value' => '0x'.$this->bcdechex($this->toWei($value))
        ]]);
    }

    public function transferToken(string $tokenContract, string $from, string $to, float $value): string
    {
        $signature = $this->getFunctionSignature('transfer(address,uint256)');
        $to = str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT);
        $value = str_pad($this->bcdechex($this->toWei($value)), 64, '0', STR_PAD_LEFT);
        return $this->call('eth_sendTransaction', [[
            'from' => $from,
            'to' => $tokenContract,
            'data' => $signature.$to.$value
        ]]);
    }

    public function approveTransfer(string $tokenContract, string $from, string $spender, float $value): string
    {
        $signature = $this->getFunctionSignature('approve(address,uint256)');
        $spender = str_pad(substr($spender, 2), 64, '0', STR_PAD_LEFT);
        $value = str_pad($this->bcdechex($this->toWei($value)), 64, '0', STR_PAD_LEFT);

        return $this->call('eth_sendTransaction', [[
            'from' => $from,
            'to' => $tokenContract,
            'data' => $signature.$spender.$value
        ]]);
    }
}
