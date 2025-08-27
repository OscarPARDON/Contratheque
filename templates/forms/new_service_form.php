
<!-- Formulaire de création d'un nouveau service -->

<main style="height:100%;">
    <div class="container pb-3">

        <h2 class="py-3 font-weight-bold main-txt">Nouveau Service</h2>

        <form action="index.php?route=new_service" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="name" class="font-weight-bold">Nom du Service</label>
                <input type='text' class="form-control mb-3" name='name' maxlength='100' placeholder="Renseignez un nom pour le service" required <?php if(isset($_GET["service_name"])){echo("value='" . htmlspecialchars($_GET["service_name"]) . "'");} ?>></input>
            </div>

            <div class="form-group">
                <label for="short_name" class="font-weight-bold">Abréviation</label>
                <input type='text' class="form-control mb-3" name='short_name' maxlength='3' placeholder="Renseignez un abrégé en 2 ou 3 lettres pour le service (Exemple : Service Informatique = SI)" required <?php if(isset($_GET["shortname"])){echo("value='" . htmlspecialchars($_GET["shortname"]) . "'");} ?>></input>
            </div>

            <!-- Affichage des erreurs -->
            <?php if(isset($_GET["error"])){
                echo("<p class='text-center' style='font-weight:bold;color:red;'>" . htmlspecialchars($_GET["error"]) . "</p>");
            } ?>

            <div class="d-flex justify-content-center mt-5">
                <button type="submit" name="submit_new_service" id="submit_service" class="btn btn-lg mr-5 main-bg">Ajouter</button>
                <!-- Vers la page précédente -->
                <a href="#" onclick="history.back(); return false;" class="btn btn-lg second-bg">Retour</a>
            </div>

            <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">

        </form>

    </div>
</main>
