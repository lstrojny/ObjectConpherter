<?php
namespace ObjectConpherter\Configuration\Reader;

use ObjectConpherter\Configuration\Configuration,
    DOMDocument,
    LibXMLError,
    DOMXPath;

class XmlReader implements Reader
{
    protected $_xmlConfigFile;

    protected $_relaxNgSchemaFile;

    protected $_validate = true;

    public function __construct($xmlConfigFile, $validate = true)
    {
        if (!file_exists($xmlConfigFile)) {
            throw new ReaderException('Config file does not exist');
        }

        if (!is_readable($xmlConfigFile)) {
            throw new ReaderException('Could not read config file');
        }
        $this->_xmlConfigFile = $xmlConfigFile;

        $this->_relaxNgSchemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.rng';
        $this->_validate = (bool)$validate;
    }

    public function readInto(Configuration $configuration)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');

        $reportXmlErrors = libxml_use_internal_errors(true);

        if (!$doc->load($this->_xmlConfigFile, defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0)) {
            $error = libxml_get_last_error();
            libxml_clear_errors();
            libxml_use_internal_errors($reportXmlErrors);

            throw new ReaderException($this->_formatXmlErrorExceptionMessage('Parse error', $error));
        }

        if ($this->_validate and !$doc->relaxNGValidate($this->_relaxNgSchemaFile)) {
            $error = libxml_get_last_error();
            libxml_clear_errors();
            libxml_use_internal_errors($reportXmlErrors);

            throw new ReaderException($this->_formatXmlErrorExceptionMessage('Validation error', $error));
        }

        libxml_use_internal_errors($reportXmlErrors);

        $this->_readClasses($doc, $configuration);
    }

    protected function _formatXmlErrorExceptionMessage($prefix, LibXMLError $error)
    {
        return $prefix . ': ' . $error->message . ' in ' . $error->file . ' on line ' . $error->line;
    }

    protected function _readClasses(DOMDocument $doc, Configuration $configuration)
    {
        $xpath = new DOMXPath($doc);
        $classNodes = $xpath->query('//converter/classes/class');

        if (!$classNodes) {
            return;
        }

        foreach ($classNodes as $classNode) {

            $propertyNames = array();

            $propertyNodes = $xpath->query('.//properties/property', $classNode);

            if (!$propertyNodes) {
                continue;
            }

            foreach ($propertyNodes as $propertyNode) {
                $propertyNames[] = $propertyNode->getAttribute('name');
            }

            $configuration->exportProperties($classNode->getAttribute('name'), $propertyNames);
        }
    }
}
