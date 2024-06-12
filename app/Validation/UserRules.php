<?php

namespace App\Validation;

use App\Models\capitalhumano\Colaborador;
use Exception;

class UserRules
{
    public function validateUser(string $str, string $fields, array $data): bool
    {
        try {
            $model = new Colaborador();
            $user = $model->findUserByEmailAddress($data['email']);
            return password_verify(htmlentities($data['password']), $user['pass']);
        } catch (Exception $e) {
            return false;
        }
    }
}
