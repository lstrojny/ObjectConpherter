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