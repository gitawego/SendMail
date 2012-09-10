SendMail
========

send mail with STMP  in PHP

forked from http://www.php.su/articles/?cat=email&page=007

example: 

    require_once ("oire_mail.php");

    /****************/
    /* Examples of sending mail with authentication */
    /****************/
    
    // Simple example without any new classes
    $notify=new SendMail ("Harry J. Potter", "harry@potter.com", "smtp.potter.com", "potter.com", "harry@potter.com", "Allohomora");
    $tonames=array("Albus Dumbledore", "Minerva McGonnagall");
    $toemails=array("adumbledore@hogwarts.com", "mmcgonnagall@hogwarts.com");
    $subject="Test message";
    $body="Dear professors,\r\nThis is a test message to inform you that our new mail service works.\r\n\r\n-- \r\nWith best regards,\r\nHarry";
    $send=$notify->Send($tonames, $toemails, $subject, $body);
    if (!empty($send)) {
    die ("An error occured during sending mail: ".$send);
    }