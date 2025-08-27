
<!-- Page de statistiques -->

<main class="d-flex flex-column align-items-center" style="min-height: 100vh;">

    <!-- Ligne 1 : Stats sur tous les services -->
    <?php if($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Current_Service"] == "*"): ?>

        <h1 class="m-0 position-relative font-weight-bold main-txt">
            Tous les services
            <button class="btn btn-sm main-bg ml-2" style="position: absolute; top: 50%; transform: translateY(-50%);" data-toggle='modal' data-target='#switch_service_modal'>
                Changer
            </button>
        </h1>

        <div class="container-fluid px-3 px-lg-5 mt-2" style="height: auto; min-height: 45%;">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3" style="min-height: 250px;">

                <div class="col mb-4">
                    <a href="index.php" class="h-100 d-flex flex-column text-decoration-none text-dark">
                        <div class="mb-3">
                            <h3 class="text-center fs-5 main-txt">Nombre de contrats actifs</h3>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative">
                            <img src="templates/images/green_hexagon.svg" alt="Nombre de contrats actifs" style="width: 80%; max-width: 180px; position: absolute; z-index: -1;">
                            <h1 class="display-4 text-white"><?php echo($counters["active_total"]); ?></h1>
                        </div>
                    </a>
                </div>

                <div class="col mb-4">
                    <a href="index.php" class="h-100 d-flex flex-column text-decoration-none text-dark">
                        <div class="mb-3">
                            <h3 class="text-center fs-5 main-txt">Nombre de contrats à reconduire</h3>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative">
                            <img src="templates/images/orange_hexagon.svg" alt="Nombre de contrats à reconduire" style="width: 80%; max-width: 180px; position: absolute; z-index: -1;">
                            <h1 class="display-4 text-white"><?php echo($counters["inalert_total"]); ?></h1>
                        </div>
                    </a>
                </div>

                <div class="col mb-4">
                    <a href="index.php?route=expired_contracts" class="h-100 d-flex flex-column text-decoration-none text-dark">
                        <div class="mb-3">
                            <h3 class="text-center fs-5 main-txt">Nombre de contrats expirés</h3>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative">
                            <img src="templates/images/red_hexagon.svg" alt="Nombre de contrats expirés" style="width: 80%; max-width: 180px; position: absolute; z-index: -1;">
                            <h1 class="display-4 text-white"><?php echo($counters["expired_total"]); ?></h1>
                        </div>
                    </a>
                </div>
                
            </div>
        </div>

    <?php endif; ?>

    <!-- Ligne 2 : Stats sur le service actuellement séléctionné -->
    <?php if($_SESSION["UserInfo"]["Current_Service"] != "*"): ?>

        <div class="position-relative d-inline-block mt-3">
            <h1 class="m-0 position-relative font-weight-bold main-txt">
                <?php echo($_SESSION["UserInfo"]["Current_Service_Name"]) ?>
                <button class="btn btn-sm main-bg ml-2" style="position: absolute; top: 50%; transform: translateY(-50%);" data-toggle='modal' data-target='#switch_service_modal'>
                    Changer
                </button>
            </h1>
        </div>

        <div class="container-fluid px-3 px-lg-5 mt-2" style="height: auto; min-height: 45%;">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3" style="min-height: 250px;">

                <div class="col mb-4">
                    <a href="index.php" class="h-100 d-flex flex-column text-decoration-none text-dark">
                        <div class="mb-3">
                            <h3 class="text-center fs-5 main-txt">Nombre de contrats actifs</h3>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative">
                            <img src="templates/images/green_hexagon.svg" alt="Nombre de contrats actifs" style="width: 80%; max-width: 180px; position: absolute; z-index: -1;">
                            <h1 class="display-4 text-white"><?php echo($counters["active_in_service"]); ?></h1>
                        </div>
                    </a>
                </div>

                <div class="col mb-4">
                    <a href="index.php" class="h-100 d-flex flex-column text-decoration-none text-dark">
                        <div class="mb-3">
                            <h3 class="text-center fs-5 main-txt">Nombre de contrats à reconduire</h3>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative">
                            <img src="templates/images/orange_hexagon.svg" alt="Nombre de contrats à reconduire" style="width: 80%; max-width: 180px; position: absolute; z-index: -1;">
                            <h1 class="display-4 text-white"><?php echo($counters["inalert_in_service"]); ?></h1>
                        </div>
                    </a>
                </div>

                <div class="col mb-4">
                    <a href="index.php?route=expired_contracts" class="h-100 d-flex flex-column text-decoration-none text-dark">
                        <div class="mb-3">
                            <h3 class="text-center fs-5 main-txt">Nombre de contrats expirés</h3>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative">
                            <img src="templates/images/red_hexagon.svg" alt="Nombre de contrats expirés" style="width: 80%; max-width: 180px; position: absolute; z-index: -1;">
                            <h1 class="display-4 text-white"><?php echo($counters["expired_in_service"]); ?></h1>
                        </div>
                    </a>
                </div>

            </div>
        </div>

    <?php endif; ?>

    <!-- Modal de changement de service -->
    <div class="modal fade" id="switch_service_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title main-txt font-weight-bold">Changer de service</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <form action="index.php?route=change_service<?php if(isset($_GET['route'])){echo("&returnto=" . $_GET['route']);} ?>"  method="POST" enctype="multipart/form-data">

                    <div class="modal-body">

                            <select name="select_service" class="form-control">
                                <?php
                                    foreach($services as $service){
                                        echo("<option value='" . $service['id'] . "'>" . $service["service_name"] . "</option>"); #Affichage des services dans un select
                                    }
                                ?>
                                <?php if($_SESSION["UserInfo"]["Role"] == "Admin"): ?>
                                    <option value="*">Tous les services</option>
                                <?php endif; ?>
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