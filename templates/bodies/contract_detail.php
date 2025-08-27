<main class="container d-flex flex-column" style="padding-bottom:5rem">

    <h1 class="mb-4 text-center font-weight-bold main-txt">
        <?php echo($_GET["intern_num"]); ?>
        <a href="#" onclick="history.back(); return false;">
            <img src="templates/images/back.svg" alt="Retourner au tableau des contrats" style="width:60px">
        </a>
    </h1>

    <!-- Informations Générales -->
    <div class="mb-2 p-2 border rounded">
        <h3 class="mb-3 main-txt">Informations Générales</h3>

        <ul><strong>Nom : </strong><?php echo($contract["contract_name"]); ?></ul>
        <ul><strong>Date de Début : </strong><?php echo($contract["contract_start"]); ?></ul>
        <ul><strong>Date de Fin : </strong><?php echo($contract["contract_end"]); ?></ul>

        <ul>
            <strong>Durée : </strong>
            <?php
                $start    = new DateTime($contract["contract_start"]);
                $end      = new DateTime($contract["contract_end"]);
                $interval = $end->diff($start);
                $months   = $interval->y * 12 + $interval->m;
                if ($months < 1) {
                    $months = "<1";
                }
                echo($months);
            ?> Mois
        </ul>

        <?php if ($contract["contract_end"] > date("Y-m-d")): ?>
            <ul>
                <strong>Durée Restante : </strong>
                <?php
                    $today    = new DateTime();
                    $end      = new DateTime($contract["contract_end"]);
                    $interval = $end->diff($today);
                    $months   = $interval->y * 12 + $interval->m;
                    if ($months < 1) {
                        $months = "<1";
                    }
                    echo($months);
                ?> Mois
            </ul>
        <?php endif; ?>

        <ul><strong>Date de Relance : </strong><?php echo($contract["contract_followup_date"]); ?></ul>

        <?php if ($contract["previous_contract_num"] != NULL): ?>
            <ul>
                <strong>N° du Contrat Précédent : </strong>
                <a href="index.php?route=contract_detail&intern_num=<?php echo($contract["previous_contract_num"]); ?>">
                    <?php echo($contract["previous_contract_num"]); ?> (Cliquez pour voir les détails)
                </a>
            </ul>
        <?php endif; ?>

        <?php if ($status == "deleted"): ?>
            <ul><strong>Date de Suppression : </strong><?php echo($contract["deletion_date"]); ?></ul>
            <ul><strong>Raison de la Suppression : </strong><?php echo($contract["reason"]); ?></ul>
        <?php endif; ?>
    </div>

    <!-- Responsable & Service -->
    <div class="mb-2 p-2 border rounded">
        <h3 class="mb-3">Informations Responsable & Service</h3>
        <?php if ($contract["mail"]): ?>
            <ul><strong>Nom : </strong><?php echo($contract["firstname"] . " " . $contract["lastname"]); ?></ul>
            <ul><strong>Mail : </strong><?php echo($contract["mail"]); ?></ul>
            <?php if ($contract["tel_num"] != NULL): ?>
                <ul><strong>Numéro de téléphone : </strong><?php echo($contract["tel_num"]); ?></ul>
            <?php endif; ?>
        <?php else: ?>
            <ul>L'utilisateur ayant créé ce contrat n'existe plus ...</ul>
        <?php endif; ?>
        <ul><strong>Service : </strong><?php echo($contract["service_name"]); ?></ul>
    </div>

    <!-- Contractant -->
    <div class="p-2 border rounded">
        <h3 class="mb-3">Informations Contractant</h3>
        <?php if ($contract["contractor_mail"]): ?>
            <ul><strong>Nom : </strong><?php echo($contract["contractor_name"]); ?></ul>
            <ul><strong>Reférence : </strong><?php echo($contract["contractor_ref"]); ?></ul>
            <ul><strong>Email : </strong><?php echo($contract["contractor_mail"]); ?></ul>
            <ul><strong>Numéro de téléphone : </strong><?php echo($contract["contractor_tel"]); ?></ul>
        <?php else: ?>
            <h5 class="p-2">Contractant supprimé</h5>
        <?php endif; ?>
    </div>

    <!-- Notes -->
    <div class="mt-3 p-2 border rounded d-flex flex-column">
        <h3 class="mb-2">Notes</h3>

        <?php if ($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Role"] == "Manager"): ?>
            <form action="index.php?route=update_notes&returnto=contract_detail" class="container" method="POST" enctype="multipart/form-data">
                <textarea class="m-2 w-100" id="notes_area" name="contract_notes" cols="30" rows="4" style="resize:none; pointer-events:none;"><?php 
                    if ($contract["contract_notes"] == NULL || str_replace(' ','',$contract["contract_notes"]) == '') {
                        echo("Aucune note à propos de ce contrat");
                    } else {
                        echo($contract["contract_notes"]);
                    } ?>
                </textarea>
                <input type="hidden" name="intern_num" id="note_contract_id" value="<?php echo($contract["intern_num"]); ?>">
                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn second-bg mr-3" style="display:none;" id="cancel" onclick="cancel_edit();">Annuler</button>
                    <button type="button" class="btn main-bg" id="edit" onclick="enable_edit()">Modifier</button>
                    <button type="submit" name="submit_notes" class="btn main-bg rounded ml-3" style="display:none;" id="save">Enregistrer</button>
                </div>
            </form>
            <input type="hidden" id="notebackup" value="<?php echo($contract["contract_notes"]); ?>">
        <?php else: ?>
            <textarea class="m-2 w-100" id="notes_area" name="contract_notes" cols="30" rows="4" style="resize:none; pointer-events:none;"><?php echo($contract["contract_notes"]); ?></textarea>
        <?php endif; ?>
    </div>
    
    <!-- PDF Actions -->
    <div class="container d-flex flex-row justify-content-around mt-4">
        <div class="d-flex flex-column align-items-center justify-content-center py-3 px-4 border rounded" style="border-color:#000F46 !important;">
            <p class="font-weight-bold">Voir le PDF</p>
            <a href="index.php?route=file_display&contract_num=<?php echo($contract["intern_num"]); ?>&status=<?php echo($status); ?>" target="_blank">
                <img src="templates/images/show.svg" alt="Voir le PDF du contrat" style="width:50px;">
            </a>
        </div>
        <div class="d-flex flex-column align-items-center justify-content-center py-3 px-2 border rounded" style="border-color:#000F46 !important;">
            <p class="font-weight-bold">Télécharger le PDF</p>
            <a href="index.php?route=file_download&contract_num=<?php echo($contract["intern_num"]); ?>&status=<?php echo($status); ?>">
                <img src="templates/images/download.svg" alt="Télécharger le PDF du contrat" style="width:50px;">
            </a>
        </div>
    </div>

    <!-- Actions Admin/Manager -->
    <?php if ($_SESSION["UserInfo"]["Role"] == "Admin" || $_SESSION["UserInfo"]["Role"] == "Manager"): ?>
        <div class="my-4 p-3 d-flex flex-column border rounded">
            <div><h3 class="text-center">Actions sur ce contrat</h3></div>

            <?php if ($status == "active" || $status == "expired"): ?>
                <div class="d-flex justify-content-around">
                    <a class="btn second-bg" href="index.php?route=new_contract&intern_num=<?php echo($contract["intern_num"]); ?>">Reconduire</a>
                    <a class="btn second-bg" href="index.php?route=update_contract&intern_num=<?php echo($contract["intern_num"]); ?>">Modifier</a>
                </div>
                <div class="mt-4 pb-3 d-flex justify-content-center">
                    <button class="btn main-bg" data-toggle='modal' data-target='#confirm_deletion_modal'>Supprimer ce Contrat</button>
                </div>
            <?php endif; ?>

            <?php if ($status == "deleted"): ?>
                <div class="mt-4 pb-3 d-flex justify-content-center">
                    <button class="btn second-bg" data-toggle='modal' data-target='#confirm_recover_modal'>Récupérer ce Contrat</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal Suppression -->
        <?php if ($status == "active" || $status == "expired"): ?>
            <div class="modal fade" id="confirm_deletion_modal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h5 class="modal-title font-weight-bold">Êtes-vous sûr de vouloir supprimer ce contrat ?</h5>
                        </div>
                        <div class="modal-body">
                            <form action="index.php?route=delete_contract" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="del_contract_num" value="<?php echo($contract['intern_num']); ?>">
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
                            <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                            <button type="submit" name="submit_deletion" class="btn main-bg">Supprimer</button>
                            <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                        </div>
                            </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Modal Récupération -->
        <?php if ($status == "deleted"): ?>
            <div class="modal fade" id="confirm_recover_modal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h5 class="modal-title font-weight-bold">Êtes-vous sûr de vouloir restaurer ce contrat ?</h5>
                        </div>
                        <div class="modal-footer">
                            <form action="index.php?route=recover_contract" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="recover_contract_num" value="<?php echo($contract["intern_num"]); ?>">
                                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                                <button type="submit" name="submit_recover" class="btn main-bg">Restaurer</button>
                            </form>
                            <button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- JS Notes -->
    <script>
        function enable_edit() {
            document.getElementById("save").style.display   = 'block';
            document.getElementById("cancel").style.display = 'block';
            document.getElementById("edit").style.display   = 'none';
            document.getElementById("notes_area").style.pointerEvents = 'auto';
        }

        function cancel_edit() {
            let original_notes = document.getElementById("notebackup").value;
            document.getElementById("save").style.display   = 'none';
            document.getElementById("cancel").style.display = 'none';
            document.getElementById("edit").style.display   = 'block';

            if (original_notes.replaceAll(" ","") == "") {
                document.getElementById("notes_area").value = "Aucune note à propos de ce contrat";
            } else {
                document.getElementById("notes_area").value = original_notes;
            }
            document.getElementById("notes_area").style.pointerEvents = 'none';
        }
    </script>

</main>

<style>
    main {
        color: #000F46;
    }
</style>
