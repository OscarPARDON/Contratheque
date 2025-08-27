<?php

#############################################################################################################################################

# Fonction d'échappement des données avant insertion dans l'HTML pour éviter les injections XSS
function escape_array($array) {
    if (is_null($array) || $array === '') {
        return ''; // Retourne une chaîne vide pour les valeurs NULL ou vides
    }

    if (!is_array($array)) {
        return htmlspecialchars($array, ENT_QUOTES, 'UTF-8'); // Echapement des données
    }

    foreach ($array as $key => $value) {
        $array[$key] = escape_array($value); // Appel récursif si c'est un tableau
    }

    return $array;
}



##########################################################################################################################


?>