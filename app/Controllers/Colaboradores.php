<?php

namespace App\Controllers;

use App\Models\capitalhumano\Colaborador;
use DateTime;
use Exception;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google_Client;
//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Colaboradores extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function generatePDF($id_colaborador)
    {
        $pdf = new Mpdf([
            'debug' => true,
            'mode' => 'utf-8'
        ]);
        $query = $this->db->query(
            "SELECT C.foto,CONCAT(nombres,' ',apellido_p,' ',apellido_m) nombre,C.NIMSS,C.fecha_nacimiento,
            DATEDIFF(YEAR,C.fecha_nacimiento,GETDATE()) edadanios,C.CURP,C.RFC,
            CASE C.edo_civil
            WHEN 0 THEN 'Soltero'
            WHEN 1 THEN 'Casado'
            WHEN 2 THEN 'Divorciado'
            WHEN 3 THEN 'Unión libre'
            ELSE 'No especifíca'
            END estado_civil, S.desc_sangre tipo_sangre,IIF(C.donante=1,'Si','No') donador,C.email emailecn,
            C.calleNumero,C.emergencia_nombre,C.colonia,C.emergencia_parent,C.codigoPostal,C.emergencia_dom,
            C.municipio,C.emergencia_num,C.dom_estado,C.pais,A.area,D.departamentos_desc departamento,
            R.nombre_region region,P.puesto,C.fecha_ingreso,C.txt_mailPersonal emailpersonal,
            IIF(C.tipo_empleado=1,'Semanal','Quincenal') tipo_pago,C.tarjeta,C.clabe,C.ingreso_bruto_men,
            C.ingreso_bruto_men * 0.1055 bono_puntualidad,TE.tipoEmpleado_desc tipo_empleado,C.PrjCode,
            C.tipo_empleado id_tipo_emp, C.n_colaborador
            FROM Colaboradores C
            LEFT JOIN Sangre S
            ON C.tipoSangre = S.id_sangre
            LEFT JOIN Areas A
            ON C.id_area = A.id_area
            LEFT JOIN tbl_departamentos D
            ON C.id_departamentos = D.departamentos_id
            LEFT JOIN Regiones R
            ON C.id_region = R.id_region
            LEFT JOIN Puestos P
            ON C.id_puesto = P.id_puesto
            LEFT JOIN tbl_colab_tabulador CT
            ON C.tabulador_id = CT.id
            LEFT JOIN tbl_tipoEmpleado TE
            ON C.tipo_empleado = TE.tipoEmpleado_id
            WHERE C.estado = 1 AND C.id_colaborador = $id_colaborador"
        );
        $result = $query->getResult()[0];
        $foto = $result->foto;
        $tipo_empleado = $result->id_tipo_emp;
        $proyecto = $result->PrjCode;
        $nombre = $result->nombre;
        $NIMSS = $result->NIMSS;
        $fnac = new DateTime($result->fecha_nacimiento);
        $edadanios = $result->edadanios;
        $CURP = $result->CURP;
        $RFC = $result->RFC;
        $edoCivil = $result->edo_civil;
        $sangre = $result->tipo_sangre;
        $dona = $result->donador;
        $email3 = $result->emailpersonal;
        $calleNumero = $result->calleNumero;
        $emergencia_nombre = $result->emergencia_nombre;
        $emergencia_parent = $result->emergencia_parent;
        $colonia = $result->colonia;
        $codigoPostal = $result->codigoPostal;
        $emergencia_dom = $result->emergencia_dom;
        $municipio = $result->municipio;
        $emergencia_num = $result->emergencia_num;
        $dom_estado = $result->dom_estado;
        $pais = $result->pais;
        $area4 = $result->area;
        $dpto = $result->departamento;
        $reg3 = $result->region;
        $puesto4 = $result->puesto;
        $fing = new DateTime($result->fecha_ingreso);
        $email = $result->emailecn;
        $tipo_pago = $result->tipo_pago;
        $tarjeta = $result->tarjeta;
        $clabe = $result->clabe;
        $ingreso_bruto_men = $result->ingreso_bruto_men;
        $bono_puntualidad = $result->bono_puntualidad;
        $templeado3 = $result->tipo_empleado;
        $n_colaborador = $result->n_colaborador;
        if (file_exists('./images/foto_colaborador/' . $foto)) {
            $foto2 = '<div style="position:absolute; left:600px; top:190px;"><img style="width:120px; heigth:200px;" src="./images/foto_colaborador/' . $foto . '"></div>';
        } else {
            $foto2 = '';
        }
        $proyecto2 = $tipo_empleado == '1' ? '<tr><td style="font-weight:bold;">Proyecto:</td><td colspan="3">' . $proyecto . '</td></tr>' : '';
        $html = '
        <style>
        body { 
            font-family: roboto; 
            font-size: 10pt; 
        }
        </style>
        ' . $foto2 . '
    <img style="width: 150px;" src="./images/logos/ECN_logo.png" alt="de">
    <table style="width:93%; position:relative;">
    <tr>
    <td colspan="4" style="text-align:center; font-weight:bold; padding-left:12px;">EXPEDIENTE INDIVIDUAL DEL COLABORADOR</td>
    </tr>
    <tr>
    <td colspan="4" style="height:12px;"></td>
    </tr>
    <tr>
    <td colspan="4" style="font-weight:bold;">DATOS PERSONALES</td>
    </tr>
    <tr>
    <td colspan="4" style="height:12px"></td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Nombre completo:</td>
    <td colspan="3" style="padding-right:200px;">' . $nombre . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">NSS:</td><td colspan="3">' . $NIMSS . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">Fecha de nacimiento:</td><td colspan="3">' . date_format($fnac, 'd/m/Y') . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">Edad:</td>
    <td colspan="3">' . $edadanios . ' años</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">CURP:</td><td colspan="3">' . $CURP . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">RFC:</td><td colspan="3">' . $RFC . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">Estado civil:</td><td colspan="3">' . $edoCivil . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">Tipo de sangre:</td>
    <td>' . $sangre . '</td>
    <td style="font-weight:bold;">Donador:</td>
    <td style="">' . $dona . '</td>
    </tr>
    <tr>
    <td colspan="4" style="font-weight:bold; text-align:left;">Teléfono:</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">E-Mail:</td>
    <td colspan="3">' . $email3 . '</td>
    </tr>
    <tr>
    <td colspan="4" style="height:24px"></td>
    </tr>
    <tr>
    <td colspan="2" style="font-weight:bold;">DOMICILIO</td>
    <td colspan="2" style="font-weight:bold;">CONTACTO DE EMERGENCIA</td>
    </tr>
    <tr>
    <td colspan="4" style="height:12px"></td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Calle y número:</td>
    <td>' . $calleNumero . '</td>
    <td style="font-weight:bold;">Nombre:</td>
    <td>' . $emergencia_nombre . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Colonia:</td>
    <td>' . $colonia . '</td>
    <td style="font-weight:bold;">Parentesco:</td>
    <td>' . $emergencia_parent . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">Código postal:</td>
    <td>' . $codigoPostal . '</td>
    <td style="font-weight:bold;">Domicilio:</td>
    <td>' . $emergencia_dom . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">Municipio:</td>
    <td>' . $municipio . '</td>
    <td style="font-weight:bold;">Télefono:</td>
    <td>' . $emergencia_num . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">Estado:</td>
    <td colspan="3">' . $dom_estado . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold; text-align:left;">País:</td>
    <td colspan="3">' . $pais . '</td>
    </tr>
    <tr>
    <td colspan="4" style="height:24px"></td>
    </tr>
    <tr>
    <td colspan="4" style="font-weight:bold;">DATOS DE INGRESO</td>
    </tr>
    <tr>
    <td colspan="4" style="height:12px"></td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Área:</td><td colspan="3">' . $area4 . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Departamento:</td><td colspan="3">' . $dpto . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Región:</td><td colspan="3">' . $reg3 . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Posición de apoyo:</td><td colspan="3">' . $puesto4 . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Fecha de ingreso:</td><td colspan="3">' . date_format($fing, 'd/m/Y') . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">E-Mail ECN:</td><td colspan="3">' . $email . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Tipo de pago:</td><td colspan="3">' . $tipo_pago . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">No. Tarjeta:</td>
    <td colspan="3">' . $tarjeta . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">No. CLABE:</td>
    <td colspan="3">' . $clabe . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Sueldo bruto:</td><td colspan="3">$' . number_format($ingreso_bruto_men, 2) . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Bono de puntualidad:</td><td colspan="3">$' . number_format($bono_puntualidad, 2) . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">No. Colaborador:</td><td colspan="3">' . $n_colaborador . '</td>
    </tr>
    <tr>
    <td style="font-weight:bold;">Tipo de empleado:</td><td colspan="3">' . $templeado3 . '</td>
    </tr>
    ' . $proyecto2 . '
    </table>
    ';
        $pdf->WriteHTML($html);

        return $this->response->setStatusCode(200)->setContentType('application/pdf')->sendBody($pdf->Output());
    }


    // public function generateExcel()
    // {
    //     $json = $this->request->getJSON();

    //     $query = $this->db->query(
    //         "SELECT ch.entrada,ch.salida,C.NIMSS,CONCAT(C.apellido_p,' ',C.apellido_m,' ',C.nombres) colaborador,
    //         ch.ubicacion,ch.tipo,ch.proyecto,ch.tipo_acceso,ch.destino
    //         FROM checadas ch
    //         JOIN Colaboradores C
    //         ON ch.id_colaborador=C.id_colaborador
    //         WHERE FORMAT(ch.entrada,'yyyy-MM-dd') BETWEEN ? AND ?",
    //         [
    //             $json->from,
    //             $json->to
    //         ]
    //     );
    //     $result = $query->getResult('array');

    //     //calcular distancia en metros
    //     function calculateDistance(
    //         $latitudeFrom,
    //         $longitudeFrom,
    //         $latitudeTo,
    //         $longitudeTo,
    //         $earthRadius = 6371000
    //     ) {
    //         // convert from degrees to radians
    //         $latFrom = deg2rad($latitudeFrom);
    //         $lonFrom = deg2rad($longitudeFrom);
    //         $latTo = deg2rad($latitudeTo);
    //         $lonTo = deg2rad($longitudeTo);

    //         $latDelta = $latTo - $latFrom;
    //         $lonDelta = $lonTo - $lonFrom;

    //         $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
    //             cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    //         return $angle * $earthRadius;
    //     }

    //     if (count($result) > 0) {


    // for ($x = 0; $x < count($data); $x++) {
    //     for ($i = 0; $i < $inner_long; $i++) {
    //         if ($data[$x][$i] === $id) {
    //             $link = $data[$x][54];
    //             $info = array("Vendedor" => $data[$x][7], "XML URL" => $data[$x][54]);
    //         }
    //     }
    // }
    // $fileName = 'asistencia.xlsx';
    // $spreadsheet = new Spreadsheet();
    // $sheet = $spreadsheet->getActiveSheet();
    // $sheet->setCellValue('A1', 'Entrada');
    // $sheet->setCellValue('B1', 'Salida');
    // $sheet->setCellValue('C1', 'Checada');
    // $sheet->setCellValue('D1', 'NSS');
    // $sheet->setCellValue('E1', 'Colaborador');
    // $sheet->setCellValue('F1', 'Rango ubicación');
    // $sheet->setCellValue('G1', 'Ubicación');
    // $sheet->setCellValue('H1', 'Tipo');
    // $sheet->setCellValue('I1', 'Proyecto');
    // $sheet->setCellValue('J1', 'Tipo de acceso');
    // $sheet->setCellValue('K1', 'Destino');
    // $from = "A1";
    // $to = "K1";

    // //estilo negrita a la primera linea
    // $spreadsheet->getActiveSheet()->getStyle("$from:$to")->getFont()->setBold(true);
    // $spreadsheet->getActiveSheet()->getStyle("$from:$to")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('80e1e4e5');
    // //$spreadsheet->getActiveSheet()->getStyle("$from:$to")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN));

    // //tamaño
    // $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(17);
    // $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(17);
    // $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(10);
    // $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    // $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(35);
    // $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
    // $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(37);
    // $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(10);
    // $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(8);
    // $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(13);
    // $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
    // $rows = 2;
    // foreach ($result as $val) {
    //     // if($rows % 2 != 0){
    //     //     $spreadsheet->getActiveSheet()->getStyle("A$rows:K$rows")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('80a1d3ff');
    //     // }
    //     $sheet->setCellValue('A' . $rows, substr($val['entrada'], 0, 16));
    //     $sheet->setCellValue('B' . $rows, substr($val['salida'], 0, 16));
    //     if (date('H:i', strtotime($val['entrada'])) <= '07:00') {
    //         $sheet->setCellValue('C' . $rows, 'A tiempo');
    //     } else {
    //         $sheet->setCellValue('C' . $rows, 'Retardo');
    //     }
    //     $sheet->setCellValue('D' . $rows, $val['NIMSS']);
    //     $sheet->setCellValue('E' . $rows, $val['colaborador']);
    //     if (calculateDistance(floatval(explode(' ', $val['ubicacion'])[0]), floatval(explode(' ', $val['ubicacion'])[1]), floatval('29.069134707168633'), floatval('-110.93836310228399')) <= 300) {
    //         $sheet->setCellValue('F' . $rows, 'En rango');
    //     } else {
    //         $sheet->setCellValue('F' . $rows, 'Fuera de rango');
    //     }
    //     $sheet->setCellValue('G' . $rows, $val['ubicacion']);
    //     $sheet->setCellValue('H' . $rows, $val['tipo']);
    //     $sheet->setCellValue('I' . $rows, $val['proyecto']);
    //     $sheet->setCellValue('J' . $rows, $val['tipo_acceso']);
    //     $sheet->setCellValue('K' . $rows, $val['destino']);
    //     $rows++;
    // }
    // $writer = new Xlsx($spreadsheet);
    // $writer->save("files/area/capitalhumano/asistencia/" . $fileName);
    //         return $this->response->setStatusCode(200);
    //     } else {
    //         return $this->response->setStatusCode(500)->setJSON(0);
    //     }
    // }

    public function getSucursales()
    {
        $query = $this->db->query(
            "SELECT S.id_sucursal,S.sucursal,R.id_region,R.nombre_region
            FROM Sucursales S LEFT JOIN Regiones R
			ON S.region = R.id_region
			WHERE S.estado=1 ORDER BY S.sucursal"
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getEstudios()
    {
        $query = $this->db->query("SELECT nivelEstudio_id,nivelEstudio_detalle FROM tbl_nivelEstudio");

        $result = $query->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getPuestos()
    {
        $query = $this->db->query(
            "SELECT id_puesto, puesto FROM Puestos WHERE estado = 1 ORDER BY puesto ASC"
        );
        $result = $query->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }
    public function getEspecialidades()
    {
        $builder = $this->db->table('tbl_especialidades');
        $builder->select('id_especialidad,especialidad');
        $builder->where('estado', 1);
        $builder->orderBy('especialidad');
        $result = $builder->get();

        return $this->response->setStatusCode(200)->setJSON($result->getResult());
    }
    public function getGeografias()
    {
        $query = $this->db->query(
            "SELECT zonaGeografica_id,CONCAT(zonaGeografica_codigo,' - ',zonaGeografica_desc)nombre
            FROM tbl_zonaGeografica
            WHERE zonaGeografica_status != 0
            ORDER BY zonaGeografica_codigo"
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }
    public function getDepartamentos()
    {
        $query = $this->db->query("SELECT D.departamentos_id, CONCAT(D.departamentos_codigo,' - ',D.departamentos_desc)departamento,D.id_area,A.area
        FROM tbl_departamentos D
        LEFT JOIN Areas A
        ON D.id_area = A.id_area
        WHERE D.departamentos_estado = 1");
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }
    public function getCarreras()
    {
        $query = $this->db->query(
            "SELECT id_carrera,carerra
            FROM Carreras WHERE estado=1"
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }
    public function getNacionalidades()
    {
        $query = $this->db->query(
            "SELECT id_nacionalidad,nacionalidad
            FROM Nacionalidades"
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getColaboradores()
    {
        $query = $this->db->query(
            "SELECT
            CONCAT(C.nombres,' ',C.apellido_p) nombreC,
            C.id_colaborador,
            C.n_colaborador,
            C.nombres,
            C.apellido_p,
            C.apellido_m,
            C.foto,
            C.id_vendedorSap,
            A.area,
            C.estado,
			R.nombre_region
            FROM Colaboradores C
			LEFT JOIN Nacionalidades N
			ON C.id_nacionalidad = N.id_nacionalidad
			LEFT JOIN Areas A
			ON C.id_area = A.id_area
			LEFT JOIN Regiones R
			ON C.id_region = R.id_region
			ORDER BY C.apellido_p"
        );
        $colaboradores = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'colaboradores' => $colaboradores
        ]);
    }

    public function getTipoEmpleado()
    {
        $query = $this->db->query("SELECT tipoEmpleado_id,tipoEmpleado_desc from tbl_tipoEmpleado");
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getColaborador($id_colaborador)
    {
        $colaboradorModel = new Colaborador();
        $colaborador = $colaboradorModel->find($id_colaborador);

        $builder = $this->db->table('tbl_colab_tabulador t');
        $builder->select("t.id,t.hoja, t.nivel_1, t.nivel_2, t.nivel_3, t.nivel_4,
        t.posicion_apoyo, t.categoria");
        $builder->where('status', 1);
        $tabuladorData = $builder->get()->getResult();
        unset($colaborador->pass);

        return $this->getResponse([
            'message' => 'Data sent successfully',
            'colaborador' => $colaborador,
            'tabulador' => $tabuladorData
        ]);
    }

    public function saveColaborador()
    {
        $post = $this->request->getPost('datos') ? $this->request->getPost('datos') : '';
        $foto = $this->request->getFile('foto');
        $json = json_decode($post);
        $colaborador = new Colaborador();

        if ($json->id_colaborador != 0) {
            $fotoAnterior = $colaborador->select('foto')->find($json->id_colaborador)->foto;
            if (isset($foto) && $foto->isValid() && !$foto->hasMoved()) {
                if (file_exists('./images/foto_colaborador/' . $fotoAnterior) && $fotoAnterior != 'default.png') {
                    unlink('./images/foto_colaborador/' . $fotoAnterior);
                }
                $json->foto = random_int(10000, 99999) . '_' . $colaborador->select("REPLACE(CONCAT(nombres,'_',apellido_p),' ','_') AS foto")->find($json->id_colaborador)->foto . '.' . $foto->getExtension();
                $foto->move('./images/foto_colaborador', $json->foto);
            } else {
                $json->foto = $fotoAnterior;
            }
        } else {
            if (isset($foto) && $foto->isValid() && !$foto->hasMoved()) {
                $json->foto = random_int(10000, 99999) . '_' . $colaborador->select("REPLACE(CONCAT(nombres,'_',apellido_p),' ','_') AS foto")->find($json->id_colaborador)->foto . '.' . $foto->getExtension();
                $foto->move('./images/foto_colaborador', $json->foto);
            } else {
                $json->foto = 'default.png';
            }
        }

        if (isset($json->passNuevo) && $json->passNuevo != '' && $json->passNuevo === $json->passNuevo2) {
            $json->pass = password_hash($json->passNuevo, PASSWORD_BCRYPT, ['cost' => 10]);
        } else {
            $json->pass = password_hash($json->pass, PASSWORD_BCRYPT, ['cost' => 10]);
        }

        if ($json->tipo_empleado != 1) {
            $post2 = $this->request->getPost('tabulador') ? $this->request->getPost('tabulador') : '';
            if ($post2 != '') {
                $tabulador = json_decode($post2);
                $data = $this->db->query("select t.nivel_1, t.nivel_2, t.nivel_3, t.nivel_4, t.id,
            isnull(tam.aumento,0) as aumento from tbl_colab_tabulador t left join
            tbl_colab_tabulador_aumento tam on tam.id_sucursal=? and t.hoja=tam.hoja
            where t.hoja=? and t.posicion_apoyo=?
            and t.categoria=? and t.status = 1", [
                    $json->id_sucursal,
                    $tabulador->hoja,
                    $tabulador->posicion_apoyo,
                    $tabulador->categoria
                ])->getResult();
                switch ($json->tab_nivel) {
                    case 1:
                        $json->ingreso_bruto_men = ($data[0]->nivel_1 * (floatval($data[0]->aumento) + 1)) * ($json->porcentaje / 100);
                        break;
                    case 2:
                        $json->ingreso_bruto_men = ($data[0]->nivel_2 * (floatval($data[0]->aumento) + 1)) * ($json->porcentaje / 100);
                        break;
                    case 3:
                        $json->ingreso_bruto_men = ($data[0]->nivel_3 * (floatval($data[0]->aumento) + 1)) * ($json->porcentaje / 100);
                        break;
                    case 4:
                        $json->ingreso_bruto_men = ($data[0]->nivel_4 * (floatval($data[0]->aumento) + 1)) * ($json->porcentaje / 100);
                        break;

                    default:
                        # code...
                        break;
                }
                $json->tabulador_id = $data[0]->id;
            }
        } else {
            $json->tabulador_id = 0;
            $json->tab_nivel = 0;
        }

        if ($json->id_colaborador == 0) {
            if ($colaborador->save($json)) {
                $nombres = $json->nombres;
                $apellido_p = $json->apellido_p;
                $apellido_m = $json->apellido_m;
                $from = "intranet@ecnautomation.com";
                $message = "<table>";
                $message .= "<tr><td style='width:100px;'></td><td >";
                $message .= "
                                         <!DOCTYPE html>
                                          <html lang='en'>
                                          <head>
                                            <meta charset='UTF-8'>
                                            <title>Titutlo</title>
                                          </head>
                                          <body>
                                          <img border=0 src='http://intranet.ecn.com.mx:8060/api/public/images/logos/ECN_logo.png' alt='ecn' style='width:80%; height:80%;'/>
                                          <hr>
                                          <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Nuevo colaborador</h1>
                                          <br>
                                          <h4 style=' font-family:Helvetica;'>Estimado colaborador, se le informa que se ha registrado un colaborador en intranet y debe ser registrado en SAP. El nombre es:' " . $nombres . " " . $apellido_p . " " . $apellido_m . "'.</h4>
                                          <br>";
                $message .= "
                                      <br>
                                      <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>
                                        
                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                      
                                      </td>
                                      <td style='width:100px;'></td>
                                       </tr> 
                                      </table>";
                $message .= "
                            
                          </body>
                          </html>";

                $subject = "Nuevo colaborador registrado";
                $to = "martin.zamora@ecnautomation.com";
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                $headers .= "From:" . $from;
                mail($to, $subject, $message, $headers);
            };
        } else {
            $colaborador->save($json);
        }

        return $this->getResponse([
            'message' => 'Data saved successfully'
        ]);
    }

    public function verifyPass()
    {
        $password = $this->getRequestInput($this->request)['password'];
        $id_colaborador = $this->getRequestInput($this->request)['id_colaborador'];
        $query = $this->db->query("SELECT pass FROM Colaboradores WHERE id_colaborador = ?", [$id_colaborador]);
        $pass = $query->getResult()[0]->pass;
        if (password_verify($password, $pass)) {
            return $this->getResponse([
                'message' => 'The passwords match.',
                'access' => true
            ]);
        } else {
            return $this->getResponse([
                'message' => 'The passwords do not match.',
                'access' => false
            ]);
        }
    }

    public function getSangre()
    {
        $query = $this->db->query(
            "SELECT id_sangre,desc_sangre
            FROM Sangre"
        );
        $tipoSangre = $query->getResult();

        return $this->getResponse(["tipoSangre" => $tipoSangre]);
    }

    public function getHojaEspecialidad()
    {
        $builder = $this->db->table('tbl_colab_tabulador');
        $builder->select('DISTINCT(hoja) AS hoja');
        $builder->where('status', 1);
        $builder->orderBy('hoja');
        $resultHoja = $builder->get()->getResult();

        $builder->select("distinct(posicion_apoyo) posicion_apoyo,hoja");
        $builder->where('status', 1);
        $builder->orderBy('posicion_apoyo');
        $resultPos = $builder->get()->getResult();

        $builder->select("distinct(categoria) categoria,hoja,posicion_apoyo");
        $builder->where('status', 1);
        $builder->orderBy('categoria');
        $resultCat = $builder->get()->getResult();

        $result = [$resultHoja, $resultPos, $resultCat];


        return $this->response->setStatusCode(200)->setJSON($result);
    }

    //funcion para iniciar sesión

    // public function login()
    // {
    //     $json = $this->request->getJSON();
    //     $query = $this->db->query(
    //         "SELECT TOP 1 id_colaborador
    //         FROM Colaboradores
    //         WHERE nombreUsuario=? COLLATE Latin1_General_CS_AS
    //         AND pass=? COLLATE Latin1_General_CS_AS",
    //         [
    //             $json->nombreUsuario,
    //             $json->pass
    //         ]
    //     );

    //     $result = $query->getResult();
    //     if (count($result) > 0) {
    //         $id_colaborador = $result[0]->id_colaborador;
    //         $query = $this->db->query(
    //             "SELECT TOP 1 C.id_colaborador,C.n_colaborador,C.id_vendedorSap,C.nombres,C.apellido_p,C.apellido_m,C.foto,C.email,C.id_area
    //             -- CASE DV.tipo
    //             -- WHEN 1 THEN 'Gerente'
    //             -- WHEN 2 THEN 'Vendedor'
    //             -- END AS tipo,DV.id_zona,Z.zona
    //             FROM Colaboradores C
    //             -- JOIN det_vendedores DV
    //             -- ON C.id_colaborador=DV.id_colaborador
    //             -- JOIN zonas Z
    //             -- ON DV.id_zona=Z.id
    //             WHERE C.id_colaborador=?",
    //             [$id_colaborador]
    //         );
    //         $result = $query->getResult();
    //         return $this->response->setStatusCode(200)->setJSON($result);
    //     } else {
    //         return $this->response->setStatusCode(200)->setJSON(0);
    //     }
    // }

    public function getColaboradoresSemanales()
    {
        $json = $this->request->getJSON();
        $query = $this->db->query(
            "SELECT id_colaborador,CONCAT(nombres,' ',apellido_p,' ',apellido_m)nombre
            FROM Colaboradores
            WHERE PrjCode = ?
            AND id_colaborador != ?",
            [
                $json->proyecto,
                $json->id_colaborador
            ]
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getProyectos()
    {
        $query = $this->db->query(
            "SELECT DISTINCT(PrjCode) FROM Colaboradores WHERE estado=1 AND tipo_empleado=1 AND PrjCode IS NOT NULL AND PrjCode!=''"
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    //funcion para registrar checada / salida

    public function checkInOut()
    {
        //Si ya tiene ambas checadas el dia de hoy, no permitir la checada
        $json = $this->request->getJSON();
        $checar = $json->checkExists;
        //$id_colaborador = $json->id_colaborador;

        function generateRandomString()
        {
            $length = 8;
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }

        function timeDiff($firstTime, $lastTime)
        {
            $firstTime = strtotime($firstTime);
            $lastTime = strtotime($lastTime);
            $timeDiff = ($lastTime - $firstTime) / 60;
            return $timeDiff / 60;
        }

        function convertHoursToTime($hora)
        {
            // start by converting to seconds
            $seconds = ($hora * 3600);
            // we're given hours, so let's get those the easy way
            $hours = floor($hora);
            // since we've "calculated" hours, let's remove them from the seconds variable
            $seconds -= $hours * 3600;
            // calculate minutes left
            $minutes = floor($seconds / 60);
            // remove those from seconds as well
            $seconds -= $minutes * 60;
            // return the time formatted HH:MM:SS
            return lz($hours) . ":" . lz($minutes) . ":00";
        }

        // lz = leading zero
        function lz($num)
        {
            return (strlen($num) < 2) ? "0{$num}" : $num;
        }

        //hasta aqui

        function calculateDistance(
            $latitudeFrom,
            $longitudeFrom,
            $latitudeTo,
            $longitudeTo,
            $earthRadius = 6371000
        ) {
            // convert from degrees to radians
            $latFrom = deg2rad($latitudeFrom);
            $lonFrom = deg2rad($longitudeFrom);
            $latTo = deg2rad($latitudeTo);
            $lonTo = deg2rad($longitudeTo);

            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            return $angle * $earthRadius;
        }


        $n_colaborador = $json->n_colaborador;

        $spreadSheetId = "1hoG_GWcpI42xl4juYQfwiWNn-Gvkv3V8_3sVDGYB5lo";

        try {

            $client = new Google_Client();
            $client->setApplicationName('Sheet PHP');
            $client->setScopes([Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');
            $client->setAuthConfig(__DIR__ . '/credentials.json');

            $service = new Sheets($client);

            $rangeChecador = "Checador";

            // $responseChecador = $service
            //     ->spreadsheets_values
            //     ->batchGet($spreadSheetId, ['ranges' => $rangeChecador]);

            // $dataChecador = $responseChecador->getValueRanges()[0]->values;

            // $hoyF = date('d/m/Y');

            // //variable para saber si es entrada o salida
            // $checkIn = true;

            // //variable para saber si ya tiene ambas checadas
            // $checar = true;

            // for ($x = 0; $x < count($dataChecador); $x++) {
            //     if ($dataChecador[$x][3] === $n_colaborador && $dataChecador[$x][7] === $hoyF) {
            //         $checkIn = false;
            //     }
            //     if($dataChecador[$x][3] === $n_colaborador && $dataChecador[$x][7] === $hoyF && $dataChecador[$x][4]=="Check OUT"){
            //         $checar = false;
            //         break;
            //     }
            // }

            $rangeVendedores = "Vendedores";
            $responseVendedores = $service->spreadsheets_values->batchGet($spreadSheetId, ['ranges' => $rangeVendedores]);
            $dataVendedores = $responseVendedores->getValueRanges()[0]->values;
            $tipo = $json->tipo;
            //$hoyU = date('Y-m-d');
            $hoyF = date('d/m/Y');
            $año = date('Y');
            $monthNum = date('n');
            $weekNum = date('W') + 1;
            $numDay = date('N') + 1;
            $hora = date('H:i') . ":00";


            for ($x = 0; $x < count($dataVendedores); $x++) {
                if ($dataVendedores[$x][0] === $n_colaborador) {
                    $info = $dataVendedores[$x];
                }
            }

            if (isset($info)) {
                $buscador = $info[1] . '-' . $año . '-' . $monthNum . '-' . $weekNum . '-' . $numDay;
                $numHoras = timeDiff($info[5], $hora);
                $horasReportadas = convertHoursToTime($numHoras);
                $values = [
                    [
                        generateRandomString(),
                        $buscador,
                        $info[1],
                        $info[0],
                        $checar ? "Check IN" : "Check OUT",
                        $info[10],
                        $info[5] . ':00',
                        $hoyF,
                        $hora,
                        $tipo,
                        "",
                        $json->proyecto ? $json->proyecto : "",
                        $json->tipo_acceso ? $json->tipo_acceso : "",
                        "",
                        $checar ? (new Datetime($hora) <= new Datetime($info[5]) ? "A tiempo" : "Retrasado") : "No aplica",
                        $checar ? "" : $horasReportadas,
                        $info[3],
                        $info[4],
                        "",
                        "",
                        "",
                        $checar ? ($json->ubicacion ? $json->ubicacion : "") : "",
                        $checar ? ($json->tipo == 'Teletrabajo' ? "" : "29.06908190125933, -110.93823740228395") : "",
                        $checar ? ($json->tipo == 'Teletrabajo' ? "" : ($json->ubicacion ? $distancia = calculateDistance(explode(',', $json->ubicacion, 2)[0], explode(',', $json->ubicacion, 2)[1], 29.06908190125933, -110.93823740228395) : "")) : "",
                        $checar ? ($json->tipo == 'Teletrabajo' ? "" : ($json->ubicacion ? ($distancia <= 300 ? "Distancia Aprobada" : "Fuera de Rango") : "")) : ""

                    ]
                ];
                $body = new ValueRange([
                    'values' => $values
                ]);

                $params = [

                    'valueInputOption' => 'USER_ENTERED',
                    "insertDataOption" => "INSERT_ROWS",
                ];
                $service->spreadsheets_values->append($spreadSheetId, $rangeChecador, $body, $params);

                return $this->getResponse([
                    'message' => 'Datos guardados'
                ]);
            } else {
                $query = $this->db->query("SELECT n_colaborador,email,NIMSS AS 'nss',CONCAT(apellido_p,' '+apellido_m,' '+nombres) AS 'colaborador',
            CASE WHEN
            tipo_empleado = 2 THEN 'Quincenal'
            ELSE 'Semanal'
            END AS 'tipo_usuario'
            FROM Colaboradores
            WHERE estado=1
            AND id_colaborador=?", [$json->id_colaborador]);
                $colaborador = $query->getResult()[0];
                $values =  [[
                    $colaborador->n_colaborador,
                    $colaborador->colaborador,
                    $colaborador->email,
                    "",
                    "",
                    "7:00:00",
                    $colaborador->nss,
                    "",
                    "",
                    "",
                    $colaborador->tipo_usuario
                ]];
                $body = new ValueRange([
                    'values' => $values
                ]);
                $params = [

                    'valueInputOption' => 'USER_ENTERED',
                    "insertDataOption" => "INSERT_ROWS",
                ];
                //guardar usuario en caso de no estar registrado
                $service->spreadsheets_values->append($spreadSheetId, $rangeVendedores, $body, $params);
                
                //volver a leer los vendedores para registrar la checada
                $responseVendedores = $service->spreadsheets_values->batchGet($spreadSheetId, ['ranges' => $rangeVendedores]);
                $dataVendedores = $responseVendedores->getValueRanges()[0]->values;

                for ($x = 0; $x < count($dataVendedores); $x++) {
                    if ($dataVendedores[$x][0] === $n_colaborador) {
                        $info = $dataVendedores[$x];
                    }
                }

                $buscador = $info[1] . '-' . $año . '-' . $monthNum . '-' . $weekNum . '-' . $numDay;
                $numHoras = timeDiff($info[5], $hora);
                $horasReportadas = convertHoursToTime($numHoras);
                $values = [
                    [
                        generateRandomString(),
                        $buscador,
                        $info[1],
                        $info[0],
                        $checar ? "Check IN" : "Check OUT",
                        $info[10],
                        $info[5] . ':00',
                        $hoyF,
                        $hora,
                        $tipo,
                        "",
                        $json->proyecto ? $json->proyecto : "",
                        $json->tipo_acceso ? $json->tipo_acceso : "",
                        "",
                        $checar ? (new Datetime($hora) <= new Datetime($info[5]) ? "A tiempo" : "Retrasado") : "No aplica",
                        $checar ? "" : $horasReportadas,
                        $info[3],
                        $info[4],
                        "",
                        "",
                        "",
                        $checar ? ($json->ubicacion ? $json->ubicacion : "") : "",
                        $checar ? ($json->tipo == 'Teletrabajo' ? "" : "29.06908190125933, -110.93823740228395") : "",
                        $checar ? ($json->tipo == 'Teletrabajo' ? "" : ($json->ubicacion ? $distancia = calculateDistance(explode(',', $json->ubicacion, 2)[0], explode(',', $json->ubicacion, 2)[1], 29.06908190125933, -110.93823740228395) : "")) : "",
                        $checar ? ($json->tipo == 'Teletrabajo' ? "" : ($json->ubicacion ? ($distancia <= 300 ? "Distancia Aprobada" : "Fuera de Rango") : "")) : ""

                    ]
                ];
                $body = new ValueRange([
                    'values' => $values
                ]);

                $service->spreadsheets_values->append($spreadSheetId, $rangeChecador, $body, $params);

                return $this->getResponse([
                    'message' => 'Datos guardados'
                ]);
            }
        } catch (Exception $ex) {
            return $this->getResponse([
                'error' => $ex
            ], 500);
        }



        //hasta aqui

        // $tipo = $json->tipo;
        // $fechaHora = date('Y-m-d H:i:s');
        // $ubicacion = $json->ubicacion;
        // $comentarios = $json->comentarios;
        // $entradaExists = $json->checkExists;
        // $detalles = $json->detalles;
        // if ($entradaExists == 0) {
        //     switch ($tipo) {
        //         case 'Ejecución':
        //             $proyecto = $json->proyecto;
        //             $tipo_acceso = $json->tipo_acceso;
        //             $this->db->query(
        //                 "INSERT INTO checadas(entrada,id_colaborador,ubicacion,tipo,proyecto,tipo_acceso,comentarios)
        //                 VALUES(?,?,?,?,?,?,?)",
        //                 [
        //                     $fechaHora,
        //                     $id_colaborador,
        //                     $ubicacion,
        //                     $tipo,
        //                     $proyecto,
        //                     $tipo_acceso,
        //                     $comentarios
        //                 ]
        //             );
        //             if (count($json->detalles) > 0) {
        //                 foreach ($json->detalles as $checkSemanal) {
        //                     $this->db->query(
        //                         "INSERT INTO checadas(entrada,id_colaborador,ubicacion,tipo,proyecto,tipo_acceso,checada_sem)
        //                         VALUES(?,?,?,?,?,?,?)",
        //                         [
        //                             $fechaHora,
        //                             $checkSemanal->id_colaborador,
        //                             $ubicacion,
        //                             $tipo,
        //                             $proyecto,
        //                             $tipo_acceso,
        //                             $checkSemanal->detalle
        //                         ]
        //                     );
        //                 }
        //             }
        //             $this->response->setStatusCode(200)->setJSON(1);
        //             break;
        //         case 'Comisión':
        //             $destino = $json->destino;
        //             $this->db->query(
        //                 "INSERT INTO checadas(entrada,id_colaborador,ubicacion,tipo,destino)
        //                 VALUES(?,?,?,?,?)",
        //                 [
        //                     $fechaHora,
        //                     $id_colaborador,
        //                     $ubicacion,
        //                     $tipo,
        //                     $destino
        //                 ]
        //             );
        //             $this->response->setStatusCode(200)->setJSON(1);
        //             break;
        //         default:
        //             $this->db->query(
        //                 "INSERT INTO checadas(entrada,id_colaborador,ubicacion,tipo)
        //             VALUES(?,?,?,?)",
        //                 [
        //                     $fechaHora,
        //                     $id_colaborador,
        //                     $ubicacion,
        //                     $tipo
        //                 ]
        //             );
        //             $this->response->setStatusCode(200)->setJSON(1);

        //             break;
        //     }
        // } else {
        //     $this->db->query(
        //         "UPDATE checadas
        //         SET
        //         salida=?
        //         WHERE id_checada = ?",
        //         [
        //             $fechaHora,
        //             $entradaExists
        //         ]
        //     );
        //     if (count($detalles) > 0) {
        //         foreach ($detalles as $detalle) {
        //             $this->db->query(
        //                 "UPDATE checadas
        //                 SET
        //                 salida=?
        //                 WHERE id_checada = ?",
        //                 [
        //                     $fechaHora,
        //                     $detalle->id_checada
        //                 ]
        //             );
        //         }
        //     }
        //     return $this->response->setStatusCode(200)->setJSON(1);
        // }
    }

    //funcion para revisar si existe checada en el dia, si hay 2 checadas, retorna 2, si solo hay entrada returna 1 y si no hay retorna 0

    public function check()
    {
        $json = $this->request->getJSON();

        $n_colaborador = $json->n_colaborador;

        try {
            $spreadSheetId = "1hoG_GWcpI42xl4juYQfwiWNn-Gvkv3V8_3sVDGYB5lo";
            $client = new Google_Client();
            $client->setApplicationName('Sheet PHP');
            $client->setScopes([Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');
            $client->setAuthConfig(__DIR__ . '/credentials.json');

            $service = new Sheets($client);

            $rangeChecador = "Checador";

            $responseChecador = $service
                ->spreadsheets_values
                ->batchGet($spreadSheetId, ['ranges' => $rangeChecador]);

            $dataChecador = $responseChecador->getValueRanges()[0]->values;

            $hoyF = date('d/m/Y');

            //variable para saber si es entrada o salida
            $checkIn = true;

            //variable para saber si ya tiene ambas checadas
            $checadasCompletas = false;

            $dataCheck = array();

            for ($x = 0; $x < count($dataChecador); $x++) {

                if ($dataChecador[$x][3] === $n_colaborador && $dataChecador[$x][7] === $hoyF && $dataChecador[$x][4] == "Check OUT") {
                    $checadasCompletas = true;
                }
                if ($dataChecador[$x][3] === $n_colaborador && $dataChecador[$x][7] === $hoyF) {
                    $dataCheck = [
                        'tipo' => $dataChecador[$x][9],
                        'horaEntrada' => $dataChecador[$x][8]
                    ];
                    $checkIn = false;
                }
            }

            return $this->getResponse([
                'checkIn' => $checkIn,
                'checadasCompletas' => $checadasCompletas,
                'checkData' => $dataCheck
            ]);

            // if($checkOut){
            //     return $this->response->setStatusCode(200)->setJSON(2);
            // }
            // if($checkIn){
            //     return $this->response->setStatusCode(200)->setJSON(0);
            // }else{
            //     return $this->response->setStatusCode(200)->setJSON($dataCheck);
            // }
        } catch (\Throwable $th) {
            return $this->getResponse(['error' => $th], 500);
        }


        //hasta aqui


        // $masterCheck = $this->db->query(
        //     "SELECT id_checada
        //     FROM checadas
        //     WHERE id_colaborador = ?
        //     AND salida is not null
        //     AND DATEDIFF(DD,GETDATE(),entrada) = 0",
        //     [
        //         $id_colaborador
        //     ]
        // );

        // $result = $masterCheck->getResult();

        // if (count($result) > 0) {
        //     return $this->response->setJSON(2);
        // } else {
        //     $masterCheck2 = $this->db->query(
        //         "SELECT id_colaborador,id_checada,tipo,tipo_acceso,destino,proyecto,ubicacion
        //         FROM checadas
        //         WHERE id_colaborador = ?
        //         AND DATEDIFF(DD,GETDATE(),entrada) = 0",
        //         [$id_colaborador]
        //     );

        //     $result = $masterCheck2->getResult();


        //     if (count($result) > 0) {
        //         $salidaSemanal = $this->db->query(
        //             "SELECT CH.id_colaborador,CH.id_checada,CONCAT(C.nombres,' ',C.apellido_p,' ',C.apellido_m)nombre,CH.checada_sem
        //             FROM checadas CH
        //             LEFT JOIN Colaboradores C
        //             ON CH.id_colaborador=C.id_colaborador
        //             WHERE CH.proyecto= ?
        //             AND CH.id_colaborador!=?
        //             AND DATEDIFF(DD,GETDATE(),CH.entrada) = 0",
        //             [
        //                 $result[0]->proyecto,
        //                 $result[0]->id_colaborador
        //             ]
        //         );
        //         $result2 = $salidaSemanal->getResult();
        //         array_push($result, $result2);
        //         return $this->response->setJSON($result);
        //     } else {
        //         return $this->response->setJSON(0);
        //     }
        // }
    }
}
