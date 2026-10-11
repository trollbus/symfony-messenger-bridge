<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Trollbus\SymfonyMessengerBridge\DependencyInjection\AsyncHandlerPass;

final class TrollbusSymfonyMessengerBridgeBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AsyncHandlerPass());
    }
}
