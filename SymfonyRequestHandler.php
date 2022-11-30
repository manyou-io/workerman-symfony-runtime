<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Workerman\Timer;

class SymfonyRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private HttpKernelInterface $kernel,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private HttpMessageFactoryInterface $httpMessageFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $sfRequest  = $this->httpFoundationFactory->createRequest($request);
        $sfResponse = $this->kernel->handle($sfRequest);
        $response   = $this->httpMessageFactory->createResponse($sfResponse);

        if ($this->kernel instanceof TerminableInterface) {
            $kernel = $this->kernel;
            Timer::add(1, static function () use ($kernel, $sfRequest, $sfResponse) {
                $kernel->terminate($sfRequest, $sfResponse);
            }, persistent: false);
        }

        return $response;
    }
}
