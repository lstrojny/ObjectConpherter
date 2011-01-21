<?php
namespace ObjectConpherter\Configuration;

class Configuration
{
    protected $_mapping = array();

    public function addType($className, array $propertyNames)
    {
        $this->_mapping[$className] = $propertyNames;

        return $this;
    }

    public function getProperties($className)
    {
        return isset($this->_mapping[$className]) ? $this->_mapping[$className] : array();
    }
}