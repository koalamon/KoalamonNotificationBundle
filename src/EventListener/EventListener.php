<?php

namespace Koalamon\NotificationBundle\EventListener;

use Bauer\IncidentDashboard\CoreBundle\Entity\Event;
use Koalamon\NotificationBundle\Sender\SenderFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventListener
{
    private $doctrineManager;
    private $router;

    public function __construct(ContainerInterface $container)
    {
        $this->doctrineManager = $container->get('doctrine')->getManager();
        $this->router = $container->get('Router');
    }

    public function onEventCreate(\Symfony\Component\EventDispatcher\Event $event)
    {
        $koalamonEvent = $event->getEvent();

        if ($this->isNotifiable($koalamonEvent, $event->getLastEvent())) {
            $this->notify($koalamonEvent);
        }
    }

    private function isNotifiable(Event $event, Event $lastEvent)
    {
        return ((!$lastEvent && $event->getStatus() == Event::STATUS_FAILURE) ||
            ($lastEvent && ($lastEvent->getStatus() != $event->getStatus())));
    }

    private function notify(Event $event)
    {
        $configs = $this->doctrineManager->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
            ->findBy(['project' => $event->getEventIdentifier()->getProject()]);

        /** @var NotificationConfiguration[] $configs */

        foreach ($configs as $config) {
            if ($config->isNotifyAll() || $config->isConnectedTool($event->getEventIdentifier()->getTool())) {
                $sender = SenderFactory::getSender($config->getSenderType());
                $sender->init($this->router, $config->getOptions());

                $sender->send($event);
            }
        }
    }

}