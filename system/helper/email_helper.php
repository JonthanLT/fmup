<?php

/**
 * Classe permettant l'envoi d'email grâce à PHPMailer
 * @version 1.0
 */
class EmailHelper
{
    /**
     * Envoi un mail avec ou sans pièces jointes
     * @param $identifiant id du template à utiliser
     * @param $send_to Destinataire
     * @param $tokens Tokens à remplacer dans le message
     * @param $files tableau de fichier sous la forme path, name et mime
     * @param $handle ressource du fichier de log
     * @param $email_cache copie cache
     * @param $params paramètres utilisés pour les accusés de réceptions des mails (stockés dans les emails_log)
     * @param $options ajouts de paramètres complémentaires tel que le forçage de l'objet ou du message,
     *                  malgré l'utilisation d'un template de mail.
     * @return retour de la fonction mail
     */
    public static function sendEmail(
        $identifiant,
        $send_to,
        $tokens = array(),
        $files = array(),
        $handle = false,
        $email_cache = "",
        $params = array(),
        $options = array()
    ) {
        $my_mail = new PHPMailer(true);

        if (preg_match('|^(?:[^<]*<)?([^>]*)(?:>)?$|i', $send_to, $matches) ||
            !Config::isEnvoiMailPossible($identifiant)
        ) {
            $adresse_mail = $matches[1];
            $email = Email::findOne($identifiant);

            // on va vérifier que tous les emails ont bien un bon format
            // séparateur d'email, le ';'
            $no_problem = true;
            $liste_mail = explode(";", $adresse_mail);
            foreach ($liste_mail as $e) {
                if (!Is::courriel($e) && Config::isEnvoiMailPossible($identifiant)) {
                    if ($handle) {
                        FileHelper::fLog('mail', 'problème adresse mail : "' . $e . '" - ' . $send_to);
                    } else {
                        throw new \Exception('adresse email incorrecte : "' . $e . '"');
                    }
                    $no_problem = true;
                }
            }

            if ($no_problem && $email) {
                try {
                    // Remplacement du message de l'email par un message particulier si ce paramètre éxiste
                    $message = isset($options['message_foce']) ? $options['message_foce'] : $email->getMessage();
                    $message = emailHelper::remplaceToken($message, $tokens);

                    $my_mail = EmailHelper::parametrerHeaders($my_mail);

                    $my_mail->SetFrom(
                        Config::paramsVariables('mail_robot'),
                        Config::paramsVariables('mail_robot_name')
                    );
                    $my_mail = self::addReplyTo(
                        $my_mail,
                        Config::paramsVariables('mail_reply'),
                        Config::paramsVariables('mail_reply_name')
                    );
                    $my_mail = self::addBCC($my_mail, $email_cache);


                    $adress_caches = Config::paramsVariables('mail_cache');
                    $my_mail = self::addBCC($my_mail, $adress_caches);

                    // Pareil pour l'objet du message
                    if (isset($options['objet_foce'])) {
                        $objet = $options['objet_foce'];
                    } else {
                        $objet = $email->getObjet();
                    }
                    $objet = emailHelper::remplaceToken($objet, $tokens);

                    if (!Config::isEnvoiMailPossible($identifiant)) {
                        $objet .= ' ## ' . $send_to;
                    }
                    if (Config::paramsVariables('version') == 'dev') {
                        $objet = ' ** DEV ** ' . $objet;
                    }
                    $my_mail->Subject = $objet;

                    foreach ($files as $files_infos) {
                        $nom_fichier = (isset($files_infos['name'])) ? $files_infos['name'] : '';
                        if (file_exists($files_infos['path'])) {
                            $my_mail->AddAttachment($files_infos['path'], $nom_fichier);
                        }
                    }

                    $adress = emailHelper::rempAdresse($send_to, $identifiant);
                    $log_adress = $adress;
                    $my_mail = self::addAddress($my_mail, $adress);

                    /* Log des envois de mail */
                    $log_mail = new EmailLog(array('id_email' => $identifiant,
                        'objet' => $objet,
                        'message' => $message,
                        'destinataire' => $log_adress,
                        'destinataire_cache' => $adress_caches));
                    if (!$log_mail->save()) {
                        if ($handle) {
                            FileHelper::fLog(
                                'mail',
                                "Problème rencontré dans l'enregistrement email_log : " . print_r($log_mail)
                            );
                        }
                    } elseif (!empty($params['accuse'])) {
                        // gestion des accusés de reception
                        $code_unique = md5($log_mail->getId());
                        $log_mail->setCodeAccuseReception($code_unique);
                        $log_mail->setParametres(serialize($params['accuse']));
                        $log_mail->save();

                        $message = str_replace('%code_unique%', $code_unique, $message);
                    }

                    $my_mail->MsgHTML($message);

                    return $my_mail->Send();

                } catch (phpmailerException $e) {
                    if ($handle) {
                        FileHelper::fLog('mail', $e->errorMessage());
                    }
                    //emailHelper::sendEmailErreur($identifiant, $e->getMessage(), $log_adress, $objet, $message);
                } catch (Exception $e) {
                    if ($handle) {
                        FileHelper::fLog('mail', $e->getMessage());
                    }
                    //emailHelper::sendEmailErreur($identifiant, $e->getMessage(), $log_adress, $objet, $message);
                }
            } else {
                throw new \FMUP\Exception('Template email absent : ' . $identifiant);
            }
        } else {
            if ($handle) {
                FileHelper::fLog('mail', 'adresse email non reconnue : ' . $send_to);
            }
        }
    }

