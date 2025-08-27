
<!-- Formulaire de modification d'un service -->

<main>
    <div class="container">

        <h2 class="py-3 font-weight-bold main-txt">Modifier un Service</h2>

        <form action="index.php?route=update_service" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="name" class="font-weight-bold">Nom du Service</label>
                <input type='text' class="form-control mb-3" name='name' maxlength='100' placeholder="Renseignez un nom pour le service" value="<?php echo($updated_service["service_name"]); ?>" required></input>
            </div>

            <div class="form-group">
                <label for="short_name" class="font-weight-bold">Abréviation</label>
                <input type='text' class="form-control mb-3" name='short_name' maxlength='3' placeholder="Renseignez un abrégé en 2 ou 3 lettres pour le service (Exemple : Service Informatique = SI)" value="<?php echo($updated_service["short_name"]); ?>" required></input>
            </div>
        
            <!-- Affichage des erreurs -->
            <?php 
                if(isset($_GET["error"])){
                    echo("<p class='text-center' style='font-weight:bold;color:red;'>" . htmlspecialchars($_GET["error"]) . "</p>");
                } 
            ?>
        
            <div class="d-flex flex-row justify-content-center">
                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                <input type="hidden" name="id" value="<?php echo($_GET["serviceId"]); ?>">
                <!-- Vers la page de gestion des services -->
                <a href="index.php?route=service_management" class="btn btn-lg second-bg">Retour</a>
                <button type="submit" name="submit_service_update" id="submit_service" class="btn btn-lg ml-5 main-bg">Enregistrer</button>
            </div>

        </form>
    </div>

</main>

<!-- CSS -->
<style>

    /* Couleur par défaut du texte */
    label, p{
        color:#000F46;
    }

</style>
