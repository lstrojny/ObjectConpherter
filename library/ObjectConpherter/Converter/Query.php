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

class Query
{
    protected $_queryParts = array();

    public function __construct(array $parts)
    {
        $this->_queryParts = $parts;
    }

    public function __toString()
    {
        $subQueries = array_map(
                        function($subQueryParts) {
                            return '/' . join('/', $subQueryParts) . '/';
                        },
                        $this->_queryParts
                      );
        return join(',', $subQueries);
    }

    public function matches(array $levels)
    {
        foreach ($this->_queryParts as $subQueryParts) {
            foreach ($levels as $level) {
                $subQueryPart = array_shift($subQueryParts);

                /** End of query reached, no remaining query part, so return true */
                if (!$subQueryPart and !$level) {
                    return true;
                }

                /** Wildcard query found */
                if ($subQueryPart === '*') {
                    continue;
                }

                /** Query matches? */
                if ($subQueryPart != $level) {
                    continue 2;
                }
            }
            return true;
        }
        return false;
    }
}