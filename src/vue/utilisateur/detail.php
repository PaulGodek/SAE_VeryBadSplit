<?php
/** @var Utilisateur $utilisateur */

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;

$loginHTML = htmlspecialchars($utilisateur->getLogin());
$loginURL = rawurlencode($utilisateur->getLogin());
$prenomHTML = htmlspecialchars($utilisateur->getPrenom());
$nomHTML = htmlspecialchars($utilisateur->getNom());
$emailhtml = htmlspecialchars($utilisateur->getEmail());
?>

<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-8 box has-background-black-ter">
                <p class="is-size-2 has-text-centered has-text-white">Détails du compte</p>
                <form>
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <label class="label is-size-4" for="login">Nom d'utilisateur</label>
                                <div class="control has-icons-left">
                                    <input id="login" name="login" class="input is-large has-background-black" type="text" value="<?=$loginHTML?>" disabled>
                                    <span class="icon is-left"><ion-icon name="person"></ion-icon></span>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="email">Adresse email</label>
                                <div class="control has-icons-left">
                                    <input id="email" name="email" class="input is-large has-background-black" type="email" value="<?=$emailhtml?>" disabled>
                                    <span class="icon is-left"><ion-icon name="document"></ion-icon></span>
                                </div>
                            </div>
                        </div>
                    </div>
                   <div class="field is-horizontal">
                        <div class="field-body">
                        <div class="field">
                            <label class="label is-size-4" for="prenom">Prénom</label>
                            <div class="control has-icons-left">
                                <input id="prenom" name="prenom" class="input is-large has-background-black" type="text" value="<?=$prenomHTML?>" disabled>
                                <span class="icon is-left"><ion-icon name="document"></ion-icon></span>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label is-size-4" for="nom">Nom</label>
                            <div class="control has-icons-left">
                                <input id="nom" name="nom" class="input is-large has-background-black" type="text" value="<?=$nomHTML?>" disabled>
                                <span class="icon is-left"><ion-icon name="document"></ion-icon></span>
                            </div>
                        </div>
                     </div>
                   </div>
                    <div class="buttons is-centered mt-5">
                        <a class="button is-link" href="controleurFrontal.php?action=afficherFormulaireMiseAJour&controleur=utilisateur">
                            <span class="icon is-left"><ion-icon name="pencil"></ion-icon></span>
                            <span>Mettre à jour le compte</span>
                        </a>
                        <a class="button is-danger" href="controleurFrontal.php?action=supprimer&controleur=utilisateur&login=<?=$loginURL?>">
                            <span class="icon is-left"><ion-icon name="trash"></ion-icon></span>
                            <span>Supprimer le compte</span>
                        </a>
                    </div>
                 </form>
            </div>
        </div>
    </div>
</div>


