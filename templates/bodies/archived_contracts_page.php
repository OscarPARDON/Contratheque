<main>

    <div class="mx-5">

        <table id="table" class="table border table-borderless table-striped table-hover" data-toggle="table" data-show-export="true" exportDataType='basic' data-pagination="true" data-search="true" data-sort-name="default" data-sort-order="asc">

            <h2 class="position-absolute font-weight-bold main-txt" style="left:44%;">Contrats Archivés</h2>

            <thead>
                <tr>
                    <th scope="col" data-sortable="true">N°Contrat</th>
                    <th scope="col" data-sortable="true">Nom</th>
                    <th scope="col" data-sortable="true">Début</th>
                    <th scope="col" data-sortable="true" data-field="default">Echéance</th>
                    <th scope="col" data-sortable="true">Mail Fournisseur</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>

              <?php

              # Ajout de tous les contrats du service affecté dans la table avec PHP
              foreach($displayed_contracts as $row): ?>
    
                <tr>

                    <td><a href='index.php?route=contract_detail&intern_num=<?php echo($row["intern_num"]);?>'><?php echo($row["intern_num"]);?></td> <!-- Numéro Interne du contrat -->
                    <td><a href='index.php?route=contract_detail&intern_num=<?php echo($row["intern_num"]);?>'><?php echo($row["contract_name"]); ?></td> <!-- Nom du contrat -->
                    <td><?php echo($row["contract_start"]); ?></td><!-- Date d'échéance du contrat -->
                    <td><?php echo($row["contract_end"]); ?></td> <!-- Date d'échéance du contrat -->
                        <td> <!-- Colonne des informations du fournisseur / prestataire -->
                            <a href='#' 
                                <?php 
                                    echo("onclick=\"fill_contractor_modal('"); #Appel de la fonction JS lorsque le lien est cliqué
                                    echo($row["contractor_name"] . "','"); #Paramètre de la fonction : name
                                    echo($row["contractor_ref"] . "','"); #Paramètre de la fonction : reference
                                    echo($row["contractor_mail"] . "','"); #Paramètre de la fonction : mail
                                    echo($row["contractor_tel"] . "')\""); #Paramètre de la fonction : tel 
                                ?> 
                            data-toggle='modal' data-target='#contractor_info_modal'> <!-- Bootstrap :  definition de l'ID du modal cible : contractor_info_modal -->
                            <?php echo($row["contractor_mail"]); ?> <!-- Affichage dans le tableau du mail du prestataire / fournisseur -->
                            </a> <!-- Fin du lien d'ouverture du modal -->
                        </td> 
                    
                    <td class='d-flex justify-content-end'> <!-- Colonne contenant les pictogrammes de fonctionnalités -->
                        <a class='mr-1' href='#' 
                            <?php
                            $js_content = "fill_note_modal(\"". $row["contract_notes"] . "\",\"" . $row["intern_num"] . "\")";
                            echo("onclick='" . $js_content . "'");
                            ?>
                             data-toggle='modal' data-target='#note_modal'> <!-- Bootstrap : définition du modal cible : note_modal -->
                            <img src='templates/images/note.svg' width='30' alt='Notes à propos du contrat'>  <!-- Pictogramme du lien -->
                        </a> <!-- Fin du lien d'ouverture du modal de note -->
                        <a class='mx-1' href="index.php?route=file_display&contract_num=<?php echo($row["intern_num"]);?>&status=archived" target="_blank"> <img src='templates/images/show.svg' width='30' alt='Voir le contrat'> </a> <!-- Voir pdf -->
                        <a class='mx-1' href="index.php?route=file_download&contract_num=<?php echo($row["intern_num"]);?>&status=archived"> <img src='templates/images/download.svg' width='30' alt='Télécharger le contrat'> </a> <!-- Télécharger pdf -->
                        </td> <!-- Fin de la colonne contenant les pictogrammes de fonctionnalités -->
                </tr> <!-- Fin de la ligne du tableau -->

            <?php endforeach; ?>
              
            </tbody>

        </table>

    </div>

    <!-- Modaux : -->
        
    <!-- Modal d'informations prestataire / fournisseur du contrat -->
    <div class="modal fade" id="contractor_info_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title font-weight-bold main-txt">Informations Fournisseur / Prestataire</h5>

                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>

                </div>

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
        
        //Remplissage auto du modale d'information sur le prestataire ou le fournisseur du contrat
        function fill_contractor_modal(name, reference, mail, tel){
            document.getElementById("contractor_name").innerHTML = name; 
            document.getElementById("contractor_ref").innerHTML = reference; 
            document.getElementById("contractor_mail").innerHTML = mail; 
            document.getElementById("contractor_tel").innerHTML = tel;
        }

    </script>

    <!-- Modal de notes du contrat -->
    <div class="modal fade" id="note_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title font-weight-bold main-txt">Notes à propos du contrat</h5>

                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>

                </div>
                    <div class="modal-body">
                        <textarea id="contract_notes" name="contract_notes" maxlength="300" cols="60" rows="15" style="resize: none; pointer-events:none" ></textarea>
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn main-bg" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    
    //Remplissage auto du modale de note du contrat
    function fill_note_modal(note, contract_id){
        if(note == null || note.trim() == ''){
            document.getElementById("contract_notes").value = "Aucune note à propos de ce contrat";
        }
        else{
            document.getElementById("contract_notes").value = note;
        }
        document.getElementById("note_contract_id").value = contract_id;
    }

    </script>

        
</main>

<!-- ############################################################################################################################################## -->
<!-- CSS -->

<style>

    /* Changement de la couleur par défaut des liens en noir */

    td a {
        color : black;
    }

    /* Masquage de la flèche du dropdown sur l'icône "plus d'options" */

    main a.dropdown-toggle::after {
        display: none;
    }

    /* Réecriture du style de la table bootsrap */

    .page-item.active .page-link {
        background-color: #FF0000 !important;
        border-color : #FF0000 !important;
    }

    .page-link {
        color: #FF0000;
    }

    .fixed-table-toolbar .dropdown-toggle{
        background-color:#FF0000 !important;
        border-color:#FF0000 !important;
    }

    .th-inner{
        color:#000F46;
    }
    .table tr td a{
        color:#000F46;
    }

    .table tr td a:hover{
        color:#FF0000;
    }

    .fixed-table-pagination .dropdown-toggle{
        background-color:#000F46 !important;
    }

</style>

