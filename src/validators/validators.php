<?php

# Fonction de validation de la longueur d'une chaine de caractère
function validateLength($input, $maxLength) {
    return ($input != "" && strlen($input) <= $maxLength);
}

# Fonction de validation du format d'une date en chaine de caractère
function validateDateFormat($date) {
    return preg_match("/^\d{4}-\d{2}-\d{2}$/", $date);
}

# Fonction de validation du format de numéro de contrat d'une chaine de caractère
function validateContractNumberFormat($contractNum) {
    return preg_match("/^\d{4}-[A-Z]{2,3}-\d{3}$/", $contractNum);
}

# Fonction de validation du format email d'une chaine de ccaractère
function validateEmailFormat($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

# Fonction de validation du format numéro de télephone d'une chaine de caractère
function validatePhoneNumberFormat($phone){
    return preg_match("/^0\d{9}$/", $phone);
}

?>