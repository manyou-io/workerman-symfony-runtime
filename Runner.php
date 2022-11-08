<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use Blackfire\Client;
use Chubbyphp\WorkermanRequestHandler\Adapter\BlackfireOnMessageAdapter;
use Chubbyphp\WorkermanRequestHandler\OnMessage;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactory;
use Chubbyphp\WorkermanRequestHandler\PsrRequestFactoryInterface;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitter;
use Chubbyphp\WorkermanRequestHandler\WorkermanResponseEmitterInterface;
use Closure;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Workerman\Worker;

use function extension_loaded;

class Runner implements RunnerInterface
{
    use LoggerAwareTrait;

    private HttpFoundationFactoryInterface $httpFoundationFactory;
    private HttpMessageFactoryInterface $httpMessageFactory;
    private Psr17Factory $psr17Factory;
    private PsrRequestFactoryInterface $psrRequestFactory;
    private WorkermanResponseEmitterInterface $workermanResponseEmitter;
    private ?Client $blackfireClient = null;

    public function setBlackfireClient(Client $blackfireClient): void
    {
        $this->blackfireClient = $blackfireClient;
    }

    public function __construct(
        private Closure $appFactory,
        private string $socket,
        private int $workers,
        private string $pidFile,
        private string $logFile,
    ) {
        $this->psr17Factory             = new Psr17Factory();
        $this->httpFoundationFactory    = new HttpFoundationFactory();
        $this->httpMessageFactory       = new ProperHeaderCasingResponseFactory($this->psr17Factory, $this->psr17Factory, $this->psr17Factory, $this->psr17Factory);
        $this->psrRequestFactory        = new PsrRequestFactory($this->psr17Factory, $this->psr17Factory, $this->psr17Factory);
        $this->workermanResponseEmitter = new WorkermanResponseEmitter();
    }

    public function run(): int
    {
        $worker           = new Worker($this->socket);
        $worker->count    = $this->workers;
        $worker::$pidFile = $this->pidFile;
        $worker::$logFile = $this->logFile;

        $worker->onWorkerStart = function (Worker $worker): void {
            // Instantiate HttpKernel
            $kernel = ($this->appFactory)();

            $psr15Handler = new SymfonyRequestHandler($kernel, $this->httpFoundationFactory, $this->httpMessageFactory);

            $onMessage = new OnMessage($this->psrRequestFactory, $this->workermanResponseEmitter, $psr15Handler);

            if (extension_loaded('blackfire') && $this->blackfireClient !== null) {
                $onMessage = new BlackfireOnMessageAdapter($onMessage, $this->blackfireClient, null, $this->logger);
            }

            $worker->onMessage = $onMessage;
        };

        Worker::runAll();

        return 0;
    }
}
