<?php

namespace App\Manager;

use Symfony\Component\Mime\Email;

class EmailManager
{
    public function __construct()
    {
    }

    public function sendMail($recipient, $subject, $textBody)
    {
        $success = shell_exec("python python/mail.py \"$recipient\" \"$subject\" \"$textBody\"");
        if (trim($success) == 'OK') {
            // alles gut
            return true;
        } else {
            // fehler
            $dateTime = new \DateTime('now');
            file_put_contents('var/log/python_custom_mail.log', $dateTime->format('Y-m-d__H:i:s')."\npython output:\n".$success."\nmailstring:\n$mailString\n\n");
            return false;
        }
    }
}