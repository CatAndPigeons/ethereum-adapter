<?php

namespace Daikon\Ethereum\Service;

interface EthereumServiceInterface
{
    public function call(string $method, array $parameters = []);

    public function unlockAccount(string $address, string $password, int $duration = 30): void;

    public function createAccount(string $password): string;

    public function getEtherBalance(string $address): float;

    public function getTokenBalance(string $contract, string $address): float;

    public function sendEther(string $from, string $to, float $value): array;

    public function getTransactionReceipt(string $txHash): ?array;

    public function transferToken(string $tokenContract, string $from, string $to, float $value): array;

    public function approveTransfer(string $tokenContract, string $from, string $spender, float $value): array;

}
