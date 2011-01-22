<?php
namespace ObjectConpherter\Converter;

use ObjectConpherter\Configuration\Configuration,
    ReflectionObject;

class Converter
{
    protected $_configuration;

    public function __construct(Configuration $configuration)
    {
        $this->_configuration = $configuration;
    }

    /**
     *
     * @param <type> $object
     * @param <type> $query
     * @return array
     */
    public function convert($object, $queryString = '/*/*/*/')
    {
        $query = Query::parse($queryString);
        var_dump($query);

        $array = array();
        $visited = array();
        
        $this->_convert($object, $array, $visited, $query, array('root'));
        return $array;
    }

    protected function _convert($object, array &$array, array &$visited, Query $query, array $levels)
    {
        if (!$query->matches($levels)) {
            return false;
        }

        if ($this->_recursionDetected($object, $visited)) {
            return false;
        }

        $class = new ReflectionObject($object);
        $propertyNames = $this->_getConfiguredPropertiesInTypeHierarchy($object, $class);

        if (!$propertyNames) {
            return false;
        }

        while ($propertyName = array_shift($propertyNames)) {

            if (!$class->hasProperty($propertyName)) {
                continue;
            }

            if (!$query->matches(array_merge($levels, array($propertyName)))) {
                continue;
            }

            $property = $class->getProperty($propertyName);
            $property->setAccessible(true);
            $propertyValue = $property->getValue($object);

            if (is_object($propertyValue)) {

                $array[$propertyName] = array();
                $levels[] = $propertyName;

                if (!$this->_convert($propertyValue, $array[$propertyName], $visited, $query, $levels)) {
                    unset($array[$propertyName]);
                }

            } else {
                $array[$propertyName] = $propertyValue;
            }
        }

        return true;
    }

    protected function _getConfiguredPropertiesInTypeHierarchy($object, ReflectionObject $class)
    {
        $className = get_class($object);
        do {
            $propertyNames = $this->_configuration->getProperties($className);
        } while ($className = get_parent_class($className));

        foreach ($class->getInterfaceNames() as $interfaceName) {
            $propertyNames = array_merge($propertyNames, $this->_configuration->getProperties($interfaceName));
        }

        return $propertyNames;
    }

    protected function _recursionDetected($object, array &$visited)
    {
        $objectHash = spl_object_hash($object);

        if (in_array($objectHash, $visited, true)) {
            return true;
        }

        $visited[] = $objectHash;

        return false;
    }
}