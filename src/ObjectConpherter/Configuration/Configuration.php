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
namespace ObjectConpherter\Configuration;

use ObjectConpherter\Filter\PropertyNameFilter,
    ObjectConpherter\Filter\PropertyValueFilter;

class Configuration
{
    /**
     * Property type mapping
     *
     * @var array
     */
    protected $_exportProperties = array();

    /**
     * Property name filter
     *
     * @var ObjectConpherter\Filter\PropertyNameFilter
     */
    protected $_propertyNameFilter;

    /**
     * Property value filter
     *
     * @var ObjectConpherter\Filter\PropertyValueFilter
     */
    protected $_propertyValueFilter;

    /**
     * Recursion detection enabled?
     *
     * @var bool
     */
    protected $_recursionDetection = true;

    /**
     * Mark specific properties of a class for export
     *
     * @param string $className
     * @param array $propertyNames
     * @return ObjectConpherter\Configuration\Configuration
     */
    public function exportProperties($className, array $propertyNames)
    {
        $this->_exportProperties[strtolower($className)] = $propertyNames;

        return $this;
    }

    /**
     * Get property mapping information for a class and its ancestors
     *
     * Traverses the type hierarchy (class, subclass, interfaces) and collects informations about mapped
     * properties and the hierarchy
     *
     * @param string $className
     * @return array
     */
    public function getHierarchyProperties($className)
    {
        $propertyNames = array();

        $ancestors = array_merge(
                        class_parents($className),
                        class_implements($className)
                     );

        do {
            $propertyNames = array_merge($propertyNames, $this->getProperties($className));
        } while ($className = array_shift($ancestors));


        return $propertyNames;
    }

    /**
     * Get export properties for a class
     *
     * @param string $className
     * @return array
     */
    public function getProperties($className)
    {
        $className = strtolower($className);
        return isset($this->_exportProperties[$className]) ? $this->_exportProperties[$className] : array();
    }

    public function setPropertyNameFilter(PropertyNameFilter $propertyNameFilter)
    {
        $this->_propertyNameFilter = $propertyNameFilter;

        return $this;
    }

    /**
     * Return property name filter
     *
     * @return ObjectConpherter\Filter\PropertyNameFilter
     */
    public function getPropertyNameFilter()
    {
        return $this->_propertyNameFilter;
    }

    /**
     *
     * @param ObjectConpherter\Filter\PropertyValueFilter $propertyValueFilter
     * @return ObjectConpherter\Configuration\Configuration
     */
    public function setPropertyValueFilter(PropertyValueFilter $propertyValueFilter)
    {
        $this->_propertyValueFilter = $propertyValueFilter;

        return $this;
    }

    /**
     * Return property value filter
     *
     * @return ObjectConpherter\Filter\PropertyNameFilter
     */
    public function getPropertyValueFilter()
    {
        return $this->_propertyValueFilter;
    }

    /**
     * Enable recursion detection
     *
     * @return ObjectConpherter\Converter\Converter
     */
    public function enableRecursionDetection()
    {
        $this->_recursionDetection = true;

        return $this;
    }

    /**
     * Disable recursion detection
     *
     * @return ObjectConpherter\Converter\Converter
     */
    public function disableRecursionDetection()
    {
        $this->_recursionDetection = false;

        return $this;
    }

    /**
     * Is recursion detection enabled?
     *
     * @return bool
     */
    public function isRecursionDetectionEnabled()
    {
        return $this->_recursionDetection;
    }
}