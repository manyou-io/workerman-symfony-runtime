<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Chubbyphp\WorkermanRequestHandler\OnMessage;
use Chubbyphp\WorkermanRequestHandler\OnMessageInterface;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactoryInterface;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitter;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitterInterface;
use Manyou\WorkermanSymfonyRuntime\HeaderNameMapper;
use Manyou\WorkermanSymfonyRuntime\SymfonyRequestHandler;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set('workerman_symfony_runtime.header_name_mappings', [
        'server' => 'Server',
        'connection' => 'Connection',
        'content-type' => 'Content-Type',
        'content-disposition' => 'Content-Disposition',
        'last-modified' => 'Last-Modified',
        'transfer-encoding' => 'Transfer-Encoding',
    ]);

    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autowire();

    // Symfony HttpFoundation to PSR
    $services->set(HttpMessageFactoryInterface::class, PsrHttpFactory::class);
    $services->set(HttpFoundationFactoryInterface::class, HttpFoundationFactory::class);

    $services->set(HeaderNameMapper::class)
        ->decorate(HttpMessageFactoryInterface::class)
        ->args([
            service('.inner'),
            param('workerman_symfony_runtime.header_name_mappings'),
        ]);

    // Workerman Request to PSR Request
    $services->set(PsrRequestFactoryInterface::class, PsrRequestFactory::class);

    $services->set(WorkermanResponseEmitterInterface::class, WorkermanResponseEmitter::class);

    $services->set(SymfonyRequestHandler::class)
        ->arg(HttpKernelInterface::class, service('kernel'));

    $services->set(OnMessageInterface::class, OnMessage::class)
        ->arg(RequestHandlerInterface::class, service(SymfonyRequestHandler::class))
        ->public();
};
