---
lang: fr
---

# SAE4A - Very Bad Split

Un clone de l'application **Tricount**, mais produite par un développeur douteux...

## Site de Base

Le site de base est une application qui permet aux utilisateurs de créer des **événements**.
Dans chaque événement, il est possible d'inviter des membres et de créer des **dépenses**.

Une **dépense** est caractérisée par :
* Un montant (en €)
* Un membre "payeur" (celui a payé la dépense)
* La liste des membres qui participent à la dépense (le membre "payeur" peut être inclus ou non dans cette liste).

Le montant d'une dépense est repartie équitablement entre les membres qui participent à la dépense, ce qui permet ensuite de calculer qui doit combien à qui (autrement dit, des **dettes**).

Par exemple, si on considère trois membres **A**, **B** et **C** :
* Soit une dépense de 120€ payée par **A** à laquelle participent **A**, **B** et **C** : **B** et **C** doivent chacun 40€ à **A** (**A** participe aussi à hauteur de 40€ mais ne se doit rien à lui-même).
* Soit une dépense de 100€ payée par **A** à laquelle participent **B** et **C** : **B** et **C** doivent chacun 50€ à **A**.
* Soit une dépense de 50€ payée par **C** à laquelle participe **A** : **A** doit 50€ à **C**.
* Soit une dépense de 20€ payée par **B** à laquelle participe **B** : pas de changement (**B** ne se doit rien à lui-même).

Pour chaque événement, on peut ensuite connaître :
* Le coût total de l'événement (la somme des dépenses).
* Les montants qu'un membre doit rembourser aux autres membres (les **dettes**).

Chaque **événement** est associé à un **code secret** généré lors de la création de l'événement.
Toutes les personnes qui possèdent ce code secret peuvent accéder aux données de l'événement, même si elles ne sont pas connectées (mais seul un membre ou le propriétaire pourra l'éditer).

Un **événement** est créé par un utilisateur, il est alors le "propriétaire" de l'événement et possède des droits supplémentaires :
* Il peut inviter ou éjecter des membres de l'événement.
* Il peut supprimer l'événement.

Les **membres** d'un événement peuvent :
* Changer le nom de l'événement.
* Le quitter.
* Ajouter, modifier et supprimer des dépenses.

Il est à noter que le propriétaire d'un événement est aussi membre de l'événement. Il ne peut néanmoins pas le quitter (mais il peut totalement supprimer l'événement).

### Fonctionnalités actuelles

* S'inscrire, se connecter.
* Gérer son compte (mise à jour, suppression).
* Gérer les événements (CRUD).
* Gérer les dépenses dans un événement (CRUD).
* Ajouter/éjecter les membres d'un événement.
* Récupération de compte si le mot de passe oublié.
* Visualiser un événement grâce à un code d'accès secret.
* Ajouter et retirer un membre d'un événement (si on est le propriétaire de l'événement en question).
* Quitter un événement (où on est simplement membre, et pas propriétaire).

Au niveau des droits d'accès, on notera que :

