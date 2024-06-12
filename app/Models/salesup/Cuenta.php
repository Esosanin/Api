<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Cuenta extends Model
{
    protected $table      = 'cuentas';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'cuenta',
        'raz_social',
        'tipo',
        'rfc',
        'id_industria',
        'produce',
        'direccion',
        'telefono',
        'estado',
        'municipio',
        'id_zona',
        'asociada',
        'id_corp',
        'LineNumCorp',
        'id_sap',
        'potencial',
        'pref_equipo',
        'pref_proyecto',
        'pref_servicio',
        'tam',
        'market_share',
        'tipo_cuenta'
    ];

    protected $useTimestamps = true;

    protected $createdField  = 'fec_registro';
    protected $updatedField  = '';
    protected $deletedField  = '';

}