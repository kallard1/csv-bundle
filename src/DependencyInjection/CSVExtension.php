<?php

declare(strict_types=1);

namespace Dnd\Bundle\CSVBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class CSVExtension
 *
 * @package   Dnd\Bundle\CSVBundle\DependencyInjection
 * @author    Area42 <contact@area42.fr>
 * @copyright 2020-present Area42
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.area42.fr/
 */
class CSVExtension extends Extension
{
    /**
     * Description load function
     *
     * @param mixed[]          $configs
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var YamlFileLoader $loader */
        $loader = new YamlFileLoader(
            $container, new FileLocator(__DIR__ . '/../Resources/config')
        );

        if (class_exists(Application::class)) {
            $loader->load('console.yaml');
        }
    }
}