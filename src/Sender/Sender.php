<?php

namespace Koalamon\NotificationBundle\Sender;

use Koalamon\Bundle\IncidentDashboardBundle\Entity\Event;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\Incident;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

interface Sender
{
    /*
     * return Option[]
     */
    public function getOptions();

    public function send(Event $event);

    public function init(Router $router, array $initOptions, VariableContainer $variableContainer);

    public function sendAcknowledge(Incident $incident);
}