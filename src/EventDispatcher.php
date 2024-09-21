<?php

declare(strict_types=1);

namespace RologIo;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

final readonly class EventDispatcher implements EventDispatcherInterface
{
    /** @var \SplQueue<ListenerProviderInterface> */
    private \SplQueue $providers;

    /**
     * @param ListenerProviderInterface $listenerProvider
     * @return EventDispatcher
     */
    public static function new(ListenerProviderInterface ...$providers): EventDispatcher
    {
        return new self(...$providers);
    }

    /**
     * @param ListenerProviderInterface $listenerProvider
     */
    private function __construct(
        ListenerProviderInterface ...$providers
    ) {

        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->providers = new \SplQueue();
        foreach ($providers as $provider) {
            $this->appendProvider($provider);
        }
    }

    /**
     * @param ListenerProviderInterface $provider
     * @return static
     */
    public function appendProvider(ListenerProviderInterface $provider): static
    {
        $this->providers->enqueue($provider);

        return $this;
    }

    /**
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        $this->providers->rewind();

        $mightStop = $event instanceof StoppableEventInterface;

        while ($this->providers->valid()) {
            if (!$this->dispatchProvider($this->providers->current(), $event, $mightStop)) {
                break;
            }
            $this->providers->next();
        }

        return $event;
    }

    /**
     * @param ListenerProviderInterface $provider
     * @param object $event
     * @param bool $mightStop
     * @return bool
     */
    private function dispatchProvider(
        ListenerProviderInterface $provider,
        object $event,
        bool $mightStop
    ): bool {

        foreach ($provider->getListenersForEvent($event) as $listener) {
            assert(is_callable($listener));
            $listener($event);
            if ($mightStop && $event->isPropagationStopped()) {
                return false;
            }
        }

        return true;
    }
}
