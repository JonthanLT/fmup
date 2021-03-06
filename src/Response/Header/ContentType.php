<?php
namespace FMUP\Response\Header;

use FMUP\Response\Header;

class ContentType extends Header
{
    const TYPE = 'Content-Type';

    const MIME_TEXT_HTML = 'text/html';
    const MIME_TEXT_CSS = 'text/css';
    const MIME_APPLICATION_CSV = 'application/csv-tab-delimited-table';
    const MIME_APPLICATION_JS = 'application/javascript';
    const CHARSET_UTF_8 = 'utf-8';
    const MIME_APPLICATION_JSON = 'application/json';
    const MIME_APPLICATION_JWT = 'application/jwt';

    private $mime = self::MIME_TEXT_HTML;
    private $charset = self::CHARSET_UTF_8;

    /**
     * @param string $contentType
     * @param string $charset
     */
    public function __construct($contentType = self::MIME_TEXT_HTML, $charset = self::CHARSET_UTF_8)
    {
        $this->setMime($contentType)->setCharset($charset);
    }

    /**
     * @return string|null
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Define charset
     * @param string|null $charset
     * @return $this
     */
    public function setCharset($charset = null)
    {
        $this->charset = is_null($charset) ? null : (string)$charset;
        return $this;
    }

    /**
     * define the mime type of the document
     * @param string $mime
     * @return $this
     */
    public function setMime($mime)
    {
        $this->mime = (string)$mime;
        return $this;
    }

    /**
     * Mime type for the document to be rendered
     * @return string
     */
    public function getMime()
    {
        return (string)$this->mime;
    }

    /**
     * Value returned in the header
     * @return string
     */
    public function getValue()
    {
        return $this->getMime() . (!is_null($this->getCharset()) ? ';charset=' . $this->getCharset() : '');
    }

    /**
     * Type for the header. Can be used to determine header to send
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
