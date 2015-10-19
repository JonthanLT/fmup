<?php
namespace FMUP;

/**
 * @Todo this should be in a better way
 * Class Error
 * @package FMUP
 */
class Error
{
    private static $session;
    /**
     * @var Config
     */
    private static $config;

    /**
     * @return Session
     */
    public static function getSession()
    {
        if (!self::$session) {
            self::$session = Session::getInstance();
        }
        return self::$session;
    }

    /**
     * @param Session $session
     */
    public static function setSession(Session $session)
    {
        self::$session = $session;
    }

    /**
     * @return Config
     */
    public static function getConfig()
    {
        if (!self::$config) {
            throw new \LogicException('Config must be set');
        }
        return self::$config;
    }

    /**
     * @param Config $config
     */
    public static function setConfig(Config $config)
    {
        self::$config = $config;
    }

    /**
     * @todo : rewrite this since this is really dirty
     * @todo : think SOLID : this function must not Format AND Write + access to super globals that might not exit
     */
    public static function addContextToErrorLog()
    {
        error_log(self::getTrace());
    }

    static public function getTrace()
    {
        ob_start();
        if (isset($_SERVER["REMOTE_ADDR"])) {
            echo "Adresse IP de l'internaute : " . $_SERVER["REMOTE_ADDR"] . ' ' . gethostbyaddr($_SERVER["REMOTE_ADDR"]) . PHP_EOL;
        }
        if (isset($_SERVER["HTTP_HOST"])) {
            echo "URL appelée : http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . PHP_EOL;
        }

        echo "État des variables GET lors de l'erreur :" . PHP_EOL;
        print_r($_GET);
        echo PHP_EOL;
        echo "État des variables POST lors de l'erreur :" . PHP_EOL;
        print_r($_POST);
        echo PHP_EOL;
        echo "État des variables SESSION lors de l'erreur :" . PHP_EOL;
        if (self::getSession()->has('id_utilisateur')) {
            print_r(self::getSession()->get('id_utilisateur'));
            echo PHP_EOL;
        }
        if (self::getSession()->has('id_historisation')) {
            print_r(self::getSession()->get('id_historisation'));
            echo PHP_EOL;
        }
        if (self::getSession()->has('id_menu_en_cours')) {
            print_r(self::getSession()->get('id_menu_en_cours'));
            echo PHP_EOL;
        }
        if (self::getSession()->has('droits_controlleurs')) {
            print_r(self::getSession()->get('droits_controlleurs'));
            echo PHP_EOL;
        }
        echo "État des variables HTTP lors de l'erreur :" . PHP_EOL;
        $http_variable['HTTP_USER_AGENT'] = !isset($_SERVER['HTTP_USER_AGENT']) ?: $_SERVER['HTTP_USER_AGENT'];
        if (isset($_SERVER['HTTP_REFERER'])) {
            $http_variable['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
        }
        print_r($http_variable);
        echo PHP_EOL;
        echo "__________________" . PHP_EOL;
        return ob_get_clean();
    }

    static public function sendMail()
    {
        $config = self::getConfig();
        if (!\Config::isEnvoiMailPossible()) {
            return false;
        }
        $mailBody = self::mailContent();

        $mail = new \PHPMailer();
        if ($config->get('smtp_serveur') != 'localhost') {
            $mail->IsSMTP();
        }
        $mail->CharSet = "UTF-8";
        $mail->SMTPAuth = $config->get('smtp_authentification');
        $mail->SMTPSecure = $config->get('smtp_secure');

        $mail->Host = $config->get('smtp_serveur');
        $mail->Port = $config->get('smtp_port');

        if ($config->get('smtp_authentification')) {
            $mail->Username = $config->get('smtp_username');      // Gmail identifiant
            $mail->Password = $config->get('smtp_password');      // Gmail mot de passe
        }

        $mail->From = $config->get('mail_robot');
        $mail->FromName = $config->get('erreur_mail_from_name');
        $mail->Subject = '[Erreur] ' . $_SERVER['SERVER_NAME'];
        $mail->AltBody = $mailBody;
        $mail->WordWrap = 50; // set word wrap

        $mail->Body = $mailBody;

        $recipients = $config->get('mail_support');
        if (strpos($recipients, ',') === false) {
            $mail->AddAddress($recipients, "Support");
        } else {
            $tab_recipients = explode(',', $recipients);
            foreach ($tab_recipients as $recipient) {
                $mail->AddAddress($recipient);
            }
        }

        return $mail->Send();
    }

    static public function mailContent()
    {
        $trace = self::getTrace();
        ob_start();
        echo str_replace(PHP_EOL, '<br/>', $trace);
        echo "Trace complète :<br/>";

        $retour = debug_backtrace();
        ksort($retour);
        echo '<style>td{padding: 3px 5px;}</style>';
        echo '<table border="1"><tr><th>Fichier</th><th>Ligne</th><th>Fonction</th></tr>';
        unset($retour[0]);
        foreach ($retour as $trace) {
            echo '<tr>';
            echo '<td>' . ((isset($trace['file'])) ? $trace['file'] : '') . '</td>';
            echo '<td style="text-align: right;">' . ((isset($trace['line'])) ? $trace['line'] : '') . '</td>';
            echo '<td>' . ((isset($trace['class'])) ? $trace['class'] : '');
            echo (isset($trace['type'])) ? $trace['type'] : '';
            echo (isset($trace['function'])) ? $trace['function'] : '';

            $arguments = array();
            if (!empty($trace['args'])) {
                foreach ($trace['args'] as $name => $arg) {
                    if (is_array($arg)) {
                        $arguments[] = 'Array';
                    } else {
                        $arg = '"' . $arg . '"';
                        $coupure = (strlen($arg) > 50) ? '...' : '';
                        $arguments[] = substr($arg, 0, 50) . $coupure;
                    }
                }
            }
            echo '(' . implode(',', $arguments) . ')</td>';

            echo '</tr>';
        }
        if (!empty($retour[0]['args'][0]) && is_object($retour[0]['args'][0])) {
            $exception = $retour[0]['args'][0];
            /* @var $exception \Exception */
            $traces = $exception->getTrace();
            foreach ($traces as $trace) {
                echo '<tr>';
                echo '<td>' . ((isset($trace['file'])) ? $trace['file'] : '-') . '</td>';
                echo '<td style="text-align: right;">' . ((isset($trace['line'])) ? $trace['line'] : '-') . '</td>';
                echo '<td>' . ((isset($trace['class'])) ? $trace['class'] : '');
                echo (isset($trace['type'])) ? $trace['type'] : '';
                echo (isset($trace['function'])) ? $trace['function'] : '';

                $arguments = array();
                if (!empty($trace['args'])) {
                    foreach ($trace['args'] as $name => $arg) {
                        if (is_array($arg)) {
                            $arguments[] = 'Array';
                        } else {
                            $arg = '"' . $arg . '"';
                            $coupure = (strlen($arg) > 50) ? '...' : '';
                            $arguments[] = substr($arg, 0, 50) . $coupure;
                        }
                    }
                }
                echo '(' . implode(',', $arguments) . ')</td>';

                echo '</tr>';
            }
        }
        echo '</table>';
        return ob_get_clean();
    }
}
