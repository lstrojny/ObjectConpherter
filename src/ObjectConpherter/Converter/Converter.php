<?php
/**
 * ObjectConpherter: converting PHP objects to arrays
 *
 * @license New BSD License
 * @copyright (c) 2011, Lars Strojny <lstrojny@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of the <organization> nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
namespace ObjectConpherter\Converter;

use ObjectConpherter\Configuration\Configuration,
    ReflectionObject,
    Traversable;

class Converter
{
    /**
     * Configuration instance
     *
     * @var ObjectConpherter\Configuration\Configuration
     */
    protected $_configuration;

    /**
     * Query factory instance
     *
     * @var ObjectConpherter\Configuration\Configuration
     */
    protected $_queryFactory;

    /**
     * Recursion detection enabled?
     *
     * @var bool
     */
    protected $_recursionDetectionEnabled = true;

    /**
     * Filter property values
     *
     * @var ObjectConpherter\Filter\PropertyValueFilter
     */
    protected $_propertyValueFilter;

    /**
     * Filters property names
     *
     * @var ObjectConpherter\Filter\PropertyNameFilter
     */
    protected $_propertyNameFilter;

    /**
     * Create new converter instance
     *
     * @param ObjectConpherter\Configuration\Configuration $configuration
     * @param ObjectConpherter\Converter\QueryFactory $queryFactory
     */
    public function __construct(Configuration $configuration, QueryFactory $queryFactory = null)
    {
        $this->_configuration = $configuration;

        if (!$queryFactory) {
            $queryFactory = new QueryFactory();
        }
        $this->_queryFactory = $queryFactory;
    }

    /**
     * Convert an object into its array representation
     *
     * @param object $object
     * @param ObjectConpherter\Converter\Query|string|array $query Query object, query string or list of query objects
     * @return array
     */
    public function convert($object, $query = null)
    {
        $queryParams = array_slice(func_get_args(), 1);

        if (!$queryParams) {
            $queryParams[] = '/*/*/*';
        } elseif (is_array($queryParams[0])) {
            $queryParams = $queryParams[0];
        }

        $queries = array();
        foreach ($queryParams as $query) {
            if (!$query instanceof Query) {
                $query = $this->_queryFactory->parse($query);
            }
            $queries[] = $query;
        }
        $query = new CompositeQuery($queries);

        $array = array();
        $visited = array();

        $this->_propertyValueFilter = $this->_configuration->getPropertyValueFilter();
        $this->_propertyNameFilter = $this->_configuration->getPropertyNameFilter();
        $this->_recursionDetectionEnabled = $this->_configuration->isRecursionDetectionEnabled();

        $this->_convert($object, $array, $visited, $query, array('root'));

        return $array;
    }

    /**
     * Conversion method which calls itself recursivly for sub objects
     *
     * @param mixed $object
     * @param array $array
     * @param array $visited
     * @param ObjectConpherter\Converter\Query $query
     * @param array $hierarchy
     * @return boolean
     */
    protected function _convert($object, array &$array, array &$visited, Query $query, array $hierarchy)
    {
        if (!$query->matches($hierarchy)) {
            return false;
        }


        if ($this->_recursionDetectionEnabled && $this->_detectRecursion($object, $visited)) {
            return false;
        }

        if ($object instanceof Traversable or is_array($object)) {

            $returnValue = false;
            foreach ($object as $listKey => $listElement) {
                if ($this->_convertSubordinate($object, $array, $visited, $query, $hierarchy, $listElement, $listKey)) {
                    $returnValue = true;
                }
            }
            return $returnValue;

        } elseif (!is_object($object)) {

            /** Found a scalar value or null, just assign it */
            $array = $object;
            return true;
        } elseif (method_exists($object, '__toString') and
                  !$this->_configuration->getHierarchyProperties(get_class($object))) {

            $array = $object->__toString();
            return true;

        } else {

            $propertyNames = $this->_configuration->getHierarchyProperties(get_class($object));

            if (!$propertyNames) {
                return false;
            }

            $class = new ReflectionObject($object);

            while ($propertyName = array_shift($propertyNames)) {

                if (!$class->hasProperty($propertyName)) {
                    continue;
                }

                if (!$query->matches(array_merge($hierarchy, array($propertyName)))) {
                    continue;
                }

                $property = $class->getProperty($propertyName);
                $property->setAccessible(true);
                $propertyValue = $property->getValue($object);

                if (is_object($object)) {
                    $this->_convertSubordinate(
                        $object,
                        $array,
                        $visited,
                        $query,
                        $hierarchy,
                        $propertyValue,
                        $propertyName
                    );
                } else {
                    $this->_appendArrayValue($array, $object, $propertyName, $propertyValue);
                }
            }

            return true;
        }
    }

    /**
     * Conversion of sub-objects
     *
     * Delegates to _convert()-method for sub-objects and managing array references and level stacking
     *
     * @param object $object
     * @param array $array
     * @param array $visited
     * @param ObjectConpherter\Converter\Query $query
     * @param array $hierarchy
     * @param mixed $propertyValue
     * @param string|integer $propertyName
     * @return boolean
     */
    protected function _convertSubordinate(
        $object,
        array &$array,
        array &$visited,
        Query $query,
        array $hierarchy,
        $propertyValue,
        $propertyName
    )
    {
        $propertyRenamed = $this->_appendArrayValue($array, $object, $propertyName, array());
        $hierarchy[] = (string)$propertyName;

        if ($this->_propertyValueFilter) {
            $type = $this->_getType($object);
            if (!$this->_propertyValueFilter->filterPropertyValue($type, $propertyName, $propertyValue)) {
                $array[$propertyRenamed] = $propertyValue;
                return true;
            }
        }

        if (!$this->_convert($propertyValue, $array[$propertyRenamed], $visited, $query, $hierarchy)) {
            unset($array[$propertyRenamed]);
            return false;
        }

        return true;
    }

    /**
     * Appends array value to the converter response
     *
     * @param array $array
     * @param mixed $object
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return string Property name
     */
    protected function _appendArrayValue(&$array, $object, $propertyName, $propertyValue)
    {
        if ($this->_propertyNameFilter) {
            $propertyName = $this->_propertyNameFilter->filterPropertyName($this->_getType($object), $propertyName);
        }

        if ($this->_propertyValueFilter) {
            $this->_propertyValueFilter->filterPropertyValue($this->_getType($object), $propertyName, $propertyValue);
        }

        $array[$propertyName] = $propertyValue;

        return $propertyName;
    }

    /**
     * Return class or primitive type of $object
     *
     * @param mixed $object
     * @return string
     */
    protected function _getType($object)
    {
        return is_object($object) ? get_class($object) : gettype($object);
    }

    /**
     * Detects if an object has been visited already
     *
     * Keeps track of all visited objects in a list of object hashes so that each instance is only converted
     * once
     *
     * @param object $object
     * @param array $visited
     * @return boolean
     */
    protected function _detectRecursion($object, array &$visited)
    {
        if (!$this->_recursionDetectionEnabled) {
            return false;
        }

        if (!is_object($object)) {
            return false;
        }

        $objectHash = spl_object_hash($object);
        if (!in_array($objectHash, $visited, true)) {
            $visited[] = $objectHash;
            return false;
        }

        return true;
    }
}
