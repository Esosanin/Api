<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Contacto extends Model
{
    protected $table      = 'contactos';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['contacto', 'posicion', 'clave', 'relacion', 'telefono', 'email','CardCode','LineNum','id_2'];

    protected $useTimestamps = false;
}