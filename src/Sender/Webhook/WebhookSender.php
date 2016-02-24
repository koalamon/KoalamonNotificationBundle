<?php

namespace Koalamon\NotificationBundle\Sender\Webhook;

use GuzzleHttp\Client;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\Event;
use Koalamon\NotificationBundle\Sender\Option;
use Koalamon\NotificationBundle\Sender\Sender;
use Koalamon\NotificationBundle\Sender\VariableContainer;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class WebhookSender implements Sender
{
    private $webhookURL;
    private $router;
    private $varContainer;

    const COLOR_SUCCESS = '#27ae60';
    const COLOR_FAILURE = '#f16059';

    /**
     * return Option[]
     */
    public function getOptions()
    {
        return [
            new Option('WebhookURL', 'webhookUrl', 'Slack webbhook url', 'string', true),
            new Option('Payload', 'payload', 'the http payload', 'info', true, "The payload looks like this: ..."),
        ];
    }

    /**
     * Initializes the sender
     *
     * @param Router $router
     * @param array $initOptions
     */
    public function init(Router $router, array $initOptions, VariableContainer $variableContainer)
    {
        if (array_key_exists('webhookUrl', $initOptions)) {
            $this->webhookURL = $initOptions["webhookUrl"];
        } else {
            throw new \RuntimeException('No webhookURL given.');
        }

        $this->router = $router;
        $this->varContainer = $variableContainer;
    }

    /**
     * Sends a message to slack created by information given in the event.
     *
     * @param Event $event
     */
    public function send(Event $event)
    {
        $payload = [
            'identifier' => $event->getEventIdentifier()->getIdentifier(),
            'system' => $event->getSystem(),
            'status' => $event->getStatus(),
            'message' => $event->getMessage(),
            'type' => $event->getEventIdentifier()->getTool()->getIdentifier(),
            'value' => $event->getValue()
        ];

        $httpClient = new Client();
        $httpClient->request('POST', $this->webhookURL, ['body' => json_encode($payload)]);
    }
}
