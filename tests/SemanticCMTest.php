<?php
namespace Opine;

use PHPUnit_Framework_TestCase;
use Opine\Container\Service as Container;
use Opine\Config\Service as Config;

/**
 * @backupGlobals disabled
 */
class SemanticCMTest extends PHPUnit_Framework_TestCase
{
    private $route;
    private $managerRoute;
    private $managerModel;

    public function setup()
    {
        $root = __DIR__.'/../public';
        $config = new Config($root);
        $config->cacheSet();
        $container = Container::instance($root, $config, $root.'/../config/containers/test-container.yml');
        $this->route = $container->get('route');
        $this->managerRoute = $container->get('managerRoute');
        $this->managerModel = $container->get('managerModel');
        $this->route->testMode();
        $this->managerRoute->paths();
    }

    public function testBuild()
    {
        $cache = json_decode($this->managerModel->build(), true);
        echo "\n\n", 'TEST!', "\n\n";
        var_dump($cache);
        //$this->formModel->cacheSet($cache);
        //$this->assertTrue('contact' === $cache['contact']['name']);
    }

/*
    public function testApiManagersNoSession () {
        $_SESSION['user']['groups'] = [];
        $json = json_decode($this->route->run('GET', '/Manager/api/managers'), true);
        $this->assertTrue(count($json['managers']) == 0);
    }

    public function testApiManagersSession () {
        $_SESSION['user']['groups'] = ['manager'];
        $json = json_decode($this->route->run('GET', '/Manager/api/managers'), true);
        $this->assertTrue(count($json['managers']) > 0);
    }

    public function testHeader () {
        $response = $this->route->run('GET', '/Manager/header');
        $this->assertTrue(substr_count($response, '<strong>Manager:</strong>') > 0);
    }
*/
}
