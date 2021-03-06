<?php
namespace FMUP\Import\Config\Field\Validator;

use FMUP\Import\Config\Field\Validator;

class Date implements Validator
{
    private $empty;

    public function __construct($empty = false)
    {
        $this->setCanEmpty($empty);
    }

    public function setCanEmpty($empty = false)
    {
        $this->empty = (bool)$empty;
        return $this;
    }

    public function canEmpty()
    {
        return (bool)$this->empty;
    }

    public function validate($value)
    {
        $valid = false;
        if (($this->canEmpty() && $value == '')
            || $this->isDate($value)
            || $this->isDateUk($value)
            || $this->isDateWithoutSeparator($value)
        ) {
            $valid = true;
        }
        return $valid;
    }

    /**
     * @param string $value
     * @return bool
     * @codeCoverageIgnore
     */
    protected function isDate($value)
    {
        return \Is::date($value);
    }

    /**
     * @param string $value
     * @return bool
     * @codeCoverageIgnore
     */
    protected function isDateUk($value)
    {
        return \Is::dateUk($value);
    }

    /**
     * @param string $value
     * @return bool
     * @codeCoverageIgnore
     */
    protected function isDateWithoutSeparator($value)
    {
        return \Is::dateWithoutSeparator($value);
    }

    public function getErrorMessage()
    {
        return "Le champ reçu n'est pas une date valide";
    }
}
