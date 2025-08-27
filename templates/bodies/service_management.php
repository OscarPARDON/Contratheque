
<!-- Page de Gestion des services -->

<main>

    <div class="mx-5">
        <!-- Bouton d'ajout d'un nouveau service positionné en absolu en haut de page -->
        <div class="position-absolute">
            <a class='btn btn-rounded main-bg' href="index.php?route=new_service">Ajouter un service</a>
        </div>

        <!-- Table principale avec fonctionnalités Bootstrap Table (pagination, recherche, tri) -->
        <table id="table" class="table border table-borderless table-striped table-hover" data-toggle="table" data-pagination="true" data-search="true">

                <thead>
                    <tr>
                        <!-- Colonne numéro de ligne -->
                        <th scope="col">#</th>
                        <!-- Colonne nom du service (triable) -->
                        <th scope="col" data-sortable="true">Nom du Service</th>
                        <!-- Colonne abréviation du service (triable) -->
                        <th scope="col" data-sortable="true">Abréviation</th>
                        <!-- Colonne actions (pas de titre) -->
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php 
                    // Compteur pour numéroter les lignes
                    $i = 0;
                    // Boucle sur tous les services récupérés depuis la base de données
                    foreach($services as $row):
                        $i++; ?>

                    <!-- Ligne du service avec mise en surbrillance si c'est le service actuel de l'utilisateur -->
                    <tr <?php if($row["id"] == $_SESSION["UserInfo"]["Current_Service"]){echo("style='background-color:#000F46' class='text-white'");} ?>>
                        <!-- Numéro de ligne -->
                        <td><?php echo($i); ?></td>
                        <!-- Nom complet du service -->
                        <td><?php echo($row["service_name"]); ?></td>
                        <!-- Nom court/abréviation du service -->
                        <td><?php echo($row["short_name"]); ?></td>
                        <!-- Colonne actions avec boutons alignés à droite -->
                        <td class='d-flex justify-content-end'>
                            <!-- Bouton de modification du service (icône adaptive selon le service courant) -->
                            <a class="mr-3" href='index.php?route=update_service&serviceId=<?php echo($row["id"]); ?>'><img <?php if($row["id"] == $_SESSION["UserInfo"]["Current_Service"]){echo("src='templates/images/edit_white.svg'");}else{echo("src='templates/images/edit.svg'");}?> width='30' alt="Edit Service"></a>
                            
                            <!-- Bouton de suppression (visible seulement si ce n'est pas le service courant) -->
                            <?php if($row["id"] != $_SESSION["UserInfo"]["Current_Service"]): ?>
                                <a class="mr-2" href='#' <?php echo("onclick=\"fill_deletion_modal('" . $row["id"] . "')\""); ?> data-toggle='modal' data-target='#confirm_deletion_modal'><img src='templates/images/trash.svg' width='30' alt="Delete User"></a>
                            <?php endif; ?>
                            
                            <!-- Bouton de gestion des utilisateurs du service (icône adaptive selon le service courant) -->
                            <a class="mr-3" href='index.php?route=service_users&serviceId=<?php echo($row["id"]); ?>'><img <?php if($row["id"] == $_SESSION["UserInfo"]["Current_Service"]){echo("src='templates/images/add_users_white.svg'");}else{echo("src='templates/images/add_users.svg'");}?> width='30' alt="Edit Service"></a>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

    </div>

    <!-- Modal d'erreur affiché si un paramètre "error" est présent dans l'URL -->
    <?php if(isset($_GET["error"])): ?>
        <div class="modal fade" id="error_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Titre de l'erreur -->
                <div class="modal-body">
                    <h5 class="modal-title">L'opération n'a pas pu aboutir ...</h5>
                </div>
                <!-- Message d'erreur sécurisé avec htmlspecialchars -->
                <div class="modal-body">
                    <?php echo(htmlspecialchars($_GET["error"])); ?>
                </div>
                <!-- Bouton de fermeture -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-lg btn-success" id="close">D'accord</button>
                </div>
            </div>
        </div>

        <!-- Script d'auto-ouverture et gestion de fermeture du modal d'erreur -->
        <script>

            document.addEventListener('DOMContentLoaded', function () {
                // Initialisation du modal Bootstrap
                const myModal = new bootstrap.Modal(document.getElementById('error_modal'));
                const closeModalBtn = document.getElementById('close');
                
                // Affichage automatique du modal
                myModal.show();

                // Gestionnaire d'événement pour fermer le modal
                closeModalBtn.addEventListener('click', function () {
                    myModal.hide();
                });
            });
            
        </script>
    <?php endif; ?>


    <!-- Modal de confirmation pour la suppression d'un service -->
    <div class="modal fade" id="confirm_deletion_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Message de confirmation -->
                <div class="modal-body">
                    <h5 class="modal-title font-weight-bold main-txt">Etes vous sûr de vouloir supprimer ce service ?</h5>
                </div>
                <!-- Boutons d'action -->
                <div class="modal-footer"> 
                    <!-- Formulaire de suppression avec protection CSRF -->
                    <form action="index.php?route=delete_service" method="POST" enctype="multipart/form-data">
                        <!-- ID du service à supprimer (rempli par JavaScript) -->
                        <input type="hidden" name="del_service_id" id="del_service_id"></input>
                        <!-- Token CSRF pour sécuriser la suppression -->
                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <!-- Bouton de confirmation de suppression -->
                        <button type="submit" name="submit_deletion" class="btn main-bg" id="submit_deletion">Supprimer</button>
                    </form>
                    <!-- Bouton d'annulation -->
                    <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Fonction JavaScript pour remplir l'ID du service dans le modal de suppression -->
    <script>

        function fill_deletion_modal(service_id){
            // Définit l'ID du service à supprimer dans le champ caché du formulaire
            document.getElementById("del_service_id").value = service_id;
        }

    </script>
    
</main>

<!-- Styles CSS personnalisés -->
<style>

    /* Réécriture du style de la table bootstrap */
    /* Personnalisation de la couleur de pagination active */

    .page-item.active .page-link {
        background-color: #FF0000 !important; /* Rouge pour l'élément de pagination actif */
        border-color : #FF0000 !important;    /* Bordure rouge assortie */
    }

    /* Style des en-têtes de colonnes triables */
    .th-inner{
        color:#000F46; /* Couleur bleu foncé pour le texte des en-têtes */
    }


</style>

<!-- Bootstrap JS - Bibliothèque JavaScript pour les composants interactifs -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>