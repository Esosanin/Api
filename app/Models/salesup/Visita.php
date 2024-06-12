<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Visita extends Model
{
    protected $table      = 'citas';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = true;

    protected $allowedFields = ['fecha_comp', 'objetivo_gen', 'hora_inicio', 'hora_fin','hora_checkin','hora_checkout', 'geo_checkin', 'geo_checkout', 'estatus', 'id_sap', 'CardCode', 'LineNum'];

    protected $useTimestamps = true;
    protected $createdField  = 'fecha_create';
    protected $updatedField  = 'fecha_update';
    protected $deletedField  = 'deleted';
}