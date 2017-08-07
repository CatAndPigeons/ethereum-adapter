<?php

namespace Daikon\Ethereum\Service;

interface EthereumServiceInterface
{
    public function call(string $method, array $parameters = []);

    public function unlockAccount(string $address, string $password, int $duration = 30): void;

    public function createAccount(string $password): string;

    public function getEtherBalance(string $address): float;

    public function getTokenBalance(string $contract, string $address): int;

    public function sendEther(string $from, string $to, float $value): string;

    public function getTransactionReceipt(string $txHash): ?array;

    public function transferToken(string $tokenContract, string $from, string $to, int $value): string;

    public function approveTransfer(string $tokenContract, string $from, string $spender, int $value): string;

}
