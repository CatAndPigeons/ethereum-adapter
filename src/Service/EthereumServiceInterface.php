<?php

namespace Daikon\Ethereum\Service;

interface EthereumServiceInterface
{
    public function call(string $method, array $parameters = []);

    public function createAccount(string $password): string;

    public function getEtherBalance(string $address): float;
}
