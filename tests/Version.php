<?php
/**
 * Version.php
 * @author: jmoulin@castelis.com
 */

namespace Tests;

class VersionMock extends \FMUP\Version
{
    public function __construct()
    {

    }
}

class VersionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetInstance()
    {
        $reflector = new \ReflectionClass('\FMUP\Version');
        $method = $reflector->getMethod('__construct');
        $this->assertTrue($method->isPrivate(), 'Construct must be private');
        try {
            $reflector->getMethod('__clone')->invoke(\FMUP\Version::getInstance());
            $this->fail('Clone must fail');
        } catch (\ReflectionException $e) {
            $this->assertEquals(
                'Trying to invoke private method FMUP\Version::__clone() from scope ReflectionMethod',
                $e->getMessage()
            );
        }

        $version = \FMUP\Version::getInstance();
        $this->assertInstanceOf('\FMUP\Version', $version);
        $version2 = \FMUP\Version::getInstance();
        $this->assertSame($version, $version2);
    }

    public function testGet()
    {
        $file = implode(DIRECTORY_SEPARATOR, array(__DIR__ , '..' , '..', '..' , '..', 'composer.lock'));
        if (!file_exists($file)) {
            $this->fail('File composer.lock do not exists');
        }
        $version = json_decode(file_get_contents($file));
        if (!$version) {
            $this->fail('File composer.lock is not correct');
        }
        if (!isset($version->version)) {
            $this->fail('File composer.lock is not correct - need version');
        }
        $this->assertSame($version->version, \FMUP\Version::getInstance()->get());
    }

    public function testGetWhenFilePathFails()
    {
        $version = $this->getMockBuilder('\Tests\VersionMock')->setMethods(array('getComposerPath'))->getMock();
        $version->method('getComposerPath')->willReturn('/unexistingFile');

        $reflection = new \ReflectionProperty('\FMUP\Version', 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue(\FMUP\Version::getInstance(), $version);

        $this->expectException('\FMUP\Exception');
        $this->expectExceptionMessage('composer.lock does not exist');
        /** @var $version \FMUP\Version */
        $version->get();
    }

    public function testGetWhenFileIsNotValid()
    {
        $version = $this->getMockBuilder('\Tests\VersionMock')->setMethods(array('getComposerPath'))->getMock();
        $version->method('getComposerPath')->willReturn(__FILE__);

        $reflection = new \ReflectionProperty('\FMUP\Version', 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue(\FMUP\Version::getInstance(), $version);

        $this->expectException('\FMUP\Exception');
        $this->expectExceptionMessage('composer.json invalid structure');
        /** @var $version \FMUP\Version */
        $version->get();
    }
}
