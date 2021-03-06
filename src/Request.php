<?php
namespace FMUP;

/**
 * Class Request - handle request
 * @package FMUP
 */
abstract class Request
{
    /**
     * Get param defined in request. Can define a default value if not define
     * @param string $name param to retrieve
     * @param mixed $defaultValue (optional default value)
     * @return mixed
     */
    abstract public function get($name, $defaultValue = null);

    /**
     * Check if a param is defined in request
     * @param string $name
     * @return bool
     */
    abstract public function has($name);

    /**
     * Get string formatted request
     * @param bool $withQuerySting must get all queries in request? (optional default false)
     * @return string
     */
    abstract public function getRequestUri($withQuerySting = false);
}
