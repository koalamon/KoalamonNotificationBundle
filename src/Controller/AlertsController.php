<?php

namespace Koalamon\NotificationBundle\Controller;

use Bauer\IncidentDashboard\CoreBundle\Controller\ProjectAwareController;
use Bauer\IncidentDashboard\CoreBundle\Entity\Tool;
use Bauer\IncidentDashboard\CoreBundle\Entity\UserRole;
use Symfony\Component\HttpFoundation\Request;
use Koalamon\NotificationBundle\Entity\NotificationConfiguration;

class AlertsController extends ProjectAwareController
{
    public function indexAction()
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $configs = $this->getDoctrine()->getRepository('KoalamonNotificationBundle:NotificationConfiguration')
            ->findBy(array('project' => $this->getProject()), ["name" => "ASC"]);

        return $this->render('KoalamonNotificationBundle:Alerts:index.html.twig', array('configs' => $configs));
    }

    public function editAction(NotificationConfiguration $notificationConfiguration)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $tools = $this->getDoctrine()->getRepository('BauerIncidentDashboardCoreBundle:Tool')
            ->findBy(array('project' => $this->getProject(), 'active' => true), ["name" => "ASC"]);

        return $this->render('KoalamonNotificationBundle:Alerts:edit.html.twig', array('config' => $notificationConfiguration, 'tools' => $tools));
    }

    public function storeAction(NotificationConfiguration $notificationConfiguration, Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        if ($request->get('notify_all') === "true") {
            $notificationConfiguration->setNotifyAll(true);
            $notificationConfiguration->clearConnectedTools();
        } else {
            $notificationConfiguration->setNotifyAll(false);
            $notificationConfiguration->clearConnectedTools();

            $tools = $request->get('tools');
            if (!is_null($tools)) {
                foreach ($tools as $toolId => $value) {
                    $tool = $this->getDoctrine()->getRepository('BauerIncidentDashboardCoreBundle:Tool')
                        ->find((int)$toolId);
                    /** @var Tool $tool */

                    if ($tool->getProject() == $this->getProject()) {
                        $notificationConfiguration->addConnectedTool($tool);
                    }
                }
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($notificationConfiguration);
        $em->flush();

        return $this->redirectToRoute('koalamon_notification_alerts_home');
    }
}