    /**
     * @param PHPMailer $my_mail
     * @return PHPMailer
     */
    public static function parametrerHeaders(PHPMailer $my_mail)
    {
        if (Config::paramsVariables('smtp_serveur') != 'localhost') {
            $my_mail->IsSMTP();
        }
        $my_mail->IsHTML(true);
        $my_mail->CharSet = "UTF-8";
        $my_mail->SMTPAuth = Config::paramsVariables('smtp_authentification');
        $my_mail->SMTPSecure = Config::paramsVariables('smtp_secure');

        $my_mail->Host = Config::paramsVariables('smtp_serveur');
        $my_mail->Port = Config::paramsVariables('smtp_port');

        if (Config::paramsVariables('smtp_authentification')) {
            $my_mail->Username = Config::paramsVariables('smtp_username'); // Gmail identifiant
            $my_mail->Password = Config::paramsVariables('smtp_password'); // Gmail mot de passe
        }

        return $my_mail;
    }

    /**
     * Remplace les tokens dans un message
     * @param $message texte à parser
     * @param $tokens tableau associatif des tokens à remplacer
     * @return message traité
     * @uses self::tokenReplaceMap
     */
    public static function remplaceToken($message = '', $tokens = array())
    {
        $search = array_keys($tokens);
        $replace = array_values($tokens);
        $search = array_map(array('\EmailHelper', 'tokenReplaceMap'), $search);
        return str_replace($search, $replace, $message);
    }

    private static function tokenReplaceMap($o)
    {
        return "{" . $o . "}";
    }

    /**
     * Remplace l'adresse mail en debug
     */
    public static function rempAdresse($adresse, $id_type_mail = 0)
    {
        if (!Config::isEnvoiMailPossible($id_type_mail)) {
            $adresse = Config::paramsVariables('mail_envoi_test');
        }

        return $adresse;
    }

    /*
     * Envoi d'un email sans Log, sans fichier attaché et sans copie
     * A UTILISER pour l'admin uniquement
     */
    public static function sendEmailSimple($objet, $to, $message)
    {
        $my_mail = new PHPMailer(true);

        $my_mail = self::parametrerHeaders($my_mail);
        $my_mail->SetFrom(Config::paramsVariables('mail_robot'), Config::paramsVariables('mail_robot_name'));
        $my_mail = self::addReplyTo(
            $my_mail,
            Config::paramsVariables('mail_reply'),
            Config::paramsVariables('mail_reply_name')
        );

        if (Config::paramsVariables('version') == 'dev') {
            $objet = ' ** DEV ** ' . $objet;
        }

        $my_mail->Subject = $objet;
        $my_mail->MsgHTML($message);

        $my_mail = self::addAddress($my_mail, $to);
        // on met CASTELIS en copie (pour les tests)
        $my_mail = self::addBCC($my_mail, Config::paramsVariables('mail_support'));

        return $my_mail->Send();
    }

    /****************************************************************************************
     *  fonctions à utiliser pour décoder les adresses mails contenants des ';' ou des ','  *
     ****************************************************************************************/

    public static function addReplyTo($my_mail, $adresses = '', $nom_adresse = '')
    {
        if ($adresses = '') {
            $adresses = Config::paramsVariables('mail_reply');
        }
        if ($nom_adresse = '') {
            $nom_adresse = Config::paramsVariables('mail_reply_name');
        }
        $tmp = self::explodeListEmails($adresses);
        foreach ($tmp as $adress) {
            if ($adress != "" && Is::courriel($adress)) {
                $my_mail->AddReplyTo(trim($adress), $nom_adresse);
            }
        }
        return $my_mail;
    }

    public static function addAddress($my_mail, $adresses)
    {
        $tmp = self::explodeListEmails($adresses);
        foreach ($tmp as $adress) {
            if ($adress != "" && Is::courriel($adress)) {
                $my_mail->AddAddress(trim($adress));
            }
        }
        return $my_mail;
    }

    public static function addBCC($my_mail, $adresses)
    {
        $tmp = self::explodeListEmails($adresses);
        foreach ($tmp as $adress) {
            if ($adress != "" && Is::courriel($adress)) {
                $my_mail->AddBCC(trim($adress));
            }
        }
        return $my_mail;
    }

    /*
     * transforme une liste d'email de String en tableau
     * @param string  ex: shuet@castelis.com;jha@castelis.com
     * @return table  ex: array('shuet@castelis.com', 'jha@castelis.com')
     */
    public static function explodeListEmails($adresses = '')
    {
        $char = (strpos($adresses, ';') === false) ? ',' : ';';
        return explode($char, $adresses);
    }
}
