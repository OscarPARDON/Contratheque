<!-- ########################################################################################################################################## -->
<!-- HTML -->

<body>

<header class="fixed-top bg-white">

  <!-- Partie Haute -->
  <div class="py-3 mb-1 d-flex align-items-center" style="padding: 0 3rem; position: relative;">

    <!-- Service actuellement séléctionné -->
    <div style="position: absolute; left: 3rem;">
        <a onclick="$('#switch_service_modal').modal('show');" class="font-weight-bold main-txt text-decoration-none">Service actuel : <?php echo($_SESSION["UserInfo"]["Current_Service_Name"]);?></a>
    </div>
    
    <!-- Logo, bouton retour au menu -->
    <div class="mx-auto text-center">
        <a href="index.php">
            <img src="templates/images/logo.svg" alt="logo Principal" width="80">
        </a>
    </div>
    
    <!-- Dropdown Paramètres -->
    <div class="dropdown show" style="position: absolute; right: 3rem;">

        <a href="#" class="dropdown-toggle text-dark py-3" data-toggle="dropdown">
            <img src="templates/images/settings.svg" width="40">
        </a>

        <div class="dropdown-menu dropdown-menu-right">

            <?php if($_SESSION["UserInfo"]["Role"] == "Manager"): ?>
              <a class="dropdown-item" href="index.php?route=service_users&serviceId=<?php echo($_SESSION["UserInfo"]["Current_Service"]); ?>">Utilisateurs</a>
              <a class="dropdown-item" href="index.php?route=contractor_management">Contractants</a>
            <?php endif; ?>

            <?php if($_SESSION["UserInfo"]["Role"] == "Admin"): ?>
              <a class="dropdown-item" href="index.php?route=user_management">Utilisateurs</a>
              <a class="dropdown-item" href="index.php?route=service_management">Services</a>
              <a class="dropdown-item" href="index.php?route=contractor_management">Contractants</a>
              <a class="dropdown-item" href="index.php?route=configuration">Configuration</a>
              <a class="dropdown-item" href="index.php?route=logs_hub">Journaux</a>
              <a class="dropdown-item" href="index.php?route=archived_contracts">Archives</a>
            <?php endif; ?>

            <a class="dropdown-item" href="index.php?route=logout">Déconnexion</a>
        </div>
    </div>

</div>

  <!-- Partie Basse -->
  <div style="background-color:#fbfbfc;border-color:#fbfbfc;" class="d-flex justify-content-around py-2 shadow-sm">
    
    <!-- Dropdown N°1 : Contrats -->
    <div class="dropdown show">

      <a class="text-decoration-none font-weight-bold dropdown-toggle py-3" href="#"  data-toggle="dropdown"> Contrats </a>

      <div class="dropdown-menu">
        <a class="dropdown-item" href="index.php">Contrats en cours</a>
        <a class="dropdown-item" href="index.php?route=expired_contracts">Contrats expirés</a>
        <a class="dropdown-item" href="index.php?route=deleted_contracts">Contrats supprimés</a>
      </div>

    </div>
    
    <!-- Lien N°2 : Statistiques -->
    <div class="dropdown show">
      <a class="text-decoration-none font-weight-bold py-3" href="index.php?route=statistics"> Statistiques </a>
    </div>

    <!-- Dropdown N°3 : Aide -->
    <div class="dropdown show">

      <a class="text-decoration-none font-weight-bold dropdown-toggle py-3" href="#" data-toggle="dropdown"> Aide </a>

      <div class="dropdown-menu">
        <a class="dropdown-item" href="index.php?route=help"> Contact</a>
        <a class="dropdown-item" href="#"> Documentation</a>
      </div>

    </div>

  </div>

</header>

<!-- ########################################################################################################################################## -->
<style>

header{
  color:#000F46;
}

.dropdown-menu {
  display: none;
}

.dropdown-menu.show {
  display: block !important;
}

header .dropdown>.dropdown-toggle:active {
    pointer-events: none;
}

main {
  margin-top: 9%;
}

@media (max-width: 1919px) {
  main {
    margin-top: 11%;
  }
}

.dropdown a {
  color : #000F46;
}

.dropdown-item:hover{
  color:#FF0000;
}

</style>

<script>
  
  // Ecouteur JS qui permet aux dropdowns de fonctionner
  document.addEventListener('DOMContentLoaded', function() {

    menus =  document.querySelectorAll('.dropdown-menu');

    document.querySelectorAll('.dropdown').forEach(dropdown => {

      const menu = dropdown.querySelector('.dropdown-menu');
      const toggle = dropdown.querySelector('.dropdown-toggle');
      
      // Ignorer les dropdowns qui n'ont pas de menu comme Statistiques
      if (!menu) return;
      
      let hideTimeout;

      // Affichage du dropdown
      const showMenu = () => {
        menus.forEach((element) => {if(element.classList.contains("show")){element.classList.remove("show");}});
        clearTimeout(hideTimeout);
        menu.classList.add('show');
      };

      // Disparition du dropdown avec delais
      const hideMenu = () => {
        hideTimeout = setTimeout(() => {
          menu.classList.remove('show');
        }, 1000);
      };

      // Application des ecouteurs sur les dropdowns
      dropdown.addEventListener('mouseenter', showMenu);
      dropdown.addEventListener('mouseleave', hideMenu);
      menu.addEventListener('mouseenter', showMenu);
      menu.addEventListener('mouseleave', hideMenu);

    });
    
  });

</script>