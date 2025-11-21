<?php

function CPFValidator($cpf) {
    $cpf = preg_replace('/[^0-9]/', "", $cpf);

    if (strlen($cpf) != 11 || preg_match('/([0-9])\1{10}/', $cpf)) {
        return false;
    }

    $sum = 0;

    for ($i = 0, $weight = 10; $i < 9; $i++, $weight--) {
        $sum += $cpf[$i] * $weight;
    }

    $result = ($sum * 10) % 11;
    $result = ($result == 10) ? 0 : $result;

    if ($cpf[9] != $result) return false;

    $sum = 0;
    for ($i = 0, $weight = 11; $i < 10; $i++, $weight--) {
        $sum += $cpf[$i] * $weight;
    }
    $result = ($sum * 10) % 11;
    $result = ($result == 10) ? 0 : $result;

    if ($cpf[10] != $result) return false;

    return true;
}
