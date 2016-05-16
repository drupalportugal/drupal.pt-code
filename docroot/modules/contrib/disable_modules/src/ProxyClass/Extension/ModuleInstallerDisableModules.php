<?php

/**
 * @file
 * Contains \Drupal\disable_modules\ProxyClass\Extension\ModuleInstallerDisableModules.
 */

namespace Drupal\disable_modules\ProxyClass\Extension {

    /**
     * Provides a proxy class for \Drupal\disable_modules\Extension\ModuleInstallerDisableModules.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class ModuleInstallerDisableModules implements \Drupal\Core\Extension\ModuleInstallerInterface
    {

        use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

        /**
         * The id of the original proxied service.
         *
         * @var string
         */
        protected $drupalProxyOriginalServiceId;

        /**
         * The real proxied service, after it was lazy loaded.
         *
         * @var \Drupal\disable_modules\Extension\ModuleInstallerDisableModules
         */
        protected $service;

        /**
         * The service container.
         *
         * @var \Symfony\Component\DependencyInjection\ContainerInterface
         */
        protected $container;

        /**
         * Constructs a ProxyClass Drupal proxy object.
         *
         * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
         *   The container.
         * @param string $drupal_proxy_original_service_id
         *   The service ID of the original service.
         */
        public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
        {
            $this->container = $container;
            $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
        }

        /**
         * Lazy loads the real service from the container.
         *
         * @return object
         *   Returns the constructed real service.
         */
        protected function lazyLoadItself()
        {
            if (!isset($this->service)) {
                $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
            }

            return $this->service;
        }

        /**
         * {@inheritdoc}
         */
        public function install(array $module_list, $enable_dependencies = true)
        {
            return $this->lazyLoadItself()->install($module_list, $enable_dependencies);
        }

        /**
         * {@inheritdoc}
         */
        public function uninstall(array $module_list, $uninstall_dependents = true)
        {
            return $this->lazyLoadItself()->uninstall($module_list, $uninstall_dependents);
        }

        /**
         * {@inheritdoc}
         */
        public function addUninstallValidator(\Drupal\Core\Extension\ModuleUninstallValidatorInterface $uninstall_validator)
        {
            return $this->lazyLoadItself()->addUninstallValidator($uninstall_validator);
        }

        /**
         * {@inheritdoc}
         */
        public function validateUninstall(array $module_list)
        {
            return $this->lazyLoadItself()->validateUninstall($module_list);
        }

    }

}
