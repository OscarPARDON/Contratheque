
<!-- Page de gestion des contractants -->

<main>

    <div class="mx-5">
        <!-- Boutons d'actions principales -->
        <div class="position-absolute">
            <!-- Bouton pour ajouter un nouveau contractant -->
            <a class='btn btn-rounded main-bg' href="index.php?route=new_contractor">Ajouter un contractant</a>
            
            <?php 
            // Affichage conditionnel du bouton de changement de service
            // Ne s'affiche que si l'utilisateur a accès à plus d'un service
            if($service_count>1){
                echo("<button class='btn btn-rounded second-bg' data-toggle='modal' data-target='#switch_service_modal'>Changer de Service</button>");
            }?>
        </div>

        <!-- Table principale des contractants avec fonctionnalités Bootstrap Table -->
        <table id="table" class="table border table-borderless table-striped table-hover" 
               data-toggle="table" 
               data-show-export="true" 
               exportDataType='basic' 
               data-pagination="true" 
               data-sort-name="default" 
               data-sort-order="asc" 
               data-search="true">

                <!-- En-têtes de colonnes avec tri activé -->
                <thead>
                    <tr>
                        <th scope="col" data-sortable="true">Référence</th>
                        <th scope="col" data-sortable="true" data-field="default">Nom</th>
                        <th scope="col" data-sortable="true">Email</th>
                        <th scope="col" data-sortable="true">Téléphone</th>
                        <th></th> <!-- Colonne pour les actions (éditer/supprimer) -->
                    </tr>
                </thead>

                <tbody>
                    <?php 
                    // Boucle pour afficher chaque contractant dans la table
                    foreach($contractors as $row): ?>

                    <tr>
                        <!-- Affichage des données du contractant -->
                        <td><?php echo($row["contractor_ref"]); ?></td>
                        <td><?php echo($row["contractor_name"]); ?></td>
                        <td><?php echo($row["contractor_mail"]); ?></td>
                        <td><?php echo($row["contractor_tel"]); ?></td>
                        
                        <!-- Colonne des actions avec boutons d'édition et suppression -->
                        <td class='d-flex justify-content-end'>
                                <!-- Bouton d'édition : ouvre le modal de modification avec les données pré-remplies -->
                                <a class="mr-3" href='#' 
                                   <?php echo("onclick=\"fill_edit_modal('" . $row["id"] . "','" . $row["contractor_ref"] . "','" . $row["contractor_name"] . "','" . $row["contractor_mail"] . "','" . $row["contractor_tel"] . "')\""); ?> 
                                   data-toggle='modal' 
                                   data-target='#edit_modal'>
                                   <img src='templates/images/edit.svg' width='30' alt="Edit User">
                                </a>
                                
                                <!-- Bouton de suppression : ouvre le modal de confirmation -->
                                <a class="mr-2" href='#' 
                                   <?php echo("onclick=\"fill_deletion_modal('" . $row["id"] . "')\""); ?> 
                                   data-toggle='modal' 
                                   data-target='#confirm_deletion_modal'>
                                   <img src='templates/images/trash.svg' width='30' alt="Delete User">
                                </a>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

    </div>

    <?php 
    // Modal de changement de service - affiché seulement si plusieurs services disponibles
    if($service_count>1): ?>

<!-- Modal de changement de service -->
<div class="modal fade" id="switch_service_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title font-weight-bold main-txt">Changer de service</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <!-- Formulaire de changement de service avec préservation de la route de retour -->
                <form action="index.php?route=change_service<?php if(isset($_GET['route'])){echo("&returnto=" . $_GET['route']);} ?>"  
                      method="POST" 
                      enctype="multipart/form-data">
                    
                    <!-- Liste déroulante des services disponibles -->
                    <select name="select_service" class="form-control">
                        <?php
                        // Génération dynamique des options de services
                        foreach($services as $service){
                            echo("<option value='" . $service['id'] . "' ");
                            // Sélection automatique du service actuel
                            if($service["id"] == $_SESSION["UserInfo"]["Current_Service"]){
                                echo("selected");
                            }
                            echo(">" . $service["service_name"] . "</option>");
                        }
                        ?>
                        <!-- Option spéciale pour voir tous les services -->
                        <option value="*" <?php if($_SESSION["UserInfo"]["Current_Service"] == "*"){echo("selected");} ?>>Tous les services</option>
                    </select>
                
            </div>

            <div class="modal-footer">
                <!-- Protection CSRF pour la sécurité -->
                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                <button type="submit" name="submit-service" class="btn main-bg">Choisir</button>
            </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="confirm_deletion_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h5 class="modal-title font-weight-bold main-txt">Êtes-vous sûr de vouloir supprimer ce contractant ?</h5>
                </div>
                <div class="modal-body">
                <!-- Formulaire de suppression -->
                <form action="index.php?route=delete_contractor" method="POST" enctype="multipart/form-data">
                    <!-- ID du contractant à supprimer (rempli par JavaScript) -->
                    <input type="hidden" name="contractor_id" id="contractor_id"></input>
                </div>
                <div class="modal-footer">
                    <!-- Protection CSRF -->
                    <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                    <button type="submit" name="submit_deletion" class="btn main-bg" id="submit_deletion">Supprimer</button>
                </form>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fonction JavaScript pour pré-remplir le modal de suppression avec l'ID du contractant
        function fill_deletion_modal(contractor_id){
            document.getElementById("contractor_id").value = contractor_id;
        }
    </script>

