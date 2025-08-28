# La Contrathèque

*Remarque : Cette application ne reflète pas mes compétences actuelles,
mais constitue plutôt un marqueur de progression.*

## Contexte

Pour en savoir plus sur le contexte de création de cette application,
vous pouvez me contacter :

-   via le [formulaire de contact](https://OscarPARDON.github.io/index.html#contact)
-   par email à l\'adresse
    [Contact.OscarPARDON@gmail.com](mailto:contact.OscarPARDON@gmail.com)

Je m\'engage à vous répondre dans un délai de 48 heures.


## À propos de l\'application


### Caractéristiques techniques

-   **Architecture :** MVC
-   **Backend :** PHP
-   **Base de données :** MySQL
-   **Frontend :** HTML, CSS, Bootstrap, JavaScript
-   **Mailing :** PHPMailer
-   **Tâches planifiées :** Cron


*Capture d'écran de la page principale*

![alt text](https://OscarPARDON.github.io/content/images/cont_illu1.webp)


### Déploiement et environnement de production

Pendant le développement, mon IDE était configuré pour synchroniser
automatiquement le code via FTP sur un serveur Web Debian avec Apache 2.

Même si l\'application n\'était pas officiellement en production, j\'ai
mis en place deux environnements distincts :

-   une version de développement sur mon poste de travail ;
-   une version dite de production, fonctionnelle et disponible en
    continu, mise à jour ponctuellement après validation des nouvelles
    fonctionnalités.


### Fonctionnalités principales

-   Hébergement structuré des contrats (données + documents PDF)
-   Rappels automatiques par email pour les reconductions
-   Entretien automatisé des tables de la base de données et des
    fichiers PDF
-   Module d\'assistance connectable à GLPI ou à une adresse email
    dédiée


## Sécurité

### Protection contre les injections

-   Protection contre les injections SQL
-   Protection contre les injections XSS

### Rôles et Cloisonnement

-   3 Niveaux d\'accès
-   Cloisonnement des données et des permissions

### Authentification

-   Authentification via Active Directory
-   Possibilité de Bruteforce
-   Blocage par IP

### Journalisation

-   Journalisation des connexions
-   Journalisation de la base de données
-   Journalisation des tâches périodiques

### Sécurisation des formulaires

-   Jetons CSRF
-   Validation des données

*Capture d'écran de la page de la page de blocage*

![alt text](https://OscarPARDON.github.io/content/images/cont_illu2.webp)


## Ce que le projet m\'a appris

### Mes réussites

-   Me familiariser avec les langages web fondamentaux : PHP, HTML, CSS,
    JavaScript
-   Découvrir Bootstrap pour le développement frontend
-   Assimiler les principes de l\'architecture MVC et son fonctionnement

### Mes erreurs

-   Ignorer les bonnes pratiques de codage
-   Absence de gestion de projet
-   Aucun test mis en place

