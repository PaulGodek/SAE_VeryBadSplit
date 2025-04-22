<?php

/** @var string $pagetitle */

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Generator\UrlGenerator;

/** @var UrlGenerator $generateurUrl */
$generateurUrl = Conteneur::recupererService("generateurUrl");
/** @var UrlHelper $assistantUrl */
$assistantUrl = Conteneur::recupererService("assistantUrl");
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pagetitle) ?></title>
    <link rel="stylesheet" href="<?php echo $assistantUrl->getAbsoluteUrl("../ressources/css/bulma.min.css") ?>">
    <link rel="stylesheet" href="<?php echo $assistantUrl->getAbsoluteUrl("../ressources/css/styles.css") ?>">
    <script type="text/javascript" src="<?php echo $assistantUrl->getAbsoluteUrl("../ressources/js/bulma.jsZz") ?>" defer></script>
</head>
<body>
    <section class="hero is-fullheight">
        <div class="hero-head pb-0 mb-0">
            <nav class="navbar is-black has-background-black is-size-4" role="navigation" aria-label="main navigation">
                <div class="container">
                    <div class="navbar-brand is-active">
                        <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navSite">
                            <span aria-hidden="true"></span>
                            <span aria-hidden="true"></span>
                            <span aria-hidden="true"></span>
                            <span aria-hidden="true"></span>
                        </a>
                    </div>
                    <div id="navSite" class="navbar-menu">
                        <div class="navbar-start">
                            <a href="./" class="navbar-item">Accueil</a>
                            <?php
                            if (ConnexionUtilisateur::estConnecte()) {?>
                                <a href="controleurFrontal.php?action=afficherListeMesEvenements&controleur=evenement"
                                   class="navbar-item">
                                    Mes évenements
                                </a>
                                <a href="controleurFrontal.php?action=afficherDetail&controleur=utilisateur"
                                   class="navbar-item">
                                    Mon compte
                                </a>
                                <a href="controleurFrontal.php?action=deconnecter&controleur=utilisateur"
                                   class="navbar-item">
                                    Se déconnecter
                                </a>
                            <?php } else { ?>
                                <a href="./connexion"
                                   class="navbar-item">
                                    Se connecter
                                </a>
                                <a href="./inscription"
                                   class="navbar-item">
                                    S'inscrire
                                </a>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="navbar-end"></div>
                    </div>
                </div>
            </nav>
            <div id="flashes-container" class="container">
                <?php
                /** @var string[][] $messagesFlash */
                foreach ($messagesFlash as $type => $messagesFlashPourUnType) {
                    // $type est l'une des valeurs suivantes : "success", "info", "warning", "danger"
                    // $messagesFlashPourUnType est la liste des messages flash d'un type
                    foreach ($messagesFlashPourUnType as $messageFlash) {
                        $messageHTML = htmlspecialchars($messageFlash);
                        echo <<< HTML
                        <div class="flash notification mt-5 is-$type">
                            <button class="delete"></button>
                            <p class="is-size-4">$messageHTML</p>
                        </div>
                        HTML;
                    }
                }
                ?>
            </div>
        </div>
        <?php
        /**
         * @var string $cheminVueBody
         */
        require __DIR__ . "/{$cheminVueBody}";
        ?>
        <div class="hero-foot has-background-black">
            <div class="content has-text-centered">
                <p><strong>Copyright Very Bad Split</strong></p>
            </div>
        </div>
    </section>
	<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@latest/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@latest/dist/ionicons/ionicons.js"></script>
</body>
</html>