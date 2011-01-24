ObjectConpherter
================
Converting PHP Objects Into Array Representations Since 2011


Mission Statement
-----------------

ObjectConpherter takes your business objects and converts them into arrays while
keeping there hierarchial structure. The project was born because [we](http://jarlssen.de)
needed a system to provide array representations of business objects for a JSON API.


License
-------

Licensed under New BSD License


Current Use Cases
-----------------

-   Property mapping support for the concrete class, superclass(es), interfaces
-   Filter infrastructure to allow implementing conversions for specific types
    (e.g. formatting date specifically), renaming of properties (if you use an
    underscore with protected properties, you might not want to show that in
    your API)
-   Configuration readers to support defining export properties as Annotations
    and in XML. For the sake of speed there might be a configuration dumper
    component to convert annotations into plain PHP
-   A minimal, XQuery-alike interface to define conversion depth of objects.
    "/root/_foo/_bar" will export the property "_foo" of the object passed and
    the property "_bar" of "_foo"


Usage
-----

    <?php
    $configuration = new ObjectConpherter\Configuration\Configuration();
    $configuration->exportProperties('stdClass', array('property1', 'property2'));
    $converter = new ObjectConpherter\Converter\Converter($configuration);

    $object = new stdClass();
    $object->property1 = new stdClass();
    $object->property1->property1 = 'propVal1_1';
    $object->property1->property1 = 'propVal1_2';
    $object->property2 = 'propVal1_2';
    var_dump($converter->convert($object, '/root/property1/property1,/root/property2'));

Will output:

    array(2) {
      ["property1"]=>
      array(1) {
        ["property1"]=>
        string(8) "propVal1_1"
      }
      ["property2"]=>
      string(8) "propVal1_2"
    }


Warning
-------

This software is still under heavy development, so literally anything might change


Thank you!
----------

This software is based on good ideas of Marc Jakubowski <marc.jakubowski@jarlssen.de>,
Max Beutel <max.beutel@jarlssen.de>, Karsten Gohm <karsten.gohm@jarlssen.de>
