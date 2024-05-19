<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use Chubbyphp\WorkermanRequestHandler\OnMessageInterface;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Workerman\Connection\TcpConnection as WorkermanTcpConnection;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;

use function explode;
use function in_array;
use function strtolower;

final class OnMessage implements OnMessageInterface
{
    public function __construct(
        private PsrRequestFactoryInterface $psrRequestFactory,
        private RequestHandlerInterface $requestHander,
    ) {
    }

    public function __invoke(WorkermanTcpConnection $workermanTcpConnection, WorkermanRequest $workermanRequest): void
    {
        $response = $this->requestHander->handle(
            $this->psrRequestFactory->create($workermanTcpConnection, $workermanRequest),
        );

        $workermanTcpConnection->send(
            (new WorkermanResponse())
                ->withStatus($response->getStatusCode(), $response->getReasonPhrase())
                ->withHeaders($response->getHeaders())
                ->withBody((string) $response->getBody()),
        );

        $keepAlive = in_array('keep-alive', explode(',', strtolower($workermanRequest->header('connection', 'close'))), true);
        if (! $keepAlive) {
            $workermanTcpConnection->close();
        }
    }
}
