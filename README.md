# Pimple IoC
Class resolver for the [Pimple](http://pimple.sensiolabs.org/) container.

This project is heavily inspired by how [Laravel](http://laravel.com/) resolve it's classes out of their IoC container. In fact most of the code is taken directly from their ```Container``` class.

## Installation
Add the IoC container to your ```composer.json``` using the command line.
```
composer require jonsa/pimple-ioc
```

## Usage
The class resolver is registered in Pimple as a ```ServiceProvider```
```php
use Jonsa\PimpleResolver\ServiceProvider;
use Pimple\Container;

$container = new Container();
$container->register(new ServiceProvider());
```

This will register the ```make``` key on the Container which resolves the requested class.
```php
$instance = $container['make']('Acme\MyClass');
```

and the ```bind``` key to bind a definition to a concrete implementation
```php
$container['bind']('Acme\MyInterface', 'Acme\MyClass');
```

### Resolved recursively
Class dependencies are resolved recursively.
```php
interface FooContract {}

class Foo implements FooContract {};

class Bar {
    public function __construct(FooContract $foo) {}
}

class Baz {
    public function __construct(Bar $bar) {}
}

$container['bind']('FooContract', 'FooClass');
$baz = $container['make']('Baz');
```

### Define constructor parameters
To override a class parameter use the parameter array on the resolver method.
```php
class Log {
    public function __construct(Psr\Log\LoggerInterface $logger, $level = Psr\Log\LogLevel::WARNING)
    {
        ...
    }
}

$container['make']('Log', array(
    'level' => Psr\Log\LogLevel::DEBUG
));
```

### Inject into the resolver workflow
To customize a resolved class before it is returned from the resolver, simply listen to the ```CLASS_RESOLVED``` event.
```php
use Jonsa\PimpleResolver\ServiceProvider;
use Jonsa\PimpleResolver\Events;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher;
$container[ServiceProvider::EVENT_DISPATCHER] = function () use ($dispatcher) {
    return $dispatcher;
});

$dispatcher->addListener(Events::CLASS_RESOLVED, function (ClassResolvedEvent $event) {
    $object = $event->getResolvedObject();
    ...
});
```
Alternatively the ```EVENT_DISPATCHER``` key can be populated with a string which in turn returns an event dispatcher from the container
```php
$container[ServiceProvider::EVENT_DISPATCHER] = 'my dispatcher key';
```

## Configuration
The ServiceProvider has three configuration parameters.
```php
class ServiceProvider implements ServiceProviderInterface {
    public function __construct($bindContainerInstance = true, $makeMethod = 'make', $bindMethod = 'bind')
    {
        ...
    }
}
```

```$bindContainerInstance``` tells the ServiceProvider whether to bind the container instance to the ```'Pimple\Container'``` key. If the container is extended, that class name will also be bound to the container instance.

```$makeMethod``` is used to define which key on the container to be used as the make method.

```$bindMethod``` is used to define which key on the container to be used as the bind method.
