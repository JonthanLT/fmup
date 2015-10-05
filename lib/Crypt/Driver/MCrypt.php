<?php

namespace FMUP\Crypt\Driver;

/**
 * Description of MCrypt
 *
 * @author sweffling
 */
use \FMUP\Crypt\CryptInterface;

class MCrypt implements CryptInterface
{
    const KEY = 'secret_test_key';

    private $key = null;
    private $iv = null;

    /**
     * 
     * @return string
     */
    private function getKey()
    {
        if (!$this->key) {
            $this->key = self::KEY;
        }
        return $this->key;
    }
    
    /**
     * 
     * @param strinh $key
     * @return \FMUP\Crypt\Driver\MCrypt
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    public function getIv()
    {
        if (!$this->iv) {
            $this->iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
        }
        return $this->iv;
    }

    /**
     * Hash the given password
     * @param string $password
     * @return string 
     */
    public function hash($password)
    {
        return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->getKey(), $password, MCRYPT_MODE_ECB, $this->getIv());
    }

    /**
     * 
     * @param type $password
     * @return type
     */
    public function unHash($password)
    {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->getKey(), $password, MCRYPT_MODE_ECB, $this->getIv());
    }

}
