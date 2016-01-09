<?php

namespace Koalamon\NotificationBundle\EventListener;

use Bauer\IncidentDashboard\CoreBundle\Entity\Event;
use Koalamon\NotificationBundle\Sender\SenderFactory;
use Koalamon\NotificationBundle\Sender\VariableContainer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventListener
{
    private $doctrineManager;
    private $router;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->doctrineManager = $container->get('doctrine')->getManager();
        $this->router = $container->get('Router');
        $this->container = $container;
    }

    public function onEventCreate(\Symfony\Component\EventDispatcher\Event $event)
    {
        $koalamonEvent = $event->getEvent();

        if ($event->hasLastEvent()) {
            if ($this->isNotifiable($koalamonEvent, $event->getLastEvent())) {
                $this->notify($koalamonEvent);
            }
        } else {
            if ($this->isNotifiable($koalamonEvent)) {
                $this->notify($koalamonEvent);
            }
        }
    }

    private function isNotifiable(Event $event, Event $lastEvent = null)
    {
        return ((!$lastEvent && $event->getStatus() == Event::STATUS_FAILURE) ||
            ($lastEvent && ($lastEvent->getStatus() != $event->getStatus())));
    }

    private function notify(Event $event)
    {
        $configs = $this->doctrineManager->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
            ->findBy(['project' => $event->getEventIdentifier()->getProject()]);

        /** @var NotificationConfiguration[] $configs */

        $container = new VariableContainer();
        $container->addVariable('event.status', $event->getStatus());

        foreach ($configs as $config) {
            if ($config->isNotifyAll() || $config->isConnectedTool($event->getEventIdentifier()->getTool())) {
                $sender = SenderFactory::getSender($config->getSenderType());

                if ($sender instanceof ContainerAwareInterface) {
                    $sender->setContainer($this->container);
                }

                $sender->init($this->router, $config->getOptions(), $container);

                $sender->send($event);
            }
        }
    }

}