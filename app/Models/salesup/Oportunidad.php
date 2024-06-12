<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Oportunidad extends Model
{
    protected $table      = 'oportunidades';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['descripcion', 'CardCode', 'monto_estim','id_industria', 'id_spk1', 'id_sap','LineNum'];

    protected $useTimestamps = true;

    protected $createdField  = 'fecha';
    protected $updatedField  = '';
    protected $deletedField  = '';

}