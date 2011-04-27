<?php
namespace ObjectConpherter\Configuration\Reader;

class XmlReaderTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->_configFile = tempnam(sys_get_temp_dir(), __CLASS__);
        $ds = DIRECTORY_SEPARATOR;
        $this->_exampleConfig = str_replace($ds . 'tests' . $ds, $ds . 'src' . $ds, __DIR__) . $ds . 'example-config.xml';
        $this->_configuration = new \ObjectConpherter\Configuration\Configuration();
        $this->_mockedConfiguration = $this->getMock('ObjectConpherter\Configuration\Configuration', array(), array(), '', false);
    }

    function tearDown()
    {
        unlink($this->_configFile);
    }

    function testUnreadableConfigFile()
    {
        chmod($this->_configFile, 000);

        $this->setExpectedException('ObjectConpherter\Configuration\Reader\ReaderException', 'Could not read config file');
        new XmlReader($this->_configFile);
    }

    function testNonExistantConfigFile()
    {
        $this->setExpectedException('ObjectConpherter\Configuration\Reader\ReaderException', 'Config file does not exist');
        new XmlReader('nonExistant.xml');
    }

    function testReadingXmlConfigWithParseErrors()
    {
        file_put_contents($this->_configFile, 'invalidXml');
        $reader = new XmlReader($this->_configFile);

        $this->setExpectedException('ObjectConpherter\Configuration\Reader\ReaderException', 'Parse error');
        $reader->readInto($this->_configuration);
    }

    function testReadingXmlConfigWithValidationErrors()
    {
        file_put_contents($this->_configFile, '<converter><invalidTag/></converter>');
        $reader = new XmlReader($this->_configFile);

        $this->setExpectedException('ObjectConpherter\Configuration\Reader\ReaderException', 'Validation error');
        $reader->readInto($this->_configuration);
    }

    function testReadingXmlConfigWithValidationErrorsAndValidationDisabledDoesNotThrowAnException()
    {
        file_put_contents($this->_configFile, '<converter><invalidTag/></converter>');
        $reader = new XmlReader($this->_configFile, false);
        $reader->readInto($this->_configuration);
    }

    function testReadingClassPropertiesFromXmlConfig()
    {
        $this->_mockedConfiguration->expects($this->at(0))
                                   ->method('exportProperties')
                                   ->with('stdClass', array('propertyOne', 'propertyTwo'));
        $this->_mockedConfiguration->expects($this->at(1))
                                   ->method('exportProperties')
                                   ->with('ArrayObject', array('0', '1'));
        $reader = new XmlReader($this->_exampleConfig);
        $reader->readInto($this->_mockedConfiguration);
    }
}
