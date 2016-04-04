<?php

namespace Koalamon\NotificationBundle\EventListener;

use Koalamon\Bundle\DefaultBundle\EventListener\IncidentEvent;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\Event;
use Koalamon\NotificationBundle\Entity\NotificationConfiguration;
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
        /** @var Event $koalamonEvent */

        $this->notify($koalamonEvent, $event->getLastEvent());
    }

    public function onIncidentAcknowledge(IncidentEvent $incidentEvent)
    {
        $tool = $incidentEvent->getIncident()->getEventIdentifier()->getTool();
        $ackUser = $incidentEvent->getIncident()->getAcknowledgedBy();

        $configs = $this->doctrineManager->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
            ->findBy(['project' => $incidentEvent->getIncident()->getEventIdentifier()->getProject()]);

        $container = new VariableContainer();
        $container->addVariable('user', $ackUser);
        $container->addVariable('incident', $incidentEvent->getIncident());

        foreach ($configs as $config) {
            if ($config->isNotifyAll() || $config->isConnectedTool($tool)) {
                $sender = SenderFactory::getSender($config->getSenderType());

                if ($sender instanceof ContainerAwareInterface) {
                    $sender->setContainer($this->container);
                }

                $sender->init($this->router, $config->getOptions(), $container);
                $sender->sendAcknowledge($incidentEvent->getIncident());
            }
        }
    }

    private function isNotifiableEvent(NotificationConfiguration $config, Event $event, Event $lastEvent = null)
    {
        switch ($config->getNotificationCondition()) {

            case NotificationConfiguration::NOTIFICATION_CONDITION_ALL:
                return true;

            default:
                return (!$lastEvent && $event->getStatus() == Event::STATUS_FAILURE) || ($lastEvent && ($lastEvent->getStatus() != $event->getStatus()));
        }
    }

    private function notify(Event $event, Event $lastEvent = null)
    {
        $configs = $this->doctrineManager->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
            ->findBy(['project' => $event->getEventIdentifier()->getProject()]);

        foreach ($configs as $config) {
            if ($config->isNotifyAll() || $config->isConnectedTool($event->getEventIdentifier()->getTool())) {
                if ($this->isNotifiableEvent($config, $event, $lastEvent)) {
                    $this->sendNotification($config, $event, $lastEvent);
                }
            }
        }
    }

    public function sendNotification(NotificationConfiguration $config, Event $event, Event $lastEvent = null)
    {
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

        $sender = SenderFactory::getSender($config->getSenderType());

        if ($sender instanceof ContainerAwareInterface) {
            $sender->setContainer($this->container);
        }

        $sender->init($this->router, $config->getOptions(), $container);
        $sender->send($event);
    }
}
