<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use ReflectionFunction;
use Symfony\Component\Runtime\ResolverInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

use function md5;

class Runtime extends SymfonyRuntime
{
    private string $socket;
    private int $workers;
    private string $pidFile;
    private string $logFile;

    public function __construct(array $options = [])
    {
        $this->socket  = $options['socket'] ?? 'http://0.0.0.0:' . ($_SERVER['PORT'] ?? $_ENV['PORT'] ?? 8283);
        $this->workers = isset($options['workers']) ? (int) $options['workers'] : 1;

        $hash          = md5(__FILE__);
        $this->pidFile = $options['pid_file'] ?? "/tmp/workerman-$hash.pid";
        $this->logFile = $options['log_file'] ?? "/tmp/workerman-$hash.log";

        parent::__construct($options);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        return new Runner($application, $this->socket, $this->workers, $this->pidFile, $this->logFile);
    }

    public function getResolver(callable $callable, ?ReflectionFunction $reflector = null): ResolverInterface
    {
        $resolver = parent::getResolver($callable, $reflector);

        return new Resolver($resolver);
    }
}
