# Trollbus Symfony Messenger Bridge

\[ [English](./README.md) | Русский \]

![](./banner.png)

Интеграция [Trollbus](https://github.com/trollbus/trollbus) с [Symfony Messenger](https://symfony.com/doc/current/messenger.html),
позволяющая использовать асинхронную обработку сообщений и возможности транспортов Symfony Messenger в приложениях на
Trollbus.

## Зачем нужен этот пакет?

Trollbus предоставляет механизм диспетчеризации сообщений и вызова обработчиков, но пока не реализует собственную
инфраструктуру асинхронной обработки.

**Trollbus Symfony Messenger Bridge** восполняет этот пробел, интегрируя Trollbus с Symfony Messenger.

Пакет позволяет:
- Выполнять обработчики синхронно или асинхронно.
- Выбирать транспорт Symfony Messenger для каждого асинхронного обработчика.
- Откладывать асинхронную обработку сообщений.
- Преобразовывать stamps Trollbus в stamps Symfony Messenger.

При этом Trollbus остаётся основным механизмом диспетчеризации сообщений, а Symfony Messenger предоставляет
инфраструктуру доставки и асинхронного выполнения.

Пакет задуман как практичное решение на период, пока Trollbus не обзавёлся собственной поддержкой асинхронной обработки.
Несмотря на небольшой объём функциональности, его достаточно для большинства типичных сценариев.

## Установка

```bash
composer require trollbus/symfony-messenger-bridge
```

Убедитесь, что в приложении установлен и настроен Symfony Messenger.

## Настройка транспортов

Транспорты настраиваются стандартными средствами Symfony Messenger.

Например, можно определить два транспорта:

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        transports:
            async1: '%env(MESSENGER_TRANSPORT_DSN)%'
            async2: '%env(MESSENGER_TRANSPORT_DSN)%'
```

В примере оба транспорта используют один DSN для простоты. В реальном приложении они могут указывать на разные очереди
или брокеры.

## Синхронная и асинхронная обработка

Чтобы назначить обработчику асинхронное выполнение, используйте атрибут `#[Async]`, указав имя транспорта Symfony Messenger.

```php
use Trollbus\SymfonyMessengerBridge\Attribute\Async;
use Trollbus\TrollbusBundle\Attribute\Handler;

final class EventHandler
{
    #[Handler]
    public function sync(SomeEvent $event): void
    {
        // Выполняется синхронно.
    }

    #[Async('async1')]
    #[Handler]
    public function async(SomeEvent $event): void
    {
        // Выполняется через транспорт async1.
    }

    #[Async('async2')]
    #[Handler]
    public function asyncOnAnotherTransport(SomeEvent $event): void
    {
        // Выполняется через транспорт async2.
    }
}
```

Один и тот же тип сообщения может обрабатываться несколькими обработчиками: как синхронными, так и асинхронными.

Например, при диспетчеризации события синхронный обработчик выполнится непосредственно в процессе обработки сообщения, а
асинхронные обработчики будут использовать указанные для них транспорты Symfony Messenger.

### Пример взаимодействия обработчиков

Рассмотрим обработчик команды, который публикует событие:

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
        // Обработка команды.

        $context->dispatch(new SomeEvent());
    }

    #[Handler]
    public function sync(SomeEvent $event): void
    {
        // Синхронная обработка события.
    }

    #[Async('async1')]
    #[Handler]
    public function async(SomeEvent $event): void
    {
        // Асинхронная обработка события.
    }
}
```

Диспетчеризация команды:

```php
$bus->dispatch(new SomeCommand());
```

В результате:
1. Выполняется обработчик `SomeCommand`.
2. Он публикует `SomeEvent`.
3. Синхронный обработчик события выполняется сразу.
4. Асинхронный обработчик получает сообщение через транспорт `async1` и обрабатывает его при доставке.

Если транспорт использует очередь, для обработки сообщений необходимо запустить соответствующий worker Symfony Messenger.

**Важно:** атрибут `#[Async]` указывает, через какой транспорт Symfony Messenger следует выполнять обработчик.
Фактическое выполнение в отдельном процессе зависит от конфигурации транспорта. Например, синхронный транспорт
Symfony Messenger не обеспечивает отложенное выполнение в другом процессе.

## Отложенная обработка

Для отложенной обработки сообщения используйте `Delay` Trollbus:

```php
use Trollbus\SymfonyMessengerBridge\Delay\Delay;

$bus->dispatch(
    Envelope::wrap(
        new SomeCommand(),
        Delay::fromSeconds(5),
    ),
);
```

В этом случае:
- Синхронные обработчики выполняются сразу.
- Асинхронная доставка сообщения откладывается на пять секунд.

Внутри интеграции `Delay` преобразуется в `DelayStamp` Symfony Messenger.

Фактическая поддержка задержки зависит от используемого транспорта Symfony Messenger.

## Преобразование stamps

Trollbus Symfony Messenger Bridge позволяет преобразовывать stamps Trollbus в соответствующие stamps Symfony Messenger.

Для этого stamp должен реализовывать интерфейс `ConvertibleToSymfonyStamp` и предоставлять метод `toSymfonyStamp()`,
возвращающий соответствующий stamp Symfony Messenger.

Например, можно реализовать `Deduplicate`, который преобразуется в `DeduplicateStamp` Symfony Messenger:

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

Теперь `Deduplicate` остаётся обычным stamp Trollbus, не зависящим от Symfony Messenger, но при передаче сообщения в
Messenger автоматически преобразуется в `DeduplicateStamp`.

Этот механизм позволяет интегрировать возможности Symfony Messenger, сохраняя независимость основного кода Trollbus от
конкретной реализации транспорта.

## Ограничения и область применения

Пакет намеренно сохраняет небольшой объём функциональности и опирается на Symfony Messenger для асинхронной доставки.

Он не пытается создавать собственную инфраструктуру очередей или заменять Symfony Messenger. Его задача — связать два
компонента так, чтобы приложения на Trollbus могли использовать возможности Messenger без необходимости самостоятельно
реализовывать интеграционный слой.

Основные особенности:
- Синхронная обработка остаётся частью обычного жизненного цикла Trollbus.
- Асинхронные обработчики используют указанные транспорты Symfony Messenger.
- Отложенная обработка и другие транспортные возможности зависят от конфигурации Messenger.
- Пользовательские stamps могут преобразовываться в stamps Symfony Messenger.

## Лицензия

См. файл [LICENSE](LICENSE).
