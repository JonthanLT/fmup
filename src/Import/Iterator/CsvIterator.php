<?php
namespace FMUP\Import\Iterator;

use FMUP\Import\Exception;

/**
 * Permet de parcourir un csv ligne par ligne
 *
 * @author jyamin
 *
 */
class CsvIterator implements \Iterator
{
    const COMMA_SEPARATOR = ',';
    const SEMICOLON_SEPARATOR = ';';
    const TABULATION_SEPARATOR = "\t";

    protected $path;
    private $fHandle;
    private $current;
    private $line;
    private $separator;

    /**
     * @param string $path
     * @param string $separator optional (if null, autodetect)
     */
    public function __construct($path = "", $separator = self::SEMICOLON_SEPARATOR)
    {
        $this->setPath($path)->setSeparator($separator);
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = (string)$path;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * @throws Exception
     */
    public function rewind()
    {
        if (!file_exists($this->path)) {
            throw new Exception("Le fichier specifie n'existe pas ou est introuvable");
        }
        $this->fHandle = fopen($this->path, "r");
        rewind($this->fHandle);
        $this->next();
        $this->line = 0;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->current;
    }

    public function next()
    {
        if (feof($this->fHandle)) {
            fclose($this->fHandle);
            $this->current = null;
        } else {
            $this->current = fgetcsv($this->fHandle, 0, $this->getSeparator());
        }
        $this->line++;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return !is_null($this->current());
    }

    /**
     * Get separator
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * Set separator
     * @param string $separator
     * @return self
     */
    public function setSeparator($separator)
    {
        $this->separator = (string)$separator;
        return $this;
    }

    public function key()
    {
        return $this->line;
    }
}
