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

class Configuration
{
    /**
     * Property type mapping
     *
     * @var array
     */
    protected $_exportProperties = array();

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
}