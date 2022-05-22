<?php

declare(strict_types=1);

namespace Rector\Symfony\Bridge\Symfony\Routing;

use Rector\Symfony\Bridge\Symfony\ContainerServiceProvider;
use Rector\Symfony\Contract\Bridge\Symfony\Routing\SymfonyRoutesProviderInterface;
use Rector\Symfony\ValueObject\SymfonyRouteMetadata;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class SymfonyRoutesProvider implements SymfonyRoutesProviderInterface
{
    /**
     * @var SymfonyRouteMetadata[]
     */
    private array $symfonyRouteMetadatas = [];

    public function __construct(
        private readonly ContainerServiceProvider $containerServiceProvider
    ) {
    }

    /**
     * @return SymfonyRouteMetadata[]
     */
    public function provide(): array
    {
        if ($this->symfonyRouteMetadatas !== []) {
            return $this->symfonyRouteMetadatas;
        }

        $router = $this->containerServiceProvider->provideByName('router');
        Assert::isInstanceOf($router, 'Symfony\Component\Routing\RouterInterface');

        $symfonyRoutesMetadatas = [];

        /** @var RouterInterface $router */
        $routeCollection = $router->getRouteCollection();

        // route name is hidden in the key - https://github.com/symfony/symfony/blob/4dde1619d6c65b662170a6a3cbbdc7092eeb1fa2/src/Symfony/Component/Routing/RouteCollection.php#L99
        foreach ($routeCollection->all() as $routeName => $route) {
            $symfonyRoutesMetadatas[] = new SymfonyRouteMetadata(
                $routeName,
                $route->getPath(),
                $route->getDefaults(),
                $route->getRequirements(),
                $route->getHost(),
                $route->getSchemes(),
                $route->getMethods(),
                $route->getCondition()
            );
        }

        $this->symfonyRouteMetadatas = $symfonyRoutesMetadatas;

        return $symfonyRoutesMetadatas;
    }

    /**
     * @param string $classMethodReference Format <class>::<method>
     */
    public function getRouteByClassMethodReference(string $classMethodReference): ?SymfonyRouteMetadata
    {
        foreach ($this->symfonyRouteMetadatas as $symfonyRouteMetadata) {
            if ($symfonyRouteMetadata->getDefault('_controller') === $classMethodReference) {
                return $symfonyRouteMetadata;
            }
        }

        return null;
    }
}