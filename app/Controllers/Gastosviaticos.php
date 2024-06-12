<?php

namespace App\Controllers;

use Mpdf\Mpdf;

use function GuzzleHttp\Promise\queue;
use function PHPUnit\Framework\fileExists;

class Gastosviaticos extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getSolicitudesGasto()
    {
        $id_colaborador = $this->request->getJSON()->id_colaborador;
        $query = $this->db->query("SELECT 
        T0.sol_id,
        T0.create_date,
        T0.sol_nombre,
        T0.motivo_gasto,
        CASE T0.sol_estado
          WHEN 1 THEN 'Por enviar'
          WHEN 2 THEN 'Enviada a líder'
          WHEN 3 THEN 'En revisión finanzas'
          WHEN 4 THEN 'Rechazada por líder'
          WHEN 5 THEN 'En depósito'
          WHEN 6 THEN 'Rechazada por finanzas'
          WHEN 7 THEN 'Depositada'
        END AS sol_estado,
        T0.sol_estado as estado,
        (SELECT SUM(t1.total) as total FROM tbl_gas_gastos t1 WHERE t1.status=1 and t1.id_sol=T0.sol_id) as total,
        (SELECT top 1 T3.id FROM tbl_calificacion_finanzas T3 WHERE T3.sol_id=T0.sol_id  and T3.tipo=2) AS calificacion_id,
        IIF(t0.create_date >= '2019-05-13',1,0) as calificar,
        t1.nombres+' '+t1.apellido_p as aprobador
        FROM tbl_gas_solicitudes T0 LEFT JOIN
        Colaboradores t1 on t0.sol_aprobador=t1.id_colaborador
        WHERE 
        T0.sol_id_solicitante = ? and 
        T0.status=1
        ORDER BY
        t0.sol_id desc", [$id_colaborador]);

        $solicitudes = $query->getResult();

        for ($i = 0; $i < count($solicitudes); $i++) {
            if ($solicitudes[$i]->estado == 7) {
                $sol_id = $solicitudes[$i]->sol_id;
                $query = $this->db->query("SELECT COUNT(inf_id) as cant,inf_estado FROM tbl_gas_informes WHERE sol_id=? and status=1 group by inf_estado", [$sol_id]);
                $result = $query->getResult();
                if (count($result) > 0) {
                    $inf_estado = $result[0]->inf_estado;
                    $solicitudes[$i]->informe = true;
                    $solicitudes[$i]->inf_estado = $inf_estado;
                }
            }
        }

        $result = [
            'solicitudes' => $solicitudes
        ];

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function generarPDF()
    {
        $pdf = new Mpdf([
            'debug' => true,
            'mode' => 'utf-8'
        ]);

        $sol_id = $this->request->getJSON()->sol_id;
        $query = $this->db->query("SELECT
        T0.sol_nombre,
        T0.sol_depto,
        T0.sol_geo,
        T0.motivo_gasto,
        t0.create_date,
        IIF(t0.sol_proyecto = '0','',T0.Sol_proyecto) as proyecto,
        T1.nombres+' '+T1.apellido_p+' '+T1.apellido_m AS solicitante,
        T1.email,
        CASE T1.ID_DEPARTAMENTOS
                 when 17 then 'FP-'+cast(t0.sol_id as varchar)
                 when 7 then 'FP-'+cast(t0.sol_id as varchar)
                 when 18 then 'FS-'+cast(t0.sol_id as varchar)
                 when 8 then 'FS-'+cast(t0.sol_id as varchar)
                 when 13 then 'GF-'+cast(t0.sol_id as varchar)
                 when 5 then 'GF-'+cast(t0.sol_id as varchar)
                 ELSE 'FU-'+cast(t0.sol_id as varchar)
                END AS codigo
        FROM
        tbl_gas_solicitudes T0 LEFT JOIN
        Colaboradores T1 ON T0.sol_id_solicitante=T1.id_colaborador
        WHERE
        T0.sol_id=?", [$sol_id]);

        $solicitud = $query->getResult()[0];

        $query = $this->db->query("SELECT 
        T0.cantidad,
        T0.precio_unitario,
        T0.total,
        T0.descripcion,
        T1.gasto_name
        FROM
        tbl_gas_gastos T0 LEFT JOIN
        tbl_gas_tipo_gastos T1 ON T0.tipo_gasto=T1.id_gasto
        WHERE
        T0.id_sol=? AND
        T0.status=1", [$sol_id]);

        $gastos = $query->getResult();

        if ($solicitud->proyecto == '') {
            $solicitud->proyecto = 'Ninguno';
        }
        //formato fecha
        $solicitud->create_date = date('d/m/Y', strtotime($solicitud->create_date));

        $html = "
        <style>
        body{
	
            font-family: 'sofiapro', sans-serif !important;
            font-size: 10pt;
            color: #58595b;
        }
        
        .solicitante{
            color:#3259A5;
        }
        </style>
        <table style='width:100%;'>
         <tr>
          <td style='width:60%; '><img src='images/logos/ECN_logo.png' style='width:30%;'></td>
          <td style='width:40%; text-align:right; border-right:2px solid #EE8624; padding-right:15px;'><h1 style='color:#EE8624; '>{$solicitud->codigo}</h1><h3>COMPROBACIÓN DE GASTOS</h3></td>
         </tr>
        </table>
        <br>
        <div>
        <h3 class='solicitante'>SOLICITANTE</h3>
        <p style='line-height:-8px;'><b>Nombre: </b>{$solicitud->solicitante}</p>
        <p style='line-height:-8px;'><b>Email: </b>{$solicitud->email}</p>
        <br>
        <h3 class='solicitante' style='margin-bottom:1px;'>DETALLE DE LA SOLICITUD</h3>
        <table style='width:100%; margin-top:-5px;'>
         <tr>
          <td style='width:50%;'>
            <p style='line-height:-7px;'><b>Nombre: </b>{$solicitud->sol_nombre}</p>
            <p style='line-height:-7px;'><b>Fecha de solicitud: </b>{$solicitud->create_date}</p>
            <p style='line-height:-7px;'><b>Cargo a: </b>{$solicitud->sol_depto} - {$solicitud->sol_geo}</p>
 
            
          </td>
          <td style='width:50%;'>
            <p style='line-height:-7px;'><b>Proyecto: </b>{$solicitud->proyecto}</p>
            <p style='line-height:-7px;'><b>Motivo gasto: </b>{$solicitud->motivo_gasto}</p>
            
          </td>
         </tr>
        </table>
        </div>
        <br>
        <table style='width:100%; border-collapse:collapse;'>  
         <tr>
          <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Concepto</b></td>
          <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Comentario</b></td>
          <td style='border-bottom:2px solid #3259A5; color:#3259A5; text-align: center;'><b>Cantidad</b></td>
          <td style='border-bottom:2px solid #3259A5; color:#3259A5; text-align: center;'><b>Precio</b></td>
          <td style='border-bottom:2px solid #3259A5; color:#3259A5; text-align: center;'><b>Total</b></td>
         </tr>
        ";

        $total = 0;
        for ($i = 0; $i < count($gastos); $i++) {
            //formato moneda
            helper('number');
            $gastos[$i]->precio_unitario = number_to_currency($gastos[$i]->precio_unitario, 'MXN', 'es_MX', 2);
            $total += $gastos[$i]->total;
            $gastos[$i]->total = number_to_currency($gastos[$i]->total, 'MXN', 'es_MX', 2);
            $html .= "<tr>
	          <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$gastos[$i]->gasto_name} </td>
	          <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$gastos[$i]->descripcion} </td>
	          <td style='border-bottom:0.3spx solid #919091; padding:4px; text-align: center;'> {$gastos[$i]->cantidad} </td>
	          <td style='border-bottom:0.3spx solid #919091; padding:4px; text-align: center;'> {$gastos[$i]->precio_unitario} </td>
	          <td style='border-bottom:0.3spx solid #919091; padding:4px; text-align: center;'> {$gastos[$i]->total} </td>
	        </tr>";
        }
        $total = number_to_currency($total, 'MXN', 'es_MX', 2);
        $html .= "<tr>
	          <td >  </td>
	          <td >  </td>
	          <td >  </td>
	          <td style='border-bottom:0.3spx solid #3259A5; padding:4px;'> <h3>TOTAL: </h3></td>
	          <td style='border-bottom:0.3spx solid #3259A5; padding:4px;'> <H3>{$total} </H3></td>
	        </tr>
            </table>";

        $footer = "<hr style='color:#EE8624; height:2px;'><table style='width:100%;'>
           <tr>
            <td style='width:55%;'><img src='images/logos/industrias.png' style='width:35%;'></td>
            <td style='width:45%; text-align:center;'>&copy; 2023 - Todos los derechos reservados - ECN</td>
           </tr>
         </table>";
        $pdf->SetHTMLFooter($footer);
        $pdf->WriteHTML($html);

        return $this->response->setStatusCode(200)->setContentType('application/pdf')->sendBody($pdf->Output());
    }

    public function enviarNotificacion()
    {
        $json = $this->request->getJSON();
        $query = $this->db->query("SELECT 
        t1.sol_nombre,
        t1.sol_id_solicitante,
        t1.sol_aprobador,
        t1.sol_depto,
        t1.sol_geo,
        t1.sol_proy_serv,
        t1.sol_proyecto,
        t1.motivo_gasto,
        t2.nombres+' '+t2.apellido_p as solicitante,
        t3.nombres+' '+t3.apellido_p as aprobador,
        t3.email,
          isnull((SELECT SUM(total) FROM tbl_gas_gastos WHERE id_sol=? and status=1),0) as total
        FROM 
        tbl_gas_solicitudes t1 left join
        Colaboradores T2 on t1.sol_id_solicitante=t2.id_colaborador left join
        Colaboradores T3 on t1.sol_aprobador=t3.id_colaborador
        where 
        t1.sol_id=?", [
            $json->sol_id,
            $json->sol_id
        ]);
        $result = $query->getResult()[0];
        $solicitante = $result->solicitante;
        $email = $result->email;
        //$total="$".$result->total;

        $foto = "http://intranet.ecn.com.mx:8060/lineup/public/images/logos/logo_sm.png";

        $asunto = "Comprobación de gastos excedida";
        $mensaje = "<!DOCTYPE html>
					<html lang='en'>
					<head>
						<meta charset='UTF-8'>
						<title>Document</title>
					</head>
					<body>
						<table style='border-spacing:0px;'>
							<tr style=''>
								<td style='width:150px;'></td>
								<td style='width:250px; border:solid 1px #E6E6E6; background-color:#E6E6E6; padding:3px; border-top-left-radius:8px;'><img src='{$foto}' alt=''></td>
								<td style='width:250px; font-family:helvetica; font-size:1.15em; text-align:center; border:solid 1px #E6E6E6; background-color:#E6E6E6; padding:5px; border-top-right-radius:8px;'>Comprobación de gasto</td>
								<td style='width:150px;'></td>
							</tr>
							<tr>
								<td style='width:150px;'></td>
								<td style='width:500px; border:solid 1px #E6E6E6; padding:10px;' colspan='2'>";

        $mensaje .= "<p style='font-family:Helvetica;'>Estimado colaborador: </p>
		           <p style='font-family:Helvetica;'>Se le informa que {$solicitante} ha registrado una comprobación de gastos por un monto mayor al que solicitó, favor de revisarla.</p>";

        $mensaje .= "</td>
								<td style='width:150px;'></td>
							</tr>
							<tr>
								<td style='width:150px;'></td>
								<td style='width:500px; border:solid 1px #E6E6E6; background-color:#E6E6E6; border-bottom-left-radius:8px; border-bottom-right-radius:8px; text-align:center;' colspan='2'>
									<p style='font-family:Helvetica; font-size:0.7em; color:#848484;'>&copy;2017 ecn.com.mx. Todos los derechos reservados ecn.</p>
									<p style='font-family:Helvetica; font-size:0.7em; color:#848484;'>Este correo fue enviado automáticamente, por favor no respondas a este mensaje</p>
								</td>
								<td style='width:150px;'></td>
							</tr>
						</table>
					</body>
					</html>";

        $to = $email;
        $from = "intranet@ecnautomation.com";
        $message = $mensaje;
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= "From: Intranet ECN <" . $from . ">";

        //echo $message2;

        mail($to, $asunto, $message, $headers);
    }

    public function terminarInforme()
    {
        $json = $this->request->getJSON();
        $query = $this->db->query("SELECT SUM(total) as total_solicitado FROM tbl_gas_gastos WHERE id_sol=? and status=1", [$json->sol_id]);
        $totalSolicitado = $query->getResult()[0]->total_solicitado;
        $query = $this->db->query("SELECT isnull(SUM(total),0) total_comprobado FROM tbl_gas_informe_detalle WHERE informe_id=? and status=1", [$json->inf_id]);
        $totalComprobado = $query->getResult()[0]->total_comprobado;
        if ($totalComprobado > $totalSolicitado) {
            $diferencia = $totalComprobado - $totalSolicitado;
            if ($diferencia > 50) {
                $afinanzas = 0;
                $excedido = 1;
            } else {
                $afinanzas = 1;
                $excedido = 0;
            }
        } else {
            $afinanzas = 1;
            $excedido = 0;
        }
        $query = $this->db->query("SELECT sol_aprobador FROM tbl_gas_solicitudes WHERE sol_id=?", [$json->sol_id]);
        $solAprobador = $query->getResult()[0]->sol_aprobador;
        if ($solAprobador == $json->id_colaborador) {
            $isaprobador = 1;
        } else {
            $isaprobador = 0;
        }

        if ($excedido == 1) {

            if ($isaprobador == 1) {
                $this->db->query("UPDATE tbl_gas_informes SET inf_estado=5,excedido=0 WHERE inf_id=?", [$json->inf_id]);
            } else {
                $this->db->query("UPDATE tbl_gas_informes SET inf_estado=2, excedido=1 WHERE inf_id=?", [$json->inf_id]);
            }
        } else {
            $this->db->query("UPDATE tbl_gas_informes SET inf_estado=2,excedido=0,fecha_terminacion=GETDATE() WHERE inf_id=?", [$json->inf_id]);
        }

        return $this->getResponse([
            'message' => 'Data successfully saved',
            'afinanzas' => $afinanzas,
        ]);
    }

    public function getGastoInforme()
    {
        $json = $this->request->getJSON();
        $sol_id = $json->sol_id;
        $id_colaborador = $json->id_colaborador;
        $informe = $this->db->query("SELECT inf_estado,inf_id,excedido,inf_coment_lider,inf_coment_fin
        FROM tbl_gas_informes WHERE sol_id = ? AND status=1", [$sol_id])->getResult()[0];

        $totalSolicitado = $this->db->query("SELECT SUM(total) as total_solicitado
        FROM tbl_gas_gastos WHERE id_sol = ? and status=1", [$sol_id])->getResult()[0]->total_solicitado;

        $query = $this->db->query("SELECT IIF(sol_id_solicitante=?,1,0) as solicitante,IIF(sol_aprobador=?,1,0) as aprobador
        FROM tbl_gas_solicitudes where sol_id=?", [$id_colaborador, $id_colaborador, $sol_id]);

        $result = $query->getResult();

        $solicitante = $result[0]->solicitante;
        $aprobador = $result[0]->aprobador;

        if ($id_colaborador == 273 || $id_colaborador == 1072 || $id_colaborador == 1193 || $id_colaborador == 298 || $id_colaborador == 1069 || $id_colaborador == 1302) {
            $finanzas = 1;
        } else {
            $finanzas = 0;
        }

        //get gastos informe

        $query = $this->db->query("SELECT 
				T0.gasd_id,
				T0.total,
				t0.fecha_transaccion,
				T1.gasto_name,
				t2.inf_estado,
				T3.sol_id_solicitante,
				IIF(DATEDIFF(DAY,t3.create_date,t0.fecha_transaccion) >= 0 ,1,0) AS fecha
				FROM 
				tbl_gas_informe_detalle T0 LEFT JOIN 
				tbl_gas_tipo_gastos T1 ON t0.tipo_gasto=T1.id_gasto LEFT JOIN
				tbl_gas_informes T2 ON T0.informe_id=T2.inf_id LEFT JOIN
				tbl_gas_solicitudes T3 ON T2.sol_id=T3.sol_id
				WHERE 
				T0.informe_id=? and 
				T0.status=1", [$informe->inf_id]);

        $gastosInforme = $query->getResult();

        $query = $this->db->query("SELECT isnull(SUM(total),0) total_comprobado
        FROM tbl_gas_informe_detalle WHERE informe_id=? and status=1", [$informe->inf_id]);

        $totalComprobado = $query->getResult()[0]->total_comprobado;


        $response = [
            'informe' => $informe,
            'total_solicitado' => $totalSolicitado,
            'total_comprobado' => $totalComprobado,
            'solicitante' => $solicitante,
            'aprobador' => $aprobador,
            'finanzas' => $finanzas,
            'gastosInforme' => $gastosInforme
        ];

        return $this->response->setStatusCode(200)->setJSON($response);
    }

    public function getDetalleInforme()
    {
        $id_gasto = $this->request->getJSON()->id_gasto;
        $query = $this->db->query("SELECT it.*,tg.gasto_name FROM tbl_gas_informe_detalle it 
        LEFT JOIN tbl_gas_tipo_gastos tg ON it.tipo_gasto = tg.id_gasto
        WHERE it.gasd_id=?", [$id_gasto]);
        $detalles = $query->getResult()[0];
        $query = $this->db->query("SELECT anexo_nombre FROM tbl_gas_anexos WHERE gasto_id=? and status=1 and anexo_tipo=1", [$id_gasto]);
        $pdf = $query->getResult();
        $query = $this->db->query("SELECT anexo_nombre FROM tbl_gas_anexos WHERE gasto_id=? and status=1 and anexo_tipo=2", [$id_gasto]);
        $xml = $query->getResult();

        $result = [
            "detalles" => $detalles,
            "pdf" => $pdf,
            "xml" => $xml
        ];

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getDetalleSolicitud()
    {
        $sol_id = $this->request->getJSON()->sol_id;

        $query = $this->db->query("SELECT * FROM tbl_gas_solicitudes WHERE sol_id=?", [$sol_id]);
        $detalles = $query->getResult()[0];

        $query = $this->db->query("SELECT CONCAT(nombres,' ',apellido_p,' ',apellido_m)nombre FROM Colaboradores WHERE id_colaborador = ?", [$detalles->sol_aprobador]);
        $aprobador = $query->getResult()[0];
        $query = $this->db->query("SELECT CONCAT(nombres,' ',apellido_p,' ',apellido_m)nombre FROM Colaboradores WHERE id_colaborador = ?", [$detalles->sol_id_solicitante]);
        $nombre_solicitante = $query->getResult()[0];

        if ($detalles->sol_proy_serv == 1) {
            $query = $this->db->query("EXEC sp_GET_ecnTur_PyS_29022016")->getResult();
            for ($i = 0; $i < $query; $i++) {
                if ($query[$i]->PrjCode == $detalles->sol_proyecto) {
                    $proyecto = [
                        'PrjCode' => $query[$i]->PrjCode,
                        'PrjName' => $query[$i]->PrjName
                    ];
                }
            }
        } else {
            $proyecto = [0];
        }

        $query = $this->db->query("SELECT 
        T0.*,
        T1.gasto_name,
        t1.id_gasto as gasto_mat
        FROM 
        tbl_gas_gastos T0 LEFT JOIN 
        tbl_gas_tipo_gastos T1 on T0.tipo_gasto=T1.id_gasto 
        WHERE 
        T0.id_sol=? and 
        T0.status=1", [$sol_id]);

        $gastos = $query->getResult();

        $result = [
            'detalles' => $detalles,
            'nombre_solicitante' => $nombre_solicitante->nombre,
            'aprobador' => $aprobador->nombre,
            'proyecto' => $proyecto[0],
            'gastos' => $gastos
        ];

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getTipoGasto()
    {
        $query = $this->db->query("SELECT id_gasto,gasto_name FROM tbl_gas_tipo_gastos WHERE status=1");
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function subirXML()
    {
        $inf_id = $this->request->getPost()['inf_id'];
        $user_id = $this->request->getPost()['user_id'];
        $archivoXML = $this->request->getFile('archivoXML');;
        if ($archivoXML->isValid() && !$archivoXML->hasMoved()) {
            if ($archivoXML->getExtension() == 'xml') {
                $time = time();
                $arr_find = array(" ", "Á", "É", "Í", "Ó", "Ú", "Ü", "á", "é", "í", "ó", "ú", "ü", "Ñ", "ñ", ",", "$", "#", "%", "&", "/", "*");
                $arr_replace = array("_", "A", "E", "I", "O", "U", "U", "a", "e", "i", "o", "u", "u", "N", "n", "_", "_", "_", "_", "_", "_", "_");
                $filename = $time . "_";
                $filename .= str_replace($arr_find, $arr_replace, $archivoXML->getName());
                if ($archivoXML->move("files/recursos/gastos/comprobacion", $filename)) {

                    if ($archivo = simplexml_load_file('files/recursos/gastos/comprobacion/' . $filename)) {
                        // SI SE PUDO LEER EL XML
                        $ns = $archivo->getNamespaces(true);
                        $archivo->registerXPathNamespace('c', $ns['cfdi']);
                        $archivo->registerXPathNamespace('t', $ns['tfd']);
                        /*if (isset($ns['implocal'])) {
                              $ish=1;
                              $archivo->registerXPathNamespace('i', $ns['implocal']);
                            }else{
                              $ish=0;
                              $ish_importe=0;
                            }*/

                        // SACAR EL UUID PARA VER SI YA ESTÁ REGISTRADO
                        foreach ($archivo->xpath('//t:TimbreFiscalDigital') as $tfd) {
                            if ($tfd['UUID']) {
                                $fac_ComplementoUUID = $tfd['UUID'];

                                $query = $this->db->query("SELECT COUNT(fac_id) AS cant from tbl_tur_facXML where fac_ComplementoUUID=? and fac_registrado = 1 ", [$fac_ComplementoUUID]);
                                $result = $query->getResult()[0];
                                $cantidad1 = $result->cant;

                                $query = $this->db->query("SELECT COUNT(fac_id) AS cant from tbl_gas_fac_xml where uuid=? and registrado = 1", [$fac_ComplementoUUID]);
                                $result = $query->getResult()[0];
                                $cantidad2 = $result->cant;

                                $cantidad = $cantidad1 + $cantidad2;

                                if ($cantidad == 0) {

                                    // REVISAR EL RFC RECEPTOR PARA VERIFICAR QUE LA FACTURA PERTENECE A ECN

                                    foreach ($archivo->xpath('//cfdi:Comprobante') as $cfdiComprobante) {
                                        if ($cfdiComprobante['version']) {
                                            $fac_version = $cfdiComprobante['version'];
                                        } else {
                                            $fac_version = $cfdiComprobante['Version'];
                                        }
                                    }

                                    foreach ($archivo->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor) {
                                        if ($fac_version == 4.0) {

                                            if ($Receptor['Rfc']) {
                                                if ($Receptor['Rfc'] == "") {
                                                    // RFC RECEPTOR NO ENCONTRADO
                                                    $rfc_receptor = "X";
                                                } else {
                                                    $rfc_receptor = $Receptor['Rfc'];
                                                }
                                            } else {
                                                // RFC RECEPTOR NO ENCONTRADO
                                                $rfc_receptor = "X";
                                            }
                                        } elseif ($fac_version == 3.3) {

                                            if ($Receptor['Rfc']) {
                                                if ($Receptor['Rfc'] == "") {
                                                    // RFC RECEPTOR NO ENCONTRADO
                                                    $rfc_receptor = "X";
                                                } else {
                                                    $rfc_receptor = $Receptor['Rfc'];
                                                }
                                            } else {
                                                // RFC RECEPTOR NO ENCONTRADO
                                                $rfc_receptor = "X";
                                            }
                                        }
                                    }


                                    if ($rfc_receptor == "ECN910416TV2") {
                                        // SI PERTENECE A ECN LA FACTURA

                                        // DEPENDIENDO DE LA VERSION, LLAMARÉ EL ESQUEMA REQUERIDO PARA VALIDAR
                                        if ($fac_version == 4.0) {
                                            $esquema_xsd = "files/recursos/gastos/informes/cfdv40.xsd";
                                        } elseif ($fac_version == 3.3) {
                                            $esquema_xsd = "files/recursos/gastos/informes/cfdv33.xsd";
                                        }

                                        // VALIDAR EL ARCHIVO CON EL ESQUEMA CORRESPONDIENTE
                                        // $xml = new \DOMDocument();
                                        // $xml->load('files/recursos/gastos/comprobacion/' . $filename);


                                        /*if (!$xml->schemaValidate($esquema_xsd)) {
                                                $error = libxml_display_errors();
                
                                                
                                                if ($error==1845 || $error==1871) {
                                                    // ERROR DE IMPORTACION EN ADENDAS U OTROS TAGS EN COMPLEMENTO INNECESARIOS - CONTINUA CON LA LECTURA E INSERCION
                                                    $array_insert=LeerXML($filename,$fac_version,$inf_id);
                                                    InsertXML($array_insert,$filename,$user_id);
                
                                                }else{
                                                    // ERROR FALTA ALGUN TAG O ALGUN ATRIBUTO EN EL XML SUBIDO
                                                    // echo 0.4
                                                    echo json_encode(array(0,"Error al leer el xml, falta un valor requerido"));
                                                }
                
                                            }else{*/

                                        // ESQUEMA VALIDO - CONTINUA CON LA LECTURA E INSERCION
                                        $array_insert = $this->LeerXML($filename, $fac_version, $inf_id);
                                        $valores = $this->InsertXML($array_insert, $filename, $user_id);

                                        $fac_id = json_decode($valores)->b;
                                        $id_anexo = json_decode($valores)->a;
                                        $query = $this->db->query("SELECT fac_fecha,lugar_expedicion,emisor_rfc,emisor_nombre,total_impuestos_trasladados,
                                    fac_subtotal,fac_moneda FROM tbl_gas_fac_xml WHERE fac_id=?", [$fac_id]);
                                        $result = $query->getResult()[0];

                                        switch ($result->fac_moneda) {
                                            case 'MXN':
                                                $result->fac_moneda = 1;
                                                break;
                                            case 'USD':
                                                $result->fac_moneda = 2;
                                                break;
                                            case 'EUR':
                                                $result->fac_moneda = 3;
                                                break;
                                            case 'SOL':
                                                $result->fac_moneda = 4;
                                                break;
                                        }

                                        $result->fac_id = $fac_id;
                                        $result->id_anexo = $id_anexo;

                                        return $this->response->setStatusCode(200)->setJSON($result);
                                    } else {
                                        // LA FACTURA NO PERTENECE A ECN
                                        //echo 0.6;
                                        unlink('files/recursos/gastos/comprobacion/' . $filename);
                                        return $this->response->setStatusCode(200)->setJSON(['error' => 'Error, esta factura no pertenece a ECN']);
                                    }
                                } else {

                                    // LA FACTURA YA ESTÁ REGISTRADA
                                    if ($cantidad1 >= 1) {
                                        // EL ARCHIVO YA SE ENCUENTRA REGISTRADO EN INTRANET EN VIATICOS
                                        $query = $this->db->query("SELECT TOP 1 t0.fac_prov_fechaCreacion as fecha,t1.nombres+' '+t1.apellido_p+' '+t1.apellido_m as colaborador
                                                    FROM tbl_tur_facXML t0 left join Colaboradores t1 on t0.user_id = t1.id_colaborador
                                                    WHERE t0.fac_ComplementoUUID=? and fac_registrado = 1
                                                    order by t0.fac_id desc", [$fac_ComplementoUUID]);
                                        $result = $query->getResult()[0];
                                        $fecha_creacion = date("d/m/Y", strtotime($result->fecha));
                                        $colaborador = $result->colaborador;
                                        $mensaje = "Error, éste archivo ya ha sido registrado por " . $colaborador . " el día " . $fecha_creacion;
                                    } elseif ($cantidad2 >= 1) {
                                        $query = $this->db->query("SELECT TOP 1 
                                                      t0.create_date as fecha,
                                                      t1.nombres+' '+t1.apellido_p+' '+t1.apellido_m as colaborador
                                                     FROM 
                                                     tbl_gas_fac_xml t0 left join 
                                                     Colaboradores t1 on t0.user_id = t1.id_colaborador
                                                     WHERE 
                                                     t0.uuid=? and 
                                                     T0.registrado = 1
                                                         order by t0.fac_id desc", [$fac_ComplementoUUID]);
                                        $result = $query->getResult()[0];

                                        $fecha_creacion = date("d/m/Y", strtotime($result->fecha));
                                        $colaborador = $result->colaborador;
                                        $mensaje = "Error, éste archivo ya ha sido registrado por " . $colaborador . " el día " . $fecha_creacion;
                                    }
                                    unlink('files/recursos/gastos/comprobacion/' . $filename);
                                    return $this->response->setStatusCode(200)->setJSON(['error' => $mensaje]);
                                }
                            } else {
                                // NO TIENE UUID
                                //echo 0.3;
                                unlink('files/recursos/gastos/comprobacion/' . $filename);
                                return $this->response->setStatusCode(200)->setJSON(['error' => 'Error, esta factura no es válida']);
                            }
                        }
                    } else {
                        // NO SE PUDO LEER EL XML
                        //echo 0.2;
                        unlink('files/recursos/gastos/comprobacion/' . $filename);
                        return $this->response->setStatusCode(200)->setJSON(['error' => 'La factura no se puede leer, error en la estructura del xml']);
                    }
                }
            } else {
                return $this->response->setStatusCode(200)->setJSON(['error' => 'Favor de seleccionar un archivo XML válido']);
            }
        }

        //print_r($nombre_archivo);
    }

    function LeerXML($filename, $version, $id_inf)
    {
        $file_xml = simplexml_load_file('files/recursos/gastos/comprobacion/' . $filename);
        $ns = $file_xml->getNamespaces(true);
        $file_xml->registerXPathNamespace('c', $ns['cfdi']);
        $file_xml->registerXPathNamespace('t', $ns['tfd']);
        $array_insert = array();
        $find = array(chr(39), chr(167));

        $array_insert['inf_id'] = $id_inf;

        // IMPUESTO DE HOSPEDAJE
        if (isset($ns['implocal'])) {
            $ish = 1;
            $file_xml->registerXPathNamespace('i', $ns['implocal']);

            if ($file_xml->xpath('//i:ImpuestosLocales')) {
                foreach ($file_xml->xpath('//i:ImpuestosLocales') as $ish) {
                    if ($ish['TotaldeTraslados']) {
                        if ($ish['TotaldeTraslados'] == "") {
                            $ish_importe = 0;
                        } else {
                            $ish_importe = strval($ish['TotaldeTraslados']);
                        }
                    } else {
                        $ish_importe = 0;
                    }
                }
            } else {
                $ish_importe = 0;
            }
        } else {
            $ish = 0;
            $ish_importe = 0;
        }

        $array_insert['no_referencia'] = $ish_importe;

        // GUARDAR TODOS LOS VALORES DE LOS TAGS REQUERIDOS EN EL ARRAY

        $query = $this->db->query("SELECT * FROM tbl_cfdi_esquema WHERE task_version=? and status=1", [$version]);
        $result = $query->getResult();
        for ($i = 0; $i < count($result); $i++) {
            $tag_name = $result[$i]->tag_name;
            $parent = $result[$i]->parent;
            $ruta = $result[$i]->ruta;
            $campo = $result[$i]->campo2;

            if ($file_xml->xpath($ruta)) {

                foreach ($file_xml->xpath($ruta) as $parent) {

                    if ($parent[$tag_name]) {
                        $array_insert[$campo] = str_replace($find, chr(32), strval($parent[$tag_name]));
                    } else {
                        $array_insert[$campo] = '';
                    }
                }
            } else {
                $array_insert[$campo] = '';
            }
        }

        // SACAR EL IEPS DE LA FACTURA

        $impuestos_trasladados = $array_insert['total_impuestos_trasladados'];
        $subtotal = $array_insert['fac_subtotal'];
        $base = (floatval($impuestos_trasladados)) / (0.16);
        $ieps = (floatval($subtotal) - floatval($base));
        $array_insert['orden_compra'] = $ieps;

        // Cambiar los valores de los campos en blanco

        if ($array_insert['fac_folio'] == "" || is_null($array_insert['fac_folio'])) {
            $array_insert['fac_folio'] = "Xml sin folio";
        }

        if ($array_insert['fac_moneda'] == "" || is_null($array_insert['fac_moneda'])) {
            $array_insert['fac_moneda'] = "MXN";
        }

        if ($array_insert['emisor_nombre'] == "" || is_null($array_insert['emisor_nombre'])) {
            $array_insert['emisor_nombre'] = "Sin nombre";
        }

        if ($array_insert['receptor_nombre'] == "" || is_null($array_insert['receptor_nombre'])) {
            $array_insert['receptor_nombre'] = "Sin nombre";
        }

        if ($array_insert['total_impuestos_trasladados'] == "" || is_null($array_insert['total_impuestos_trasladados'])) {
            $array_insert['total_impuestos_trasladados'] = 0;
        }

        if ($array_insert['total_impuestos_retenidos'] == "" || is_null($array_insert['total_impuestos_retenidos'])) {
            $array_insert['total_impuestos_retenidos'] = 0;
        }



        // BUSCAR EL LUGAR DE EXPEDICIÒN SEGUN EL CODIGO POSTAL
        if ($version == 3.3) {
            $fac_LugarExpedicion = $array_insert['lugar_expedicion'];

            $query = $this->db->query("SELECT Municipio+', '+Estado as LugarExpedicion FROM CodigosPostales WHERE CodigoPostal=?", [$fac_LugarExpedicion]);
            $result = $query->getResult()[0];
            $fac_LugarExpedicion = $result->LugarExpedicion;
            $array_insert['lugar_expedicion'] = $fac_LugarExpedicion;
        }


        return $array_insert;
    }

    function InsertXML($array_insert, $filename, $user_id)
    {

        $array_insert['user_id'] = $user_id;

        $key = array_keys($array_insert);

        $sql = "INSERT INTO tbl_gas_fac_xml (";

        $fac_ComplementoUUID = $array_insert['uuid'];

        for ($i = 0; $i <= sizeof($array_insert) - 1; $i++) {

            $field = $key[$i];

            if ($i == sizeof($array_insert) - 1) {
                $sql .= "{$field})";
            } else {
                $sql .= "{$field},";
            }
        }

        $sql .= "VALUES (";

        for ($i = 0; $i <= sizeof($array_insert) - 1; $i++) {

            $field = $key[$i];
            $valor = $array_insert[$field];

            if ($i == sizeof($array_insert) - 1) {
                $sql .= "'{$valor}')";
            } else {
                $sql .= "'{$valor}',";
            }
        }

        // INSERT DEL XML ANEXO
        $query = $this->db->query("EXEC sp_insert_gas_anx_07032018 '{$filename}', 2, {$user_id}");
        $id_xml = $query->getResult()[0]->id;

        // INSERT DE LA INFO DEL XML LEIDO
        $this->db->query($sql);



        // SELECT DEL ID DE LA INFO INSERTADA
        $query = $this->db->query("SELECT TOP 1 fac_id FROM tbl_gas_fac_xml WHERE uuid=? order by fac_id desc", [$fac_ComplementoUUID]);
        $id_fac = $query->getResult()[0]->fac_id;

        $valores = array(1, "a" => $id_xml, "b" => $id_fac);

        return json_encode($valores);
    }

    public function cancelGasto()
    {
        $fac_id = $this->request->getJSON()->fac_id;
        $id_anexo = $this->request->getJSON()->id_anexo;

        $this->db->query("DELETE FROM tbl_gas_fac_xml WHERE fac_id = ?", [$fac_id]);

        $query = $this->db->query("SELECT anexo_nombre FROM tbl_gas_anexos WHERE id_anexo = ?", [$id_anexo]);
        $archivo = $query->getResult()[0]->anexo_nombre;

        if (fileExists('files/recursos/gastos/comprobacion/' . $archivo)) {
            unlink('files/recursos/gastos/comprobacion/' . $archivo);
        }

        $this->db->query("DELETE FROM tbl_gas_anexos WHERE id_anexo = ?", [$id_anexo]);

        return $this->response->setStatusCode(200);
    }

    //guardar comprobacion de gasto
    public function comprobarGasto()
    {
        $post = $this->request->getPost();
        $datos = json_decode($post['datos']);
        $ids = json_decode($post['ids']);

        $tipoComprobante = $ids->tipoComprobante;

        if ($tipoComprobante == 1) {
            $archivoPDF = $this->request->getFile('archivoPDF');;
            if ($archivoPDF->isValid() && !$archivoPDF->hasMoved()) {
                if ($archivoPDF->getExtension() == 'pdf') {
                    $time = time();
                    $arr_find = array(" ", "Á", "É", "Í", "Ó", "Ú", "Ü", "á", "é", "í", "ó", "ú", "ü", "Ñ", "ñ", ",", "$", "#", "%", "&", "/", "*");
                    $arr_replace = array("_", "A", "E", "I", "O", "U", "U", "a", "e", "i", "o", "u", "u", "N", "n", "_", "_", "_", "_", "_", "_", "_");
                    $filename = $time . "_";
                    $filename .= str_replace($arr_find, $arr_replace, $archivoPDF->getName());
                    if ($archivoPDF->move("files/recursos/gastos/comprobacion", $filename)) {
                        $query = $this->db->query("EXEC sp_insert_gas_anx_07032018 '{$filename}',1,{$ids->user_id}");
                        $id_pdf = $query->getResult()[0]->id;

                        $total = $datos->subtotal + $datos->iva;

                        $query = $this->db->query("EXEC sp_insert_gas_gasto_07032018
                        {$datos->tipo_gasto},
                        '{$datos->fecha_transaccion}',
                        '{$datos->cd_compra}',
                        '{$datos->rfc}',
                        '{$datos->razon_social}',
                        {$datos->subtotal},
                        {$datos->iva},
                        {$total},
                        {$datos->moneda},
                        '{$datos->comentarios}',
                        {$ids->inf_id},
                        {$tipoComprobante},
                        0,
                        {$ids->user_id}
                        ");

                        $id_gasto = $query->getResult()[0]->id;

                        $this->db->query("UPDATE tbl_gas_anexos SET gasto_id=?
                        WHERE id_anexo=? or id_anexo=?", [
                            $id_gasto,
                            $id_pdf,
                            $ids->id_xml
                        ]);

                        $this->db->query("UPDATE tbl_gas_fac_xml SET registrado=1, gasd_id=?
                        WHERE fac_id=?", [
                            $id_gasto,
                            $ids->fac_id
                        ]);
                    }
                }
            }
        } else {
            $archivoIMG = $this->request->getFile('archivoIMG');;
            if ($archivoIMG->isValid() && !$archivoIMG->hasMoved()) {
                if ($archivoIMG->getExtension() == 'jpg' || $archivoIMG->getExtension() == 'jpeg' || $archivoIMG->getExtension() == 'png' || $archivoIMG->getExtension() == 'pdf') {
                    $time = time();
                    $arr_find = array(" ", "Á", "É", "Í", "Ó", "Ú", "Ü", "á", "é", "í", "ó", "ú", "ü", "Ñ", "ñ", ",", "$", "#", "%", "&", "/", "*");
                    $arr_replace = array("_", "A", "E", "I", "O", "U", "U", "a", "e", "i", "o", "u", "u", "N", "n", "_", "_", "_", "_", "_", "_", "_");
                    $filename = $time . "_";
                    $filename .= str_replace($arr_find, $arr_replace, $archivoIMG->getName());
                    if ($archivoIMG->move("files/recursos/gastos/comprobacion", $filename)) {
                        $query = $this->db->query("EXEC sp_insert_gas_anx_07032018 '{$filename}',1,{$ids->user_id}");
                        $id_img = $query->getResult()[0]->id;

                        $total = $datos->subtotal + $datos->iva;

                        $query = $this->db->query("EXEC sp_insert_gas_gasto_07032018
                        {$datos->tipo_gasto},
                        '{$datos->fecha_transaccion}',
                        '{$datos->cd_compra}',
                        '{$datos->rfc}',
                        '{$datos->razon_social}',
                        {$datos->subtotal},
                        {$datos->iva},
                        {$total},
                        {$datos->moneda},
                        '{$datos->comentarios}',
                        {$ids->inf_id},
                        {$tipoComprobante},
                        0,
                        {$ids->user_id}
                        ");

                        $id_gasto = $query->getResult()[0]->id;

                        $this->db->query("UPDATE tbl_gas_anexos SET gasto_id=?
                        WHERE id_anexo=?", [
                            $id_gasto,
                            $id_img
                        ]);
                    }
                }
            }
        }

        return $this->response->setStatusCode(200);
    }

    //guardar o modificar gasto desde solicitud
    public function saveGasto()
    {
        $datos = $this->request->getJSON();
        if ($datos->id_gasto != 0) {
            $this->db->query("UPDATE tbl_gas_gastos SET tipo_gasto=?,cantidad=?,precio_unitario=?,total=?,descripcion=?
            WHERE id_gasto = ?", [
                $datos->tipo_gasto,
                $datos->cantidad,
                $datos->precio_unitario,
                $datos->cantidad * $datos->precio_unitario,
                $datos->descripcion,
                $datos->id_gasto
            ]);
        } else {
            $this->db->query("INSERT INTO tbl_gas_gastos (id_sol,tipo_gasto,cantidad,precio_unitario,total,descripcion,create_user)
        VALUES (?,?,?,?,?,?,?)", [
                $datos->sol_id,
                $datos->tipo_gasto,
                $datos->cantidad,
                $datos->precio_unitario,
                $datos->cantidad * $datos->precio_unitario,
                $datos->descripcion,
                $datos->create_user,
            ]);
        }

        return $this->response->setStatusCode(200);
    }

    public function deleteGasto()
    {
        $id_gasto = $this->request->getJSON()->id_gasto;

        $this->db->query("DELETE FROM tbl_gas_gastos WHERE id_gasto = ?", [$id_gasto]);

        return $this->response->setStatusCode(200);
    }

    public function enviarRevision()
    {
        $sol_id = $this->request->getJSON()->sol_id;
        $query = $this->db->query("SELECT sol_id_solicitante,sol_aprobador FROM tbl_gas_solicitudes WHERE sol_id=?", [$sol_id]);
        $result = $query->getResult()[0];

        $sol_aprobador = $result->sol_aprobador;
        $sol_id_solicitante = $result->sol_id_solicitante;

        // EL MISMO SE APRUEBA
        if ($sol_id_solicitante == $sol_aprobador) {
            $query = $this->db->query("UPDATE tbl_gas_solicitudes SET sol_estado=3,sol_coment_lider='Solicitud aprobada',date_aprobacion_lider=GETDATE()
                WHERE sol_id=?", [$sol_id]);
            $afinanzas = true;

            // LO APRUEBA ALGUIEN MAS
        } else {
            $query = $this->db->query("UPDATE tbl_gas_solicitudes SET sol_estado=2 WHERE sol_id=?", [$sol_id]);
            $afinanzas = false;
        }

        $result = [
            'afinanzas' => $afinanzas
        ];

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function crearInforme()
    {
        $sol_id = $this->request->getJSON()->sol_id;
        $departamento = $this->request->getJSON()->departamento;

        if ($departamento == 17 || $departamento == 7) {

            $inf_folio = "FP-0" . $sol_id;
        } elseif ($departamento == 18 || $departamento == 8) {

            $inf_folio = "FS-0" . $sol_id;
        } elseif ($departamento == 13 || $departamento == 5) {

            $inf_folio = "GF-0" . $sol_id;
        } else {

            $inf_folio = "FU-0" . $sol_id;
        }

        $this->db->query("INSERT INTO tbl_gas_informes (sol_id,inf_folio) VALUES (?,?)", [
            $sol_id,
            $inf_folio
        ]);

        return $this->response->setStatusCode(200);
    }

    public function getAdeudos()
    {
        $id_colaborador = $this->request->getJSON()->id_colaborador ? $this->request->getJSON()->id_colaborador : 0;
        $query = $this->db->query("SELECT 
        t1.sol_nombre,
        t0.inf_folio,
        t2.nombres+' '+t2.apellido_p as solicitante,
        t2.id_departamentos,
        t2.id_region,
        t4.monto,
        IIF(t4.comprobado=1,'Si','No') as comprobado,
        case t4.estatus
         when 1 then 'Pendiente'
         when 2 then 'Enviado'
         when 3 then 'Aprobado'
         when 4 then 'Rechazado'
        end as estatus,
        t4.comprobado as comp_estatus,
        t4.id_adeudo
        FROM 
        tbl_gas_adeudos t4 left join
        tbl_gas_informes t0 on t4.inf_id=t0.inf_id left join
        tbl_gas_solicitudes t1 on t0.sol_id=t1.sol_id left join
        Colaboradores t2 on t1.sol_id_solicitante=t2.id_colaborador
        WHERE 
        t4.status=1 and
        t1.sol_id_solicitante=?
        order by
        t4.comprobado", [$id_colaborador]);
        $adeudos = $query->getResult();
        return $this->getResponse([
            "message" => "Data successfully retrieved",
            "adeudos" => $adeudos
        ]);
    }

    public function verAnexoAdeudo()
    {
        $id_adeudo = $this->request->getJSON()->id_adeudo;
        $query = $this->db->query("SELECT id_anexo,anexo_nombre FROM tbl_gas_adeudos_anx WHERE id_adeudo=? AND status=1",[$id_adeudo]);
        $anexo = $query->getResult() ? $query->getResult()[0] : NULL;

        return $this->getResponse([
            "message" => "Data successfully retrieved",
            "anexo" => $anexo
        ]);
    }

    public function saveAnexoAdeudo()
    {
        $anexoAdeudo = $this->request->getFile('anexoAdeudo');
        $id_adeudo = $this->request->getpost('id_adeudo');
        if ($anexoAdeudo->isValid() && !$anexoAdeudo->hasMoved()) {
            if ($anexoAdeudo->getExtension() == 'jpg' || $anexoAdeudo->getExtension() == 'jpeg' || $anexoAdeudo->getExtension() == 'png') {
                $time = time();
                $arr_find = array(" ", "Á", "É", "Í", "Ó", "Ú", "Ü", "á", "é", "í", "ó", "ú", "ü", "Ñ", "ñ", ",", "$", "#", "%", "&", "/", "*");
                $arr_replace = array("_", "A", "E", "I", "O", "U", "U", "a", "e", "i", "o", "u", "u", "N", "n", "_", "_", "_", "_", "_", "_", "_");
                $filename = $time . "_";
                $filename .= str_replace($arr_find, $arr_replace, $anexoAdeudo->getName());
                if ($anexoAdeudo->move("files/recursos/gastos/anexosAdeudos", $filename)) {
                    $result1 = $this->db->query("INSERT INTO tbl_gas_adeudos_anx (anexo_nombre,id_adeudo) VALUES (?,?)",[$filename,$id_adeudo]);
                    if($result1){
                        $result2 = $this->db->query("UPDATE tbl_gas_adeudos SET comprobado=1, estatus = 2 WHERE id_adeudo=?",[$id_adeudo]);
                    }
                    if($result1 && $result2){
                        return $this->getResponse([
                            "message" => "Data successfully saved"
                        ]);
                    }else{
                        return $this->getResponse([
                            "error" => "Hubo un error al actualizar los datos. Intente de nuevo."
                        ]);
                    }
                }
            }else{
                return $this->getResponse([
                    "error" => "Favor de subir una imagen jpg, jpeg, png."
                ]);
            }
        }else{
            return $this->getResponse([
                "error" => "El archivo no es válido."
            ]);
        }
    }
}
