
<!-- Tableau des logs de connexion -->

<main class="dark-bg d-flex vh-100 flex-column align-items-center" >

        <div class="container w-75">

            <div class="position-absolute"> <!-- Vers les logs de la Base de données -->
                <a class='btn btn-rounded second-bg' href="index.php?route=database_logs">Journaux de base de données >>></a>
            </div>

            <table id="table" class="table border table-borderless table-striped table-hover" data-toggle="table" data-show-export="true" exportDataType='basic' data-pagination="true" data-sort-name="default" data-sort-order="desc" data-search="true">

                <thead>
                    <tr>
                        <th scope="col" data-sortable="true">Adresse IP</th>
                        <th scope="col" data-sortable="true">ID Utilisateur</th>
                        <th scope="col" data-sortable="true">Tentative de connexion</th>
                        <th scope="col" data-sortable="true" data-field="default">Date - Heure</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($logs as $row): ?>

                        <tr>
                            <td><?php echo($row["remote_address"]); ?></td> <!-- IP d'origine de la tentative -->
                            <td><?php echo($row["user_sam"]); ?></td> <!-- Samaccountname ciblé par la tentative -->
                            <td><?php echo($row["result"]); ?></td> <!-- Résultat de la tentative -->
                            <td><?php echo($row["datetime"]); ?></td> <!-- Date et Heure de la tentative -->
                        </tr>

                    <?php endforeach; ?>
                </tbody>

            </table>

        </div>


</main>

<style>

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

    .fixed-table-pagination .dropdown-toggle{
        background-color:#000F46 !important;
    }

</style>
