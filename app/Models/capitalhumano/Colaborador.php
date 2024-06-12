<?php

namespace App\Models\capitalhumano;

use CodeIgniter\Model;
use Exception;


class Colaborador extends Model
{
    protected $table      = 'Colaboradores';
    protected $primaryKey = 'id_colaborador';

    protected $returnType = 'object';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'nombres',
        'apellido_p',
        'apellido_m',
        'fecha_nacimiento',
        'lugarNacimiento',
        'sexo',
        'id_nacionalidad',
        'tipoSangre',
        'donante',
        'alergias',
        'enfermedades',
        'limitaciones',
        'camisa',
        'txt_mailPersonal',
        'nivel_estudio',
        'id_carrera',
        'certificado',
        'ingles',
        'CURP',
        'RFC',
        'NIMSS',
        'foto',
        'edo_civil',
        'celular',
        'calleNumero',
        'colonia',
        'codigoPostal',
        'dom_estado',
        'municipio',
        'pais',
        'emergencia_nombre',
        'emergencia_parent',
        'emergencia_dom',
        'emergencia_num',
        'visa_num',
        'pasaporte',
        'poliza',
        'registro_patronal',
        'n_colaborador',
        'nombreUsuario',
        'email',
        'tipo_empleado',
        'unidad',
        'fecha_ingreso',
        'jefe_inmediato',
        'id_aprobador',
        'colaborador_politicas',
        'id_region',
        'id_sucursal',
        'id_area',
        'id_departamentos',
        'id_geo',
        'id_puesto',
        'especialidad',
        'desempeno',
        'tarjeta',
        'clabe',
        'tabulador_id',
        'tab_nivel',
        'modif_salarial',
        'porcentaje',
        'pass',
        'infonavit',
        'fonacot',
        'nr_reporte',
        'monto_sgmm_mes',
        'banco',
        'geografica_sap',
        'ingreso_bruto_men'
    ];

    protected $useTimestamps = false;

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data): array
    {
        return $this->getUpdatedDataWithHashedPassword($data);
    }

    protected function beforeUpdate(array $data): array
    {
        return $this->getUpdatedDataWithHashedPassword($data);
    }

    private function getUpdatedDataWithHashedPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $plaintextPassword = $data['data']['password'];
            $data['data']['password'] = $this->hashPassword($plaintextPassword);
        }
        return $data;
    }

    private function hashPassword(string $plaintextPassword): string
    {
        return password_hash($plaintextPassword, PASSWORD_BCRYPT);
    }
                                      
    public function findUserByEmailAddress(string $emailAddress)
    {
        $user = $this
            ->asArray()
            ->select("DV.id_sap,DV.tipo,Z.zona,DV.id_zona,DV.especialidad,email,pass,foto,id_colaborador,n_colaborador,nombres,CONCAT(nombres,' ',apellido_p,' ',apellido_m) AS nombreCompleto,id_area,id_vendedorSap")
            ->join('det_vendedores DV','id_vendedorSap=DV.id_sap','left')
            ->join('zonas Z','DV.id_zona=Z.id','left')
            ->where('email' , $emailAddress, true)
            ->where('estado' , 1, true)
            ->first();

        if (!$user) 
            throw new Exception('User does not exist for specified email address');

        return $user;
    }

    public function getPermisos() : array
    {
        $permisos = $this->db->query("SELECT id_sap FROM det_vendedores WHERE id_sap!=0")->getResult('array');

        return $permisos;
    }
}
