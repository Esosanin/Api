<?php

namespace App\Controllers;

class Seguros extends BaseController
{
    private $db;

    public function __construct() {
        $this->db = db_connect();
    }
    public function segurosFlotilla(){
        $query = $this->db->query("SELECT
        t2.slpname,
        t1.U_Oficina,
        t1.U_departamento,
        t1.U_Marca,
        t1.Name
        FROM
        syn_flotilla t1 left join 
        syn_oslp t2 on t1.U_Responsable=t2.slpcode
        WHERE 
        T1.U_Estatus=1 order by
        t1.name");

        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }
}