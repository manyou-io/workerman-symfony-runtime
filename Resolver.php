<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use Closure;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\ResolverInterface;

class Resolver implements ResolverInterface
{
    public function __construct(private ResolverInterface $resolver)
    {
    }

    public function resolve(): array
    {
        // Called in "autoload_runtime.php"
        [$app, $args] = $this->resolver->resolve();

        return [
            static function () use ($app, $args): Closure {
                // App instantiator as an app
                return static function () use ($app, $args): HttpKernelInterface {
                    return $app(...$args);
                };
            },
            [],
        ];
    }
}
