
<!-- Tableau des Logs de la base de données -->

<main class="dark-bg d-flex flex-column vh-100 align-items-center">

        <div class="container w-75">

            <div class="position-absolute"> <!-- Vers les logs de connexion -->
                <a class='btn btn-rounded second-bg' href="index.php?route=login_logs">Journaux de connexion >>></a>
            </div>

            <!-- Tableau des événements -->
            <table id="table" class="table border table-borderless table-striped table-hover" data-toggle="table" data-show-export="true" exportDataType='basic' data-pagination="true" data-sort-name="default" data-sort-order="desc" data-search="true">

                <thead>
                    <tr>
                        <th scope="col" data-sortable="true">Adresse IP</th>
                        <th scope="col" data-sortable="true">ID Utilisateur</th>
                        <th scope="col" data-sortable="true">Table</th>
                        <th scope="col" data-sortable="true">Opération</th>
                        <th scope="col" data-sortable="true" data-field="default">Date - Heure</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($logs as $row): ?>

                        <tr>
                            <td><?php echo($row["remote_address"]); ?></td> <!-- IP de l'utilisateur -->
                            <td><?php echo($row["user_sam"]); ?></td> <!-- Samaccountname de l'utilisateur -->
                            <td><?php echo($row["db_table"]); ?></td> <!-- Table sur laquelle la modification a été effectuée -->
                            <td><?php echo($row["operation"]); ?></td> <!-- Type de modification effectuée -->
                            <td><?php echo($row["datetime"]); ?></td> <!-- Date et Heure de la modification -->
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