* Un événement peut être **partagé** en lecture seule (même si la personne n'est pas membre du tableau) à n'importe qui par son URL. L'URL est déterminée par un système de **code secret**.
* Les membres invités d'un événement ont quasiment les mêmes droits que le propriétaire (créateur) mais ne peuvent pas supprimer l'événement ou en gérer les membres.

Actuellement, l'application est **complètement fonctionnelle**, mais **uniquement codée en PHP** (pas de JavaScript). Donc, chaque action demande un chargement d'une nouvelle page.

Le **SGBD** utilisé est **MySQL** (vous ne devez pas en changer).

Concernant le style, le framework CSS utilisé est [Bulma](https://bulma.io/) (mais vous pourrez en changer, si besoin). Les icônes affichées à divers endroits de l'application ne sont pas stockées en locale et sont fournies par l'outil [ionicons](https://ionic.io/ionicons) dont le fonctionnement est possible grâce à des [scripts](https://ionic.io/ionicons/usage) importés dans les pages de l'application.

### Règles particulières

* Le propriétaire d'un événement est obligatoirement membre de l'événement (et ne peut pas le quitter, sauf s'il supprime l'événement). Lors de la création d'un événement, il est automatiquement ajouté à la liste des membres.
* Il n'y a pas besoin de droits nécessaires pour accéder à la visualisation d'un événement (le lien avec le code secret suffit, il n'y a même pas besoin d'être connecté).
* Une dépense doit obligatoirement posséder un membre payeur et au moins un participant.
* Le montant minimum d'une dépense est 1€, et maximum 10000€.
* Il existe diverses contraintes sur les champs manipulés dans l'application (par exemple, pour le mot de passe, la longueur du login, du titre d'un événement, etc.). Ces contraintes sont notamment visibles en inspectant le code HTML des formulaires.
* Quand un membre se retire d'un événement, les dépenses dans lesquelles il intervient sont affectées :
  * S'il a payé la dépense, la dépense est supprimée.
  * S'il participe à la dépense, il est retiré de la liste des participants. Si c'était le dernier participant de cette dépense, elle est supprimée.
* Le dernier point s'applique également quand un utilisateur supprime son compte : toutes les dépenses qui le concernent sont mises à jour adéquatement.

### Calcul des dettes

Actuellement, le **calcul des dettes** de chaque membre d'un événement est un algorithme "brut" et non optimisé :
* On initialise un dictionnaire associant chaque couple d'utilisateurs membres de l'événement (en excluant le couple composé de deux fois le même utilisateur) à une dette (au début 0€). Cela représente la dette totale que doit un membre à un autre membre.
* On parcourt ensuite chaque dépense de l'événement :
  * On calcule le montant à payer par participant → (montant de la dépense) / (nombre de participants).
  * Pour chaque participant, on augmente la dette due au payeur du montant à payer par participant (en excluant le payeur s'il participe aussi à la dépense).

À la fin, on se retrouve donc avec beaucoup de dettes qui pourraient être optimisées.

Par exemple :
* **A** paye une dépense de 100€ à laquelle **A** et **B** participent.
* **B** paye une dépense de 100€ à laquelle **A** et **B** participent.

Avec l'algo actuel, cela donne donc :
* **A** doit 50€ à **B**.
* **B** doit 50€ à **A**.

De manière optimisée, dans ce scénario, ces dettes devraient être annulées. **Il y a des cas beaucoup plus complexes** à prendre en compte, avec des cycles, dans le cas où il y a plusieurs membres. L'objectif est de **réduire au maximum le nombre de transactions à effectuer d'un membre vers un autre pour rembourser toutes les dettes**.

## Projet

La SAÉ s'articule en trois parties : 
* **Analyse** et **audit** (qualité, sécurité...) de l'application existante.
* **Normalisation** de la base de données (avec preuve/justification).
* **Amélioration** et **virtualisation** de l'application.

### Analyse

*Cette partie devra être débutée immédiatement (**rendu le jeudi 1er mai au soir**).*

Il semble que ce site web a été codé par un développeur amateur... Il y a donc un gros risque que le code produit (et ce qu'il y a autour) soit de mauvaise qualité, voir dangereux !

Avant de toucher au code à proprement parler, vous devrez faire une analyse profonde des défauts de l'application dans son état actuel, réfléchir et proposer des solutions puis faire un **rapport commenté** sur les points suivants :

* La **qualité du code** et de l'**architecture** de l'application :
  * Vis-à-vis du respect des différents principes de qualité logicielle (**DRY**, **SOLID**, etc.)
  * En relevant et en expliquant les zones du code qui peuvent engendrer des problèmes de performance.

* Les différentes **failles de sécurité** (et bugs) exploitables. Pour chaque faille/bug, vous devrez :
    * Donner un scénario permettant d'exploiter ce problème de manière malicieuse.
    * Décrire (de manière suffisamment détaillée) une solution pour réparer ce problème (sans la coder).

* La modélisation de la **base de données**, vous devrez notamment :
    * Commenter le choix de la **clé primaire** choisie par le développeur.
    * Expliquer les différentes **anomalies** qui peuvent survenir à cause de cette modélisation et leurs impacts sur l'application.
    * Proposer un **nouveau schéma** "sain" et plus adapté pour éviter les anomalies détectées. Dans le schéma final, vous pourrez supprimer certains attributs que vous jugez problématique vis-à-vis de la sécurité de l'application.

### Normalisation de la base de données

*Cette partie pourra être débutée après le 3ᵉ ou 4ᵉ cours de la ressource "Qualité et au-delà du relationnel" (**rendu à la fin du projet, le lundi 9 juin au soir**).*

L'objectif de cette partie est de produire un rapport sur la **normalisation** de la base de données (en partant du schéma initial contenant une seule table, donné en début de SAE).

Ce rapport sera divisé en **quatre parties**, permettant de passer d'un schéma initial dénormalisé à un schéma final sain et normalisé, tout en justifiant votre décomposition.

#### Première partie : passage en 1ere forme normale

Le schéma initial (contenant seulement la table/relation `app_db`) n'est même pas en 1ere forme normale. Dans cette partie, vous devrez donc :

* Expliquer pourquoi le schéma n'est pas en 1ère forme normale (**uniquement les problèmes qui empêchent le schéma d'être en 1ère forme normale**, pas les problèmes qui l'empêchent de passer aux formes supérieures).
* Passer le schéma en 1ère forme normale (sans justifier) afin de régler les problèmes relevés au point précédent.
* Pour les nouvelles tables potentiellement créées, ne choisissez pas de clé primaire pour le moment.

**Note importante :** si certaines tables sont "dupliquées" (partagent des attributs similaires), ne les fusionnez pas pour le moment. Il sera possible de les regrouper à la toute fin de la décomposition.

**Attention :** encore une fois, on insiste bien qu'on ne s'intéresse pas aux formes normales au-dessus de la 1ère pour le moment. Les tables que vous allez créer et les modifications que vous allez effectuer (sans justifier pour le moment) ont pour unique but de régler les problèmes qui empêchent le schéma d'être en 1ère forme normale et rien d'autre. Pour le passage aux formes supérieures (au-delà de la 1ere) il faut décomposer en justifiant, et c'est le but des étapes suivantes.

#### Deuxième partie : lister les dépendances fonctionnelles et choisir des clés adaptées

À partir du nouveau schéma obtenu à l'étape précédente (qui est normalement au moins en 1ère forme normale) vous devrez, pour chaque table :
* Lister les dépendances fonctionnelles (à partir de votre analyse des règles de l'application).
* Choisir une clé primaire adéquate (en justifiant).

#### Troisième partie : décomposer le schéma pour atteindre la 3ᵉ forme normale.

Pour chaque table du schéma qui ne sont pas déjà en 3ᵉ forme normale, vous devrez décomposer les tables (relations) afin d'obtenir un schéma final où toutes les tables sont en 3ᵉ forme normale. Il faudra aussi justifier que votre décomposition :
* Préserve les données.
* Préserve les dépendances fonctionnelles.

**Information importante** : attention, une table/relation contient nécessairement des attributs distincts, même si dans le schéma final, il sera éventuellement possible de les regrouper (après décomposition) pour des raisons logiques et pratiques. Par exemple, dans la décomposition, **loginProprietaire** et **loginPayeur** sont deux attributs distincts. Ainsi, la dépendance fonctionnelle **loginProprietaire -> nomProprietaire** est juste, mais la dépendance fonctionnelle **loginPayeur -> nomProprietaire** est fausse.

#### Quatrième partie : schéma final

Pour finir, vous présenterez le schéma final obtenu après normalisation. Dans ce schéma, vous pouvez :
* Fusionner des tables (sans justifier) si cela vous semble judicieux.
* Supprimer certains attributs (par exemple, ceux que vous avez éventuellement supprimés dans la partie analyse vis-à-vis des problèmes de sécurité).

**Concernant la fusion des tables** : à la fin de la décomposition (arpès avoir justifié la conservation des données et des dépendances fonctionnelles), on peut "fusionner" deux relations qui, à priori n'ont pas les mêmes attributs, mais qui sont logiques à regrouper dans la pratique. Par exemple, si vous obtenez une table donc la clé est **loginPayeur** et une autre table dont la clé est **loginPayeur**, etc.

### Amélioration et virtualisation

*Cette partie pourra être débutée immédiatement et devra être traitée tout au long du projet, en incluant peu à peu les notions étudiées en cours de **complément web** et **d'architecture logicielle**, JavaScript (**rendu à la fin du projet, le lundi 9 juin au soir**).*

Après votre analyse, il vous est demandé d'**améliorer cette application** de différentes manières :

* Corriger toutes les failles de sécurité.
* Remplacer l'ancien schéma de base de données par le nouveau (issu de la partie analyse) et le faire fonctionner avec l'application.
* Chercher puis implémenter un nouvel algorithme pour le calcul des dettes, plus optimisé et permettant de minimiser le plus possible le nombre de transactions à faire (d'un membre à un autre) pour régler toutes les dettes (plutôt implémenté dans la partie **JavaScript** pour la dynamisation du site).
* Améliorer la **qualité du code** et l'**architecture** de l'application. Il faudra appliquer les principes étudiés en cours de **complément web** :
  * Meilleur système de **routage**.
  * Vues codées avec le moteur de template **Twig**.
  * Architecture en couches, en introduisant notamment une couche **service**.
  * Mise en places de tests unitaires côté back-end (avec l'outil **PHPUnit** vu en cours). Attention à ce que vos tests ne dépendent pas de l'état (en production) de votre application ! C'est pour cela qu'il est important de posséder une bonne architecture. L'objectif est d'obtenir une couverture de test de **100%** pour la partie **métier** (notamment, les **services**). 
  * Mise en place d'une **API** pour certaines parties de l'application (avec authentification par **JWT**) qui servira particulièrement à la partie dynamisation/JavaScript/AJAX.
* Ajouter du **dynamisme** sur le site grâce à l'utilisation de **JavaScript** et de **AJAX** :
    * On dynamise plusieurs formulaires grâce à des appels à l'API :
      * Lors de l'**inscription**, on vérifie que le **login** et l'**adresse email** n'existent pas déjà avant de pouvoir soumettre le formulaire. Même chose pour **l'édition du compte** (mais seulement pour l'email).
      * Lors de **l'ajout d'un membre** à un événement, on utilise un système d'auto-complétion plutôt que d'afficher une liste de tous les membres disponibles.
    * La page qui affiche les détails d'un **événement** doit être le plus **dynamique** possible, il doit être possible d'effectuer toutes les actions sans recharger la page (via des appels **AJAX** à l'API) :
      * Ajout/retrait d'un membre.
      * Édition du nom de l'événement.
      * Suppression de l'événement.
      * Quitter l'événement.
      * Ajout/modification/suppression d'une dépense.
      * Il faudra donc "migrer" certains formulaires sur cette page.
    * La page qui affiche les détails d'un événement doit aussi inclure des **éléments réactifs**, en exploitant la bibliothèque réactive développée lors du **TD7** du cours d'**architecture logicielle** (JavaScript) :
      * Par exemple, quand on ajoute ou qu'on retire un membre, les éléments concernés (liste des membres et dépenses) sont mis à jour automatiquement **sans avoir besoin de déclencher la mise à jour explicitement côté javascript après avoir ajouté/retiré le membre**.
      * Par exemple, quand on ajoute, modifie ou supprime une dépense, les éléments concernés (liste des dépenses, coût total et **dettes**) sont mis à jour automatiquement **sans avoir besoin de déclencher la mise à jour explicitement côté javascript après avoir ajouté/supprimé la dépense**.
      * Pour simplifier, on ne prendra pas en compte le fait que plusieurs personnes peuvent interagir avec le même événement en même temps (par exemple, si deux utilisateurs interagissent avec le même événement, les actions de l'un ne seront pas répercutées "en direct" sur la page de l'autre).
    * L'algorithme d'optimisation du calcul des **dettes** sera donc plutôt implémenté côté **JavaScript** (et pourra être exploité par le côté réactif de l'application, quand une dépense est ajoutée, modifiée, supprimée...).
    * L'application doit faire appel à une **API externe** (cohérente dans le contexte de l'application). 
      * Idée que vous pouvez exploiter : lors de la création ou l'édition d'une dépense, on peut saisir un montant dans une autre **devise**. Le montant est alors converti en €. Plusieurs **APIs** proposent des services de conversion d'un montant d'une devise vers une autre.
      * Vous pouvez choisir une autre idée/API, tant que cela reste cohérent.
    * Il est possible d'ajouter d'autres choses, mais les fonctionnalités citées ci-dessus sont prioritaires et doivent être complétées avant d'ajouter d'autres fonctionnalités dynamiques.

Enfin, il faudra proposer une **virtualisation** multi-conteneur de votre projet avec un fichier `docker-compose.yml` permettant de déployer :

* Un conteneur pour la base de données **MySQL** de l'application.
* Un conteneur qui fait tourner l'application sur un **serveur web** (qui communiquera donc avec le premier conteneur).
L'objectif est que votre application puisse être déployée sans problème en quelques commandes simples grâce à **Docker**.

En complément, l'application devra aussi être **déployée** et accessible sur le serveur web du département (**webinfo**).

## Consignes particulières

* Concernant le **CSS**, vous n'êtes pas obligés de continuer avec **Bulma**, vous pouvez choisir un autre framework CSS (Tailwind, Bootstrap, etc) et/ou faire votre propre style CSS. Il en va de même pour l'utilisation de **ionicons** pour l'affichage des icônes.
* D'une manière générale, **le style CSS ne doit pas être une priorité**, un beau site ne rapportera pas beaucoup de points en plus.
* Au niveau du **dynamisme** (JavaScript/AJAX), il est possible d'ajouter des choses supplémentaires en plus de ce que demande le sujet, mais les fonctionnalités requises doivent être développées avant d'ajouter d'autres choses. Il en va de même pour le réactif.
* Côté **front** et côté **back**, vous devez vous contenter de ce qui est vu en cours seulement : du **JavaScript "pur"** pour le front (pas de TypeScript ou de framework) et du "framework maison" PHP développé en TD de **complément web**.
* Côté **réactif**, vous devez obligatoirement utiliser la **bibliothèque réactive** développée en TD (**TD7 d'architecture logicielle**). Vous pouvez cependant étendre cette bibliothèque, si besoin.

## Ressources

### Contenu du dépôt

* Le code source site **Very Bad Split**.
* Un fichier **VeryBadSplit.sql** pour créer la table unique de l'application dans une base de données **MySQL**.

### Installation et mise en route

* Peut être installé dans le dépôt docker utilisé en cours de web de 2ᵉ année.
* Il faut créer une base de données sous MySQL et exécuter le code contenu dans **VeryBadSplit.sql** afin de créer de la table **app_db**. Si, pendant le développement, vous souhaitez utiliser la base de données MySQL de l'IUT, c'est possible (informations disponibles sur [cette page](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/bases-de-donnees/).), mais retenez bien qu'à la fin (lors du rendu final), votre base de données devra être virtualisée et que votre application web sera liée à la base du conteneur correspondant.
* Il faut ensuite éditer les informations de connexion dans `src/Configuration/ConfigurationBaseDeDonnees.php`.

## Liens avec les différentes ressources et progression

Cette **SAÉ** mobilise des compétences développées lors de différentes ressources du semestre 4 :

* R4.A.10 Complément web
* R4.01 Architecture logicielle (JavaScript)
* R4.02 Qualité de développement
* R4.03 Qualité et au-delà du relationnel
* R4.A.08 Virtualisation

Voici un récapitulatif de la progression dans les ressources principales nécessaires au développement du projet et les notions qui peuvent être injectées dans le projet après chaque TD/TP.

### Compléments Web (durée : 6 semaines, 6 cours de 3h)

* **TD1** : meilleur système de **routage**.
* **TD2** : développement de **vues** avec le moteur de template **Twig**.
* **TD3** : introduction d'une **couche service** et développement de **tests unitaires** (avec **PHPUnit**).
* **TD4** : utilisation d'un **conteneur de services** et **tests unitaires** avancés (**mocks**).
* **TD5** : développement d'une **API REST**, authentification avec **JWT**.

### Architecture logicielle/JavaScript (durée : 5 semaines, 8 cours de 3h)

* **TD1** à **TD3** : découverte de **JavaScript** (pas encore suffisant pour proprement mettre en place le dynamisme du site, mais vous pouvez déjà commencer à dynamiser la page de gestion d'un événement "pour de faux" sans appel réel à l'API, donc seulement visuellement sur l'interface).
* **TD4** à **TD6** : utilisation de **AJAX** (permet de mettre en place les fonctionnalités "dynamiques" liées à la vérification du login, de l'adresse mail lors de l'inscription/l'édition du compte, pour la mise système d'auto-complétion demandé lors de l'ajout d'un membre, pour la dynamisation de la page de gestion d'un événement, pour l'appel à une API externe).
* **TD7** : programmation **réactive**, développement d'une mini-bibliothèque réactive (pour inclure des fonctionnalités réactives sur la page de gestion d'un événement).

### Qualité et au-delà du relationnel (base de données)

Le rapport concernant la **normalisation de la base de données** peut être commencé après la 3ᵉ-4ᵉ séance de ce cours (quand vous aurez vu comment décomposer en justifiant la conservation des données et des dépendances fonctionnelles).

### Virtualisation

La **virtualisation** du projet étant seulement nécessaire pour livrable final, vous aurez le temps de mettre cela en place vers la fin du projet. Mais ne vous y prenez pas au dernier moment. Il faudra avoir abordé la virtualisation multi-conteneur en cours afin de mettre en place ce qui est demandé pour ce projet.

## Groupes et suivi

Les groupes sont composés de **5 personnes** (exceptionnellement 4).

Contrairement à la SAÉ du semestre 3, il n'y a pas vraiment de client. Néanmoins, vous pouvez quand même vous organiser (en interne) en sprints et appliquer les méthodes de gestion de projet.

Liste des enseignants référents pour chaque groupe :

* Groupe Q1 : [Romain Lebreton](mailto:romain.lebreton@umontpellier.fr)
* Groupe Q2 : [Malo Gasquet](mailto:malo.gasquet@umontpellier.fr)
* Groupe Q5 : [Cyrille Nadal](mailto:cyrille.nadal@umontpellier.fr)

Dès que les groupes sont formés vous devez :

* Créer un **dépôt gitlab privé** pour votre équipe en effectuant un **fork** de ce dépôt. À terme, le fichier `docker-compose.yml` devra aussi être placé dans ce dépôt.
* Inviter votre enseignant référent dans de dépôt.
* Envoyer un mail à votre enseignant référent pour lui donner la composition de l'équipe ainsi que le lien du dépôt.

## FAQ

Un serveur **discord** est mis à disposition afin de répondre à vos questions après le lancement de la SAE.

Voici l'adresse du serveur : [https://discord.gg/NRygeTrFzE](https://discord.gg/NRygeTrFzE).

**Il n'y a aucune obligation de rejoindre ce serveur.** Les informations importantes seront toujours transmises par mail. De même, vous pouvez poser vos questions à votre enseignant référent par mail ou en cours.

Si vous rejoignez le serveur, **il vous est demandé de changer votre pseudonyme par votre nom/prénom**.

## Rendus et soutenance

Il y aura **trois rendus** à faire pour cette SAÉ :

* Rendu du **rapport d'analyse** (format PDF) de l'application sur ce [dépôt Moodle](https://moodle.umontpellier.fr/mod/assign/view.php?id=798160) le **1er mai (23h59 max)**.

* Rendu du **rapport de normalisation de la base de données** (format PDF) le **9 juin (23h59 max)** sur ce [dépôt Moodle](https://moodle.umontpellier.fr/mod/assign/view.php?id=798161).

* Rendu du **projet amélioré** le **9 juin (23h59 max)** sur ce [dépôt Moodle](https://moodle.umontpellier.fr/mod/assign/view.php?id=798162). Votre rendu sera sous la forme d'une **archive zip** contenant :
    * Les sources du projet (qui devra aussi inclure le fichier `docker-compose.yml`).
    * Un script SQL permettant de créer votre base de données.
    * Un fichier `README.md` contenant :
        * L'adresse du site (hébergé dur **webinfo**)
        * Un récapitulatif de tout ce que vous avez amélioré.
        * La répartition du travail dans l'équipe.

Pour chaque rendu, **un seul membre du groupe effectue le dépôt sur Moodle**.
    
Les **soutenances** de projet auront lieu le **16 juin** et le **17 juin** à Montpellier et à Sète (la date précise vous sera communiquée ultérieurement).

## Par où commencer ?

Pendant la première période (donc, les vacances de printemps), vous devez concentrer vos efforts sur le **rapport d'analyse** qui est à rendre le **1er mai**. Cependant, il est aussi **très fortement conseillé** de commencer à améliorer l'application en parallèle. Attention cependant, certaines notions nécessaires n'arrivent qu'au fur et à mesure des TDs de complément web et d'architecture logicielle (JavaScript), il ne faut donc pas trop avancer non plus au risque d'inclure des changements qui seront beaucoup modifiés par la suite. 

Pendant cette première période, vous pouvez donc travailler, au niveau du code :
* La mise en place de votre **nouveau schéma de base de données**, et donc adapter les classes **repositories** et les contrôleurs qui y font appel pour que l'application continue de fonctionner.
* Réparer les **failles de sécurité/bugs** côté back.
* Mettre en place un **nouveau système de routage**.
* Commencer à "faussement" **dynamiser** la page de gestion d'un événement par rapport aux fonctionnalités demandées (en mettant en place du JavaScript). Par exemple, gérer l'ajout/édition, suppression des dépenses **visuellement** dans un événement en affectant seulement le côté front (l'interface) pour le moment, sans appel au back (et donc sans enregistrement des modifications). Il en va de même pour la gestion des membres. Vous pouvez, en quelque sorte, commencer à "migrer" les formulaires liés à la gestion d'un événement dans une même page.

Bon projet !