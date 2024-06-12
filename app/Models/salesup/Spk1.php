<?php

namespace App\Models\salesup;

use CodeIgniter\Model;

class Spk1 extends Model
{
    protected $table      = 'spk1';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = ['spk1'];

    protected $useTimestamps = false;
}