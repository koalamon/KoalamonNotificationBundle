<?php
/**
 * Created by PhpStorm.
 * User: nils.langner
 * Date: 30.12.15
 * Time: 13:56
 */

namespace Koalamon\NotificationBundle\Sender;


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