<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Plan extends Model
{
    protected $table      = 'planes';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['id_sap', 'proy_men', 'proy_sem','confirmacion'];

    protected $useTimestamps = true;
    protected $createdField  = 'fecha_create';
    protected $updatedField  = '';
    protected $deletedField  = '';
}