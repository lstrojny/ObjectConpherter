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
    stdClass;

class Superclass
{
    public $property;
    protected $protectedProperty;
    private $privateProperty;
    public static $publicStaticProperty;
    protected static $protectedStaticProperty;
    private static $privateStaticProperty;

    public function __construct(array $properties = array())
    {
        $class = new \ReflectionObject($this);
        foreach ($properties as $propertyName => $propertyValue) {
            $property = $class->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($this, $propertyValue);
        }
    }
}

class Subclass extends Superclass
{
}

interface Interface1
{
}

interface Interface2
{
}

class InterfaceImplementor implements Interface1, Interface2
{
}


class ConverterTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->_configuration = new Configuration();
        $this->_converter = new Converter($this->_configuration);
    }

    function testConvertingSimpleObject()
    {
        $array = array('prop1' => 'propVal1', 'prop2' => 'propVal2');

        $this->assertSame(array(), $this->_converter->convert((object)$array));
        $this->_configuration->addType('stdClass', array('prop1', 'prop2'));
        $this->assertSame($array, $this->_converter->convert((object)$array));
    }

    function testConvertingNestedObject()
    {
        $array = array(
                  'prop1' => 'propVal1',
                  'prop2' => 'propVal2',
                  'prop3' => array('propNested1' => 'propValNested1'),
                 );

        $this->_configuration->addType('stdClass', array('prop1', 'prop2', 'prop3', 'propNested1'));
        $this->assertSame($array, $this->_converter->convert($this->toObject($array)));
    }

    function testConvertingRecursiveNestedObject()
    {
        $object = new stdClass();
        $object->prop1 = 'propVal1';
        $object->prop2 = new stdClass();
        $object->prop2 = &$object;

        $array = array('prop1' => 'propVal1');
        $this->_configuration->addType('stdClass', array('prop1', 'prop2', 'prop3'));
        $this->assertSame($array, $this->_converter->convert($object));
    }

    function testSuperclassBasedConfiguration()
    {
        $object = new Superclass();
        $object->property = new Subclass();
        $object->property->property = 'test';
        $array = array('property' => array('property' => 'test'));

        $this->_configuration->addType('ObjectConpherter\Converter\Superclass', array('property'));
        $this->assertSame($array, $this->_converter->convert($object));
    }

    function testInterfaceBasedConfiguration()
    {
        $object = new InterfaceImplementor();
        $object->iface1 = 'ifaceProp1';
        $object->iface2 = 'ifaceProp2';
        $array = array('iface1' => 'ifaceProp1', 'iface2' => 'ifaceProp2');

        $this->_configuration->addType('ObjectConpherter\Converter\Interface1', array('iface1'));
        $this->_configuration->addType('ObjectConpherter\Converter\Interface2', array('iface2'));
        $this->assertSame($array, $this->_converter->convert($object));
    }

    function testExportingPropertiesWithVisibilityOtherThanPublic()
    {
        $array = array(
                  'protectedProperty'       => 'protected',
                  'privateProperty'         => 'private',
                  'publicStaticProperty'    => 'publicStatic',
                  'protectedStaticProperty' => 'protectedStatic',
                  'privateStaticProperty'   => 'privateStatic',
                 );
        $object = new Superclass($array);

        $this->_configuration->addType('ObjectConpherter\Converter\Superclass', array_keys($array));
        $this->assertSame($array, $this->_converter->convert($object));
    }

    function testLimitingConversionDepth()
    {
        $array = array(
                  'prop1' => 'propVal1',
                  'prop2' => 'propVal2',
                  'prop3' => array('propNested1' => 'propValNested1'),
                 );

        $this->_configuration->addType('stdClass', array('prop1', 'prop2', 'prop3', 'propNested1'));
        $this->assertSame($array, $this->_converter->convert($this->toObject($array)));
        $this->assertSame($array, $this->_converter->convert($this->toObject($array), '/*/*/*'));
        $this->assertSame(array('prop1' => 'propVal1', 'prop2' => 'propVal2'), $this->_converter->convert($this->toObject($array), '/*/prop1/,/*/prop2/'));
        $this->assertSame(
            array('prop1' => 'propVal1', 'prop3' => array('propNested1' => 'propValNested1')),
            $this->_converter->convert($this->toObject($array), '/*/prop1/,/*/prop3/*/')
        );
    }

    function testComplicatedDumping()
    {
        $object = new SuperClass(array(
                                  'property' => 'prop1',
                                  'protectedProperty' => new Subclass(array(
                                                                       'property'          => 'subclassPropVal1',
                                                                       'protectedProperty' => 'subclassPropVal2'
                                                                     )),
                                  'privateProperty' => new Superclass(
                                            array('protectedProperty' => new Subclass(array('property' => 'nestedProp')))
                                   ),
                                 )
        );
        $this->_configuration->addType(
            'ObjectConpherter\Converter\Superclass',
            array('property', 'protectedProperty', 'privateProperty')
        );
        $this->_configuration->addType(
            'ObjectConpherter\Converter\Subclass',
            array('property')
        );
        $this->assertSame(
            array('property' => 'prop1', 'protectedProperty' => array('property' => 'subclassPropVal1', 'protectedProperty' => 'subclassPropVal2')),
            $this->_converter->convert($object, '/root/property/,/root/protectedProperty/*/')
        );
        $this->assertSame(
            array('privateProperty' => array('protectedProperty' => array())),
            $this->_converter->convert($object, '/root/privateProperty/protectedProperty/')
        );
        $this->assertSame(
            array(
             'protectedProperty' => array('property' => 'subclassPropVal1', 'protectedProperty' => 'subclassPropVal2'),
             'privateProperty' => array('protectedProperty' => array('property' => 'nestedProp')),
            ),
            $this->_converter->convert($object, '/root/protectedProperty/*/,/root/privateProperty/protectedProperty/property/')
        );
    }

    function testMappingInfoDefinedForConcreteClassAndInterfaces()
    {
        $object = new InterfaceImplementor();
        $object->concrete1 = "concreteVal1";
        $object->concrete2 = "concreteVal2";
        $object->iface11 = "ifaceVal11";
        $object->iface21 = "ifaceVal21";
        $object->iface22 = "ifaceVal22";
        $this->_configuration->addType('ObjectConpherter\Converter\InterfaceImplementor', array('concrete1', 'concrete2'))
                             ->addType('ObjectConpherter\Converter\Interface1', array('iface11'))
                             ->addType('ObjectConpherter\Converter\Interface2', array('iface21', 'iface22'));
        $this->assertSame(
            array(
              'concrete1' => 'concreteVal1',
              'concrete2' => 'concreteVal2',
              'iface11'   => 'ifaceVal11',
              'iface21'   => 'ifaceVal21',
              'iface22'   => 'ifaceVal22',
            ),
            $this->_converter->convert($object)
        );
    }

    function toObject(array $array)
    {
        $memory = array();

        $converter = function($value, $key, $object) use(&$converter, &$memory) {
            if (!is_array($value)) {
                $object->{$key} = $value;
            } else {
                $object->{$key} = new \stdClass();
                $object = $object->{$key};
                array_walk($value, $converter, $object);
            }
        };
        $object = new \stdClass();
        array_walk($array, $converter, $object);
        return $object;
    }
}