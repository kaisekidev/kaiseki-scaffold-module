<?php

declare(strict_types=1);

namespace Kaiseki\WordPress\%namespace%;

final class ConfigProvider
{
    /**
     * @return array<mixed>
     */
    public function __invoke(): array
    {
        return [
            '%config_base_key%' => [
                'feature_notice' => '%package_name_dash%',
            ],
            'hook' => [
                'provider' => [
                    FeatureName::class,
                ],
            ],
            'dependencies' => [
                'aliases' => [],
                'factories' => [
                    FeatureName::class => FeatureNameFactory::class,
                ],
            ],
        ];
    }
}
