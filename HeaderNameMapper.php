<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HeaderNameMapper implements HttpMessageFactoryInterface
{
    public function __construct(
        private HttpMessageFactoryInterface $httpMessageFactory,
        private array $mappings = [],
    ) {
    }

    public function createRequest(Request $symfonyRequest): ServerRequestInterface
    {
        return $this->httpMessageFactory->createRequest($symfonyRequest);
    }

    public function createResponse(Response $symfonyResponse): ResponseInterface
    {
        $response = $this->httpMessageFactory->createResponse($symfonyResponse);
        $headers  = $response->getHeaders();

        foreach ($headers as $key => $value) {
            if ($name = $this->mappings[$key] ?? false) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
