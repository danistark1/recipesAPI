<?php


namespace App;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CorsListener implements EventSubscriberInterface {

    /**
     * Get SubscribedEvents.
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array {
        return [
            KernelEvents::RESPONSE => 'onResponse'
        ];
    }

    /**
     * Hook into response and update headers.
     *
     * @param ResponseEvent $filterResponseEvent
     */
    public function onResponse(ResponseEvent $filterResponseEvent) {
        $response = $filterResponseEvent->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->headers->set('Allow', 'GET, POST, OPTIONS, PUT, DELETE');
    }
}
