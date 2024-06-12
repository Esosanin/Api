<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Levantamiento extends Model
{
    protected $table      = 'levantamientos';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['nombre_apli', 'fecha_entrega', 'comentarios', 'estatus','CntctCode', 'id_sap', 'id_actividad','CardCode','LineNum'];

    protected $useTimestamps = false;
}