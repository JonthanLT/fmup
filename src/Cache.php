<?php
namespace FMUP;

use FMUP\Cache\Factory;

/**
 * Get the Cache instance
 * @package FMUP
 * @example
 */
class Cache
{
    /**
     * Cache instance
     * @var Cache
     */
    private static $instance = array();

    private $driver = Factory::DRIVER_RAM;
    private $cacheInstance = null;
    private $params = array();
    private $factory;

    /**
     * Multiton - private construct
     */
    private function __construct()
    {
    }

    /**
     * @param string $instanceKey
     * @return $this
     */
    final public static function getInstance($instanceKey)
    {
        if (!isset(self::$instance[$instanceKey])) {
            $class = get_called_class();
            /* @var $instance $this */
            $instance = new $class;
            self::$instance[$instanceKey] = $instance->setDriver($instanceKey);
        }
        return self::$instance[$instanceKey];
    }

    /**
     * @return Cache\CacheInterface
     * @throws Cache\Exception
     */
    final public function getCacheInstance()
    {
        if (!is_null($this->cacheInstance)) {
            return $this->cacheInstance;
        }

        $this->cacheInstance = $this->getFactory()->create($this->driver, $this->params);

        return $this->cacheInstance;
    }

    /**
     * Define a factory
     * @param Factory $factory
     * @return $this
     */
    public function setFactory(Factory $factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * Get factory instance
     * @return Factory
     */
    public function getFactory()
    {
        if (!$this->factory) {
            $this->factory = Factory::getInstance();
        }
        return $this->factory;
    }

    /**
     * set cacheInstance
     * @param \FMUP\Cache\CacheInterface $instance
     * @return \FMUP\Cache
     */
    public function setCacheInstance(Cache\CacheInterface $instance)
    {
        $this->cacheInstance = $instance;
        return $this;
    }

    /**
     * set driver
     * @param string $driver
     * @return \FMUP\Cache
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get defined driver
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * set params for construct \FMUP\Cache\CacheInterface
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = (array)$params;
        return $this;
    }

    /**
     * Driver settings
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * set a param in cache
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->getCacheInstance()->set((string)$key, $value);
        return $this;
    }

    /**
     * return value of a param
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        return $this->getCacheInstance()->get((string)$key);
    }

    /**
     * check param is set
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->getCacheInstance()->has((string)$key);
    }

    /**
     * remove param
     * @param string $key
     * @return $this
     */
    public function remove($key)
    {
        $this->getCacheInstance()->remove((string)$key);
        return $this;
    }
}
