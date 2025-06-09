<?php

namespace App\VeryBadSplit\Service;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Service\Exception\ServiceException;

use App\VeryBadSplit\Service\Interface\EmailServiceInterface;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmailService implements EmailServiceInterface
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        // Paramètres du serveur
        $this->mailer->isSMTP();                                      // Utiliser SMTP
        $this->mailer->Host = 'smtp.gmail.com';                       // Serveur SMTP
        $this->mailer->SMTPAuth = true;                               // Activer l'authentification SMTP
        $this->mailer->Username = 'VeryBadSplitC@gmail.com';               // Nom d'utilisateur SMTP
        $this->mailer->Password = 'rjzl fxoh arzv bsvk';                // App password
        $this->mailer->SMTPSecure = PHPmailer::ENCRYPTION_STARTTLS;   // Activer TLS
        $this->mailer->Port = 587;                                    // Port TCP pour TLS
        $this->mailer->CharSet = 'UTF-8';
    }


    /**
     * @throws ServiceException
     */
    public function envoyerMailMdpOublie(Utilisateur $utilisateur,$mdp): void
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
            $this->envoiEmail($destinataire, $sujet, $corpsEmailHTML, $corpsEmailAlt);

        }catch(ServiceException $e){
            throw new ServiceException($e->getMessage(), Response::HTTP_BAD_REQUEST,"afficherFormulaireConnexion");
        }
    }


    /**
     * @throws ServiceException
     */
    public function envoiEmail(string $destinataire, string $sujet, string $corpsEmailHTML, string $corpsEmailAlt): void
    {
        try {
            // Destinataire
            $this->mailer->setFrom('verybadsplit@gmail.com', 'Verybadsplit');
            $this->mailer->addAddress($destinataire);

            // Contenu de l'email
            $this->mailer->isHTML();                                      // Format HTML
            $this->mailer->Subject = $sujet;
            $this->mailer->Body = $corpsEmailHTML;
            $this->mailer->AltBody = $corpsEmailAlt;

            // Envoyer l'email
            $this->mailer->send();
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage(), Response::HTTP_BAD_REQUEST,"afficherFormulaireConnexion");
        }
    }
}