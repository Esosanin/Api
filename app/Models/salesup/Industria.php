<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Industria extends Model
{
    protected $table      = 'industrias';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['industria'];

    protected $useTimestamps = false;
}