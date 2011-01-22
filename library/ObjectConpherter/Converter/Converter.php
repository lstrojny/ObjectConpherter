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
                $nextLevels = array_merge($levels, array($propertyName));

                if (!$this->_convert($propertyValue, $array[$propertyName], $visited, $query, $nextLevels)) {
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