<?php
namespace ObjectConpherter\Converter;

class Query
{
    protected $_queryParts = array();

    public static function parse($queryString)
    {
        $parts = array();
        $subQueries = explode(',', $queryString);
        array_walk($subQueries, function($subQuery) use(&$parts) {
            $parts[] = array_filter(explode('/', $subQuery));
        });

        return new static($parts);
    }

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