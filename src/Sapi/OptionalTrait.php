<?php
namespace FMUP\Sapi;

use FMUP\Sapi;

trait OptionalTrait
{
    private $sapi;

    /**
     * Define SAPI
     * @param Sapi|null $sapi
     * @return $this
     */
    public function setSapi(Sapi $sapi = null)
    {
        $this->sapi = $sapi;
        return $this;
    }

    /**
     * @return Sapi|null
     */
    public function getSapi()
    {
        if (!$this->sapi) {
            $this->sapi = Sapi::getInstance();
        }
        return $this->sapi;
    }

    /**
     * Checks whether SAPI is defined
     * @return bool
     */
    public function hasSapi()
    {
        return (bool)$this->sapi;
    }
}
