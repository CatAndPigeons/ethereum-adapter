<?php

namespace Daikon\Ethereum\Service;

interface EthereumServiceInterface
{
    public function unlockAccount(string $address, string $password, int $duration = 30): void;

    public function createAccount(string $password): string;

    public function getEtherBalance(string $address): float;

    public function getTokenBalance(string $contract, string $address): int;

    public function sendEther(string $from, string $to, float $value): string;

    public function transferToken(string $tokenContract, string $from, string $to, int $value): string;
}
