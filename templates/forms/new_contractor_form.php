
<!-- Formulaire de création d'un contractant -->

<main style="height:100%;">
    <div class="container mt-5 pt-5">

        <h2 class="mb-5 font-weight-bold main-txt">Ajouter un fournisseur / prestataire</h2>

        <form action="index.php?route=new_contractor" method="post" enctype="multipart/form-data">

            <div class="form-group font-weight-bold">
                <label for="nom">Nom</label>
                <input type="text" class="form-control" name="name" maxlength="100" placeholder="Entrer le nom du fournisseur / prestataire" required <?php if(isset($_GET["contractor_name"])){echo("value='" . htmlspecialchars($_GET["contractor_name"]). "'");} ?>>
            </div>

            <div class="form-group font-weight-bold">
                <label for="reference">Référence</label>
                <input type="text" class="form-control" name="ref" maxlength="30" placeholder="Entrer la référence du fournisseur" required <?php if(isset($_GET["contractor_ref"])){echo("value='" . htmlspecialchars($_GET["contractor_ref"]). "'");} ?>>
            </div>

            <div class="form-group font-weight-bold">
                <label for="mail">Mail</label>
                <input type="email" class="form-control" name="email" maxlength="100" placeholder="Entrer l'email de contact" required <?php if(isset($_GET["contractor_mail"])){echo("value='" . htmlspecialchars($_GET["contractor_mail"]). "'");} ?>>
            </div>

            <div class="form-group font-weight-bold">
                <label for="telephone">Téléphone</label>
                <input type="tel" class="form-control" name="telephone" minlength="10" maxlength="10" placeholder="Entrer le numéro de téléphone" required <?php if(isset($_GET["contractor_tel"])){echo("value='" . htmlspecialchars($_GET["contractor_tel"]) . "'");} ?>>
            </div>

            <!-- Séléction du service lié au contractant si l'utilisateur n'a pas de service actuellement séléctionné -->
            <?php if($_SESSION["UserInfo"]["Role"] == "Admin" && $_SESSION["UserInfo"]["Current_Service"] == "*"): ?>

                <div class="form-group font-weight-bold">
                    <label for="service_id">Service</label>
                    <select class="form-control" name="service_id" id="service_id" required>
                        <?php foreach($services as $service): ?>
                            <option value="<?php echo($service["id"]); ?>" <?php if(isset($_GET["contractor_service"]) == $service["id"]){echo("selected");} ?>>
                                <?php echo($service["service_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            <?php endif; ?>

            <!-- Affichage des erreurs -->
            <?php if(isset($_GET["error"])){
                echo("<p class='text-center' style='font-weight:bold;color:red;'>" . htmlspecialchars($_GET["error"]) . "</p>");
            } ?>

            <div class="d-flex justify-content-center mt-5">
                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                <!-- Vers la page précédente -->
                <a href="#" onclick="history.back(); return false;" class="btn btn-lg second-bg mr-3">Retour</a>
                <button type="submit" name="submit_new_contractor" class="btn btn-lg main-bg">Ajouter</button>
            </div>

        </form>
    </div>