<!-- Modal de modification d'un contractant -->
<div class="modal fade" id="edit_modal" tabindex="-1" aria-labelledby="edit_modal" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title font-weight-bold main-txt">Modifier un contractant</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <!-- Formulaire de modification avec validation côté client -->
        <form action="index.php?route=edit_contractor" method="POST" class="container-fluid d-flex flex-column">
            
            <!-- Champ Référence -->
            <strong>Référence</strong>
            <input class="mb-3 form-control" type="text" name="ref" id="ref" maxlength="30" required></input>
            
            <!-- Champ Nom -->
            <strong>Nom</strong>
            <input class="mb-3 form-control" type="text" name="name" id="name" maxlength="100" required></input>
            
            <!-- Champ Email avec validation JavaScript -->
            <strong>Email</strong>
            <input class="mb-3 form-control" type="text" name="email" id="email" maxlength="100" required></input>
            
            <!-- Champ Téléphone avec validation (format français) -->
            <strong>N° de Téléphone</strong>
            <input class="form-control" type="text" name="telephone" id="telephone" minlength="10" maxlength="10" required></input>
            
            <!-- Champ caché pour l'ID du contractant -->
            <input type="hidden" name="id" id="id" required></input>
            
            <!-- Zone d'affichage des erreurs de validation -->
            <p class="mt-3" id="error" style="color:red;display:none"></p>
      </div>
      <div class="modal-footer">
        <!-- Protection CSRF -->
        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
        <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
        <button type="submit" name="submit_edit" id="submit_edit" class="btn main-bg">Enregistrer</button>
      </div>
    </form>
    </div>
  </div>
</div>

<?php 
// Affichage d'une alerte JavaScript si une erreur est passée en paramètre GET
if(isset($_GET["error"])):?>

<script>
  alert("<?php echo($_GET['error']); ?>");
</script>

<?php endif; ?>

<script>
        // Fonction pour pré-remplir le modal d'édition avec les données du contractant sélectionné
        function fill_edit_modal(id,ref,name,email,phone){
            document.getElementById("id").value = id;
            document.getElementById("ref").value = ref;
            document.getElementById("name").value = name;
            document.getElementById("email").value = email;
            document.getElementById("telephone").value = phone;
        }

        // Fonction pour afficher un message d'erreur et désactiver le bouton de soumission
        function display_error(message){
            document.getElementById("submit_edit").disabled = true;
            document.getElementById("error").innerHTML = message;
            document.getElementById("error").style.display = 'block';
        }

        // Fonction pour masquer l'erreur et réactiver le bouton de soumission
        function hide_error(){
            document.getElementById("submit_edit").disabled = false;
            document.getElementById("error").style.display = 'none';
        }

        // Validation de l'email en temps réel
        document.getElementById("email").addEventListener("change",function(e){
            // Expression régulière pour valider le format email
            const regex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
            if(!regex.test(document.getElementById("email").value)){
                display_error("Veuillez entrer un email valide");
            }
            else{
                hide_error();
            }
        });

        // Validation du numéro de téléphone français (format 0xxxxxxxxx)
        document.getElementById("telephone").addEventListener("change",function(e){
            // Expression régulière pour format français : 0 suivi de 9 chiffres
            const regex = /^0\d{9}$/;
            if(document.getElementById("telephone").value != '' && !regex.test(document.getElementById("telephone").value)){
                display_error("Veuillez entrer un numéro de télephone valide");
            }
            else{
                hide_error();
            }
        });

</script>
    
</main>

<!-- Styles CSS personnalisés pour l'interface -->
<style>

    /* Masquage de la flèche du dropdown sur l'icône "plus d'options" */
    main a.dropdown-toggle::after {
        display: none;
    }

    /* Personnalisation des styles de pagination Bootstrap Table */
    
    /* Couleur de fond de la page active dans la pagination */
    .page-item.active .page-link {
        background-color: #FF0000 !important;
        border-color : #FF0000 !important;
    }

    /* Couleur des liens de pagination */
    .page-link {
        color: #FF0000;
    }

    /* Style du bouton de la barre d'outils de la table */
    .fixed-table-toolbar .dropdown-toggle{
        background-color:#FF0000 !important;
        border-color:#FF0000 !important;
    }

    /* Couleur du texte dans les en-têtes de colonnes */
    .th-inner{
        color:#000F46;
    }

</style>

<!-- Inclusion de Bootstrap JS pour les fonctionnalités modales et interactives -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>