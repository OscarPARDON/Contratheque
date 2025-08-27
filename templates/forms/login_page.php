
<!-- Formulaire de connexion -->

<body>

    <main class="main-txt">

        <div class="container d-flex justify-content-center align-items-center min-vh-100">
            <div class="card p-4" style="width: 400px;">

                <img src="templates/images/logo.webp" class="card-img-top mx-auto d-block" alt="Image de connexion" style="width: 50%;">

                <div class="card-body">

                    <h5 class="card-title text-center font-weight-bold">Veuillez vous connecter</h5>

                    <form action="index.php" method="POST" enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="username">Identifiant</label>
                            <input type="text" class="form-control" name="username" placeholder="Entrez votre identifiant" maxlenght=30 required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" class="form-control" name="password" placeholder="Entrez votre mot de passe" maxlenght=100 required>
                        </div>

                        <?php
                            // Import du fichier de configuration
                            include("/etc/contratheque/conf.php");
                            
                            // Apparition d'un message si l'utilisateur echoue une tentative
                            if(isset($_SESSION["System"]["tries"]) && $_SESSION["System"]["tries"] < $MAX_TRIES){
                                echo("<div class='alert alert-danger d-flex flex-column'><strong>ATTENTION !</strong> Identifiant ou mot de passe incorrect </div>");
                            }
                        ?>

                        <input type="hidden" name="CSRF" value="<?php echo($_SESSION["System"]["CSRF"]); ?>">
                        <button type="submit" class="btn main-bg btn-block">Se connecter</button>

                    </form>

                </div>

            </div>
        </div>

    </main>

</body>


