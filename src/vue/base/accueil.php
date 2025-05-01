<?php

use App\VeryBadSplit\Lib\ConnexionUtilisateur;

?>
<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns has-text-centered has-text-white-ter is-size-3">
            <div class="column">
                <p>Bienvenue sur <strong>Very Bad Split</strong>!</p>
                <?php if (ConnexionUtilisateur::estConnecte()) { ?>
                    <div class="has-text-centered mt-3">
                        <a class="button has-background-black is-size-3" href="./evenements">
                            <span class="icon is-left"><ion-icon name="eye"></ion-icon></span>
                            <span>Consulter mes événements</span>
                        </a>
                    </div>
                <?php } else { ?>
                    <p>
                        Pour créer des événements, commencez par vous
                        <a href="./connexion">connecter</a>
                        ou par <a href="./inscription">créer un compte</a>!
                    </p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>