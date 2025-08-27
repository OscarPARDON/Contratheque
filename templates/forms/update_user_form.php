
<!-- Page de modification d'un utilisateur -->

<main class="d-flex flex-row vh-100">
    <form action="index.php?route=update_user" method='post' enctype="multipart/form-data" class="container d-flex flex-column">

        <h2 class="text-center mb-2 font-weight-bold main-txt">Modification d'un utilisateur <a href="index.php?route=user_management"><img src="templates/images/back.svg" alt="Retour au tableau des utilisateurs" style="width:60px"></a></h2>

        <div class="d-flex flex-row nowrap w-100 mb-4">

            <div class="d-flex flex-column w-50 px-3">
                <label for="lastname" class="font-weight-bold">Nom</label>
                <input class="form-control border-dark" name="lastname" type="text" maxlength="100" placeholder="Entrez le nom de l'utilisateur" value="<?php echo($updated_user["lastname"]); ?>" required>
            </div>

            <div class="d-flex flex-column w-50 px-3">
                <label for="firstname" class="font-weight-bold">Prénom</label>
                <input class="form-control border-dark" name="firstname" type="text" maxlength="100" placeholder="Entrez le prénom de l'utilisateur" value="<?php echo($updated_user["firstname"]); ?>" required>
            </div>
        
        </div>

        <!-- Possibilité de modifier le samaccountname sauf pour soi même -->
        <?php if($updated_user["samaccountname"] != $_SESSION["UserInfo"]["Username"]): ?>

            <div class="px-3 mb-4 flex-column">
                <label for="samaccountname" class="font-weight-bold">Identifiant</label>
                <input class="form-control border-dark" type="text" name="samaccountname" maxlength="100" placeholder="Entrez l'identifiant de l'utilisateur" value="<?php echo($updated_user["samaccountname"]); ?>" required> 
            </div>

        <?php endif; ?>
            
        <div class="px-3 mb-4 flex-column">
            <label for="email" class="font-weight-bold">Email</label>
            <input class="form-control border-dark" type="text" name="email" maxlength="100" placeholder="Entrez l'email de l'utilisateur" value="<?php echo($updated_user["mail"]); ?>" required> 
        </div>

        <div class="px-3 mb-4 flex-column">
            <label for="telephone" class="font-weight-bold">Téléphone</label>
            <input class="form-control border-dark" type="tel" name="telephone" maxlength="10" placeholder="Entrez le numéro de téléphone de l'utilisateur" value="<?php echo($updated_user["tel_num"]); ?>"> 
        </div>
        
        <!-- Checkboxes de paramétrage de l'utilisateur -->
        <div class="d-flex flex-column mb-3">

                <p class="font-weight-bold m-1">Options</p>

                <div class="d-flex flex-row p-2 rounded align-items-center justify-content-around" style="border:1px solid lightgray">

                    <!-- Possibilité de changer le status Admin ou non sauf pour soi même -->
                    <?php if($updated_user["samaccountname"] != $_SESSION["UserInfo"]["Username"]): ?>

                        <div class="form-group d-flex flex-row mb-0">
                            <label for="isadmin" class="font-weight-bold">Administrateur :</label>
                            <input name="isadmin" class="ml-2" style="zoom:200%;" id="isadmin" type="checkbox" <?php if($updated_user["is_admin"]){echo("checked");} ?>>
                        </div>

                    <?php endif; ?>

                    <div class="form-group d-flex flex-row mb-0">
                        <label for="reminder" class="font-weight-bold">Rappels de contrats :</label>
                        <input name="reminder" class="ml-2" style="zoom:200%;" id="reminder" type="checkbox" <?php if($updated_user["contracts_reminders"]){echo("checked");} ?>>
                    </div>

                </div>
            </div>

        <div class="w-100 d-flex flex-column align-items-center mt-4">

            <!-- Affichage des erreurs -->
            <?php if(isset($_GET["error"])){echo("<p style='color:red;font-weight:bold'>" . htmlspecialchars($_GET["error"]) . "</p>");} ?>

            <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
            <input type="hidden" name="old_samaccountname" value="<?php echo($updated_user["samaccountname"]); ?>">
            <button class="btn btn-lg main-bg w-50 text-center" type="submit" name="submit_edit">Enregistrer les modifications</button>
        </div>
        
    </form>

</main>

<!-- CSS -->
<style>

    /* Couleur par défaut du texte */
    label, p{
        color:#000F46;
    }

</style>

