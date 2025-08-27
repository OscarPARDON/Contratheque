
<!-- Page de modification d'un contrat -->

<main style="height:100%;">
    <div class="container">

        <h2 class="py-3 font-weight-bold main-txt">Modifier le Contrat</h2>

        <form action="index.php?route=update_contract<?php if($contract['status'] == 'expired'){echo("&returnto=expired_contracts");}?>" method="POST" enctype="multipart/form-data">
            
            <!-- Ligne 1 & 2 -->
            <div class="row">

                <!-- Colonne 1 -->
                <div class="col-md-6">

                    <div class="form-group">
                        <label for="contract_name">Nom du contrat</label>
                        <input type="text" class="form-control" name="contract_name" id="contract_name" value="<?php if(isset($_GET["contract_name"])){echo(htmlspecialchars($_GET["contract_name"]));}else{echo($contract["contract_name"]);}?>" placeholder="Entrer le nom du contrat" maxlength="30" required>
                        <?php if(isset($_GET["nameError"])){echo("<p class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["nameError"]) . "</p>");} ?>
                    </div>

                    <div class="form-group">
                        <label for="contract_start">Date de Début</label>
                        <input id="start" type="date" class="form-control" name="contract_start" value="<?php if(isset($_GET["contract_start"])){echo(htmlspecialchars($_GET["contract_start"]));}else{echo($contract["contract_start"]);}?>" required>
                    </div>

                </div>
                
                <!-- Colonne 2 -->
                <div class="col-md-6">

                    <div class="form-group">
                            <label for="followup">Date de relance</label>
                            <div class="d-flex flex-row ">    
                                <input id="followup" placeholder="Mois avant expiration ..." type="number" class="form-control w-50" name="followup" min="0">
                                <input class="form-control w-50" id="followup_date" name="followup_date" type="date" required value="<?php if(isset($_GET["contract_followup"])){echo(htmlspecialchars($_GET["contract_followup"]));}else{echo($contract["contract_followup_date"]);}?>">
                            </div>
                    </div>

                    <div class="form-group">
                        <label for="contract_end">Date d'Echéance</label>
                        <input id="end" type="date" class="form-control" name="contract_end" value="<?php if(isset($_GET["contract_end"])){echo(htmlspecialchars($_GET["contract_end"]));}else{echo($contract["contract_end"]);}?>" required>
                    </div>

                </div>

            </div>
            
            <!-- Ligne 3 & 4 -->
            <div class="form-group">

                <label for="contractor">Fournisseur / Préstataire</label>
                <div class="d-flex flex-row">

                    <select name='contractor' class="form-control" required>

                        <?php
                            foreach($contractors as $row) {
                                echo("<option name='contractor' value='" . $row["id"]) . "'";
                                if($row["id"] == $contract["contractor_id"]){echo("selected");}
                                echo(">" . $row["contractor_name"] . "</option>");
                            }
                        ?>
                    </select>

                    <!-- Ouverture du modal d'ajout rapide d'un fournisseur -->
                    <button type="button" class="btn second-bg" onclick="save_contract('add_contractor')" data-toggle='modal' data-target='#add_contractor'>+</button>

                </div>
                <!-- Affichage des erreurs -->
                <?php if(isset($_GET["contractorError"])){echo("<p class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["contractorError"]) . "</p>");} ?>
                            
                <!-- Champ de séléction du service si l'utilisateur n'a pas de service actuellement séléctionnée -->
                <?php if($_SESSION["UserInfo"]["Current_Service"] == "*"): ?>

                    <label for="service">Service</label>

                    <div class="d-flex flex-row">

                        <select class="form-control" name="service" id="service" default="<?php echo($contract["service_id"]); ?>" required>

                            <option hidden>Choisir un service auquel attribuer le contrat</option>

                            <?php foreach($services as $service): ?>
                                <option 
                                    value="<?php echo($service["id"]); ?>" 
                                    <?php if(isset($_GET["service_id"]) && $_GET["service_id"] == $service["id"]): ?> selected 
                                    <?php elseif($service["id"] == $contract["service_id"]):?> selected
                                    <?php endif; ?>
                                >
                                    <?php echo($service["service_name"]); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <button type="button" class="btn second-bg" data-toggle='modal' data-target='#leave_confirmation'>+</button>

                    </div>
                    <!-- Affichage des erreurs -->
                    <?php if(isset($_GET["serviceError"])){echo("<p class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["serviceError"]) . "</p>");} ?>
                
                <!-- Si l'utilisateur a un service actuellement sélectionné, autoremplissage du champ service -->
                <?php else: ?>

                    <input type="hidden" name="service" value="<?php echo($contract["service_id"]); ?>" required>

                <?php endif; ?>

                <input type="hidden" name="intern_num" value="<?php echo($contract["intern_num"]);?>">

            </div>
            
            <!-- Ligne 5 -->
            <div class="form-group">
                <label for="pdf_file"><strong>Ajoutez un fichier ici si vous souhaitez remplacer le PDF actuel</strong><strong style="color:red;"> (ATTENTION : L'ancien pdf sera supprimé définitivement)</strong></label>
                <input type="file" class="form-control mb-3 " name="pdf_file" id="pdf_file" accept="application/pdf">
            </div>

            <!-- Affichage des erreurs -->
            <div class="text-center">
                <?php if($_SESSION["UserInfo"]["Current_Service"] != "*" && isset($_GET["serviceError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . $_GET["serviceError"] . "</p>");}?>
                <?php if(isset($_GET["internNumError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["internNumError"]) . "</p>");} ?>
                <?php if(isset($_GET["dateError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["dateError"]) . "</p>");} ?>
                <?php if(isset($_GET["fileError"])){echo("<p id='errorMsg' class='text-danger font-weight-bold'>" . htmlspecialchars($_GET["fileError"]) . "</p>");} ?>
                <p class='text-danger font-weight-bold' id="dateerror" style="display:none;">Les dates renseignées sont invalides, veuillez vérifer les valeurs</p>
            </div>

            <div class="d-flex flex-row justify-content-center">
                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                <!-- Retour à la page précédente -->
                <button type="button" class="btn btn-lg second-bg" onclick="history.back(); return false;">Retour</button>
                <button id="submit_btn" type="submit" name="submit_contract_update" class="btn btn-lg main-bg ml-5">Enregistrer</button>
            </div>

        </form>
    </div>
    
    <!-- Modal d'ajout rapide d'un contractant -->
    <div class="modal fade" id="add_contractor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title font-weight-bold main-txt">Ajout rapide contractant</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>

                </div>

                <form action="index.php?route=new_contractor&returnto=update_contract" method="POST" enctype="multipart/form-data">

                    <div class="modal-body">

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
                                            <option value="<?php echo($service["id"]); ?>"><?php echo($service["service_name"]); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                </div>                    
                            <?php endif; ?>
                            
                            <!-- Sauvegarde des champs déjà remplis -->
                            <input type="hidden" name="contract_name" id="save_name">
                            <input type="hidden" name="contract_followup" id="save_followup">
                            <input type="hidden" name="contract_end" id="save_end">
                            <input type="hidden" name="contract_start" id="save_start">
                            <input type="hidden" name="intern_num" id="save_num" value="<?php echo(htmlspecialchars($_GET["intern_num"])); ?>">
                            <input type="hidden" name="service_id" id="save_service">
                        
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

    <!-- Modal de confirmation avant redirection vers l'ajout d'un service -->
    <div class="modal fade" id="leave_confirmation" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title font-weight-bold main-txt">Attention</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>

                </div>

                <div class="modal-body">

                    <p>Les modifications ne seront pas conservées ... Voulez-vous poursuivre ?</p>
                    
                </div>

                <div class="modal-footer">
                    <a href="index.php?route=new_service" class="btn main-bg">Oui</a>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Non</button>
                </div>

            </div>
        </div>
    </div>

</main>

<!-- JS -->
<script>

    // Sauvegarde des champs déjà remplis lorsque l'utilisateur utilise l'ajout rapide d'un contractant
    function save_contract(dest){
        modal = document.getElementById(dest);
        modal.querySelector("#save_name").value = document.getElementById("contract_name").value;
        modal.querySelector("#save_start").value = document.getElementById("start").value;
        modal.querySelector("#save_end").value = document.getElementById("end").value;
        modal.querySelector("#save_followup").value = document.getElementById("followup_date").value;
        modal.querySelector("#save_service").value = document.getElementById("service").value;
    }

    // Désactive la soumission du formulaire lorsqu'une erreur est détectée
    function disable_form(){
        document.querySelectorAll("#errorMsg").forEach((element) => {element.style.display="none";});
        document.getElementById("submit_btn").disabled = true;
        document.getElementById("dateerror").style.display = 'block';
    }

    // Réactive la soumission du formulaire lorsque l'erreur est corrigée
    function enable_form(){
        document.getElementById("submit_btn").disabled = false;
        document.getElementById("dateerror").style.display = 'none';
    }

    // Calcul de la date de relance avec la fonctionnalité "nombre de mois avant l'echéance"
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

    // Vérification de l'ordre des dates (Début < Relance < Fin)
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

    // Ajout de l'écouteur sur le champ "Début du contrat"
    document.getElementById('start').addEventListener('change', function() {
        check_dates();
    });

    // Ajout de l'ecouteur sur le champ "Fin du contrat"
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

    // Ajout d'un écouteur sur le champ assistant de calcul de la date de relance
    document.getElementById('followup').addEventListener('change', function() {

        if (document.getElementById("end").value != '' && document.getElementById("followup").value != 0) {
            calc_followup();
        }
        else if(document.getElementById("followup").value == 0){
            document.getElementById("followup_date").value = document.getElementById("end").value;
        }

        check_dates();

    });

    // Ajout de l'ecouteur sur le champ "Date de relance"
    document.getElementById('followup_date').addEventListener('change', function() {
        check_dates();
    });

</script>