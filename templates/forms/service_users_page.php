
<!-- Page de gestion des utilisateurs d'un service -->

<main>

    <div class="mx-5">

        <!-- En cas d'ajout d'un nouvel utilisateur, message de confirmation -->
        <?php if(isset($_GET["success"])): ?>
            <div class="alert alert-success w-100 font-weight-bold">L'utilisateur a bien été ajouté</div>
        <?php endif; ?>

        <!-- Affichage des erreurs -->
        <?php if(isset($_GET["error"])): ?>
            <div class="alert alert-danger w-100 font-weight-bold"><?php echo(htmlspecialchars($_GET["error"])); ?></div>
        <?php endif; ?>
        
        <div class="position-absolute">
            <!-- Vers formulaire d'ajout d'un utilisateur au service -->
            <a class='btn btn-rounded main-bg' href="index.php?route=add_service_user&serviceId=<?php echo($_GET["serviceId"]); ?>">Ajouter un utilisateur</a>
            <!-- Retour à la page de gestion des services -->
            <a class='btn btn-rounded second-bg' href="index.php?route=service_management">Retour</a>
        </div>

        <table id="table" class="table border table-borderless table-striped table-hover" data-toggle="table" data-pagination="true" data-search="true">

                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col" data-sortable="true">Nom</th>
                        <th scope="col" data-sortable="true">Prénom</th>
                        <th scope="col" data-sortable="true">Role</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php 
                    $i = 0;
                    foreach($users as $row):
                        $i++; ?>

                        <tr <?php if($row["samaccountname"] == $_SESSION["UserInfo"]["Username"]){echo("style='background-color:#000F46;' class='text-white'");}elseif($row["role"] == "Admin"){echo("style='background-color:#5F7CEB;' class='text-white'");} ?>>
                            
                            <td><?php echo($i); ?></td>
                            <td><?php echo($row["lastname"]); ?></td>
                            <td><?php echo($row["firstname"]); ?></td>
                            <td><?php echo($row["role"]); ?></td>
                            <td class='d-flex justify-content-end'>

                                <?php if($role == "Admin" && $_SESSION["UserInfo"]["Username"] != $row["samaccountname"] && $row["role"]!="Admin"): ?>
                                    <a href="#" class="mr-3" onclick="<?php echo("fill_role_modal('" . $row["samaccountname"] . "','" . $_GET['serviceId'] . "','" . $row["role"] . "');"); ?>" data-toggle='modal' data-target='#confirm_role_modal'><img src="templates/images/edit.svg" alt="Edit user role in service" width='30'></a>  
                                <?php endif; ?>

                                <?php if($role === "Admin" || ($role === "Manager" && $row["role"] === "User")): ?>
                                <a href="#" onclick="<?php echo("fill_removal_modal('" . $row["samaccountname"] . "','" . $_GET['serviceId'] . "');"); ?>" data-toggle='modal' data-target='#confirm_removal_modal'><?php if($row["role"] == "Admin") : ?><img src="templates/images/cross_white.svg" alt="Remove user from service" width='20'><?php else: ?><img src="templates/images/cross.svg" alt="Remove user from service" width='20'><?php endif; ?></a>  
                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

        </table>

    </div>

    <!-- Modal de confirmation avant de retirer un utilisateur du service -->
    <div class="modal fade" id="confirm_removal_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-body">
                    <h5 class="modal-title font-weight-bold main-txt">Êtes-vous sûr de vouloir retirer cet utilisateur du service ?</h5>
                </div>

                <div class="modal-footer">

                    <form action="index.php?route=remove_service_user" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="user_sam" id="user_sam"></input>
                        <input type="hidden" name="service_id" id="service_id"></input>
                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <button type="submit" name="submit_removal" class="btn main-bg">Retirer</button>
                    </form>

                    <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                </div>

            </div>
        </div>
    </div>

    <!-- JS du modal -->
    <script>

        // Autoremplissage des informations de l'utilisateur à retirer dans le formulaire
        function fill_removal_modal(user_sam,service_id){
            modal = document.getElementById("confirm_removal_modal");
            modal.querySelector("#user_sam").value = user_sam;
            modal.querySelector("#service_id").value = service_id;
        }

    </script>

    <?php if($role == "Admin"):?>

        <!-- Modal de sélection du rôle -->
        <div class="modal fade" id="confirm_role_modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-body">
                        <h5 class="modal-title font-weight-bold main-txt">Quel rôle voulez-vous attribuer à cet utilisateur ?</h5>
                    </div>

                    <form action="index.php?route=update_user_role" method="POST" enctype="multipart/form-data">

                        <div class="modal-body">

                            <select class="form-control" name="new_role">
                                <option value="User">Utilisateur</option>
                                <option value="Manager">Responsable</option>
                            </select>

                            <input type="hidden" name="user_sam" id="user_sam"></input>
                            <input type="hidden" name="service_id" id="service_id"></input>
                            <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">

                        </div>

                        <div class="modal-footer"> 
                            <button type="submit" name="submit_role" class="btn main-bg">Modifier</button>
                            <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        <!-- Js du modal -->
        <script>

            // Autoremplissage des informations de l'utilisateur à retirer dans le formulaire
            function fill_role_modal(user_sam,service_id,current_role){

                const modal = document.getElementById("confirm_role_modal");
                modal.querySelector("#user_sam").value = user_sam;
                modal.querySelector("#service_id").value = service_id;

                options = modal.querySelectorAll("option");
                options.forEach(option => {
                    if(option.value == current_role){
                        option.setAttribute('selected', true);
                    }
                });
            }

        </script>

    <?php endif; ?>

    <!-- CSS -->
    <style>

        .th-inner{
            color:#000F46;
        }

    </style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
