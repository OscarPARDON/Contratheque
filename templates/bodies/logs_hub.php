<style>

    /* Animation de grossissement lorsque un des élément est survolé */
    .menu-element {
        display: inline-block;
        transition: transform 0.3s ease; 
    }

    .menu-element:hover {
        transform: scale(1.1); 
    }

</style>

<!-- Menu d'accès aux logs -->

<main class="container vh-100 d-flex flex-column align-items-center justify-content-center mt-0">

    <!-- Vers les logs de connexion -->
    <a href="index.php?route=login_logs" class="w-50 p-5 h3 my-5 second-bg text-white text-decoration-none menu-element">Journaux de connexion</a>

    <!-- Vers les logs de BDD -->
    <a href="index.php?route=database_logs" class="w-50 p-5 h3 main-bg text-white text-decoration-none menu-element">Journaux de base de données</a>

</main>