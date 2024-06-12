<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Meta extends Model
{
    protected $table      = 'metas_salesup';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['meta_oport', 'meta_tvfc', 'meta_eh','meta_uep', 'meta_sva', 'meta_sci', 'meta_spf', 'meta_stm', 'id_sap', 'id_zona'];

    protected $useTimestamps = false;

    protected $createdField  = '';
    protected $updatedField  = '';
    protected $deletedField  = '';

}