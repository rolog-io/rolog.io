# Rolog.io - Event Dispatcher Made Easy

---

Rolog.io is *extremely* easy to use:

```php
RologIo\listen(fn (object $object) => $object->message .= 'Hello');
RologIo\listen(fn (object $object) => $object->message .= ' World');

$result = RologIo\dispatch((object) ['message' => '']);

assert($result->message === 'Hello World');
```

This code might remind you of **WordPress' `add_action()` / `do_action()`** and indeed the WP hook system simplicity is an inspiration to Rolog.io but, unlike WordPress, Rolog.io is fully type safe, and is a **complete PSR-14 implementation**.




## Events and Listeners

An **event** is any object that is passed to `dispatch()`.

A **listener** is a callable that takes one object parameter, the event.

The way listeners are mapped to events is based on the **listener's first argument type declaration**.

Take the following example: 

```php
class Foo {
    public array $messages = [];
}

class Bar extends Foo {}


RologIo\listen(fn (Foo $foo) => $foo->messages[] = 'First');

RologIo\listen(fn (Bar $bar) => $bar->messages[] = 'Second');

$foo = RologIo\dispatch(new Foo);
assert($foo->messages === ['First']);

$bar = RologIo\dispatch(new Bar);
assert($bar->messages === ['First', 'Second']);
```

The first listener, declaring an argument type of `Foo`, is used to handler events of type `Foo`, but events of type `Bar`.

The second listener, declaring an argument type of `Bar`, is attached to both events, because both events satisfy the type declaration.



### All Type Declarations Are Supported

Listeners can use any argument type declaration supported by PHP, including union, intersection and DNF types.

```php
RologIo\listen(function ((Iterator&Countable)|Collection|array $things) {
    // do stuff
);
```



## Listeners Ordering

By default listeners are added FIFO mode, but it is possible to control the ordering in two ways:
- Priority
- Relative positioning



### Order By Priority

The first way to assign a priority to a listener is via an attribute:

```php
use RologIo\Listener;

$hello = #[Listener(priority:2)] fn (object $object) => $object->message .= 'Hello';
$world = #[Listener(priority:1)] fn (object $object) => $object->message .= 'World ';

// listen takes a variadic number of listeners
RologIo\listen($hello, $world);

$result = RologIo\dispatch((object) ['message' => '']);

assert($result->message === 'World Hello');
```

Alternatively, there's the `RologIo\prioritize()` function.

```php
use RologIo\Listener;
use function RologIo\{listen, emit, prioritize};

listen(prioritize(2, fn (object $object) => $object->message .= ' World'));
listen(prioritize(1, fn (object $object) => $object->message .= 'Hello'));

$result = RologIo\dispatch((object) ['message' => '']);

assert($result->message === 'Hello World');
```



### Relative positioning

A listener can be executed before or after another listener whose ID is known.

Positioning is obtained using an attribute or via the `RologIo\before()` and `RologIo\after()` functions.

Regarding the ID, for "named callbacks", like plain functions or methods, the default ID is easily retrieved:

```php
use RologIo\Listener;
use function RologIo\{listen, dispatch, after};

$exclamation = after('World::__invoke', fn (object $object) => $object->message .= '!');

class World
{
    #[Listener(after:'sayHello')]
    public function __invoke(object $object): void
    {
        $object->message .= ' World';
    }
}

function sayHello(object $object): void
{
    $object->message .= 'Hello';
}

listen($exclamation, new World, 'sayHello');

$result = dispatch((object) ['message' => '']);

assert($result->message === 'Hello World!');
```

For anonymous callbacks the ID must be manually provided via an attribute or via the `RologIo\identify()` function.

```php
use RologIo\Listener;
use function RologIo\{listen, dispatch, before, after, identify};

$exclamation = after('world', fn (object $object) => $object->message .= '!');
    
$world = identify('world', fn (object $object) => $object->message .= ' World');

$hello = identify('hello', before('world', fn (object $object) => $object->message .= 'Hello'));

listen($exclamation, $world, $hello);

$result = dispatch((object) ['message' => '']);

assert($result->message === 'Hello World!');
```

These two "manual" ways of setting up ID work also on named callbacks, for which the "auto-discovered" ID is a fallback.

Moreover, classes can implement the `RologIo\Listener\IdentifiableListener` interface that has an `id()` method.



## Subscribers

A subscriber is a class that has one or more methods that are used as listeners. While it is possible to add all the methods using `listen()`, a simpler way  can be adding the subscriber via the `RologIo\subscribe()` function. Take the following example:

```php
class PostSubscriber
{
    #[Listener]
    public function postAdded(PostAddedEvent $event): void
    {
    }
    
    #[Listener]
    public function postEdited(PostEditedEvent $event): void
    {
    }
    
    #[Listener]
    public function postDeleted(PostDeletedEvent $event): void
    {
    }
}

RologIo\subscribe(new PostSubscriber);
```

The subscriber class might have methods not meant to be listeners, but all listeners in the class must have the `Listener` attribute, with or without properties (id, priority, before, after).




## Third-party PSR-14 Listener Providers

It might happen to have an instance of a `ListenerSubscriberInterface` from another PSR-14 implementation. That's the beauty of standards after all. Any instance of `ListenerSubscriberInterface` can be added via `RologIo\appendListenerProvider()`.

As the name suggests, the providers are in this way are *appended* to the Rolog.io provider,  which will process the events first.



## Object-Oriented Usage

Behind the scenes of the functions documented so far, there are objects that fully implement PSR-14.

For those who prefer to use object-oriented style, the `RologIo\EventDispatcher` can be used to dispatch events, via the `EventDispatcher::dispatch()` events defined in PSR-14 `EventDispatcherInterface`.

The `EventDispatcher` class accepts a variadic number of `ListenerProviderInterface` implementation (not just the Rolog.io implementation) in ts static constructor `EventDispatcher::new()`. After the instance is constructed, more listener providers can be added via the   `EventDispatcher::appedProvider()`.

The `ListenerProviderInterface` is implemented via the `RologIo\ListenerProvider` class, that "resolves" events the way it is described above.

To add listeners to the provider, the following methods are available:

- `ListenerProvider::addListeners()`, that accepts a variadic number of callbacks
- `ListenerProvider::addSubscriber()`, that accepts a "subscriber" class as described above.

Here's a full example:

```php
$listener = RologIo\ListenerProvider::new();
$dispatcher = RologIo\EventDispatcher::new($listener);

$listener->addListeners(
    fn (object $object) => $object->message .= 'Hello',
    fn (object $object) => $object->message .= ' World',
);

$result = $dispatcher->dispatch((object) ['message' => '']);

assert($result->message === 'Hello World');
```

In real world the objects resolution will probably happen via a DI container, but this should be enough to get anyone started.

It is worth nothing that listeners added via the `RologIo\listen` function, as well as event dispatched via  `RologIo\dispatch` will be completely **independent** from listeners and events handled via Rolog.io objects created "the OOP way".