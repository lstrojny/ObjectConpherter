<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../library/ObjectConpherter');
spl_autoload_register(function($className) {
    include str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
});