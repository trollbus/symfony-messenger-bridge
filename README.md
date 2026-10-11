# Trollbus Symfony Messenger Bridge

\[ English | [Русский](./README-RU.md) \]

![](./banner.png)

Integration of [Trollbus](https://github.com/trollbus/trollbus) with [Symfony Messenger](https://symfony.com/doc/current/messenger.html), enabling asynchronous message handling and the use of Symfony Messenger transports in Trollbus applications.

## Why this package?

Trollbus provides message dispatching and handler invocation mechanisms, but does not yet implement its own asynchronous processing infrastructure.

**Trollbus Symfony Messenger Bridge** fills this gap by integrating Trollbus with Symfony Messenger.

The package allows you to:
- Execute handlers synchronously or asynchronously.
- Select a Symfony Messenger transport for each asynchronous handler.
- Delay message processing.
- Convert Trollbus stamps into Symfony Messenger stamps.

Trollbus remains the primary message dispatching mechanism, while Symfony Messenger provides the infrastructure for message delivery and asynchronous execution.

This package is intended as a practical solution until Trollbus introduces native support for asynchronous processing. Despite its deliberately small feature set, it covers most common use cases.

## Installation

```bash
composer require trollbus/symfony-messenger-bridge
```

Make sure Symfony Messenger is installed and configured in your application.

## Configuring transports

Transports are configured using the standard Symfony Messenger configuration.

For example, you can define two transports:

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        transports:
            async1: '%env(MESSENGER_TRANSPORT_DSN)%'
            async2: '%env(MESSENGER_TRANSPORT_DSN)%'
```

For simplicity, both transports use the same DSN in this example. In a real application, they can point to different queues or brokers.

## Synchronous and asynchronous processing

To configure a handler for asynchronous execution, use the `#[Async]` attribute and specify the name of a Symfony Messenger transport.

```php
use Trollbus\SymfonyMessengerBridge\Attribute\Async;
use Trollbus\TrollbusBundle\Attribute\Handler;

final class EventHandler
{
    #[Handler]
    public function sync(SomeEvent $event): void
    {
        // Executed synchronously.
    }

    #[Async('async1')]
    #[Handler]
    public function async(SomeEvent $event): void
    {
        // Executed through the async1 transport.
    }

    #[Async('async2')]
    #[Handler]
    public function asyncOnAnotherTransport(SomeEvent $event): void
    {
        // Executed through the async2 transport.
    }
}
```

The same message type can be handled by multiple handlers, both synchronous and asynchronous.

For example, when an event is dispatched, the synchronous handler executes as part of the current message-processing flow, while asynchronous handlers use their configured Symfony Messenger transports.

### Example: handler interaction

Consider a command handler that dispatches an event:

```php
use Trollbus\SymfonyMessengerBridge\Attribute\Async;
use Trollbus\TrollbusBundle\Attribute\Handler;

final class EventHandler
{
    #[Handler]
    public function command(
        SomeCommand $command,
        MessageContext $context,
    ): void {
        // Handle the command.

        $context->dispatch(new SomeEvent());
    }

    #[Handler]
    public function sync(SomeEvent $event): void
    {
        // Handle the event synchronously.
    }

    #[Async('async1')]
    #[Handler]
    public function async(SomeEvent $event): void
    {
        // Handle the event asynchronously.
    }
}
```

Dispatch the command:

```php
$bus->dispatch(new SomeCommand());
```

The following sequence takes place:

1. The `SomeCommand` handler is executed.
2. It dispatches a `SomeEvent`.
3. The synchronous event handler executes immediately.
4. The asynchronous handler receives the message through the `async1` transport and processes it when the message is delivered.

If the transport uses a queue, you must run the corresponding Symfony Messenger worker to process messages.

**Important:** The `#[Async]` attribute specifies which Symfony Messenger transport should be used to execute a handler. Whether execution actually takes place in a separate process depends on the transport configuration. For example, a synchronous Symfony Messenger transport does not provide deferred execution in another process.

## Delayed processing

To delay message processing, use Trollbus's `Delay`:

```php
use Trollbus\SymfonyMessengerBridge\Delay\Delay;

$bus->dispatch(
    Envelope::wrap(
        new SomeCommand(),
        Delay::fromSeconds(5),
    ),
);
```

In this case:
- Synchronous handlers execute immediately.
- Asynchronous message delivery is delayed by five seconds.

Under the hood, `Delay` is converted into a Symfony Messenger `DelayStamp`.

Actual delay support depends on the Symfony Messenger transport being used.

## Stamp conversion

Trollbus Symfony Messenger Bridge allows Trollbus stamps to be converted into corresponding Symfony Messenger stamps.

To enable conversion, a stamp must implement the `ConvertibleToSymfonyStamp` interface and provide a `toSymfonyStamp()` method that returns the corresponding Symfony Messenger stamp.

For example, you can implement `Deduplicate`, which is converted into Symfony Messenger's `DeduplicateStamp`:

```php
use Symfony\Component\Messenger\Stamp\DeduplicateStamp;

final class Deduplicate implements ConvertibleToSymfonyStamp
{
    public function __construct(
        public readonly string $deduplicationKey,
    ) {}

    public function toSymfonyStamp(): DeduplicateStamp
    {
        return new DeduplicateStamp(
            key: $this->deduplicationKey,
        );
    }
}
```

`Deduplicate` remains a regular Trollbus stamp with no dependency on Symfony Messenger. However, when the message is passed to Messenger, it is automatically converted into a `DeduplicateStamp`.

This mechanism allows you to integrate Symfony Messenger capabilities while keeping Trollbus's core code independent of the specific transport implementation.

## Limitations and intended use

The package deliberately keeps its feature set small and relies on Symfony Messenger for asynchronous message delivery.

It does not attempt to implement its own queue infrastructure or replace Symfony Messenger. Its purpose is to connect the two components so that Trollbus applications can use Messenger's capabilities without having to implement the integration layer themselves.

Key characteristics:
- Synchronous processing remains part of Trollbus's normal message-processing lifecycle.
- Asynchronous handlers use the configured Symfony Messenger transports.
- Delayed processing and other transport-specific capabilities depend on the Messenger configuration.
- Custom stamps can be converted into Symfony Messenger stamps.

## License

See the [LICENSE](LICENSE) file.