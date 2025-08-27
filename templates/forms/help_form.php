
<!-- Formulaire de création d'un ticket -->

<main class="container vh-100 d-flex flex-column">

    <h1 class="font-weight-bold main-txt">Demande d'assistance</h1>

    <form action="index.php?route=help" method="POST" enctype="multipart/form-data" class="d-flex flex-column mt-5">

        <div class="form-group">
            <label for="description" class="font-weight-bold main-txt">Titre de votre demande :</label>
            <input name="title" type="text"class="form-control" placeholder="En quoi consiste votre demande ?" maxlength="200">
        </div>
            
        <div class="form-group">
            <label for="description" class="font-weight-bold main-txt">Decrivez votre problème :</label>
            <textarea name="description" class="form-control" id="description" cols="30" rows="10" style="resize:none;" maxlength="500" placeholder="Decrivez le problème rencontré ..."></textarea>
        </div>
        
        <div class="d-flex justify-content-center mt-3">
            <button class="btn w-25 btn-lg main-bg" type="submit" name="submit_help_request">Envoyer</button>
        </div>
            
    </form>

</main>