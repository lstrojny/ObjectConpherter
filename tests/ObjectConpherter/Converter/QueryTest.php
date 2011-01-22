<?php
namespace ObjectConpherter\Converter;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    function testExactyQuery()
    {
        $q = new Query(array(array('foo', 'bar')));
        $this->assertTrue($q->matches(array('foo', 'bar')));
        $this->assertFalse($q->matches(array('bar')));
        $this->assertTrue($q->matches(array('foo')));
    }

    function testFuzzyQuery()
    {
        $q = new Query(array(array('*', 'foo', 'bar', '*')));
        $this->assertTrue($q->matches(array('bla')));
        $this->assertTrue($q->matches(array('bla', 'foo', 'bar')));
        $this->assertTrue($q->matches(array('bla', 'foo')));
        $this->assertFalse($q->matches(array('bla', 'wrong')));
        $this->assertTrue($q->matches(array('foo', 'foo', 'bar', 'baz')));
        $this->assertFalse($q->matches(array('foo', 'foo', 'bar', 'baz', 'bla')));
    }

    function testMultiQuery()
    {
        $q = new Query(array(array('*', 'foo'), array('*', 'bar', '*')));
        $this->assertTrue($q->matches(array('test', 'foo')));
        $this->assertTrue($q->matches(array('test')));
        $this->assertTrue($q->matches(array('test', 'bar')));
        $this->assertTrue($q->matches(array('test')));
        $this->assertFalse($q->matches(array('test', 'bla')));
        $this->assertTrue($q->matches(array('test', 'bar', 'bla')));
        $this->assertFalse($q->matches(array('test', 'bar', 'bla', 'gnarf')));

        $q = new Query(array(array('root', 'property',), array('root', 'protectedProperty', '*')));
        $this->assertFalse($q->matches(array('root', 'privateProperty')));
    }

    function testParseQueryString()
    {
        $this->assertSame('/foo/bar/*/', (string)Query::parse('//foo/bar/*'));
        $this->assertSame('/*/test/', (string)Query::parse('//*//test//'));
        $this->assertSame('/*/test/,/foo/', (string)Query::parse('//*//test//,foo'));
    }
}