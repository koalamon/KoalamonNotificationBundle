<?php

namespace Koalamon\NotificationBundle\Sender;

use Koalamon\NotificationBundle\Sender\EMail\EMailSender;
use Koalamon\NotificationBundle\Sender\Slack\SlackSender;

class SenderFactory
{
    static public function getSenders()
    {
        return [
            'slack' => new SlackSender(),
            'email' => new EMailSender()
        ];
    }

    /**
     * @param string $senderType
     * @return Sender
     */
    static public function getSender($senderType)
    {
        $sender = self::getSenders();
        return $sender[$senderType];
    }
}