<?php
namespace ObjectConpherter\Converter;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    function testExactyQuery()
    {
        $q = new Query(array('foo', 'bar'));
        $this->assertTrue($q->matches(array('foo', 'bar')));
        $this->assertFalse($q->matches(array('bar')));
        $this->assertTrue($q->matches(array('foo')));
    }

    function testFuzzyQuery()
    {
        $q = new Query(array('*', 'foo', 'bar', '*'));
        $this->assertTrue($q->matches(array('bla')));
        $this->assertTrue($q->matches(array('bla', 'foo', 'bar')));
        $this->assertTrue($q->matches(array('bla', 'foo')));
        $this->assertFalse($q->matches(array('bla', 'wrong')));
        $this->assertTrue($q->matches(array('foo', 'foo', 'bar', 'baz')));
        $this->assertFalse($q->matches(array('foo', 'foo', 'bar', 'baz', 'bla')));
    }
}