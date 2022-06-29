<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use ReflectionFunction;
use Symfony\Component\Runtime\ResolverInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

class Runtime extends SymfonyRuntime
{
    private string $socket;
    private int $workers;

    public function __construct(array $options = [])
    {
        $this->socket  = $options['socket'] ?? 'http://0.0.0.0:' . ($_SERVER['PORT'] ?? $_ENV['PORT'] ?? 8080);
        $this->workers = $options['workers'] ?? 2;

        parent::__construct($options);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        return new Runner($application, $this->socket, $this->workers);
    }

    public function getResolver(callable $callable, ?ReflectionFunction $reflector = null): ResolverInterface
    {
        $resolver = parent::getResolver($callable, $reflector);

        return new Resolver($resolver);
    }
}
