<?php
/**
 * PAGE DE GESTION DES CONTRATS
 * 
 * Cette page affiche une table des contrats avec différentes vues :
 * - Contrats actifs (par défaut)
 * - Contrats expirés 
 * - Contrats supprimés
 * 
 * Fonctionnalités principales :
 * - Affichage sous forme de tableau Bootstrap avec tri et pagination
 * - Export des données pour Admin/Manager
 * - Gestion des notes de contrats
 * - Actions CRUD selon les permissions utilisateur
 * - Modaux pour les informations détaillées
 */
?>

<main>
    <div class="mx-5">
        <!-- 
        TABLE PRINCIPALE DES CONTRATS
        - Utilise Bootstrap Table avec fonctionnalités avancées
        - Export conditionnel selon le rôle utilisateur
        - Tri par défaut : ASC pour contrats actifs, DESC pour les autres
        -->
        <table id="table" 
               class="table border table-borderless table-striped table-hover" 
               data-toggle="table" 
               <?php if($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Role"] == "Manager"):?> 
                   data-show-export="true" 
                   exportDataType='basic' 
               <?php endif; ?> 
               data-pagination="true" 
               data-search="true" 
               data-sort-name="default" 
               data-sort-order="<?php if($table == "contracts"){echo("asc");}else{echo("desc");} ?>">

            <!-- BARRE D'ACTIONS (boutons flottants) -->
            <div class="position-absolute">
                <?php 
                // Bouton "Ajouter un Contrat" - uniquement pour Admin/Manager avec service assigné
                if(($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Role"] == "Manager") && $_SESSION["UserInfo"]["Current_Service"]): ?>
                    <a class='btn btn-rounded main-bg' href="index.php?route=new_contract">Ajouter un Contrat</a>
                <?php endif; ?>

                <?php 
                // Bouton "Changer de Service" - affiché si l'utilisateur a accès à plusieurs services
                if($service_count > 1){
                    echo("<button class='btn btn-rounded second-bg' data-toggle='modal' data-target='#switch_service_modal'>Changer de Service</button>");
                }?>
            </div>

            <!-- TITRE DYNAMIQUE selon le type de contrats affichés -->
            <?php if(isset($_GET["route"]) && $_GET["route"] == "expired_contracts"): ?>
                <h2 class="position-absolute font-weight-bold main-txt" style="left:44%;">Contrats expirés</h2>
            <?php elseif(isset($_GET["route"]) && $_GET["route"] == "deleted_contracts"): ?>
                <h2 class="position-absolute font-weight-bold main-txt" style="left:43%;">Contrats supprimés</h2>
            <?php else : ?>
                <h2 class="position-absolute font-weight-bold main-txt" style="left:45%;">Contrats actifs</h2>
            <?php endif; ?>

            <!-- EN-TÊTES DE COLONNES (conditionnelles selon le type de vue) -->
            <thead>
                <tr>
                    <th scope="col" data-sortable="true">N°Contrat</th>
                    <th scope="col" data-sortable="true">Nom</th>

                    <!-- Colonne "Début" uniquement pour les contrats actifs -->
                    <?php if($table == 'contracts'): ?> 
                        <th scope="col" data-sortable="true">Début</th> 
                    <?php endif; ?>

                    <!-- Colonne "Échéance" toujours présente, utilisée pour le tri par défaut -->
                    <th scope="col" data-sortable="true" data-field="default">Echéance</th>

                    <!-- Colonnes spécifiques aux contrats actifs -->
                    <?php if($table == 'contracts'): ?> 
                        <th scope="col" data-sortable="true">Relance</th>
                        <th scope="col" data-sortable="true">Mail Fournisseur</th> 
                    <?php endif; ?>

                    <!-- Colonnes spécifiques aux contrats supprimés -->
                    <?php if($table == 'deleted_contracts'): ?> 
                        <th scope="col" data-sortable="true">Date de Supression</th>
                        <th scope="col" data-sortable="true">Motif</th> 
                    <?php endif; ?>
                    
                    <!-- Colonne actions (sans titre) -->
                    <th></th>
                </tr>
            </thead>

            <tbody>
              <?php
              /**
               * GÉNÉRATION DYNAMIQUE DES LIGNES DE CONTRATS
               * Parcours du tableau $displayed_contracts fourni par le contrôleur
               */
              foreach($displayed_contracts as $row): 
                  
                  // Détermination du statut du contrat (actif/supprimé)
                  if(isset($row["status"])){
                      $status = $row["status"];
                  } else {
                      $status = "deleted"; // Par défaut pour les contrats supprimés
                  }
              ?>
    
                <!-- 
                LIGNE DE CONTRAT avec mise en forme conditionnelle
                - Fond rouge si le contrat est en retard de relance (date de relance < aujourd'hui)
                -->
                <tr <?php if($table == "contracts" && $row["contract_followup_date"] < date("Y-m-d") && $status == 'active'){echo("style='background-color:#FF6865'");}; ?> >

                    <!-- COLONNES DE DONNÉES PRINCIPALES -->
                    <!-- Numéro de contrat (lien vers détail) -->
                    <td><a href='index.php?route=contract_detail&intern_num=<?php echo($row["intern_num"]);?>'><?php echo($row["intern_num"]);?></a></td>
                    
                    <!-- Nom du contrat (lien vers détail) -->
                    <td><a href='index.php?route=contract_detail&intern_num=<?php echo($row["intern_num"]);?>'><?php echo($row["contract_name"]); ?></a></td>
                    
                    <!-- Date de début (contrats actifs uniquement) -->
                    <?php if($table == 'contracts'): ?>
                        <td><?php echo($row["contract_start"]); ?></td>
                    <?php endif; ?>
                    
                    <!-- Date d'échéance -->
                    <td><?php echo($row["contract_end"]); ?></td>
                    
                    <!-- COLONNES SPÉCIFIQUES AUX CONTRATS ACTIFS -->
                    <?php if($table == 'contracts'): ?>
                        <!-- Date de relance -->
                        <td><?php echo($row["contract_followup_date"]); ?></td>
                        
                        <!-- Informations du fournisseur/prestataire -->
                        <?php if($row["contractor_mail"]): ?>
                            <td>
                                <!-- Lien modal pour afficher les infos complètes du contractant -->
                                <a href='#' 
                                    <?php 
                                    // Préparation des paramètres JavaScript pour le modal
                                    echo("onclick=\"fill_contractor_modal('"); 
                                    echo($row["contractor_name"] . "','");     // Nom
                                    echo($row["contractor_ref"] . "','");      // Référence
                                    echo($row["contractor_mail"] . "','");     // Email
                                    echo($row["contractor_tel"] . "')\"");     // Téléphone
                                    ?> 
                                data-toggle='modal' data-target='#contractor_info_modal'>
                                <?php echo($row["contractor_mail"]); ?>
                                </a>
                            </td> 
                        <?php else: ?>
                            <!-- Cas où le contractant a été supprimé -->
                            <td>Contractant Supprimé</td>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- COLONNES SPÉCIFIQUES AUX CONTRATS SUPPRIMÉS -->
                    <?php if($table == "deleted_contracts"): ?> 
                        <td><?php echo($row["deletion_date"]); ?></td>
                        <td><?php echo($row["reason"]); ?></td>
                    <?php endif; ?>
                    
                    <!-- COLONNE ACTIONS (icônes de fonctionnalités) -->
                    <td class='d-flex justify-content-end'>
                        
                        <!-- ICÔNE NOTES - Accessible à tous les utilisateurs -->
                        <a class='mr-1' href='#' 
                            <?php
                            // Préparation du contenu JavaScript pour le modal de notes
                            $js_content = "fill_note_modal(\"". $row["contract_notes"] . "\",\"" . $row["intern_num"] . "\")";
                            echo("onclick='" . $js_content . "'");
                            ?>
                             data-toggle='modal' data-target='#note_modal'>
                            <img src='templates/images/note.svg' width='30' alt='Notes à propos du contrat'>
                        </a>
                        
                        <!-- ICÔNE VISUALISATION - Ouvrir le PDF dans un nouvel onglet -->
                        <a class='mx-1' href="index.php?route=file_display&contract_num=<?php echo($row["intern_num"]);?>&status=<?php echo($status); ?>" target="_blank"> 
                            <img src='templates/images/show.svg' width='30' alt='Voir le contrat'> 
                        </a>
                        
                        <!-- ICÔNE TÉLÉCHARGEMENT - Télécharger le PDF -->
                        <a class='mx-1' href="index.php?route=file_download&contract_num=<?php echo($row["intern_num"]);?>&status=<?php echo($status); ?>"> 
                            <img src='templates/images/download.svg' width='30' alt='Télécharger le contrat'> 
                        </a>
                        
                        <!-- MENU DROPDOWN "PLUS D'OPTIONS" - Réservé aux Admin/Manager -->
                        <?php if($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Role"] == "Manager"):?>
                            <div class='ml-1' class='dropdown show'>
                                    <!-- Icône 3 points pour ouvrir le menu -->
                                    <a href='#' class='dropdown-toggle' data-toggle='dropdown'> 
                                        <img src='templates/images/more.svg' width='30' alt="Plus d'options"> 
                                    </a>
                                    
                                    <!-- CONTENU DU MENU DROPDOWN -->
                                    <div class='dropdown-menu dropdown-menu-right'>
                                    
                                    <!-- OPTIONS POUR CONTRATS ACTIFS -->
                                    <?php if($table == "contracts"): ?>
                                        <!-- Reconduire le contrat (créer un nouveau contrat basé sur celui-ci) -->
                                        <a class='dropdown-item' href="index.php?route=new_contract&intern_num=<?php echo($row['intern_num']); ?>">Reconduire</a>
                                        
                                        <!-- Modifier le contrat -->
                                        <a class='dropdown-item' href="index.php?route=update_contract&intern_num=<?php echo($row["intern_num"]); ?>">Modifier</a>
                                        
                                        <!-- Supprimer le contrat (avec modal de confirmation) -->
                                        <a class='dropdown-item' href='#' 
                                           <?php echo("onclick=\"fill_deletion_modal('" . $row["intern_num"] . "')\""); ?> 
                                           data-toggle='modal' data-target='#confirm_deletion_modal'>Supprimer</a>
                                    <?php endif; ?>
                                    
                                    <!-- OPTIONS POUR CONTRATS SUPPRIMÉS -->
                                    <?php if($table == "deleted_contracts"): ?>
                                        <!-- Restaurer le contrat supprimé -->
                                        <a class='dropdown-item' href='#' 
                                           <?php echo("onclick=\"fill_recover_modal('" . $row["intern_num"] . "')\""); ?> 
                                           data-toggle='modal' data-target='#confirm_recover_modal'>Restaurer</a>
                                    <?php endif; ?>
                                    </div>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>
              
            </tbody>
        </table>
    </div>

    <!-- ========================================== -->
    <!-- SECTION DES MODAUX (POPUPS) -->
    <!-- ========================================== -->

    <?php if($table == "contracts" ): ?>
        
    <!-- 
    MODAL D'INFORMATIONS CONTRACTANT/FOURNISSEUR
    Affiche les détails complets du prestataire
    -->
    <div class="modal fade" id="contractor_info_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold main-txt">Informations Fournisseur / Prestataire</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <!-- Contenu du modal - Informations remplies par JavaScript -->
                <div class="modal-body">
                    <ul class="row"> <p class="font-weight-bold mr-2">Nom : </p> <p id="contractor_name"></p></ul>
                    <ul class="row"> <p class="font-weight-bold mr-2">Référence : </p> <p id="contractor_ref"></p></ul>
                    <ul class="row"> <p class="font-weight-bold mr-2">Mail : </p> <p id="contractor_mail"></p></ul>
                    <ul class="row"> <p class="font-weight-bold mr-2">Téléphone : </p> <p id="contractor_tel"></p></ul>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn main-bg" data-dismiss="modal">Fermer</button>
                </div>

            </div>
        </div>
    </div>

    <script>
        /**
         * FONCTION JAVASCRIPT - Remplissage du modal contractant
         * Appelée lors du clic sur l'email du fournisseur dans le tableau
         * 
         * @param {string} name - Nom du contractant
         * @param {string} reference - Référence du contractant
         * @param {string} mail - Email du contractant
         * @param {string} tel - Téléphone du contractant
         */
        function fill_contractor_modal(name, reference, mail, tel){
            document.getElementById("contractor_name").innerHTML = name; 
            document.getElementById("contractor_ref").innerHTML = reference; 
            document.getElementById("contractor_mail").innerHTML = mail; 
            document.getElementById("contractor_tel").innerHTML = tel;
        }
    </script>

    <!-- MODAL DE CONFIRMATION DE SUPPRESSION - Admin/Manager uniquement -->
    <?php if($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Role"] == "Manager"):?>

    <div class="modal fade" id="confirm_deletion_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h5 class="modal-title font-weight-bold main-txt">Etes vous sûr de vouloir supprimer ce contrat ?</h5>
                </div>
                
                <!-- Formulaire de suppression avec motif obligatoire -->
                <div class="modal-body">
                <form action="index.php?route=delete_contract<?php 
                    // Conservation de l'URL de retour si on vient de la page contrats expirés
                    if(isset($_GET['route']) && $_GET['route'] == 'expired_contracts'){
                        echo("&returnto=expired_contracts");
                    } ?>" method="POST" enctype="multipart/form-data">
                    
                    <!-- Champ caché pour l'ID du contrat (rempli par JavaScript) -->
                    <input type="hidden" name="del_contract_num" id="del_contract_num"></input>
                    
                    <!-- Sélection du motif de suppression -->
                    <div class="d-flex flex-row align-items-center justify-content-center">
                        <h6 class="fw-bold mr-2">Raison : </h6>
                        <select name="reason" required>
                            <option hidden></option>
                            <option value="Contrat Erroné">Contrat Erroné</option>
                            <option value="Contrat Obsolète">Contrat Obsolète</option>
                            <option value="Duplicata">Duplicata</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer"> 
                    <!-- Protection CSRF -->
                    <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                    <button type="submit" name="submit_deletion" class="btn main-bg" id="submit_deletion">Supprimer</button>
                </form>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * FONCTION JAVASCRIPT - Préparation du modal de suppression
         * @param {string} contract_id - ID du contrat à supprimer
         */
        function fill_deletion_modal(contract_id){
            document.getElementById("del_contract_num").value = contract_id;
        }
    </script>

    <?php endif; ?>
    <?php endif; ?>

    <!-- MODAL DE RESTAURATION - Pour contrats supprimés, Admin/Manager uniquement -->
    <?php if($table == "deleted_contracts" && ($_SESSION["UserInfo"]["Role"] == "Manager" || $_SESSION["UserInfo"]["Role"] == "Admin")): ?>

    <div class="modal fade" id="confirm_recover_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h5 class="modal-title main-txt font-weight-bold">Etes vous sûr de vouloir restaurer ce contrat ?</h5>
                </div>
                
                <div class="modal-footer">
                <form action="index.php?route=recover_contract" method="POST" enctype="multipart/form-data">
                    <!-- Champ caché pour l'ID du contrat (rempli par JavaScript) -->
                    <input type="hidden" name="recover_contract_num" id="recover_contract_num"></input>
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
         * FONCTION JAVASCRIPT - Préparation du modal de restauration
         * @param {string} contract_id - ID du contrat à restaurer
         */
        function fill_recover_modal(contract_id){
                document.getElementById("recover_contract_num").value = contract_id;
        }
    </script>

    <?php endif; ?>

    <!-- 
    MODAL DE GESTION DES NOTES
    - Accessible à tous les utilisateurs
    - Éditable uniquement pour Admin/Manager
    - Lecture seule pour les autres rôles
    -->
    <div class="modal fade" id="note_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold main-txt">Notes à propos du contrat</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <!-- VERSION ÉDITABLE - Admin/Manager -->
                <?php if($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Role"] == "Manager"):?>
                <form action="index.php?route=update_notes<?php 
                    // Conservation de l'URL de retour
                    if(isset($_GET["route"]) && $_GET["route"]){
                        echo("&returnto=" . $_GET["route"]);
                    }?>" id="note_form" method="POST" enctype="multipart/form-data">
                
                <div class="modal-body">
                    <!-- Zone de texte éditable -->
                    <textarea id="contract_notes" name="contract_notes" maxlength="300" cols="48" rows="15" style="resize: none;" ></textarea>
                </div>

                <div class="modal-footer">
                    <!-- Champs cachés pour l'ID du contrat et protection CSRF -->
                    <input type="hidden" name="intern_num" id="note_contract_id">
                    <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                    <button type="submit" name="submit_notes" class="btn main-bg" id="save_note">Enregistrer</button>
                </form>
                
                <!-- VERSION LECTURE SEULE - Autres rôles -->
                <?php else: ?>
                    <div class="modal-body">
                        <!-- Zone de texte non-éditable -->
                        <textarea id="contract_notes" name="contract_notes" maxlength="300" cols="48" rows="15" style="resize: none; pointer-events:none" ></textarea>
                    </div>
                    <div class="modal-footer">
                <?php endif; ?>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * FONCTION JAVASCRIPT - Remplissage du modal de notes
         * @param {string} note - Contenu de la note (peut être null/vide)
         * @param {string} contract_id - ID du contrat concerné
         */
        function fill_note_modal(note, contract_id){
            // Gestion du cas où il n'y a pas de note
            if(note == null || note.trim() == ''){
                document.getElementById("contract_notes").value = "Aucune note à propos de ce contrat";
            }
            else{
                document.getElementById("contract_notes").value = note;
            }
            document.getElementById("note_contract_id").value = contract_id;
        }
    </script>

<!-- MODAL DE CHANGEMENT DE SERVICE - Si l'utilisateur a accès à plusieurs services -->
<?php if($service_count>1): ?>

    <div class="modal fade" id="switch_service_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold main-txt">Changer de service</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <form action="index.php?route=change_service<?php 
                        // Conservation de l'URL de retour
                        if(isset($_GET['route'])){
                            echo("&returnto=" . $_GET['route']);
                        } ?>"  method="POST" enctype="multipart/form-data">
                        
                        <!-- Liste déroulante des services disponibles -->
                        <select name="select_service" class="form-control">
                            <?php
                            // Génération des options de services
                            foreach($user_services as $service){
                                echo("<option value='" . $service['id'] . "' ");
                                // Pré-sélection du service actuel
                                if($service["id"] == $_SESSION["UserInfo"]["Current_Service"]){
                                    echo("selected");
                                }
                                echo(">" . $service["service_name"] . "</option>");
                            }
                            ?>
                            <!-- Option "Tous les services" -->
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
        
</main>

<!-- ========================================== -->
<!-- STYLES CSS PERSONNALISÉS -->
<!-- ========================================== -->

<style>
    /* Liens dans les cellules du tableau en noir */
    td a {
        color : black;
    }

    /* Masquage de la flèche dropdown sur l'icône "plus d'options" */
    main a.dropdown-toggle::after {
        display: none;
    }

    /* PERSONNALISATION DE LA TABLE BOOTSTRAP */
    
    /* Couleur de la page active dans la pagination */
    .page-item.active .page-link {
        background-color: #FF0000 !important;
        border-color : #FF0000 !important;
    }

    /* Couleur des liens de pagination */
    .page-link {
        color: #FF0000;
    }

    /* Couleur du bouton d'export */
    .fixed-table-toolbar .dropdown-toggle{
        background-color:#FF0000 !important;
        border-color:#FF0000 !important;
    }

    /* Couleur des en-têtes de colonnes */
    .th-inner{
        color:#000F46;
    }
    
    /* Couleur des liens dans les cellules */
    .table tr td a{
        color:#000F46;
    }

    /* Couleur des liens au survol */
    .table tr td a:hover{
        color:#FF0000;
    }

    /* Couleur du sélecteur de nombre d'éléments par page */
    .fixed-table-pagination .dropdown-toggle{
        background-color:#000F46 !important;
    }
</style>