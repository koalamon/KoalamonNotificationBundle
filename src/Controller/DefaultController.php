<?php

namespace Koalamon\NotificationBundle\Controller;

use Koalamon\Bundle\IncidentDashboardBundle\Controller\ProjectAwareController;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\Event;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\EventIdentifier;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\Project;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\System;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\Tool;
use Koalamon\Bundle\IncidentDashboardBundle\Entity\UserRole;
use Koalamon\NotificationBundle\EventListener\EventListener;
use Koalamon\NotificationBundle\Sender\EMail\EMailSender;
use Koalamon\NotificationBundle\Sender\Slack\SlackSender;
use Koalamon\NotificationBundle\Sender\Webhook\WebhookSender;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Koalamon\NotificationBundle\Entity\NotificationConfiguration;

class DefaultController extends ProjectAwareController
{
    private function getSenders()
    {
        return [
            'slack' => ['name' => 'Slack', 'description' => '', 'sender' => new SlackSender()],
            'email' => ['name' => 'E-Mail', 'description' => '', 'sender' => new EMailSender()],
            'webhook' => ['name' => 'Webhook', 'descritpion' => '', 'sender' => new WebhookSender()],
        ];
    }

    public function indexAction()
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $senders = $this->getSenders();

        $configs = $this->getDoctrine()->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
            ->findBy(array('project' => $this->getProject()), ['name' => 'ASC']);

        return $this->render('KoalamonNotificationBundle:Default:index.html.twig', array('senders' => $senders, 'configs' => $configs));
    }

    public function deleteAction(NotificationConfiguration $notificationConfiguration)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $em = $this->getDoctrine()->getManager();

        $em->remove($notificationConfiguration);
        $em->flush();

        return $this->redirectToRoute('koalamon_notification_home');
    }

    public function createAction($senderIdentifier)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $senders = $this->getSenders();
        $sender = $senders[$senderIdentifier]['sender'];

        $config = new NotificationConfiguration();
        $config->setSenderType($senderIdentifier);

        return $this->render('KoalamonNotificationBundle:Default:create.html.twig', array('sender' => $sender, 'config' => $config));
    }

    public function editAction(NotificationConfiguration $notificationConfiguration)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $senders = $this->getSenders();
        $sender = $senders[$notificationConfiguration->getSenderType()]['sender'];

        return $this->render('KoalamonNotificationBundle:Default:create.html.twig', array('sender' => $sender, 'config' => $notificationConfiguration));
    }

    public function storeAction(Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $em = $this->getDoctrine()->getManager();

        if ($request->get('configurationId') > 0) {
            $configuration = $this->getDoctrine()
                ->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
                ->find($request->get('configurationId'));
        } else {
            $configuration = new NotificationConfiguration();
            $configuration->setNotifyAll(false);
        }

        $configuration->setOptions($request->get('options'));
        $configuration->setSenderType($request->get('senderIdentifier'));
        $configuration->setProject($this->getProject());
        $configuration->setName($request->get('name'));

        $em->persist($configuration);
        $em->flush();

        return $this->redirectToRoute('koalamon_notification_home');
    }

    public function sendTestNotificationAction(NotificationConfiguration $configuration)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $system = new System();
        $system->setName('TEST_SYSTEM');
        $system->setUrl('http://www.koalamon.com');
        $system->setIdentifier('TEST_NOTIFICATION_SYSTEM_IDENTIFIER');

        $tool = new Tool();
        $tool->setName('TEST_NOTIFICATION');
        $tool->setIdentifier('TEST_NOTIFICATION_TOOL_IDENTIFIER');

        $eventIdentifier = new EventIdentifier();
        $eventIdentifier->setProject($this->getProject());
        $eventIdentifier->setTool($tool);
        $eventIdentifier->setIdentifier('TEST_NOTIFICATION_EVENT_IDENTIFIER');

        $eventIdentifier->setSystem($system);
        $eventIdentifier->setCurrentState(Event::STATUS_FAILURE);

        $event = new Event();
        $event->setStatus(Event::STATUS_FAILURE);
        $event->setMessage('This is a test notification send by ' . $this->getUser()->getUsername() . '.');
        $event->setEventIdentifier($eventIdentifier);
        $event->setType($tool->getIdentifier());

        $notificationSender = new EventListener($this->container);
        $notificationSender->sendNotification($configuration, $event);

        return new JsonResponse(['status' => 'success', 'message' => 'Test notification was send.', 'configId' => $configuration->getId()]);
    }
}
