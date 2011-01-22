<?php
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