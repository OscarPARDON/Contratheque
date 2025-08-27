
<!-- Page de modification de la configuration de l'application -->

<main>
    <div class="container">

        <div class="d-flex flex-row">
            <h2 class="mr-3 font-weight-bold main-txt">Configuration</h2>
            <!-- Retour à la page principale -->
            <a href="index.php"><img src="templates/images/back.svg" alt="Retour à la page principale" style="width:50px"></a>
        </div>

        <i class="font-weight-bold text-danger fs-4">Attention : Ces paramètres sont vitaux pour le fonctionnement de l'application, une mauvaise configuration pourrait compromettre le fonctionnement de celle-ci !</i>
        
        <!-- Message d'information en cas de modification réussie -->
        <?php if(isset($_GET["success"])): ?>
            <p class="alert alert-success mb-5 font-weight-bold">Les changements ont bien été effectués</p>
        <?php endif; ?>
        
        <!-- Liste des paramètres par catégorie -->
        <form action="index.php?route=configuration" method="post" enctype="multipart/form-data">

            <h3>Base de donnée</h3>
            <div class="form-group">
                <label for="hotstname">Nom de l'hôte :</label>
                <input type="text" class="form-control" name="hostname" placeholder="Entrer le nom d'hôte" value="<?php echo($HOSTNAME); ?>" required>
            </div>
            <div class="form-group">
                <label for="dbname">Nom de la base de donnée :</label>
                <input type="text" class="form-control" name="dbname" placeholder="Entrez le nom de la base de donnée" value="<?php echo($DB_NAME); ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" class="form-control" name="username" placeholder="Entrez le nom d'utilisateur" value="<?php echo($USERNAME); ?>" required>
            </div>
            <div class="form-group">
                <label for="dbpassword">Mot de passe :</label>
                <input type="password" class="form-control" name="dbpassword" placeholder="Entrez le mot de passe" value="<?php echo($DB_PASSWORD); ?>" required>
            </div>

            <h3>LDAP</h3>
            <div class="form-group">
                <label for="ldapsrvaddress">Adresse du server LDAP :</label>
                <input type="text" class="form-control" name="ldapsrvaddress" placeholder="Entrez l'adresse du server LDAP" value="<?php echo($LDAPSRV_ADDRESS); ?>" required>
            </div>
            <div class="form-group">
                <label for="domain">Domaine :</label>
                <input type="text" class="form-control" name="domain" placeholder="Entrez le nom du domaine" value="<?php echo($DOMAIN); ?>" required>
            </div>
            <div class="form-group">
                <label for="searchbase">Base de la recherche utilisateur :</label>
                <input type="text" class="form-control" name="searchbase" placeholder="Entrez la base de la recherche" value="<?php echo($SEARCH_BASE); ?>" required>
            </div>

            <h3>PDF</h3>
            <div class="form-group">
                <label for="pdfroot">Racine du répértoire de stockage des PDF :</label>
                <input type="text" class="form-control" name="pdfroot" placeholder="Entrez le chemin vers le répértoire de stockage des pdf" value="<?php echo($PDF_ROOT); ?>" required>
            </div>

            <h3>Securité</h3>
            <div class="form-group">
                <label for="maxtries">Nombre d'essais avant blocage :</label>
                <input type="number" class="form-control" name="maxtries" placeholder="Entrez le nombre d'essais d'authentification maximum" value="<?php echo($MAX_TRIES); ?>" required>
            </div>
            <div class="form-group">
                <label for="expiration">Durée d'inactivité avant déconnexion (en secondes) :</label>
                <input type="number" class="form-control" name="expiration" placeholder="Entrez la durée en seconde avant l'expiration de la session" value="<?php echo($EXPIRATION); ?>" required>
            </div>

            <h3>Conservation des contrats</h3>
            <div class="form-group">
                <label for="keepingduration">Durée de conservation après suppression (A-m-j) :</label>
                <input type="text" class="form-control" name="keepingduration" placeholder="Entrez la durée de conservation (A-m-j)" value="<?php echo($KEEPING_DURATION); ?>" required>
            </div>

            <h3>Mailing</h3>
            <div class="form-group">
                <label for="smtphost">Adresse du serveur SMTP :</label>
                <input type="text" class="form-control" name="smtphost" placeholder="Entrez l'adresse du serveur SMTP" value="<?php echo($SMTP_HOST); ?>" required>
            </div>
            <div class="form-group">
                <label for="smtpport">Port SMTP :</label>
                <input type="text" class="form-control" name="smtpport" placeholder="Entrez le port utilisé par le server SMTP" value="<?php echo($SMTP_PORT); ?>" required>
            </div>
            <div class="form-group">
                <label for="mailingaddr">Adresse de Mailing :</label>
                <input type="text" class="form-control" name="mailingaddr" placeholder="Entrez l'adresse utilisé par le service de mailing" value="<?php echo($SMTP_ADDR); ?>" required>
            </div>
            <div class="form-group">
                <label for="glpiaddr">Adresse mail de GLPI :</label>
                <input type="text" class="form-control" name="glpiaddr" placeholder="Entrez l'adresse mail de GLPI" value="<?php echo($GLPI_ADDR); ?>" required>
            </div>
            <div class="form-group">
                <label for="email_limit">Ticket maximum / Jour par utilisateur :</label>
                <input type="number" class="form-control" name="email_limit" placeholder="Entrez le nombre de ticket maximum autorisé par utilisateur tous les jours" value="<?php echo($EMAIL_LIMITATION); ?>" required>
            </div>


            <div class="d-flex justify-content-center mt-5 pb-5">
                <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                <button type="button" data-toggle='modal' data-target='#confirm_update_modal' class="btn btn-lg main-bg">Enregistrer</button>
            </div>

	    <!-- Modal de confirmation avant d'enregistrer les modifications -->
            	<div class="modal fade" id="confirm_update_modal" tabindex="-1">
                	<div class="modal-dialog">
                        	<div class="modal-content">

                			<div class="modal-body">
                    				<h5 class="modal-title font-weight-bold main-txt">Êtes-vous sûr de vouloir procéder aux modifications ?</h5>
                			</div>

                			<div class="modal-footer"> 
                    				<button type="submit" name="edit_configuration" class="btn main-bg">Continuer</button>
                    				<button type="button" class="btn second-bg" data-dismiss="modal">Annuler</button>
                			</div>

            			</div>
        		</div>
    		</div>

        </form>
    </div>
