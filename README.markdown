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
Licensed under the New BSD License


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


Warning
-------
This software is still under heavy development, so literally anything might change