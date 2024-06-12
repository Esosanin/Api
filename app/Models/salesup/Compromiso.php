<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Compromiso extends Model
{
    protected $table      = 'compromisos';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['detalles', 'fecha', 'hora', 'estatus', 'CntctCode', 'id_sap','id_actividad','CardCode','LineNum'];

    protected $useTimestamps = false;
}