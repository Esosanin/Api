<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Actividad extends Model
{
    protected $table      = 'actividades';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['id_cita', 'CntctCode', 'objetivo', 'detalles', 'id_cot', 'estatus'];

    protected $useTimestamps = false;
}