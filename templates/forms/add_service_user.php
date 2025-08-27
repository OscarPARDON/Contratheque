
<!-- Formulaire pour ajouter un utilisateur à un service -->

<main>

    <div class="mx-5">

        <!-- En cas d'erreur : Affichage de l'erreur dans une alerte -->
        <?php if(isset($_GET["error"])): ?>
            <div class="alert alert-danger w-100 font-weight-bold"><?php echo(htmlspecialchars($_GET["error"])); ?></div>
        <?php endif; ?>

        <!-- Retour à la page de gestion des utilisateurs du service -->
        <div class="position-absolute">
            <a class='btn btn-rounded second-bg' href="index.php?route=service_users&serviceId=<?php echo(htmlspecialchars($_GET["serviceId"])); ?>">Retour</a>
        </div>

        <!-- Table des utilisateurs non ajoutés au service -->
        <table id="table" class="table border table-borderless table-striped table-hover" data-toggle="table" data-pagination="true" data-search="true">

                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col" data-sortable="true">Nom</th>
                        <th scope="col" data-sortable="true">Prénom</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    <?php 
			$i=0;
                        foreach($users as $row):
			$i++; ?>

                        <tr>
                            <td><?php echo($i); ?></td>
                            <td><?php echo($row["lastname"]); ?></td>
                            <td><?php echo($row["firstname"]); ?></td>
                            <td class='d-flex justify-content-end'>

                                <?php if($_SESSION["UserInfo"]["Role"] == "Admin"): ?>
                                    <?php if($row["is_admin"]): ?>
                                        <a href="#" onclick="<?php echo("add_admin_user('" . $row['samaccountname'] . "','" . $_GET['serviceId'] . "');"); ?>"><img src="templates/images/plus.svg" alt="Add user in service" width='20'></a>  
                                    <?php else : ?>
                                        <a href="#" onclick="<?php echo("fill_role_modal('" . $row["samaccountname"] . "','" . $_GET['serviceId'] . "');"); ?>" data-toggle='modal' data-target='#confirm_role_modal'><img src="templates/images/plus.svg" alt="Add user in service" width='20'></a>  
                                    <?php endif; ?>

                                <?php else: ?>
                                    <a href="#" onclick="<?php echo("add_user('" . $row['samaccountname'] . "','" . $_GET['serviceId'] . "');"); ?>"><img src="templates/images/plus.svg" alt="Add user in service" width='20'></a>  
                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

    </div>

    <!-- Modal de séléction du rôle de l'utilisateur ajouté dans le service -->
    <div class="modal fade" id="confirm_role_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-body">
                    <h5 class="modal-title font-weight-bold main-txt">Veuillez renseigner le rôle de l'utilisateur dans le service :</h5>
                </div>

                <div class="modal-body">
                    <select class="form-control" name="role" id="select_role">
                        <option value="User">Utilisateur</option>
                        <option value="Manager">Responsable</option>
                    </select>
                </div>

                <div class="modal-footer"> 
                    <a href="#" onclick="add_user();" class="btn main-bg">Ajouter</a>
                    <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Formulaire caché d'ajout d'un utilisateur : utilisé par JS -->
    <form action="index.php?route=add_service_user" method="POST" enctype="multipart/form-data" id="add_user">

        <?php if($_SESSION["UserInfo"]["Role"] == "Admin"): ?>
            <input type="hidden" name="role" id="role"></input>

        <?php endif; ?>
            <input type="hidden" name="user_sam" id="user_sam"></input>
            <input type="hidden" name="service_id" id="service_id"></input>
            <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">

    </form>
    
    <?php if($_SESSION["UserInfo"]["Role"] == "Admin"): ?>

        <!-- JS -->
        <script>

            // Ajout d'un utilisateur admin dans le service : autoremplissage du formulaire et envoi
            function add_admin_user(user_sam,service_id){
                form = document.getElementById("add_user");
                document.getElementById("user_sam").value = user_sam;
                document.getElementById("service_id").value = service_id;
                form.submit();
            }

            // Préremplissage du modal de séléction du rôle
            function fill_role_modal(user_sam, service_id){
                document.getElementById("user_sam").value = user_sam;
                document.getElementById("service_id").value = service_id;
            }

            // Ajout d'un utilisateur non admin au service : autoremplissage du formulaire et envoi
            function add_user(){
                form = document.getElementById("add_user");
                role = document.getElementById("select_role").value;
                if(role == "Manager" || role == "User"){
                    document.getElementById("role").value = role;
                    form.submit();   
                }
            }

        </script>

    <?php else: ?>

        <!-- JS -->
        <script>

            // Ajout d'un utilisateur non admin au service : autoremplissage du formulaire et envoi
            function add_user(user_sam, service_id){
                form = document.getElementById("add_user");
                document.getElementById("user_sam").value = user_sam;
                document.getElementById("service_id").value = service_id;
                form.submit();
            }

        </script>

    <?php endif; ?>

    <style>

        .th-inner{
            color:#000F46;
        }

    </style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
