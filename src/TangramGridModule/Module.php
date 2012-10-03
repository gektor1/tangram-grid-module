<?php

namespace TangramGridModule;

use Zend\Loader\AutoloaderFactory;
use Zend\Loader\StandardAutoloader;
use Zend\ModuleManager\Feature\ServiceProviderInterface;

class Module implements ServiceProviderInterface {

    public function getAutoloaderConfig() {
        return array(
            AutoloaderFactory::STANDARD_AUTOLOADER => array(
                StandardAutoloader::LOAD_NS => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @inheritdoc
     */
    public function getServiceConfig() {
        return array(
            'aliases' => array(
            ),
            'invokables' => array(
//                'TangramGridModule\Grid'             => 'TangramGridModule\Grid',
            ),
            'factories' => array(
//                'TangramGridModule\Grid' => function ($sm) {
//                    $a = new Grid();
//
//                    return $a;
//                }
            ),
        );
    }

}