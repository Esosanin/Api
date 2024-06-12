<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Acompanamiento extends Model
{
    protected $table      = 'acompanamientos';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['id_sap', 'id_cita'];

    protected $useTimestamps = false;
}