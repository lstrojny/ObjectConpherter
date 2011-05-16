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

```php
<?php
class User
{
    public $id;
    public $friends = array();
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

$user = new User('John Doe');
$user->id = 23;
$user->friends[] = new User('Max Mustermann');

$configuration = new ObjectConpherter\Configuration\Configuration();
$configuration->exportProperties('User', array('id', 'name', 'friends'));
$converter = new ObjectConpherter\Converter\Converter($configuration);
var_dump($converter->convert($object, null, '/root/*', '/root/friends/*/name'));
```

Will output:

    ["id"]=>
      int(23)
      ["name"]=>
      string(8) "John Doe"
      ["friends"]=>
      array(1) {
        [0]=>
        array(1) {
          ["name"]=>
          string(14) "Max Mustermann"
        }
      }
    }


Thank you!
----------

This software is based on good ideas of Marc Jakubowski <marc.jakubowski@jarlssen.de>,
Max Beutel <max.beutel@jarlssen.de>, Karsten Gohm <karsten.gohm@jarlssen.de>, Stefan Matheis
<stefphpan.matheis@jarlssen.de>
