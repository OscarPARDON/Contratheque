<!-- Page de Gestion des utilisateurs -->
<!-- Interface d'administration pour gérer les comptes utilisateurs d'un système -->

<main>

    <div class="px-5">
        <!-- Zone des boutons d'action principaux -->
        <div class="position-absolute">
            <!-- Bouton pour créer un nouvel utilisateur -->
            <a class='btn btn-rounded main-bg' href="index.php?route=new_user">Ajouter un utilisateur</a>
            
            <?php 
            // Affichage conditionnel du bouton de changement de service
            // Ne s'affiche que si l'utilisateur a accès à plusieurs services
            if($service_count > 1){
                echo("<button class='btn btn-rounded second-bg' data-toggle='modal' data-target='#switch_service_modal'>Changer de Service</button>");
            }?>
        </div>

        <!-- Tableau principal listant tous les utilisateurs -->
        <!-- Utilise Bootstrap Table avec fonctionnalités avancées : tri, recherche, pagination, export -->
        <table id="table" class="table border table-borderless table-striped table-hover" data-toggle="table" data-show-export="true" exportDataType='basic' data-pagination="true" data-sort-name="default" data-sort-order="asc" data-search="true">

                <thead>
                    <!-- En-têtes des colonnes avec tri activé -->
                    <tr>
                        <th scope="col" data-sortable="true" data-field="default">Samaccountname</th> <!-- Identifiant unique de l'utilisateur -->
                        <th scope="col" data-sortable="true">Nom</th>
                        <th scope="col" data-sortable="true">Prénom</th>
                        <th scope="col" data-sortable="true">Email</th>
                        <th scope="col" data-sortable="true">Téléphone</th>
                        <th scope="col" data-sortable="true">Administrateur</th> <!-- Statut admin Oui/Non -->
                        <th scope="col" data-sortable="true">Actif</th> <!-- Compte actif/désactivé -->
                        <th></th> <!-- Colonne pour les actions (édition, suppression, etc.) -->
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($displayed_users as $row): ?>
                    <!-- Boucle pour afficher chaque utilisateur -->
                    <!-- Mise en forme conditionnelle des lignes selon le statut -->
                    <tr <?php 
                        // Ligne rouge si utilisateur désactivé
                        if($row["active"] == 0){
                            echo("style='background-color:#FF6969;color:white'");
                        }
                        // Ligne bleu foncé si c'est l'utilisateur connecté actuellement  
                        elseif($row["samaccountname"] == $_SESSION["UserInfo"]["Username"]){
                            echo("style='background-color:#000F46;color:white'");
                        } ?>>
                        
                        <!-- Affichage des données utilisateur -->
                        <td><?php echo($row["samaccountname"]); ?></td>
                        <td><?php echo($row["lastname"]); ?></td>
                        <td><?php echo($row["firstname"]); ?></td>
                        <td><?php echo($row["mail"]); ?></td>
                        <td><?php echo($row["tel_num"]); ?></td>
                        
                        <!-- Conversion booléen vers texte pour le statut admin -->
                        <td><?php if($row["is_admin"] == 1){echo("Oui");}else{echo("Non");} ?></td>
                        
                        <!-- Conversion booléen vers texte pour le statut actif -->
                        <td><?php if($row["active"] == 1){echo("Oui");}else{echo("Non");} ?></td>
                        
                        <!-- Colonne des actions disponibles -->
                        <td class='d-flex justify-content-end'>
                            <!-- Bouton d'édition (toujours disponible) -->
                            <!-- Icône différente si c'est l'utilisateur connecté (blanc sur fond foncé) -->
                            <a class="mr-2" href='index.php?route=update_user&userId=<?php echo($row["samaccountname"]); ?>'>
                                <?php if($row["samaccountname"] != $_SESSION["UserInfo"]["Username"]): ?>
                                    <img src='templates/images/edit.svg' width='30' alt="Edit User">
                                <?php else: ?>
                                    <img src='templates/images/edit_white.svg' width='30' alt="Edit User">
                                <?php endif; ?>
                            </a>
                            
                            <?php if($row["samaccountname"] != $_SESSION["UserInfo"]["Username"]): ?>
                                <!-- Actions interdites sur son propre compte -->
                                
                                <?php if($row["active"] == 1) : ?>
                                    <!-- Bouton de désactivation (si compte actif) -->
                                    <a class="mr-2" href='#' <?php echo("onclick=\"load_user_id('" . $row["samaccountname"] . "','confirm_deactivation_modal')\""); ?> data-toggle='modal' data-target='#confirm_deactivation_modal'>
                                        <img src='templates/images/deactivate_user.svg' width='30' alt="Deactivate User">
                                    </a>
                                <?php else: ?>
                                    <!-- Bouton de réactivation (si compte désactivé) -->
                                    <a class="mr-2" href='#' <?php echo("onclick=\"load_user_id('" . $row["samaccountname"] . "','confirm_recover_modal')\""); ?> data-toggle='modal' data-target='#confirm_recover_modal'>
                                        <img src='templates/images/recover.svg' width='30' alt="Recover User">
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Bouton de suppression définitive (toujours disponible sauf pour soi-même) -->
                                <a class="mr-2" href='#' <?php echo("onclick=\"load_user_id('" . $row["samaccountname"] . "','confirm_complete_deletion_modal')\""); ?> data-toggle='modal' data-target='#confirm_complete_deletion_modal'>
                                    <img src='templates/images/trash.svg' width='30' alt="User Full Deletion">
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

    </div>

    <?php if($service_count > 1): ?>
    <!-- Modal de changement de service - Affiché seulement si plusieurs services disponibles -->

    <div class="modal fade" id="switch_service_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold main-txt">Changer de service</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <!-- Formulaire de sélection du service -->
                    <form action="index.php?route=change_service<?php 
                        // Conservation de la route de retour si elle existe
                        if(isset($_GET['route'])){
                            echo("&returnto=" . $_GET['route']);
                        } ?>"  method="POST" enctype="multipart/form-data">
                        
                        <!-- Liste déroulante des services disponibles -->
                        <select name="select_service" class="form-control">
                            <?php
                            // Génération des options à partir des services filtrés
                            foreach($filtered_services as $service){
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
                    <!-- Protection CSRF -->
                    <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                    <button type="submit" name="submit-service" class="btn main-bg">Choisir</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <!-- Modal de confirmation de désactivation d'utilisateur -->
    <div class="modal fade" id="confirm_deactivation_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold main-txt">Êtes-vous vraiment sûr de vouloir désactiver cet utilisateur ?</h5>
                </div>
                <div class="modal-footer">
                    <!-- Formulaire de désactivation -->
                    <form action="index.php?route=deactivate_user" method="POST" enctype="multipart/form-data">
                        <!-- L'ID utilisateur sera injecté par JavaScript -->
                        <input type="hidden" name="deactivate_user_id" id="user_id"></input>
                        <!-- Protection CSRF -->
                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <button type="submit" name="submit_deactivation" class="btn main-bg" id="submit_deactivation">Désactiver</button>
                        <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression définitive -->
    <div class="modal fade" id="confirm_complete_deletion_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h5 class="modal-title font-weight-bold main-txt">Êtes-vous vraiment sûr de vouloir supprimer cet utilisateur ?</h5>
                </div>
                <div class="modal-body">
                    <!-- Avertissement sur l'irréversibilité de l'action -->
                    <i class="main-txt">Il ne pourra plus être récupéré après cette opération ...</i>
                </div>
                <div class="modal-footer">
                    <!-- Formulaire de suppression définitive -->
                    <form action="index.php?route=delete_user" method="POST" enctype="multipart/form-data">
                        <!-- L'ID utilisateur sera injecté par JavaScript -->
                        <input type="hidden" name="del_user_id" id="user_id"></input>
                        <!-- Protection CSRF -->
                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <button type="submit" name="submit_deletion" class="btn main-bg" id="submit_deactivation">Supprimer</button>
                    </form>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de réactivation d'utilisateur -->
    <div class="modal fade" id="confirm_recover_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h5 class="modal-title font-weight-bold main-txt">Etes vous sûr de vouloir réactiver cet utilisateur ?</h5>
                </div>
                <div class="modal-footer">
                    <!-- Formulaire de réactivation -->
                    <form action="index.php?route=recover_user" method="POST" enctype="multipart/form-data">
                        <!-- L'ID utilisateur sera injecté par JavaScript -->
                        <input type="hidden" name="recover_username" id="user_id"></input>
                        <!-- Protection CSRF -->
                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <button type="submit" name="submit_recover" class="btn main-bg" id="submit_deletion">Restaurer</button>
                    </form>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>

<script>
    /**
     * Fonction JavaScript pour charger l'ID utilisateur dans le modal approprié
     * Utilisée pour les actions de confirmation (désactivation, suppression, réactivation)
     * 
     * @param {string} user_id - L'identifiant de l'utilisateur (samaccountname)
     * @param {string} modal_id - L'ID du modal à cibler
     */
    function load_user_id(user_id, modal_id) {
        // Récupération du modal par son ID
        const modal = document.getElementById(modal_id);
        if (modal) {
            // Recherche du champ hidden "user_id" dans le modal
            const input = modal.querySelector("#user_id");
            if (input) {
                // Injection de l'ID utilisateur dans le champ hidden
                input.value = user_id;
            }
        }
    }
</script>
    
</main>

<style>
    /* Styles personnalisés pour la page */
    
    /* Couleur des liens dans les cellules du tableau */
    td a {
        color : black;
    }

    /* Masquage de la flèche du dropdown sur l'icône "plus d'options" */
    /* Améliore l'aspect visuel des boutons dropdown */
    main a.dropdown-toggle::after {
        display: none;
    }

    /* Réécriture du style de la table bootstrap */
    /* Personnalisation des couleurs de pagination */
    
    /* Couleur de l'élément de page actif */
    .page-item.active .page-link {
        background-color: #FF0000 !important; /* Rouge */
        border-color : #FF0000 !important;
    }

    /* Couleur des liens de pagination */
    .page-link {
        color: #FF0000; /* Rouge */
    }

    /* Couleur du bouton de la barre d'outils de la table */
    .fixed-table-toolbar .dropdown-toggle{
        background-color:#FF0000 !important; /* Rouge */
        border-color:#FF0000 !important;
    }

    /* Couleur des en-têtes de colonnes */
    .th-inner{
        color:#000F46; /* Bleu marine */
    }
</style>

<!-- Bootstrap JS - Nécessaire pour le fonctionnement des modals et autres composants interactifs -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>