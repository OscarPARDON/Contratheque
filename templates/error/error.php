<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratèque</title>
    <link rel="icon" type="image/x-icon" href="templates/images/logo.webp">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="card p-4" style="width: 400px;">

            <img src="templates/error/images/error.svg" class="card-img-top mx-auto d-block" alt="Image de blocage" style="width: 50%;">

            <div class="card-body">
                <h5 class="card-title text-center font-weight-bold" style="color:#FF0000;">ERREUR</h5>
                <p class="text-center"><?php if(isset($_GET["error"])){echo(htmlspecialchars($_GET["error"]));} else{echo("Une erreur est survenue, Veuillez contacter le support");}?></p>
            </div>
            <a href="index.php" class="btn main-bg text-center">Revenir à la page d'accueil</a>
        </div>
    </div>
</body>
<style>

    *{
        font-family:"Amino", sans-serif;
    }

    .main-bg{
        background-color:#FF0000;
        color : white;
    }

    .main-bg:hover{
        background-color:#FF6969;
    }

</style>
</html>