Proc
====

What?
-----
Proc is a wrapping class for PHP proc\_\* functions. It was made with
and for PHP 5.3. It comes with a Worker class to execute PHP code from
file or from string.


Why?
----
Because I was bored enough. Also, it allows somewhat thread-like things
in PHP, which is cool.


How?
---
Proc uses PHP proc\_\* functions to launch a separate thread and provides
ways to interact with it.  
Files are documented in a Doxygen-friendly way.


Who? When?
---------
    $ whoami
    leo
    $ date
	Sat Sep 24 00:27:27 CEST 2011

Example
-------
```php
<?php
require_once 'vendor/autoload.php';

$foo = new lpeltier\Worker();
$foo->in('<?php echo "Hello world!"; ?>');
echo $foo->out(); // Hello World!
$foo->close();
```
