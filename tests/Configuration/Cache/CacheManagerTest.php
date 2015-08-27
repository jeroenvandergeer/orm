<?php

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Tools\Setup;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use LaravelDoctrine\ORM\Configuration\Cache\ArrayCacheProvider;
use LaravelDoctrine\ORM\Configuration\Cache\CacheManager;
use LaravelDoctrine\ORM\Configuration\Cache\FileCacheProvider;
use LaravelDoctrine\ORM\Exceptions\DriverNotFound;
use Mockery as m;

class CacheManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CacheManager
     */
    protected $manager;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Repository
     */
    protected $config;

    protected function setUp()
    {
        $this->app = m::mock(Application::class);
        $this->app->shouldReceive('make')->andReturn(m::self());
        $this->app->shouldReceive('get')->with('doctrine.cache.default', 'array')->andReturn('array');

        $this->manager = new CacheManager(
            $this->app
        );
    }

    public function test_driver_returns_the_default_driver()
    {
        $this->app->shouldReceive('resolve')->andReturn(new ArrayCacheProvider());

        $this->assertInstanceOf(ArrayCacheProvider::class, $this->manager->driver());
        $this->assertInstanceOf(ArrayCache::class, $this->manager->driver()->resolve());
    }

    public function test_driver_can_return_a_given_driver()
    {
        $config = m::mock(Repository::class);

        $this->app->shouldReceive('resolve')->andReturn(new FileCacheProvider(
            $config
        ));

        $this->assertInstanceOf(FileCacheProvider::class, $this->manager->driver());
    }

    public function test_cant_resolve_unsupported_drivers()
    {
        $this->setExpectedException(DriverNotFound::class);
        $this->manager->driver('non-existing');
    }

    public function test_can_create_custom_drivers()
    {
        $this->manager->extend('new', function () {
            return 'provider';
        });

        $this->assertEquals('provider', $this->manager->driver('new'));
    }

    public function test_can_use_application_when_extending()
    {
        $this->manager->extend('new', function ($app) {
            $this->assertInstanceOf(Application::class, $app);
        });
    }

    public function test_can_replace_an_existing_driver()
    {
        $this->manager->extend('memcache', function () {
            return 'provider';
        });

        $this->assertEquals('provider', $this->manager->driver('memcache'));
    }
}
