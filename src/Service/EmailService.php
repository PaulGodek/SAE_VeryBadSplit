<?php

namespace App\VeryBadSplit\Service;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Service\Exception\ServiceException;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    private static ?PHPMailer $instanceMailer = null;

    public static function getInstanceMailer(): PHPMailer
    {
        if (is_null(EmailService::$instanceMailer)) {
            $mailer = new PHPMailer(true);
            // Paramètres du serveur
            $mailer->isSMTP();                                      // Utiliser SMTP
            $mailer->Host = 'smtp.gmail.com';                       // Serveur SMTP
            $mailer->SMTPAuth = true;                               // Activer l'authentification SMTP
            $mailer->Username = 'VeryBadSplitC@gmail.com';               // Nom d'utilisateur SMTP
            $mailer->Password = 'rjzl fxoh arzv bsvk';                // App password
            $mailer->SMTPSecure = PHPmailer::ENCRYPTION_STARTTLS;   // Activer TLS
            $mailer->Port = 587;                                    // Port TCP pour TLS
            $mailer->CharSet = 'UTF-8';
            EmailService::$instanceMailer = $mailer;
        }
        return EmailService::$instanceMailer;
    }


    /**
     * @throws ServiceException
     */
    public static function envoyerMailMdpOublie(Utilisateur $utilisateur,$mdp): void
    {

        $destinataire = $utilisateur->getEmail();
        $login = $utilisateur->getLogin();
        $loginHTMl = htmlspecialchars($login);

        $lienChangementMdp = Conteneur::recupererService("assistantUrl")->getAbsoluteUrl("connexion");
        $sujet = "Réinitialisation du mot de passe";
        $corpsEmailHTML = "
        <p>Vous avez demandé la reinitialisation de votre mot de passe pour le compte $loginHTMl.
        
        Votre nouveau mot de passe est $mdp, cliquez <a href=\"$lienChangementMdp\">ici</a> pour vous connecter.
        N'oubliez pas de modifier ce mot de passe lors de votre prochaine connexion.
        
        Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.
        </p>
        ";
        $corpsEmailAlt = "Vous avez demandé la reinitialisation de votre mot de passe pour le compte $loginHTMl.
        
        Votre nouveau mot de passe est $mdp, rendez-vous sur le lien suivant: $lienChangementMdp pour vous connecter.\n
        N'oubliez pas de modifier ce mot de passe lors de votre prochaine connexion\n

        
        Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.";

        try{
            self::envoiEmail($destinataire, $sujet, $corpsEmailHTML, $corpsEmailAlt);

        }catch(ServiceException $e){
            throw new ServiceException($e->getMessage(),"connexion");
        }
    }


    /**
     * @throws ServiceException
     */
    public static function envoiEmail(string $destinataire, string $sujet, string $corpsEmailHTML, string $corpsEmailAlt): void
    {
        try {
            $mailer = self::getInstanceMailer();
            // Destinataire
            $mailer->setFrom('verybadsplit@gmail.com', 'Verybadsplit');
            $mailer->addAddress($destinataire);

            // Contenu de l'email
            $mailer->isHTML();                                      // Format HTML
            $mailer->Subject = $sujet;
            $mailer->Body = $corpsEmailHTML;
            $mailer->AltBody = $corpsEmailAlt;

            // Envoyer l'email
            $mailer->send();
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage(),"connexion");
        }
    }
}