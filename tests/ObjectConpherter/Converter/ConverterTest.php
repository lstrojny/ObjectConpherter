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
        $this->_configuration->exportProperties('stdClass', array('prop1', 'prop2'));
        $this->assertSame($array, $this->_converter->convert((object)$array));
    }

    function testConvertingNestedObject()
    {
        $array = array(
                  'prop1' => 'propVal1',
                  'prop2' => 'propVal2',
                  'prop3' => array('propNested1' => 'propValNested1'),
                 );

        $this->_configuration->exportProperties('stdClass', array('prop1', 'prop2', 'prop3', 'propNested1'));
        $this->assertSame($array, $this->_converter->convert($this->toObject($array)));
    }

    function testConvertingRecursiveNestedObject()
    {
        $object = new stdClass();
        $object->prop1 = 'propVal1';
        $object->prop2 = new stdClass();
        $object->prop2 = &$object;

        $array = array('prop1' => 'propVal1');
        $this->_configuration->exportProperties('stdClass', array('prop1', 'prop2', 'prop3'));
        $this->assertSame($array, $this->_converter->convert($object));
    }

    function testSuperclassBasedConfiguration()
    {
        $object = new Superclass();
        $object->property = new Subclass();
        $object->property->property = 'test';
        $array = array('property' => array('property' => 'test'));

        $this->_configuration->exportProperties('ObjectConpherter\Converter\Superclass', array('property'));
        $this->assertSame($array, $this->_converter->convert($object));
    }

    function testInterfaceBasedConfiguration()
    {
        $object = new InterfaceImplementor();
        $object->iface1 = 'ifaceProp1';
        $object->iface2 = 'ifaceProp2';
        $array = array('iface1' => 'ifaceProp1', 'iface2' => 'ifaceProp2');

        $this->_configuration->exportProperties('ObjectConpherter\Converter\Interface1', array('iface1'));
        $this->_configuration->exportProperties('ObjectConpherter\Converter\Interface2', array('iface2'));
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

        $this->_configuration->exportProperties('ObjectConpherter\Converter\Superclass', array_keys($array));
        $this->assertSame($array, $this->_converter->convert($object));
    }

    function testLimitingConversionDepth()
    {
        $array = array(
                  'prop1' => 'propVal1',
                  'prop2' => 'propVal2',
                  'prop3' => array('propNested1' => 'propValNested1'),
                 );

        $this->_configuration->exportProperties('stdClass', array('prop1', 'prop2', 'prop3', 'propNested1'));
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
        $this->_configuration->exportProperties(
            'ObjectConpherter\Converter\Superclass',
            array('property', 'protectedProperty', 'privateProperty')
        );
        $this->_configuration->exportProperties(
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
        $this->_configuration->exportProperties('ObjectConpherter\Converter\InterfaceImplementor', array('concrete1', 'concrete2'))
                             ->exportProperties('ObjectConpherter\Converter\Interface1', array('iface11'))
                             ->exportProperties('ObjectConpherter\Converter\Interface2', array('iface21', 'iface22'));
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

    function testConvertingTraversableOfObjects()
    {
        $object = new \ArrayObject();
        $object->append(new Superclass(array('property' => 'prop1')));
        $object->append(new Superclass(array('property' => 'prop2')));
        $object->append(new Superclass(array('property' => 'prop3')));

        $this->_configuration->exportProperties('ObjectConpherter\Converter\Superclass', array('property'));
        $this->assertSame(
            array(
             array('property' => 'prop1'),
             array('property' => 'prop2'),
             array('property' => 'prop3'),
            ),
            $this->_converter->convert($object)
        );
        $this->assertSame(
            array(
             1 => array('property' => 'prop2'),
            ),
            $this->_converter->convert($object, '/root/1/property')
        );
        $this->assertSame(
            array(
             1 => array('property' => 'prop2'),
             2 => array('property' => 'prop3'),
            ),
            $this->_converter->convert($object, '/root/1/property,/root/2/*')
        );
    }

    function testConvertingArrayOfObjects()
    {
        $object = array(
            new Superclass(array('property' => 'prop1')),
            new Superclass(array('property' => 'prop2')),
            new Superclass(array('property' => array(
                                                'test1' => new Subclass(array('protectedProperty' => 'test1Val')),
                                                'test2' => new Subclass(array('protectedProperty' => 'test2Val')),
                                               ))),
        );

        $this->_configuration->exportProperties('ObjectConpherter\Converter\Superclass', array('property'))
                             ->exportProperties('ObjectConpherter\Converter\Subclass', array('protectedProperty'));

        $this->assertSame(
            array(
             array('property' => 'prop1'),
             array('property' => 'prop2'),
             array('property' => array(
                                  'test1' => array('protectedProperty' => 'test1Val', 'property' => null),
                                  'test2' => array('protectedProperty' => 'test2Val', 'property' => null),
                                 )
             ),
            ),
            $this->_converter->convert($object, '/*/*/*/*/*/')
        );
        $this->assertSame(
            array(
             1 => array('property' => 'prop2'),
            ),
            $this->_converter->convert($object, '/root/1/property')
        );
        $this->assertSame(
            array(
             1 => array('property' => 'prop2'),
             2 => array('property' => array('test2' => array('protectedProperty' => 'test2Val'))),
            ),
            $this->_converter->convert($object, '/root/1/property,/root/2/property/test2/protectedProperty')
        );
    }

    function testComplicatedNestedObjects()
    {
        $stack = new \SplStack();
        $stack->push(array(new \ArrayObject(array('foo', new Subclass(array('protectedProperty' => 'p3'))))));

        $object = array(
                    new Superclass(array('property' => 'p1')),
                    new Superclass(array('property' => 'p2')),
                    $stack,
                    new Subclass(array('protectedProperty' => new Superclass(array('property' => 'p4'))))
                  );

        $this->_configuration->exportProperties('ObjectConpherter\Converter\Superclass', array('property'))
                             ->exportProperties('ObjectConpherter\Converter\Subclass', array('protectedProperty'));

        $this->assertSame(
            array(
              array('property' => 'p1'),
              array('property' => 'p2'),
              array(array(array('foo', array('protectedProperty' => 'p3', 'property' => null)))),
              array('protectedProperty' => array('property' => 'p4'), 'property' => null)
            ),
            $this->_converter->convert($object, '/*/*/*/*/*/*/')
        );

        $this->assertSame(
            array(
              0 => array('property' => 'p1'),
              2 => array(array(array(1 => array('protectedProperty' => 'p3')))),
            ),
            $this->_converter->convert($object, '/root/0/property/,/root/2/0/0/1/protectedProperty')
        );
    }

    function testLimitingListItem()
    {
        $object = array(new Superclass(array('property' => 1)));
        $this->_configuration->exportProperties('ObjectConpherter\Converter\Superclass', array('property'));
        $this->assertSame(array(0 => array('property' => 1)), $this->_converter->convert($object));
        $this->assertSame(array(0 => array('property' => 1)), $this->_converter->convert($object, '/root/0/*'));
    }

    function testPassingQueryObjectInsteadOfQueryString()
    {
        $array = array('prop1' => 'propVal1', 'prop2' => 'propVal2');

        $this->assertSame(array(), $this->_converter->convert((object)$array));
        $this->_configuration->exportProperties('stdClass', array('prop1', 'prop2'));
        $this->assertSame($array, $this->_converter->convert((object)$array, new Query(array(array('*', '*')))));
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