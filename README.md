---
lang: fr
---

# SAE4A - Very Bad Split

### Adresse du site : 

https://webinfo.iutmontp.univ-montp2.fr/~dipasqualem/very-bad-split-code-de-base/web/ (plus hébergé, à host/dockeriser soit-même)

### Améliorations : 

Nous avons améliorer les choses suivantes sur le site web original : 
- Intégration des routes dans le système
- Création de la couche Service
- Normalisation de la Base de Donnée vers un modèle normé 
- API interne pour le JS (pas entièrement exploité)
- Passage des vues sous le moteur de template Twig
- Intégration du formulaire d'ajout de dépense en JS dans la vue evenement.html.twig
- Modification du nom d'un événement dynamisé en JS
- Interface dynamique pour les événements (cout total, dettes de chacun...) lors de changements
- Conteneurisation du projet avec un Dockerfile et un docker-compose.yml
- conteneur.yml pour remplacer le Conteneur.php, plus modulaire
- Injection des interfaces pour pouvoir faire des tests
- Envoi de mail pour la récupération de compte avec reset de mot de passe
- Tests mocké avec phpunits + coverage à 100% sur les services (sauf mail)
- Page HTML générée pour analyser le coverage des tests
- API externe de conversion de devise
- Amélioration du calcul des dettes optimisé
- Correction de toutes les failles de sécurité notées dans notre rapport d'analyse
- Ajout de favicon fonctionnels
- Pour REST, création de ServiceExceptions qui renvoient un code d'erreur 
- Pour REST, renvoi de Requests vers le RouteurURL

### Répartition du travail : 

Théodore : **27%**
Paul : **26%**
Matteo : **21%**
Kilyan : **26%**
