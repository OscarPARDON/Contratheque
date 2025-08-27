<style>

    /* Animation de bascule sur la checkmark */        
    #check{
        animation: tilt-shaking 1s linear infinite;
    }

    @keyframes tilt-shaking {
        0% { transform: rotate(0deg); }
        25% { transform: rotate(5deg); }
        50% { transform: rotate(0deg); }
        75% { transform: rotate(-5deg); }
        100% { transform: rotate(0deg); }
    }

    /* Style de l'alvéole entourant la checkmark */
    #img-bg{
        padding:75px;
        background-image: url('templates/images/empty-red-hexagon.svg');
        background-size: cover, 100px;
        background-position: center, top left;
        background-repeat: no-repeat;
    }

</style>

<!-- Page de confirmation de la soumission d'un ticket -->

<main class="dark-bg d-flex flex-column vh-100 align-items-center">

    <div class="text-center container secondary-bg d-flex flex-column align-items-center justify-content-center p-5 rounded">
        
        <h1 class="font-weight-bold main-txt rounded p-4 mb-3">Votre demande d'assistance a été envoyée avec succès !</h1>
        
        <div id="img-bg">
            <img id="check" src="templates/images/check.svg" style="width: 130px" alt="Checkmark Icon">
        </div>
        
        <div class="d-flex flex-row">
            <h3 class="h2 main-txt">Le service informatique reviendra rapidement vers vous pour vous informer de l'avancée de la demande.</h3>
        </div>
        
        <!-- Vers la page principale -->
        <div class="d-flex flex-column">
            <a href="index.php" class="btn btn-lg main-bg mt-3">D'accord</a>
        </div>
        
    </div>

</main>