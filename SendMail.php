<?
/* 
| OireMail, Version 1.4|
| Copyright © Nostië & Menelion Elensúle, The Fantasy social network of Oire, 2010 — 2011|
| http://oire.org/ |
| Portions Copyright (idea and some excerts of code) |
| http://www.php.su/articles/?cat=email&page=007 |
*/

Class SendMail
{

    public function __construct($from_name, $from_addr, $smtp_server, $smtp_domain = "",
                                $smtp_login = "", $smtp_pass = "", $smtp_port = 25)
    {
        $this->from_name = $from_name;
        $this->from_addr = $from_addr;
        $this->smtp_server = $smtp_server;
        $this->smtp_domain = $smtp_domain;
        $this->smtp_login = $smtp_login;
        $this->smtp_pass = $smtp_pass;
        $this->smtp_port = $smtp_port;
    }

    private function HeaderEncode($str)
    {
// For the compatibility with PHP versions lower than 5.3.0
        if (!function_exists('quoted_printable_encode')) {
            function quoted_printable_encode($str)
            {
                $res = str_replace("+", "_", str_replace("%", "=", urlencode($str)));
                return $res;
            }
        }
        $result = '=?utf-8?Q?' . quoted_printable_encode($str) . '?=';
        return $result;
    }

    private function GetResponse($smtp)
    {
        $data = "";
        while ($str = fgets($smtp, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $data;
    }

    /**
     * @param {String|Array} $tonames
     * @param {String|Array} $toemails
     * @param {String} $subject
     * @param {String} $message
     * @return string
     */
    public function Send($tonames, $toemails, $subject, $message)
    {
// It doesn't matter if the user defined "some@domain.com" or "John Doe <some@domain.com>"
        $from = empty($this->from_name) ? $this->from_addr : $this->HeaderEncode($this->from_name) . " <" . $this->from_addr . ">";
// The user can enter only one pair as a string
        $tonames = is_array($tonames) ? $tonames : (array)$tonames;
        $toemails = is_array($toemails) ? $toemails : (array)$toemails;
// Avoiding names and mails mismatch
        $n = count($tonames);
        $m = count($toemails);
        if ($n != $m) {
            $err = "Recipients amount mismatch: $n names, $m addresses";
            return $err;
        }

        $smtp = fsockopen($this->smtp_server, $this->smtp_port, $errno, $errstr, 10);
        if (!$smtp) {
            $err = sprintf("Error opening socket at %s on port %d: %s (error code: %d)", $this->smtp_server, $this->smtp_port, $errstr, $errno);
            fclose($smtp);
            return $err;
        }
        $respdata = $this->GetResponse($smtp);
        if (substr($respdata, 0, 3) != 220) {
            $err = "Greeting error: $respdata";
            fclose($smtp);
            return $err;
        }
// do we need authorization?
        if ((!empty($this->smtp_domain)) && (!empty($this->smtp_login)) && (!empty($this->smtp_pass))) {
// Authorization
            fputs($smtp, "ehlo " . $this->smtp_domain . "\r\n");
            $respdata = $this->GetResponse($smtp);
            if (substr($respdata, 0, 3) != 250) {
                $err = "EHLO error: $respdata";
                fclose($smtp);
                return $err;
            }
            fputs($smtp, "AUTH LOGIN\r\n");
            $respdata = $this->GetResponse($smtp);
            if (substr($respdata, 0, 3) != 334) {
                $err = "Server didn't allow to start authorization: $respdata";
                fclose($smtp);
                return $err;
            }
            fputs($smtp, base64_encode($this->smtp_login) . "\r\n");
            $respdata = $this->GetResponse($smtp);
            if (substr($respdata, 0, 3) != 334) {
                $err = "Invalid login: $respdata";
                fclose($smtp);
                return $err;
            }
            fputs($smtp, base64_encode($this->smtp_pass) . "\r\n");
            $respdata = $this->GetResponse($smtp);
            if (substr($respdata, 0, 3) != 235) {
                $err = "Invalid password: $respdata";
                fclose($smtp);
                return $err;
            }
        } else {
// No authorization
            fputs($smtp, "helo localhost \r\n");
            $respdata = $this->GetResponse($smtp);
            if (substr($respdata, 0, 3) != 250) {
                $err = "HELO error: $respdata";
                fclose($smtp);
                return $err;
            }
        }

// Starting the actual sendmail
        for ($i = 0; $i < $m; $i++) {
            fputs($smtp, "MAIL FROM:$this->from_addr\r\n");
            $respdata = $this->GetResponse($smtp);
            if (substr($respdata, 0, 3) != 250) {
                $err = "Server didn't allow the MAIL FROM command on sending to $toemails[$i]: $respdata";
                fclose($smtp);
                return $err;
            }
            fputs($smtp, "RCPT TO:$toemails[$i]\r\n");
            $respdata = $this->GetResponse($smtp);
            if ((substr($respdata, 0, 3) != 250) && (substr($respdata, 0, 3) != 251)) {
                $err = "Server didn't accept the RCPT TO command for $toemails[$i]: $respdata";
                fclose($smtp);
                return $err;
            }
            fputs($smtp, "DATA\r\n");
            $respdata = $this->GetResponse($smtp);
            if ((substr($respdata, 0, 3) != 354) && (substr($respdata, 0, 3) != 250)) {
                $err = "Server didn't accept the DATA command: $respdata";
                fclose($smtp);
                return $err;
            }

// Building the headers
            $header = "Date: " . date("D, j M Y G:i:s") . "\r\n";
            $header .= "From: " . $from . "\r\n";
            $header .= "X-Mailer: Oire Mail V1.4\r\n";
            $header .= "Reply-To: " . $from . "\r\n";
            $header .= "X-Priority: 3 (Normal)\r\n";
            $header .= "Message-ID: <406156191." . date("YmjHis") . "@" . $this->smtp_domain . ">\r\n";
// Whether the name is set or not 
            if (!empty($tonames[$i])) {
                $header .= "To: " . $this->HeaderEncode($tonames[$i]) . " <$toemails[$i]>\r\n";
            } else {
                $header .= "To: $toemails[$i]\r\n";
            }
            $header .= "Subject: " . $this->HeaderEncode($subject) . "\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-Type: text/html; charset=utf-8\r\n";
            $header .= "Content-Transfer-Encoding: 8bit\r\n";
// End of header building

            fputs($smtp, $header . "\r\n" . $message . "\r\n.\r\n");
            $respdata = $this->GetResponse($smtp);
            if ((substr($respdata, 0, 3) != 250) && (substr($respdata, 0, 3) != 354)) {
                $err = "Error sending message to $toemails[$i]: $respdata";
                fclose($smtp);
                return $err;
            }
            fputs($smtp, "RSET\r\n");
            $respdata = $this->GetResponse($smtp);
            if (substr($respdata, 0, 3) != 250) {
                $err = "Error terminating send process on $toemails[$i]: $respdata";
            }
        }
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        return "";
    }
}

?>