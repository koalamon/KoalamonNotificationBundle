<?php

namespace Koalamon\NotificationBundle\Sender\EMail;

use Bauer\IncidentDashboard\CoreBundle\Entity\Event;
use Koalamon\NotificationBundle\Sender\Option;
use Koalamon\NotificationBundle\Sender\Sender;
use Koalamon\NotificationBundle\Sender\VariableContainer;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EMailSender implements Sender, ContainerAwareInterface
{
    private $emailAddresses;
    private $subject;
    private $router;

    /**
     * @var VariableContainer
     */
    private $varContainer;
    private $container;

    const COLOR_SUCCESS = '#27ae60';
    const COLOR_FAILURE = '#f16059';

    /**
     * return Option[]
     */
    public function getOptions()
    {
        return [
            new Option('E-Mail Addresses *', 'emailaddresses', 'List of comma seperated e-mail addresses.', 'text'),
            new Option('Subject', 'subject', 'The email subject', 'text', false),
        ];
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Initializes the sender
     *
     * @param Router $router
     * @param array $initOptions
     */
    public function init(Router $router, array $initOptions, VariableContainer $variableContainer)
    {
        if (array_key_exists('emailaddresses', $initOptions)) {
            $this->emailAddresses = explode(',', $initOptions["emailaddresses"]);
        } else {
            throw new \RuntimeException('No email addresses given.');
        }

        $this->router = $router;
        $this->varContainer = $variableContainer;

        if (array_key_exists('subject', $initOptions)) {
            $this->subject = $initOptions["subject"];
        } else {
            $this->subject = 'Koalamon Alert';
        }
    }

    /**
     * Sends a message to slack created by information given in the event.
     *
     * @param Event $event
     */
    public function send(Event $event)
    {
        $subject = $this->varContainer->replace($this->subject);

        $body = $this->container->get('templating')->render('KoalamonNotificationBundle:Sender:Email/email.html.twig',
            $this->varContainer->getVariables());

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('send@example.com')
            ->setTo($this->emailAddresses)
            ->setBody(
                $body,
                'text/html');

        $this->container->get('mailer')->send($message);
    }
}