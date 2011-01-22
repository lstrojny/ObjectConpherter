<?php
namespace ObjectConpherter\Converter;

class Query
{
    protected $_parts = array();

    public function __construct(array $parts)
    {
        $this->_parts = $parts;
    }

    public function matches(array $hierarchy)
    {
        $parts = $this->_parts;
        while ($hierarchyElement = array_shift($hierarchy)) {
            $part = array_shift($parts);

            /** End of query reached, no remaining query part, so return true */
            if (!$part and !$hierarchyElement) {
                return true;
            }

            /** Wildcard query */
            if ($part === '*') {
                continue;
            }

            /** Query matches? */
            if ($part != $hierarchyElement) {
                return false;
            }
        }
        return true;
    }
}