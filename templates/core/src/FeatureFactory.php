<?php

declare(strict_types=1);

namespace Kaiseki\%namespace%;

use Psr\Container\ContainerInterface;

final class FeatureFactory
{
    public function __invoke(ContainerInterface $container): Feature
    {
        return new Feature();
    }
}
