<?php
namespace FMUP\Crypt;

interface CryptInterface
{
    /**
     * Hash the given string
     * @param string $string
     * @return string
     */
    public function hash($string);

    /**
     * UnHash the given string
     * @param string $string
     * @return string
     */
    public function unHash($string);
}
