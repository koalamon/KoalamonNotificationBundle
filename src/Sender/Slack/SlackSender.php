<?php

namespace Koalamon\NotificationBundle\Sender\Slack;

use Koalamon\Bundle\IncidentDashboardBundle\Entity\Event;
use Koalamon\NotificationBundle\Sender\Option;
use Koalamon\NotificationBundle\Sender\Sender;
use Koalamon\NotificationBundle\Sender\VariableContainer;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class SlackSender implements Sender
{
    private $webhookURL;
    private $settings;
    private $router;

    const COLOR_SUCCESS = '#27ae60';
    const COLOR_FAILURE = '#f16059';

    /**
     * return Option[]
     */
    public function getOptions()
    {
        return [
            new Option('WebhookURL', 'webhookUrl', 'Slack webbhook url', 'string', true),
            new Option('Username', 'username', 'The Username Koalamon posts from', 'string'),
            new Option('Icon', 'icon', 'The user icon', 'string')
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

        if (array_key_exists('username', $initOptions)) {
            $this->settings["username"] = $initOptions["username"];
        } else {
            $this->settings["username"] = 'www.Koalamon.com';
        }

        if (array_key_exists('link_names', $initOptions)) {
            $this->settings["link_names"] = $initOptions["link_names"];
        } else {
            $this->settings["link_names"] = true;
        }

        if (array_key_exists('icon', $initOptions)) {
            $this->settings["icon"] = $initOptions["icon"];
        } else {
            $this->settings["icon"] = 'http://www.Koalamon.com/images/logo_slack.png';
        }
    }

    /**
     * Sends a message to slack created by information given in the event.
     *
     * @param Event $event
     */
    public function send(Event $event)
    {
        $client = new \Maknz\Slack\Client($this->webhookURL, $this->settings);

        $gotoUrl = "<" . $this->router->generate("bauer_incident_dashboard_core_homepage", array('project' => $event->getEventIdentifier()->getProject()->getIdentifier()), true) . "|Go to Koalamon>";

        if ($event->hasUrl()) {
            $gotoUrl .= "\n<" . $event->getUrl() . "|Go to " . $event->getEventIdentifier()->getTool()->getName() . ">";
        }

        if ($event->getStatus() == Event::STATUS_SUCCESS) {
            $color = self::COLOR_SUCCESS;
            $label = "Your test succeeded (" . $event->getSystem() . ")\nIdentifier: " . $event->getEventIdentifier()->getIdentifier();
            $message = "";
        } else {
            $color = self::COLOR_FAILURE;
            $label = "Your test failed (" . $event->getEventIdentifier()->getSystem()->getName() . ") \nIdentifier: " . $event->getEventIdentifier()->getIdentifier();
            $message = $this->slackifyText($event->getMessage()) . "\n";
        }

        $client->enableMarkdown()
            ->attach(['text' => $message . $gotoUrl, 'color' => $color])
            ->send($label);
    }

    /**
     * Reformats html text to slack readable text
     *
     * @param string $text
     * @return string
     */
    public static function slackifyText($text)
    {
        $message = str_replace("<b>", "", $text . "\n");
        $message = str_replace("</b>", "", $message);
        $message = str_replace("<br>", "\n", $message);
        $message = str_replace("<strong>", "", $message);
        $message = str_replace("</strong>", "", $message);
        $message = str_replace("<ul>", "\n", $message);
        $message = str_replace("</ul>", "", $message);
        $message = str_replace("</li>", "\n", $message);
        $message = str_replace("<li>", "  - ", $message);

        return $message;
    }
}