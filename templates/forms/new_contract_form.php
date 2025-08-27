
<!-- Formulaire d'ajout d'un nouveau contrat -->

<main>

    <div class="container mt-5 pt-5">

        <h2 class="py-3 main-txt font-weight-bold">Nouveau Contrat</h2>

        <form action="index.php?route=new_contract" method="POST" enctype="multipart/form-data">

            <!-- Ligne 1 & 2  -->
            <div class="row">

                <!-- Colonne 1 -->
                <div class="col-md-6">

                    <div class="form-group">
                        <label for="contract_name">Nom du contrat</label>
                        <input type="text" class="form-control" name="contract_name" id="contract_name" placeholder="Entrer le nom du contrat" maxlength="50" <?php if(isset($_GET["contract_name"])){echo("value='" . htmlspecialchars($_GET['contract_name']) ."'");} elseif($preload){echo("value='" . htmlspecialchars($preload['contract_name']) ."'");} ?> required>
                        <?php if(isset($_GET["nameError"])){echo("<p class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["nameError"]) . "</p>");} ?>
                    </div>

                    <div class="form-group">
                        <label for="contract_start">Début du contrat</label>
                        <input id="start" type="date" class="form-control" name="contract_start" <?php if(isset($_GET["contract_start"])){echo("value='" . htmlspecialchars($_GET['contract_start']) ."'");} ?> required>
                    </div>

                </div>

                <!-- Colonne 2 -->
                <div class="col-md-6">

                    <div class="form-group">
                        <label for="followup">Date de relance</label>
                        <div class="d-flex flex-row ">    
                            <input id="followup" placeholder="Mois avant expiration ..." type="number" class="form-control w-50" name="followup" min="0">
                            <input class="form-control w-50" id="followup_date" name="followup_date" type="date" required <?php if(isset($_GET["contract_followup"])){echo("value='" . htmlspecialchars($_GET['contract_followup']) ."'");} ?>>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deadline">Date d'échéance</label>
                        <input id="end" type="date" class="form-control" name="contract_end" min="<?php echo(date("Y-m-d")); ?>" <?php if(isset($_GET["contract_end"])){echo("value='" . htmlspecialchars($_GET['contract_end']) ."'");} ?> required>
                    </div>

                </div>

            </div>
            
            <!-- Ligne 3  -->
            <div class="form-group">

                <label for="contractor">Fournisseur / Préstataire</label>

                <div class="d-flex flex-row">

                    <select name='contractor' class="form-control" required>

                        <option hidden>Choisir un fournisseur / prestataire</option>

                        <?php foreach($contractors as $row): ?>
                            <option name='contractor' 
                                value="<?php echo($row["id"]);?>" 
                                <?php 
                                    if($preload && $preload["contractor_id"] == $row["id"]){echo("selected");}
                                    elseif(isset($_GET["contractor_id"]) && $_GET["contractor_id"] == $row["id"]){echo("selected");} 
                                ?>
                            >
                                <?php echo($row["contractor_name"]);?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                    <!-- Ouverture du modal d'ajout rapide d'un fournisseur -->
                    <button type="button" class="btn second-bg" onclick="save_contract('add_contractor')" data-toggle='modal' data-target='#add_contractor'>+</button>

                </div>
                <!-- Affichage des erreurs -->
                <?php if(isset($_GET["contractorError"])){echo("<p class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["contractorError"]) . "</p>");} ?>
                
                <!-- Ligne conditionnelle de séléction du service, si aucun service n'est actuellement séléctionné par l'utilisateur -->
                <?php if($_SESSION["UserInfo"]["Current_Service"] == "*"): ?>

                    <label for="service">Service</label>

                    <div class="d-flex flex-row">

                        <select class="form-control" name="service" id="service" required>

                            <option hidden>Choisir un service auquel attribuer le contrat</option>

                            <?php foreach($user_services as $service): ?>
                                <option value="<?php echo($service["id"]); ?>" 
                                    <?php 
                                        if(isset($_GET["service_id"]) && $_GET["service_id"] == $service["id"]) {echo("selected");}
                                        elseif($preload && $preload["service_id"] == $service["id"]){echo("selected");} 
                                    ?> 
                                >
                                    <?php echo($service["service_name"]); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                        <button type="button" class="btn second-bg" data-toggle='modal' data-target='#leave_confirmation'>+</button>

                    </div>
                    <!-- Affichage des erreurs -->
                    <?php if(isset($_GET["serviceError"])){echo("<p class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["serviceError"]) . "</p>");} ?>
                
                <!-- Si l'utilisateur a séléctionné un service, préremplissage du champ -->
                <?php else: ?>
                    <input type="hidden" name="service" value="<?php echo($_SESSION["UserInfo"]["Current_Service"]); ?>" required>
                <?php endif; ?>
                
                <!-- Si il s'agit d'une reconduction de contrat, le numéro du précédent contrat est prérempli dans ce champ -->
                <input type="hidden" name="previous_contract_num" value="<?php if(isset($_GET["intern_num"])){ echo(htmlspecialchars($_GET["intern_num"]));}?>">

            </div>
            
            <!-- Ligne 5 -->
            <div class="form-group">
                <label for="pdf_file">Fichier PDF du contrat</label>
                <input type="file" class="form-control mb-3 " name="pdf_file" id="pdf_file" accept="application/pdf">
            </div>
            
            <!-- Affichage des erreurs -->
            <div class="text-center">
                
                <?php if($_SESSION["UserInfo"]["Current_Service"] != "*" && isset($_GET["serviceError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . $_GET["serviceError"] . "</p>");}?>
                <?php if(isset($_GET["dateError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["dateError"]) . "</p>");} ?>
                <?php if(isset($_GET["prevContractNumError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["prevContractNumError"]) . "</p>");} ?>
                <?php if(isset($_GET["fileError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["fileError"]) . "</p>");} ?>
                <p class='text-danger font-weight-bold' id="dateerror" style="display:none;">Les dates renseignées sont invalides, veuillez vérifer les valeurs</p>
                
            </div>

            <!-- Boutons -->
            <div class="d-flex flex-row justify-content-center">
                
                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                <!-- Vers la page consultée précédemment -->
                <button type="button" onclick="history.back(); return false;" class="btn btn-lg second-bg mr-5">Retour</button>
                <button id="submit_btn" type="submit" name="submit_new_contract" class="btn btn-lg main-bg ">Ajouter</button>

            </div>

        </form>

    </div>

    <!-- Modal d'ajout rapide d'un contrac -->
    <div class="modal fade" id="add_contractor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title font-weight-bold main-txt">Ajout rapide contractant</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>

                </div>

                <div class="modal-body">
                    <form action="index.php?route=new_contractor&returnto=new_contract" method="POST" enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="name">Nom</label>
                            <input class="form-control" type="text" name="name" maxlength="100" placeholder="Entrer le nom du fournisseur / prestataire" required>
                        </div>

                        <div class="form-group">
                            <label for="ref">Référence</label>
                            <input class="form-control" type="text" name="ref" maxlength="30" placeholder="Entrer la référence du fournisseur" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input class="form-control" type="email" name="email" maxlength="100" placeholder="Entrer l'email de contact" required>
                        </div>

                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input class="form-control" type="tel" name="telephone" minlength="10" maxlength="10"placeholder="Entrer le numéro de téléphone" required>
                        </div>

                        <?php if($_SESSION["UserInfo"]["Current_Service"] == "*"): ?>
                            <label for="service">Service</label>
                            <div class="d-flex flex-row">

                                <select class="form-control" name="service_id" id="service" required>
                                    <option hidden>Choisir un service pour le fournisseur</option>
                                    <?php foreach($user_services as $service): ?>
                                        <option value="<?php echo($service["id"]); ?>">
                                            <?php echo($service["service_name"]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            </div>                    
                        <?php endif; ?>

                        <input type="hidden" name="contract_name" id="save_name">
                        <input type="hidden" name="contract_followup" id="save_followup">
                        <input type="hidden" name="contract_end" id="save_end">
                        <input type="hidden" name="contract_start" id="save_start">
                        <input type="hidden" name="service_id" id="save_service">
                        <?php if(isset($_GET["intern_num"])){echo("<input type='hidden' name='intern_num' value='" . htmlspecialchars($_GET["intern_num"]) . "'>");} ?>
                    
                    </div>

                    <div class="modal-footer">
                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <button type="submit" name="submit_new_contractor" class="btn main-bg">Ajouter</button>
                        <button type="button" class="btn second-bg">Annuler</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation en cas d'utilisation de l'accès rapide : création d'un service -->
    <div class="modal fade" id="leave_confirmation" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold main-txt">Attention</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <p>Les données actuellement renseignées ne seront pas conservées ... Voulez-vous poursuivre ?</p>
                </div>

                <div class="modal-footer">
                    <a href="index.php?route=new_service" class="btn main-bg">Oui</a>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Non</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal de changement de service actuellement séléctionné -->
    <div class="modal fade" id="switch_service_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold main-txt">Changer de service</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <form action="index.php?route=change_service<?php if(isset($_GET['route'])){echo("&returnto=" . $_GET['route']);} ?>"  method="POST" enctype="multipart/form-data">

                    <div class="modal-body">

                        <select name="select_service" class="form-control">
                                <?php foreach($user_services as $service): ?>
                                    <option 
                                        value="<?php echo($service['id']);?>" 
                                        <?php if($service["id"] == $_SESSION["UserInfo"]["Current_Service"]){
                                            echo("selected");
                                        } ?> 
                                    >
                                        <?php echo($service["service_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="*" <?php if($_SESSION["UserInfo"]["Current_Service"] == "*"){echo("selected");} ?>>Tous les services</option>
                            </select>
                        
                    </div>

                    <div class="modal-footer">
                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <button type="submit" name="submit-service" class="btn main-bg">Choisir</button>
                    </div>

                </form>

            </div>
        </div>
    </div>

</main>

<!-- JS -->
<script>

    // Sauvegarde des champs déjà rempli lorsque l'utilisateur utilise l'ajout rapide d'un contractant
    function save_contract(dest){
        modal = document.getElementById(dest);
        modal.querySelector("#save_name").value = document.getElementById("contract_name").value;
        modal.querySelector("#save_start").value = document.getElementById("start").value;
        modal.querySelector("#save_end").value = document.getElementById("end").value;
        modal.querySelector("#save_followup").value = document.getElementById("followup_date").value;
        modal.querySelector("#save_service").value = document.getElementById("service").value;
    }

    // Désactivation de la soumission du formulaire lorsque une erreur est détectée
    function disable_form(){
        document.querySelectorAll("#errorMsg").forEach((element) => {element.style.display="none";});
        document.getElementById("submit_btn").disabled = true;
        document.getElementById("dateerror").style.display = 'block';
    }

    // Réactivation de la soumission du formulaire lorsque l'erreur est résolue
    function enable_form(){
        document.getElementById("submit_btn").disabled = false;
        document.getElementById("dateerror").style.display = 'none';
    }

    // Calcul automatique de la date relance grâce à l'assistant "Mois précédents l'expiration"
    function calc_followup(){
        const dateStr = document.getElementById("end").value;
        const moisASoustraire = parseInt(document.getElementById("followup").value, 10);

        const date = new Date(dateStr);
        date.setMonth(date.getMonth() - moisASoustraire);

        const annee = date.getFullYear();
        const mois = String(date.getMonth() + 1).padStart(2, "0");
        const jour = String(date.getDate()).padStart(2, "0");

        const dateFormatee = `${annee}-${mois}-${jour}`;
        document.getElementById("followup_date").value = dateFormatee;
    }

    // Vérification de l'ordre de dates : Début < Relance < Fin
    function check_dates(){
        start = new Date(document.getElementById("start").value);
        followup = new Date(document.getElementById("followup_date").value);
        end = new Date(document.getElementById("end").value);
        if((document.getElementById("start").value != '' || document.getElementById("followup_date").value != '' && document.getElementById("end").value != '')  && (start > end || start > followup || followup > end)){
            disable_form();
        }
        else if (followup < new Date()){
            disable_form();
        }
        else{
            enable_form();
        }
    }

    // Création d'un ecouteur sur le champ "Début du contrat"
    document.getElementById('start').addEventListener('change', function() {
        check_dates();
    });

    // Création d'un ecouteur sur le champ "Fin du contrat"
    document.getElementById('end').addEventListener('change', function() {
        end = new Date(this.value);

        if(document.getElementById("followup").value == 0 && document.getElementById("followup_date").value == ''){
            document.getElementById("followup_date").value = String(end.getFullYear())+ "-" +String(end.getMonth() + 1).padStart(2, "0")+"-"+String(end.getDate()).padStart(2, "0");
        }
        else if (document.getElementById("end").value != '' && document.getElementById("followup").value != 0) {
            calc_followup();
        }

        check_dates();
    });

    // Création d'un ecouteur sur le champ de calcul de la date de relance
    document.getElementById('followup').addEventListener('change', function() {

        if (document.getElementById("end").value != '' && document.getElementById("followup").value != 0) {
            calc_followup();
        }
        else if(document.getElementById("followup").value == 0){
            document.getElementById("followup_date").value = document.getElementById("end").value;
        }

        check_dates();
    });

    // Création d'un ecouteur sur le champ de la date de relance
    document.getElementById('followup_date').addEventListener('change', function() {
        check_dates();
    });

</script>