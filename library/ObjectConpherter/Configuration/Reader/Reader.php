<?php
namespace ObjectConpherter;

use ObjectConpherter\Configuration\Configuration;

interface Reader
{
    public function read(Configuration $configuration);
}