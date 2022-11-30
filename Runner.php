<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use Chubbyphp\WorkermanRequestHandler\OnMessageInterface;
use Closure;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Workerman\Worker;

use function get_debug_type;
use function sprintf;

class Runner implements RunnerInterface
{
    public function __construct(
        private Closure $appFactory,
        private string $socket,
        private int $workers,
        private string $pidFile,
        private string $logFile,
    ) {
    }

    public function run(): int
    {
        $worker           = new Worker($this->socket);
        $worker->count    = $this->workers;
        $worker::$pidFile = $this->pidFile;
        $worker::$logFile = $this->logFile;

        $worker->onWorkerStart = function (Worker $worker): void {
            $kernel = ($this->appFactory)();

            if (! $kernel instanceof KernelInterface) {
                throw new InvalidArgumentException(sprintf('Expecting "%s" while given "%s".', KernelInterface::class, get_debug_type($kernel)));
            }

            $kernel->boot();
            $container = $kernel->getContainer();

            $worker->onMessage = $container->get(OnMessageInterface::class);
        };

        Worker::runAll();

        return 0;
    }
}
