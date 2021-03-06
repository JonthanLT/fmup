<?php
namespace FMUP;

/**
 * Class Cookie
 * @package FMUP
 */
class Cookie
{
    private static $instance;

    private function __construct()
    {
    }

    /**
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * Retrieve cookie system - start cookie if not started
     * @return Cookie
     */
    final public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = get_called_class();
            self::$instance = new $class;
        }
        return self::$instance;
    }

    /**
     * Check whether a specific information exists in cookie
     * @param string $name
     * @throws Exception if parameter is not a string
     * @return bool
     */
    public function has($name)
    {
        if (!is_string($name)) {
            throw new Exception('Parameter must be a string');
        }
        return isset($_COOKIE[$name]);
    }

    /**
     * Define a new cookie
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @throws Exception if parameter is not a string
     * @return $this
     */
    public function set($name, $value, $expire = 0, $path = "/", $domain = "", $secure = false, $httpOnly = false)
    {
        if (!is_string($name)) {
            throw new Exception('Parameter must be a string');
        }
        $time = time();
        if ($expire < $time) {
            $expire = $time + $expire;
        }
        $this->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    /**
     * Method that sends cookie - Unit test only
     * @codeCoverageIgnore
     * @param $name
     * @param $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool|false $secure
     * @param bool|false $httpOnly
     * @return bool
     */
    protected function setCookie(
        $name,
        $value,
        $expire = 0,
        $path = "/",
        $domain = "",
        $secure = false,
        $httpOnly = false
    ) {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Retrieve a specific Cookie value
     * @param string $name
     * @throws Exception if parameter is not a string
     * @return mixed
     */
    public function get($name)
    {
        return $this->has($name) ? $_COOKIE[$name] : null;
    }

    /**
     * Remove a specific Cookie
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @return $this
     */
    public function remove($name, $path = "/", $domain = "", $secure = false)
    {
        if ($this->has($name)) {
            $this->setCookie($name, "", time() - (3600 * 24), $path, $domain, $secure);
            unset($_COOKIE[$name]);
        }
        return $this;
    }

    /**
     * Remove all Cookies
     * @return $this
     */
    public function destroy()
    {
        foreach (array_keys($_COOKIE) as $cookie) {
            $this->remove($cookie);
        }
        return $this;
    }
}
