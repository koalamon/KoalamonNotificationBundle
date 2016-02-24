<?php

namespace Koalamon\NotificationBundle\EventListener;

use Koalamon\Bundle\DefaultBundle\EventListener\AdminMenuEvent;
use Koalamon\Bundle\DefaultBundle\Menu\Element;
use Koalamon\IntegrationBundle\EventListener\PluginInitEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginListener
{
    public function __construct(ContainerInterface $container)
    {
        $this->router = $container->get('Router');
    }

    public function onKoalamonPluginAdminMenuInit(AdminMenuEvent $event)
    {
        $menu = $event->getMenu();
        $project = $event->getProject();

        $element = new Element($this->router->generate('koalamon_notification_home', ['project' => $project->getIdentifier()], true),
            'Notification Channels', 'menu_admin_notification_channels');

        $element->addSubElement(new Element($this->router->generate('koalamon_notification_home', ['project' => $project->getIdentifier()], true),
            'Channels', 'menu_admin_notification_channels'));

        $element->addSubElement(new Element($this->router->generate('koalamon_notification_alerts_home', ['project' => $project->getIdentifier()], true),
            'Alerts', 'menu_admin_alerts'));

        $menu->addElement($element);
    }
}