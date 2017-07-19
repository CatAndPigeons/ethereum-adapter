<?php

namespace Daikon\Ethereum\Service;

interface EthereumServiceInterface
{
    public function call(string $method, array $parameters = []): array;
}
