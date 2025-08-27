
<!-- Formulaire de création d'un nouvel utilisateur -->

<main style="height:100%;">
    <div class="container">

        <h2 class="py-3 font-weight-bold main-txt">Nouvel Utilisateur</h2>

        <form action="index.php?route=new_user" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="samaccountname" class="font-weight-bold">Identifiant Windows</label>
                <input type='text' class="form-control mb-3" name='samaccountname' id='samaccountname' maxlength='30' placeholder="Renseignez l'identifiant Windows de l'utilisateur" required <?php if(isset($_GET["samname"])){echo("value='" . htmlspecialchars($_GET["samname"]) . "'");} ?>></input>
            </div>

            <div class="form-group">
                <label for="firstname" class="font-weight-bold">Prénom</label>
                <input type='text' class="form-control mb-3" name='firstname' id='firstname' maxlength='30' placeholder="Renseignez le prénom de l'utilisateur" required <?php if(isset($_GET["firstname"])){echo("value='" . htmlspecialchars($_GET["firstname"]) . "'");} ?>></input>
            </div>

            <div class="form-group">
                <label for="lastname" class="font-weight-bold">Nom</label>
                <input type='text' class="form-control mb-3" name='lastname' id='lastname' maxlength='30' placeholder="Renseignez le nom de famille de l'utilisateur" required <?php if(isset($_GET["lastname"])){echo("value='" . htmlspecialchars($_GET["lastname"]) . "'");} ?>></input>
            </div>

            <div class="form-group">
                <label for="email" class="font-weight-bold">Email</label>
                <input type="email" class="form-control mb-3" name="email" id="email" maxlength='50' placeholder="Renseignez l'email de l'utilisateur" required <?php if(isset($_GET["email"])){echo("value='" . htmlspecialchars($_GET["email"]) . "'");} ?>></input>
            </div>

            <div class="form-group">
                <label for="phone" class="font-weight-bold">Téléphone</label>
                <input type="tel" class="form-control mb-4" name="phone" id="phone" minlength="10" maxlength='10' placeholder="Renseignez le numéro de téléphone de l'utilisateur (Facultatif)" <?php if(isset($_GET["phone"])){echo("value='" . htmlspecialchars($_GET["phone"]) . "'");} ?>></input>
            </div>

            <!-- Checkboxes de paramétrage de l'utilisateur -->
            <div class="d-flex flex-column mb-3">

                <p class="font-weight-bold m-1">Options</p>

                <div class="d-flex flex-row p-2 rounded align-items-center justify-content-around" style="border:1px solid lightgray">
                    <div class="form-group d-flex flex-row mb-0">
                        <label for="isadmin" class="font-weight-bold">Administrateur :</label>
                        <input name="isadmin" class="ml-2" style="zoom:200%;" id="isadmin" type="checkbox" <?php if(isset($_GET["isadmin"]) && $_GET["isadmin"]){echo("checked");} ?>>
                    </div>
                    <div class="form-group flex-row mb-0" style="display:none;" id="ifadmin">
                        <label for="addallservices" class="font-weight-bold">Ajouter à tous les services ?</label>
                        <input name="addallservices" class="ml-2" style="zoom:200%;" id="addallservices" type="checkbox">
                    </div>
                    <div class="form-group d-flex flex-row mb-0">
                        <label for="reminder" class="font-weight-bold">Rappels de contrats :</label>
                        <input name="reminder" class="ml-2" style="zoom:200%;" id="reminder" type="checkbox" <?php if(isset($_GET["reminder"]) && !$_GET["reminder"]){} else{echo("checked");} ?>>
                    </div>
                </div>

            </div>

            <div class="d-flex flex-column align-items-center">
                <!-- Affichage des erreurs -->
                <?php if(isset($_GET["error"])){echo("<p style='color:red;font-weight:bold'>" . htmlspecialchars($_GET["error"]) . "</p>");} ?>
                <p id="error" style="display:none; color:red"></p>

                <div class="d-flex flex-row">
                    <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                    <button type="submit" name="submit_new_user" id="submit_user" class="btn btn-lg mr-5 main-bg">Ajouter</button>
                    <!-- Vers la page de gestion des utilisateurs -->
                    <a href="index.php?route=user_management" class="btn btn-lg second-bg">Retour</a>
                </div>
            </div>

        </form>

    </div>

    <!-- JS -->
    <script>

        // Apparition de l'option "Ajouter à tous les services ?" si l'utilisateur est admin
        document.getElementById("isadmin").addEventListener("change",function(e){

            if(document.getElementById("isadmin").checked == true){
                document.getElementById("ifadmin").style.display="flex";
            }
            else{
                document.getElementById("ifadmin").style.display="none";
            }

        });

        // Affichage du message d'erreur
        function display_error(message){
            document.getElementById("submit_user").disabled = true;
            document.getElementById("error").innerHTML = message;
            document.getElementById("error").style.display = 'block';
        }

        // Disparition du message d'erreur
        function hide_error(){
            document.getElementById("submit_user").disabled = false;
            document.getElementById("error").style.display = 'none';
        }

        // Vérification que l'email renseigné est valide
        document.getElementById("email").addEventListener("change",function(e){

            const regex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;

            if(!regex.test(document.getElementById("email").value)){
                display_error("Veuillez entrer un email valide");
            }
            else{
                hide_error();
            }
        });

        // Vérification que le numéro de téléphone est valide
        document.getElementById("phone").addEventListener("change",function(e){

            const regex = /^0\d{9}$/;

            if(document.getElementById("phone").value != '' && !regex.test(document.getElementById("phone").value)){
                display_error("Veuillez entrer un numéro de télephone valide");
            }
            else{
                hide_error();
            }
        });

    </script>

    <!-- CSS -->
    <style>

        /* Couleur du texte par défaut */
        label, p{
            color:#000F46;
        }

    </style>

</main>
