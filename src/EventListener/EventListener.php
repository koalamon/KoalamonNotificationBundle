<?php

namespace Koalamon\NotificationBundle\EventListener;

use Koalamon\Bundle\IncidentDashboardBundle\Entity\Event;
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

    private function notify(Event $event, Event $lastEvent = null)
    {
        $configs = $this->doctrineManager->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
            ->findBy(['project' => $event->getEventIdentifier()->getProject()]);

        /** @var NotificationConfiguration[] $configs */

        $container = new VariableContainer();
        $container->addVariable('event.status', $event->getStatus());
        $container->addVariable('event.message', $event->getMessage());
        $container->addVariable('event.url', $this->router->generate("bauer_incident_dashboard_core_homepage", array('project' => $event->getEventIdentifier()->getProject()->getIdentifier()), true));

        if ($lastEvent) {
            $container->addVariable('lastevent.message', $lastEvent->getMessage());
            $container->addVariable('lastevent.status', $lastEvent->getStatus());
        }

        $container->addVariable('system.name', $event->getSystem());

        $container->addVariable('tool.name', $event->getEventIdentifier()->getTool()->getName());

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