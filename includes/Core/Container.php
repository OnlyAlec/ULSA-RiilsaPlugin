<?php

declare(strict_types=1);

/**
 * Dependency Injection Container
 *
 * @package RIILSA\Core
 * @since 3.1.0
 */

namespace RIILSA\Core;

use DI\ContainerBuilder;
use DI\Container as DIContainer;
use Psr\Container\ContainerInterface;
use RIILSA\Infrastructure\Services\BrevoMailService;
use RIILSA\Infrastructure\Services\PhpSpreadsheetExcelService;
use RIILSA\Infrastructure\Services\MJMLTemplateService;
use RIILSA\Infrastructure\Repositories\WordPressProjectRepository;
use RIILSA\Infrastructure\Repositories\WordPressNewsRepository;
use RIILSA\Infrastructure\Repositories\WordPressCallRepository;
use RIILSA\Infrastructure\Repositories\DatabaseNewsletterRepository;
use RIILSA\Infrastructure\Repositories\DatabaseSubscriberRepository;
use RIILSA\Domain\Repositories\ProjectRepositoryInterface;
use RIILSA\Domain\Repositories\NewsRepositoryInterface;
use RIILSA\Domain\Repositories\CallRepositoryInterface;
use RIILSA\Domain\Repositories\NewsletterRepositoryInterface;
use RIILSA\Domain\Repositories\SubscriberRepositoryInterface;

/**
 * Singleton container implementation using PHP-DI
 * 
 * Pattern: Singleton Pattern
 * This class provides a centralized dependency injection container
 * for the entire plugin using the PHP-DI library.
 */
class Container implements ContainerInterface
{
    /**
     * The singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * The PHP-DI container instance
     *
     * @var DIContainer
     */
    private DIContainer $container;

    /**
     * Private constructor to prevent direct instantiation
     * 
     * Pattern: Singleton Pattern - Private Constructor
     */
    private function __construct()
    {
        $this->initializeContainer();
    }

    /**
     * Get the singleton instance of the container
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Initialize and configure the PHP-DI container
     *
     * @return void
     */
    private function initializeContainer(): void
    {
        $builder = new ContainerBuilder();

        // Enable compilation in production
        if (!isDebugMode()) {
            $uploadDir = wp_upload_dir();
            $compilationPath = $uploadDir['basedir'] . '/riilsa-cache';
            
            if (!file_exists($compilationPath)) {
                wp_mkdir_p($compilationPath);
            }
            
            $builder->enableCompilation($compilationPath);
            $builder->writeProxiesToFile(true, $compilationPath . '/proxies');
        }

        // Enable attributes for dependency injection
        $builder->useAttributes(true);
        $builder->useAutowiring(true);

        // Add definitions
        $builder->addDefinitions($this->getDefinitions());

        // Build the container
        $this->container = $builder->build();
    }

    /**
     * Get container definitions
     *
     * @return array
     */
    private function getDefinitions(): array
    {
        return [
            // Repository bindings
            ProjectRepositoryInterface::class => \DI\autowire(WordPressProjectRepository::class),
            NewsRepositoryInterface::class => \DI\autowire(WordPressNewsRepository::class),
            CallRepositoryInterface::class => \DI\autowire(WordPressCallRepository::class),
            NewsletterRepositoryInterface::class => \DI\autowire(DatabaseNewsletterRepository::class),
            SubscriberRepositoryInterface::class => \DI\autowire(DatabaseSubscriberRepository::class),

            // Service bindings
            'mail_service' => \DI\autowire(BrevoMailService::class),
            'excel_service' => \DI\autowire(PhpSpreadsheetExcelService::class),
            'template_service' => \DI\autowire(MJMLTemplateService::class),

            // WordPress specific
            'wpdb' => \DI\factory(function () {
                global $wpdb;
                return $wpdb;
            }),
            
            // Plugin paths
            'plugin_dir' => RIILSA_PLUGIN_DIR,
            'plugin_url' => RIILSA_PLUGIN_URL,
            'plugin_version' => RIILSA_VERSION,
        ];
    }

    /**
     * Get a service from the container
     *
     * @template T
     * @param class-string<T>|string $id The service identifier
     * @return T|mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Check if a service exists in the container
     *
     * @param string $id The service identifier
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Call a method with dependency injection
     *
     * @param callable|array $callable The method to call
     * @param array $parameters Additional parameters
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function call(callable|array $callable, array $parameters = []): mixed
    {
        return $this->container->call($callable, $parameters);
    }

    /**
     * Make a new instance with dependency injection
     *
     * @template T
     * @param class-string<T> $className
     * @param array $parameters Additional parameters
     * @return T
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function make(string $className, array $parameters = []): object
    {
        return $this->container->make($className, $parameters);
    }

    /**
     * Inject dependencies into an existing object
     *
     * @param object $object The object to inject into
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function injectOn(object $object): void
    {
        $this->container->injectOn($object);
    }

    /**
     * Prevent cloning of the singleton instance
     *
     * @return void
     */
    private function __clone()
    {
        // Prevent cloning
    }

    /**
     * Prevent unserialization of the singleton instance
     *
     * @return void
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
