<?php

namespace Koalamon\NotificationBundle\EventListener;

use Bauer\IncidentDashboard\CoreBundle\Entity\Event;
use Koalamon\DefaultBundle\Menu\Element;
use Koalamon\DefaultBundle\Menu\Menu;
use Koalamon\NotificationBundle\Sender\SenderFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminMenuListener
{
    private $router;

    public function __construct(ContainerInterface $container)
    {
        $this->router = $container->get('Router');
    }

    public function onAdminMenu(\Symfony\Component\EventDispatcher\Event $event)
    {
        $menu = $event->getMenu();
        $project = $event->getProject();
        /** @var Menu $menu */

        $menu->addElement(new Element($this->router->generate('koalamon_notification_home', ['project' => $project->getIdentifier()]),
            'Notification Channels', 'menu_admin_notification_channels'));

        $menu->addElement(new Element($this->router->generate('koalamon_notification_alerts_home', ['project' => $project->getIdentifier()]),
            'Alerts', 'menu_admin_alerts'));
    }
}