<?php

namespace App\Controllers;

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Finanzas extends BaseController{

    private $db;
    //private $sapServer;

    private $from = "intranet@ecnautomation.com";

    public function __construct(){
        $this->db = db_connect();
        //$this->sapServer = db_connect('sapServer');
        //date_default_timezone_set("America/Hermosillo");
    }

    //////////////////////////
    // SOLICITUDES VIATICOS //
    //////////////////////////

    
    // MODULO: SOLICITUDES REGISTRADAS

    // Listado de solicitudes pendientes
    public function SR_getSolicitudesFinanzas_Pendientes(){
        $json = $this->request->getJSON();
        $search = $json->search;
        // {CALL sp_GET_ecnTur_SolicitudesFin_19042016() }
        $query1 = "  SELECT 
                        t0.* ,
                        t1.nombres+' '+t1.apellido_p AS nombre,
                        t2.nombres+' '+t2.apellido_p AS nombreAprob,
                        (SELECT SUM(con_TOTAL) FROM tbl_conceptos WHERE con_sol_id = t0.sol_id) AS sumaTotal,
                        t1.id_departamentos,
                        t1.id_region,
                        IIF(T0.sol_proyectoServicioOpcion = '0','',T0.sol_proyectoServicioOpcion) AS  proy,
                        t0.sol_motivo,
                        IIF(t0.sol_cuentaDePago = 1,'Personal','Empresarial') AS pago,
                        CASE T1.ID_DEPARTAMENTOS
                            WHEN 17 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 7 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 18 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 8 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 13 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 5 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                            ELSE 'FU'+CAST(t0.sol_id AS VARCHAR)
                        END AS codigo,
                        ISNULL(t1.cuenta_bbva, '') AS 'bbva',
                        ISNULL(t1.clabe, '') AS 'cuentabancaria',
                        FORMAT(t0.sol_fecha_aprobacion, 'dd/MM/yyyy') AS sol_fecha_aprobacion_n,
                        FORMAT(CAST(t0.sol_hr_aprobacion_lider AS datetime2), N'hh:mm tt') AS sol_hr_aprobacion_lider_n
                    FROM 
                        tbl_tur_solicitudes t0 LEFT JOIN
                        Colaboradores t1 ON t0.sol_nombre_solicitante=t1.id_colaborador LEFT JOIN
                        Colaboradores t2 ON t0.sol_jefeDirecto=t2.id_colaborador
                    WHERE 
                        (t0.sol_retenida =0 OR t0.sol_retenida=1) AND 
                        t0.sol_estatus=1 AND 
                        t0.sol_estado=3 AND
                        (
                            t0.sol_nombre_solicitud LIKE ('%$search%') OR
                            (t1.nombres+' '+t1.apellido_p) LIKE ('%$search%') OR
                            (t2.nombres+' '+t2.apellido_p) LIKE ('%$search%')
                        )
                    ORDER BY t0.sol_fecha_aprobacion DESC;";
        $return1 = $this->db->query($query1)->getResult();

        return $this->response->setStatusCode(200)->setJSON($return1);
    }
    // Listado de solicitudes aprobadas
    public function SR_getSolicitudesFinanzas_Aprobadas(){
        $json = $this->request->getJSON();
        $search = $json->search;
        // {CALL sp_GET_ecnTur_SolicitudesFin5_27042016() }
        $query = "  SELECT 
                        t0.* ,
                        t1.nombres+' '+t1.apellido_p AS nombre,
                        t2.nombres+' '+t2.apellido_p AS nombreAprob,
                        (SELECT SUM(con_TOTAL) FROM tbl_conceptos WHERE con_sol_id = t0.sol_id) AS sumaTotal,
                        t1.id_departamentos,
                        t1.id_region,
                        IIF(T0.sol_proyectoServicioOpcion = '0','',T0.sol_proyectoServicioOpcion) AS  proy,
                        t0.sol_motivo,
                        IIF(t0.sol_cuentaDePago = 1,'Personal','Empresarial') AS pago,
                        CASE T1.ID_DEPARTAMENTOS
                            WHEN 17 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 7 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 18 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 8 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 13 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 5 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                            ELSE 'FU'+CAST(t0.sol_id AS VARCHAR)
                        END AS codigo,
                        ISNULL(t1.cuenta_bbva, '') AS 'bbva',
                        ISNULL(t1.clabe, '') AS 'cuentabancaria',
                        FORMAT(t0.sol_fecha_aprobacion, 'dd/MM/yyyy') AS sol_fecha_aprobacion_n,
                        FORMAT(CAST(t0.sol_hr_aprobacion_lider AS datetime2), N'hh:mm tt') AS sol_hr_aprobacion_lider_n
                    FROM 
                        tbl_tur_solicitudes t0 LEFT JOIN
                        Colaboradores t1 ON t0.sol_nombre_solicitante=t1.id_colaborador LEFT JOIN
                        Colaboradores t2 ON t0.sol_jefeDirecto=t2.id_colaborador
                    WHERE
                        t0.sol_retenida = 2 AND 
                        t0.sol_estatus = 1 AND
                        (
                            t0.sol_nombre_solicitud LIKE ('%$search%') OR
                            (t1.nombres+' '+t1.apellido_p) LIKE ('%$search%') OR
                            (t2.nombres+' '+t2.apellido_p) LIKE ('%$search%')
                        )
                    ORDER BY t0.sol_fecha_aprobacion DESC";
        $return = $this->db->query($query)->getResult();
        return $this->response->setStatusCode(200)->setJSON($return);
    }
    // Listado de solicitudes depositadas
    public function SR_getSolicitudesFinanzas_Depositadas(){
        $json = $this->request->getJSON();
        $search = $json->search;
        if(empty($this->db->connID))
            $this->db->initialize();
        // {CALL sp_GET_ecnTur_SolicitudesFin4_27042016() }
        $query = "  SELECT 
                        t0.* ,
                        t1.nombres+' '+t1.apellido_p AS nombre,
                        t2.nombres+' '+t2.apellido_p AS nombreAprob,
                        (SELECT SUM(con_TOTAL) FROM tbl_conceptos WHERE con_sol_id = t0.sol_id) AS sumaTotal,
                        t1.id_departamentos,
                        t1.id_region,
                        IIF(T0.sol_proyectoServicioOpcion = '0','',T0.sol_proyectoServicioOpcion) AS  proy,
                        t0.sol_motivo,
                        IIF(t0.sol_cuentaDePago = 1,'Personal','Empresarial') AS pago,
                        CASE T1.ID_DEPARTAMENTOS
                            WHEN 17 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 7 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 18 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 8 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 13 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 5 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                            ELSE 'FU'+CAST(t0.sol_id AS VARCHAR)
                        END AS codigo,
                        ISNULL(t1.cuenta_bbva, '') AS 'bbva',
                        ISNULL(t1.clabe, '') AS 'cuentabancaria',
                        FORMAT(t0.sol_fecha_aprobacion, 'dd/MM/yyyy') AS sol_fecha_aprobacion_n,
                        FORMAT(CAST(t0.sol_hr_aprobacion_lider AS datetime2), N'hh:mm tt') AS sol_hr_aprobacion_lider_n
                    FROM 
                        tbl_tur_solicitudes t0 LEFT JOIN
                        Colaboradores t1 ON t0.sol_nombre_solicitante=t1.id_colaborador LEFT JOIN
                        Colaboradores t2 ON t0.sol_jefeDirecto=t2.id_colaborador
                    WHERE 
                        t0.sol_retenida = 3 AND 
                        t0.sol_estatus = 1 AND  
                        t0.sol_fechaElaboracion>='2018-10-01' AND
                        (
                            t0.sol_nombre_solicitud LIKE ('%$search%') OR
                            (t1.nombres+' '+t1.apellido_p) LIKE ('%$search%') OR
                            (t2.nombres+' '+t2.apellido_p) LIKE ('%$search%')
                        )
                    ORDER BY t0.sol_fecha_aprobacion DESC;";
        
        $result = sqlsrv_query($this->db->connID, $query);

        $depositadas = array();
        while($row = sqlsrv_fetch_array($result)){
            $nombreAprob = $row['nombreAprob'];
			$departamento = $row['sol_departamentos_id'];
			$region = $row['sol_geografica_id'];
            $sol_id = $row['sol_id'];
			$sumaTotal = $row['sumaTotal'];
            // $solicitante = $row['sol_nombre_solicitante'];
			$solicitante = $row['nombre'];
			$sol_fecha_aprobacion=$row['sol_fecha_aprobacion_n'];
			$sol_hr_aprobacion_lider=$row['sol_hr_aprobacion_lider_n'];
			$sol_nombre_solicitud = $row['sol_nombre_solicitud'];
			$proy = $row['proy'];
			$sol_retenida = $row['sol_retenida'];
			$sol_motivo = $row['sol_motivo'];
			$pago = $row['pago'];
			$codigo=$row['codigo'];

            
            $depositadas[] = array(
                "sol_id" => $sol_id,
                "nombreAprob" => $nombreAprob,
                "nombre" => $solicitante,
                "sol_departamentos_id" => $departamento,
                "sol_geografica_id" => $region,
                "sumaTotal" =>  $sumaTotal,
                "sol_fecha_aprobacion_n" => $sol_fecha_aprobacion,
                "sol_hr_aprobacion_lider_n" => $sol_hr_aprobacion_lider,
                "sol_nombre_solicitud" => $sol_nombre_solicitud,
                "proyecto" => $proy,
                "sol_retenida" => $sol_retenida,
                "sol_motivo" => $sol_motivo,
                "pago" => $pago,
                "codigo" => $codigo
            );
            
        }
        return $this->response->setStatusCode(200)->setJSON($depositadas);
    }

    // Actualizaciones y notificaciones dependiendo de la acción a realizar.
    public function SR_updateSolicitudesFinanzas_acciones(){
        $json = $this->request->getJSON();

        $usuario = $json->usuario;
        $tipo = $json->tipo;
        $id = $json->sol_id;
        $comment = $json->comment;

        // { CALL sp_update_ecnTur_RetencionSol_20042016(?,?,?)}
        $query1 = "";

        $result = 0;
        switch($tipo){
            case 1: // Depositar
                // { CALL sp_update_ecnTur_RetencionSol_20042016(?,?,?) }
                $query1 = " UPDATE tbl_tur_solicitudes 
                            SET 
                                sol_retenida = 2, 
                                sol_fecha_deposito = GETDATE(), 
                                sol_comentarios_reten = '$comment' 
                            WHERE sol_id = $id;";

                if($this->db->query($query1)){
                //if(true){
                    $query2 = "SELECT sol_nombre_solicitante, FORMAT(sol_fechaElaboracion, 'dd-MM-yyyy') AS fechaElaboracion, ISNULL(sol_comentarios_reten, '') AS sol_comentarios_reten FROM tbl_tur_solicitudes WHERE sol_id =$id;";
                    $result2 = $this->db->query($query2)->getResult();
                    $id_soli = $result2[0]->sol_nombre_solicitante;
                    $fechaElaboracion = $result2[0]->fechaElaboracion;
                    $comentarios = $result2[0]->sol_comentarios_reten;

                    $query3 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_soli;";
                    $result3 = $this->db->query($query3)->getResult();
                    $email = $result3[0]->email;
                    $nombre = $result3[0]->nombres;

                    $query4 = "SELECT * FROM tbl_conceptos WHERE con_sol_id = $id;";
                    $result4 = $this->db->query($query4)->getResult();
                    $total = 0;
                    for ($i=0; $i < sizeof($result4); $i++) { 
                        $total+=$result4[$i]->con_TOTAL;
                    }

                    $query5 = "INSERT INTO tbl_logCyV (id_registro,id_usuario,tipo_accion,id_mod) VALUES ($id,$usuario,5,2)";
                    $this->db->query($query5);
                    

                    $to = $email;
                    $subject = "Solicitud de viáticos en depósito";
                    $message ="<table>";

                    $message .="<tr><td style='width:100px;'></td><td >";
                    
                    $message .="
                                    <!DOCTYPE html>
                                    <html lang='en'>
                                    <head>
                                    <meta charset='UTF-8'>
                                    <title>Titutlo</title>
                                    </head>
                                    <body>
                                    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                    <hr>
                                    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Solicitud en depósito</h1>
                                    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>00{$id}</h1>
                                    <br>
                                    <h3 style=' font-family:Helvetica;'>Estimado {$nombre}</h3>
                                    <h4 style=' font-family:Helvetica;'>Le informamos que el monto requerido en la solicitud de viáticos que registro el dia {$fechaElaboracion} está proceso de depósito.</h4>
                                    <br>";

                    $message .= "    <br>
                                    <h3 style=' font-family:Helvetica;' >Monto a depositar:</h3><h2 style=' font-family:Helvetica;'><b>$ {$total}</b></h2>";
                    
                    $message .= "
                    <h4 style=' font-family:Helvetica;'>Comentarios: {$comentarios}</h4>  
                    <p style=' font-family:Helvetica; color:black;'>Para más información favor de contactar al departamento de finanzas.<p>
                                <hr style=' color:#D8D8D8;'>";

                    $message .="
                                <br>
                                <br>


                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>
                                
                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                
                                </td>
                                <td style='width:100px;'></td>
                                </tr> 
                                </table>";



                    $message .="
                    
                    </body>
                    </html>";
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $this->from;

                    
                    if(mail($to,$subject,$message,$headers))
                        $result = 1;
                    else
                        $result = 2;
                }
                break;
            case 2: // Retener
                // { CALL sp_update_ecnTur_RetencionSol_20042016(?,?,?) }
                $query1 = " UPDATE tbl_tur_solicitudes 
                            SET 
                                sol_retenida=1, 
                                sol_comentarios_reten='$comment'
                            WHERE sol_id = $id;";
                
                if($this->db->query($query1)){
                //if(true){
                    $query2 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id;";
                    $result2 = $this->db->query($query2)->getResult();
                    $id_soli = $result2[0]->sol_nombre_solicitante;
                    $sol_name = $result2[0]->sol_nombre_solicitud;
                    $sol_fecha = date_format(date_create($result2[0]->sol_fechaElaboracion),'d-m-Y');
                    $comentarios = $result2[0]->sol_comentarios_reten != null ? $result2[0]->sol_comentarios_reten : '';


                    $query3 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_soli";
                    $result3 = $this->db->query($query3)->getResult();
                    $email = $result3[0]->email;
                    $nombre_sol = $result3[0]->nombres;

                    $query4 = "SELECT * FROM tbl_conceptos WHERE con_sol_id =$id";
                    $result4 = $this->db->query($query4)->getResult();

                    $total_sol = 0;
                    for ($i=0; $i < sizeof($result4); $i++) { 
                        $total_sol += $result4[$i]->con_TOTAL;
                    }

                    $query5 = "INSERT INTO tbl_logCyV (id_registro,id_usuario,tipo_accion,id_mod) VALUES ($id,$usuario,4,2)";
                    $this->db->query($query5);
                    

                    $to = $email;
                    $subject = "Solicitud de viáticos retenida";
                    $message ="<table>";

                    $message .="<tr><td style='width:100px;'></td><td >";
                    $message .="
                         <!DOCTYPE html>
                          <html lang='en'>
                          <head>
                            <meta charset='UTF-8'>
                            <title>Titutlo</title>
                          </head>
                          <body>
                          <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                          <hr>
                          <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Solicitud retenida</h1>
                          <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>00{$id}</h1>
                          <br>
                          <h3 style=' font-family:Helvetica;'>Estimado {$nombre_sol}</h3>
                          <h4 style=' font-family:Helvetica;'>Le informamos la solicitud de viáticos que registro el dia {$sol_fecha} ha sido retenida por el departamento de finanzas.</h4>
                          ";

                    $message .= " <br>
                                <h3 style=' font-family:Helvetica;' >Monto retenido:</h3><h2 style=' font-family:Helvetica;'><b>$ {$total_sol}</b></h2>";

                    $message .= "
                            <h4 style=' font-family:Helvetica;'>Comentarios: {$comentarios}</h4>  
                            <p style=' font-family:Helvetica; color:black;'>Para más información favor de contactar al departamento de finanzas.<p>
                                        <hr style=' color:#D8D8D8;'>";
                    
                    $message .="
                            <br>
                            <br>


                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>
                                
                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                            
                            </td>
                            <td style='width:100px;'></td>
                                </tr> 
                            </table>";
            
            
            
                    $message .="
                            </body>
                            </html>";
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $this->from;
                    if(mail($to,$subject,$message,$headers))
                        $result = 1;
                    else 
                        $result = 2;
                }
                break;
            case 3: // ¿?
                // { CALL sp_update_ecnTur_RetencionSol_20042016(?,?,?) }
                $query = " UPDATE tbl_tur_solicitudes 
                            SET 
                                sol_oculta=1 
                            WHERE sol_id = $id";
                if($this->db->query($query))
                    $result = 1;
                break;
            case 4: // Archivar
                // { CALL sp_update_ecnTur_RetencionSol_20042016(?,?,?) }
                $query1 = " UPDATE tbl_tur_solicitudes 
                            SET 
                                sol_retenida=3,
                                sol_oculta=1,
                                sol_comentario_deposito='$comment'
                            WHERE sol_id = $id;";
                
                if($this->db->query($query1)){
                //if(true){

                    $query2 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id;";
                    $result2 = $this->db->query($query2)->getResult();
                    $id_soli = $result2[0]->sol_nombre_solicitante;
                    $sol_name = $result2[0]->sol_nombre_solicitud;
                    $sol_fecha = date_format(date_create($result2[0]->sol_fechaElaboracion),'d-m-Y');
                    $comentarios = $result2[0]->sol_comentario_deposito;

                    $query3 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_soli";
                    $result3 = $this->db->query($query3)->getResult();
                    $email = $result3[0]->email;
                    $nombre_sol = $result3[0]->nombres;
                    // $email = "ecolores93@gmail.com";

                    $query4 = "SELECT * FROM tbl_conceptos WHERE con_sol_id =$id";
                    $result4 = $this->db->query($query4)->getResult();

                    $total_sol = 0;
                    for ($i=0; $i < sizeof($result4); $i++) { 
                        $total_sol += $result4[0]->con_TOTAL;
                    }

                    $query5 = "INSERT INTO tbl_logCyV (id_registro,id_usuario,tipo_accion,id_mod) VALUES ($id,$usuario,6,2)";
                    $this->db->query($query5);

                    $to = $email;
                    $subject = "Solicitud de viáticos depositada";
                    $message ="<table>";

                    $message .="<tr><td style='width:100px;'></td><td >";
                    $message .="<!DOCTYPE html>
                                <html lang='en'>
                                <head>
                                    <meta charset='UTF-8'>
                                    <title>Titutlo</title>
                                </head>
                                <body>
                                <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                <hr>
                                <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Solicitud depositada</h1>
                                <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>00{$id}</h1>
                                <br>
                                <h3 style=' font-family:Helvetica;'>Estimado {$nombre_sol}</h3>
                                <h4 style=' font-family:Helvetica;'>Le informamos la solicitud de viáticos que registro el dia {$sol_fecha} ha sido depositada por el departamento de finanzas.</h4>";
                    $message .="<h4 style=' font-family:Helvetica;'>Comentarios: {$comentarios}</h4>  
                                <p style=' font-family:Helvetica; color:black;'>Para más información favor de contactar al departamento de finanzas.<p>
                                            <hr style=' color:#D8D8D8;'>";
                    $message .="<br><br>
            
                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>
                                    
                                    <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                
                                </td>
                                <td style='width:100px;'></td>
                                    </tr> 
                                </table>";
                    $message .="</body>
                                </html>";
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $this->from;
                    if(mail($to,$subject,$message,$headers))
                        $result = 1;
                    else
                        $result = 2;
                }
                break;
            case 5: // Eliminar
                $query = "UPDATE tbl_tur_solicitudes SET sol_estatus=0 WHERE sol_id=$id";
                if($this->db->query($query))
                    $result = 1;
                break;
            case 6: // Regresar
                $query = "UPDATE tbl_tur_solicitudes SET sol_estado=3,sol_retenida=0 WHERE sol_id=$id";
                if($this->db->query($query))
                    $result = 1;
                break;
            case 103: // Aprobar solicitud
                
                $fecha_actual = date('Y-m-d');

                // { CALL sp_update_ecnTur_AprobarSol_24022016(?,?,?) }
                $query1 = " UPDATE tbl_tur_solicitudes 
                            SET 
                                sol_estado = 3 , 
                                sol_comentarios_estado = '$comment',
                                sol_fecha_aprobacion = '$fecha_actual',
                                sol_hr_aprobacion_lider = GETDATE()
                            WHERE sol_id = $id;";
                if($this->db->query($query1)){

                    $query2 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id";
                    $result2 = $this->db->query($query2)->getResult()[0];
                    $id_sol = $result2->sol_nombre_solicitante;
                    $sol_name = $result2->sol_nombre_solicitud;
                    $sol_fecha = date_format(date_create($result2->sol_fechaElaboracion),'d-m-Y');

                    $query3 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_sol";
                    $result3 = $this->db->query($query3)->getResult()[0];
                    $email = $result3->email;
                    $nombre_sol = $result3->nombres;
                    // $email = "ecolores93@gmail.com";

                    $query4 = "SELECT * FROM tbl_conceptos WHERE con_sol_id = $id";
                    $result4 = $this->db->query($query4)->getResult();
                    
                    $total_sol = 0;
                    for ($i=0; $i < sizeof($result4); $i++) { 
                        $total_sol += $result4[$i]->con_TOTAL;
                    }

                    $query5 = " INSERT INTO tbl_logCyV (id_registro,id_usuario,tipo_accion,id_mod) 
                                VALUES ($id,$usuario,2,2)";
                    $this->db->query($query5);

                    $to = $email;
                    $subject = "Solicitud de viáticos aprobada";
                    $message = "<html lang='en'>
                                    <head>
                                        <meta charset='UTF-8'>
                                        <title>Titutlo</title>
                                    </head>
                                    <body>";

                    $message .="<table>
                                <tr>
                                    <td style='width:100px;'></td>
                                    <td style='width:1100px;'>";

                    $message .= "    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                    <hr>";

                    $message .= "    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Confirmación de solicitud</h1>
                                    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>00{$id}</h1>
                                    <br>";

                    $message .= "    <h3 style=' font-family:Helvetica;'>Estimado {$nombre_sol}</h3>
                                    <h4 style=' font-family:Helvetica;'>Le informamos que la solicitud de viáticos que registro el dia {$sol_fecha} ha sido aprobada por su líder.</h4>";
                    $message .= "    <br>
                                    <h3 style=' font-family:Helvetica;' >Monto aprobado:</h3><h2 style=' font-family:Helvetica;'><b>$ {$total_sol}</b></h2>
                                    <h4 style=' font-family:Helvetica;'>Comentarios: {$result2->sol_comentarios_estado}</h4>  
                                    <hr style=' color:#D8D8D8;'>";

                    $message .="    <br>
                                    <br>";
          
                    $message .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>";
                                  
                    $message .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>";
          
                    $message .="  </td>
                                  <td style='width:100px;'></td>
                                </tr>
                                </table>";
          
                    $message .= "</body>
                                 </html>";
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $this->from;

                    if(mail($to, $subject, $message, $headers))
                        $result = 1;
                    else
                        $result = 2;
                    
                }

                break;
            case 104: // Rechazar solicitud
                
                $fecha_actual = date('Y-m-d');
                $query1 = " UPDATE tbl_tur_solicitudes 
                            SET 
                                sol_estado = 4 , 
                                sol_comentarios_estado = '$comment',
                                sol_fecha_aprobacion = '$fecha_actual' 
                            WHERE sol_id = $id;";
                if($this->db->query($query1)){

                    $query2 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id";
                    $result2 = $this->db->query($query2)->getResult()[0];
                    $id_sol = $result2->sol_nombre_solicitante;
                    $sol_name = $result2->sol_nombre_solicitud;
                    $sol_fecha = date_format(date_create($result2->sol_fechaElaboracion),'d-m-Y');

                    $query3 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_sol";
                    $result3 = $this->db->query($query3)->getResult()[0];
                    $email = $result3->email;
                    $nombre_sol = $result3->nombres;

                    $query5 = " INSERT INTO tbl_logCyV (id_registro,id_usuario,tipo_accion,id_mod) 
                                VALUES ($id, $usuario, 3, 2)";
                    $this->db->query($query5);

                    $to = $email;
                    $subject = "Solicitud de viáticos rechazada";
                    $message ="<table>";

                    $message .="<tr><td style='width:100px;'></td><td >";
                    
                    $message .="<!DOCTYPE html>
                                <html lang='en'>
                                <head>
                                    <meta charset='UTF-8'>
                                    <title>Titutlo</title>
                                </head>
                                <body>
                                <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                <hr>
                                <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Rechazo de solicitud</h1>
                                <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>00{$id}</h1>
                                <br>
                                <h3 style=' font-family:Helvetica;'>Estimado {$nombre_sol}</h3>
                                <h4 style=' font-family:Helvetica;'>Le informamos que la solicitud de viáticos que registro el dia {$sol_fecha} ha sido rechazada por su líder.</h4>";
                                    
                    $message .="<br>
                                <h4 style=' font-family:Helvetica;'>Comentario de rechazo:</h4>
                                <p style=' font-family:Helvetica;'>";

                    $message .= $result2->sol_comentarios_estado;

                    $message .= "</p>";

                    $message .= "<p style=' font-family:Helvetica; color:black;'>Para más información favor de contactarlo.<p>";
                    
                    $message .="<br>
                                <hr style=' color:#D8D8D8;'>";

                    $message .="<br>
                                <br>
                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>
                                    
                                    <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>EEste correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                
                                </td>
                                <td style='width:100px;'></td>
                                </tr> 
                                </table>";

                    $message .="</body>
                                </html>";
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $this->from;

                    if(mail($to, $subject, $message, $headers))
                        $result = 1;
                    else
                        $result = 2;

                }
                break;
        }
        return $this->response->setStatusCode(200)->setJSON(array('tipo' => $tipo, 'result' => $result));
    }
    // Obtiene información detallada de la solicitud
    public function SR_getSolicitud_Detalles(){
        $json = $this->request->getJSON();
        $query1 = " SELECT 
                        t1.*,
                        IIF(t1.sol_proyectoServicioOpcion = '0', '', ISNULL(t1.sol_proyectoServicioOpcion, '')) AS sol_proyectoServicioOpcion_n,
                        FORMAT(t1.sol_fechaSalida, 'dd/MM/yyyy') AS sol_fechaSalida_n,
                        FORMAT(t1.sol_fechallegada, 'dd/MM/yyyy') AS sol_fechallegada_n,
                        IIF(t1.sol_horaSalida = '00:00:00', '', FORMAT(CAST(t1.sol_horaSalida AS DATETIME2), N'HH:mm:ss')) AS sol_horaSalida_n,
                        IIF(t1.sol_horaLlegada = '00:00:00', '',FORMAT(CAST(t1.sol_horaLlegada AS DATETIME2), N'HH:mm:ss')) AS sol_horaLlegada_n,
                        CONCAT(t2.nombres, ' ', t2.apellido_p, ' ', t2.apellido_m) AS nombre_solicitante
                    FROM 
                        tbl_tur_solicitudes  t1 LEFT JOIN 
                        Colaboradores t2 ON t2.id_colaborador = t1.sol_nombre_solicitante
                    WHERE 
                        t1.sol_id = $json->solicitud;";
        $result1 = $this->db->query($query1)->getResult();

        // {CALL sp_GET_ecnTur_PyG_29022016(?)} 
        // SAPSERVER
        /*
        $query2 = " SELECT 
                        T0.[PrjCode],
                        T0.[PrjName],
	                    UPPER(IIF(T0.[U_Zona] = 'Seleccione', '', ISNULL(T0.[U_Zona], ''))) AS PrcCode,
                        CASE 
                            WHEN  T0.[PrjCode] like 'GF%'   THEN 'SPF'
                            WHEN  T0.[PrjCode] like 'P%'    THEN 'SCI'
                            WHEN  T0.[PrjCode] like 'S-%'   THEN T0.u_depto
                            WHEN  T0.[PrjCode] like 'SM-0%' THEN 'COM'
                            WHEN  T0.[PrjCode] like 'SM%'   THEN 'STM'
                            WHEN  T0.[PrjCode] like 'D-%'   THEN 'ESC'
                            WHEN  T0.[PrjCode] like 'ATP%'  THEN 'OP'
                            WHEN  T0.[PrjCode] like 'ATC%'  THEN 'OP'
                        END AS DEPTO
                    FROM 
                        SAPSERVER.SBO_ECN.dbo.OPRJ T0 
                    WHERE  T0.[PrjCode] = ".$result1[0]->sol_proyectoServicioOpcion;
        $result2 = $this->db->query($query2)->getResult();
        */

        // JEFE INMEDIATO
        $query3 = "SELECT id_departamentos, id_aprobador FROM Colaboradores WHERE id_colaborador=$json->user";
        $result3 = $this->db->query($query3)->getResult();
        
        // JEFE DIRECTO
        $query4 = "SELECT CONCAT(nombres, ' ', apellido_p, ' ',apellido_m) AS nombre_lider FROM Colaboradores WHERE id_colaborador=".$result1[0]->sol_jefeDirecto;
        $result4 = $this->db->query($query4)->getResult();


        return $this->response->setStatusCode(200)->setJSON(array($result1[0], $result3[0], $result4[0]));
    }
    // Habilita o inhabilita los checkbox en el detalle de la solicitud 
    public function SR_habilitarCheckbox(){
        $json = $this->request->getJSON();
        // {CALL sp_get_habilitarCheckboxConcepto_20022016(?,?)}
        $query1 = " SELECT DISTINCT
                        con_tipoSolicitud,
                        (SELECT IIF(t2.con_edit = NULL, 1, 0) FROM tbl_conceptos t2 WHERE t2.con_sol_id = 85 AND t1.con_tipoSolicitud = t2.con_tipoSolicitud) AS habilitar	
                    FROM tbl_conceptos t1
                    ORDER BY con_tipoSolicitud ASC";
        $result1 = $this->db->query($query1)->getResult();

        $query2 = " SELECT ISNULL(sol_estado, 0) AS sol_estado 
                    FROM tbl_tur_solicitudes 
                    WHERE sol_id = $json->sol_id;";
        $result2 = $this->db->query($query2)->getResult();

        return $this->response->setStatusCode(200)->setJSON(array($result1, $result2));
    }
    // Se obtiene el total de los conceptos
    public function SR_getTotalConceptos(){
        $json = $this->request->getJSON();
        $query = "SELECT * FROM tbl_conceptos where con_sol_id=$json->sol_id ORDER BY con_tipoSolicitud ASC;";
        $result = $this->db->query($query)->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function SR_generarPDF_solicitud(){
        $json = $this->request->getJSON();

        $sol_id = $json->sol_id;
        $url = $json->url;

        $html = '';

        if($sol_id != 0){
            $query1="  SELECT 
                        T0.sol_id,
                        t0.sol_origen,
                        t0.sol_destino,
                        t0.sol_duracion,
                        FORMAT(t0.sol_fechaSalida, 'dd-MM-yyyy') AS sol_fechaSalida,
                        FORMAT(t0.sol_fechallegada, 'dd-MM-yyyy') AS sol_fechallegada,
                        t0.sol_departamentos_id,
                        t0.sol_geografica_id,
                        t0.sol_proyectoServicioOpcion,
                        t0.sol_cliente,
                        t0.sol_motivo,
                        t0.sol_nombre_solicitud,
                        t0.sol_duracion,
                        t0.sol_comentarioGeneral,
                        IIF(T0.sol_cuentaDePago = 1, 'Personal','Empresarial') AS tipo_pago,
                        T1.nombres+' '+T1.apellido_p+' '+T1.apellido_m AS solicitante,
                        T1.email,
                        CASE t0.sol_tipo_viaje 
                            WHEN 1 THEN 'Nacional'
                            WHEN 2 THEN 'Extranjero'
                        END AS tipo_viaje,
                        CASE T1.ID_DEPARTAMENTOS
                            WHEN 17 THEN 'FP-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 7 THEN 'FP-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 18 THEN 'FS-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 8 THEN 'FS-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 13 THEN 'GF-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 5 THEN 'GF-'+CAST(t0.sol_id AS VARCHAR)
                            ELSE 'FU-'+CAST(t0.sol_id AS VARCHAR)
                        END AS codigo
                    FROM
                        tbl_tur_solicitudes T0 LEFT JOIN
                        Colaboradores T1 ON T0.sol_nombre_solicitante=T1.id_colaborador
                    WHERE T0.sol_id=$sol_id";
            $result1= $this->db->query($query1)->getResult()[0];
            
            $sol_origen = $result1->sol_origen;
            $sol_destino = $result1->sol_destino;
            $sol_duracion = $result1->sol_duracion;
            $sol_fechaSalida = $result1->sol_fechaSalida;
            $sol_fechallegada = $result1->sol_fechallegada;
            $sol_departamentos_id = $result1->sol_departamentos_id;
            $sol_geografica_id = $result1->sol_geografica_id;
            $sol_proyectoServicioOpcion = $result1->sol_proyectoServicioOpcion;
            $sol_cliente = $result1->sol_cliente;
            $sol_motivo = $result1->sol_motivo;
            $sol_comentarioGeneral = $result1->sol_comentarioGeneral;
            $solicitante = $result1->solicitante;
            $email = $result1->email;
            $codigo = $result1->codigo;
            $sol_nombre = $result1->sol_nombre_solicitud;
            $tipo_viaje = $result1->tipo_viaje;
            $duracion = $result1->sol_duracion;
            $tipo_pago = $result1->tipo_pago;

            $html.="<html>
                        <head>
                            <style>
                                body{
                                    font-family: 'sofiapro', sans-serif !important;
                                    font-size: 10pt;
                                    color: #58595b;
                                }
                            </style>
                        </head>
                        <body>";

            $html=" <table style='width:100%;'>
                        <tr>
                            <td style='width:60%; '><img src='http://{$url}/assets/images/logo-2020.png' style='width:30%;'></td>
                            <td style='width:40%; text-align:right; border-right:2px solid #EE8624; padding-right:15px;'><h1 style='color:#EE8624; '>{$codigo}</h1><h3>SOLICITUD DE VIÁTICOS</h3></td>
                        </tr>
                    </table>
                    <br>
                    <div>
                        <h3 class='solicitante'>SOLICITANTE</h3>
                        <p style='line-height:-8px;'><b>Nombre:</b>{$solicitante}</p>
                        <p style='line-height:-8px;'><b>Email:</b>{$email}</p>

                        <h3 class='solicitante' style='margin-bottom:1px;'>DETALLE DE LA SOLICITUD</h3>
                        <table style='width:100%; margin-top:-5px;'>
                            <tr>
                                <td style='width:50%;'>
                                    <p style='line-height:-7px;'><b>Nombre: </b>{$sol_nombre}</p>
                                    <p style='line-height:-7px;'><b>Origen: </b>{$sol_origen}</p>
                                    <p style='line-height:-7px;'><b>Destino: </b>{$sol_destino}</p>
                                    <p style='line-height:-7px;'><b>Fecha de salida: </b>{$sol_fechaSalida}</p>
                                    <p style='line-height:-7px;'><b>Fecha de regreso: </b>{$sol_fechallegada}</p>
                                    <p style='line-height:-7px;'><b>Duración: </b>{$duracion} días</p>
                                </td>
                                <td style='width:50%;'>
                                    <p style='line-height:-7px;'><b>Proyecto: </b>{$sol_proyectoServicioOpcion}</p>
                                    <p style='line-height:-7px;'><b>Cargo a: </b>{$sol_departamentos_id} - {$sol_geografica_id}</p>
                                    <p style='line-height:-7px;'><b>Destino: </b>{$sol_destino}</p>
                                    <p style='line-height:-7px;'><b>Tipo de viaje: </b>{$tipo_viaje}</p>
                                    <p style='line-height:-7px;'><b>Tipo de pago: </b>{$tipo_pago}</p>
                                    <p style='line-height:-7px;'><b>Motivo del viaje: </b>{$sol_motivo}</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <br>
                    ";
        

            $html.="<table style='width:100%;  border-collapse:collapse; '>  
                        <tr>
                            <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Concepto</b></td>
                            <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Comentario</b></td>
                            <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Cantidad</b></td>
                            <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Precio Unitario</b></td>
                            <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Total</b></td>
                        </tr>";

            $query2="   SELECT
                            FORMAT(T0.con_precioUnitario, 'C') AS pu,
                            IIF(T0.con_tipoSolicitud = 3, T0.con_cantidadNoches , T0.con_cantidad+COALESCE(T0.con_cantidad2,0)+COALESCE(T0.con_cantidad3,0)) AS cantidad,
                            T0.con_TOTAL AS total,
                            FORMAT(T0.con_TOTAL, 'C') AS total_n,
                            CASE T0.con_tipoSolicitud
                                WHEN 1 THEN 'Comidas'
                                WHEN 2 THEN 'Transporte'
                                WHEN 3 THEN 'Hospedaje'
                                WHEN 4 THEN 'Casetas'
                                WHEN 5 THEN 'Combustibles'
                                WHEN 6 THEN 'Celular/Telefonía'
                                WHEN 7 THEN 'Lavandería'
                                WHEN 9 THEN 'Estacionamiento'
                                WHEN 10 THEN 'Varios'
                            END AS tipo,
                            T0.con_comentario AS comentario
                        FROM tbl_conceptos T0
                        WHERE T0.CON_SOL_ID=$sol_id
                        ORDER BY con_tipoSolicitud ASC";
            $result2=$this->db->query($query2)->getResult();
            $total_sol=0;

            for ($i=0; $i < sizeof($result2); $i++) { 
                $pu = $result2[$i]->pu;
                $cantidad = $result2[$i]->cantidad;
                $total_sol+=$result2[$i]->total;
                $total = $result2[$i]->total_n;
                $tipo = $result2[$i]->tipo;
                $comentario = $result2[$i]->comentario;
            
                $html.="<tr>
                            <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$tipo} </td>
                            <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$comentario} </td>
                            <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$cantidad} </td>
                            <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$pu} </td>
                            <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$total} </td>
                        </tr>";
            }

            $total_sol='$'.number_format($total_sol,2,'.',',');
            $html.="<tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style='border-bottom:0.3spx solid #3259A5; padding:4px;'> <h3>TOTAL</h3></td>
                        <td style='border-bottom:0.3spx solid #3259A5; padding:4px;'> <H3>{$total_sol} </H3></td>
                    </tr>";

            $footer="   </table>
                        <hr style='color:#EE8624; height:2px;'>
                        <table style='width:100%;'>
                            <tr>
                                <td style='width:55%;'><img src='http://{$url}/assets/images/industrias.png' style='width:35%;'></td>
                                <td style='width:45%; text-align:center;'>&copy; 2020 - Todos los derechos reservados - ecn</td>
                            </tr>
                        </table>";

            $html.= $footer;
            $html.="</body></html>";
        }

        
        $pdf = new Mpdf([
            'debug' => true,
            'mode' => 'utf-8'
        ]);
        
        //$pdf->debug = true;
        $pdf->WriteHTML($html);
        //$pdf->Output('files/propuestasalarial/propuesta-salarial-' . (($sol_id || $sol_id != '') ? $sol_id : '0') . '_solicitud.pdf', 'F');
        //$result2 = array(["filename" =>'propuesta-salarial-' . (($propuestaID || $propuestaID != '') ? $propuestaID : '0') . '_' . (($result->nombre_comp || $result->nombre_comp != '') ? $result->nombre_comp : 'nombre') . '.pdf']);
        
        return $this->response->setStatusCode(200)->setContentType('application/pdf')->sendBody($pdf->Output());
        //return $this->response->setStatusCode(200)->setJSON(array($html));
    }

    public function SR_guardarConceptosSolicitados(){
        $json = $this->request->getJSON();
        $tipo = $json->tipo;
        $sol_id = $json->sol_id;
        switch($tipo){
            case 1: // COMIDAS

                // { CALL sp_update_ecnTur_conceptoComida_24022016(?,?,?,?,?,?,?,?)}

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_tipoComidas = $json->comida_tipo,
                                con_cantidad = $json->comida_cantidad,
                                con_precioUnitario = $json->comida_precio,
                                con_TOTAL = $json->comida_total,
                                con_comentario = '$json->comida_comentario',
                                con_modificado = 1,
                                con_modificadoComentarios = '$json->comida_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 2: // AUTOBUS

                // { CALL sp_update_ecnTur_conceptoAutobus_24022016(?,?,?,?,?,?,?,?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_cantidad = $json->autobus_cantidad,
                                con_precioUnitario = $json->autobus_precio,
                                con_descripcion = '$json->autobus_descripcion',

                                con_cantidad2 = $json->autobus_cantidad2,
                                con_precioUnitario2 = $json->autobus_precio2,
                                con_descripcion2 = '$json->autobus_descripcion2',

                                con_cantidad3 = $json->autobus_cantidad3,
                                con_precioUnitario3 = $json->autobus_precio3,
                                con_descripcion3 = '$json->autobus_descripcion3',

                                con_TOTAL = $json->autobus_total,

                                --	 con_fechaSalida = @date_autobusFechaSalida,
                                --	 con_fechaLlegada= @date_autobusFechaLlegada,
                                con_comentario = '$json->autobus_comentario',
                                con_modificado = 1,
                                con_modificadoComentarios = '$json->autobus_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 3: // HOSPEDAJE

                // { CALL sp_update_ecnTur_conceptoHospedaje_25022016(?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_cantidadNoches        = $json->hospedaje_noches,
                                --	 con_PersonasCantidad      = @num_hospedajeCantidad,
                                con_precioUnitario        = $json->hospedaje_precio,
                                con_TOTAL                 = $json->hospedaje_total,
                                --	 con_fechaSalida           = @date_hospedajeFechaSalida ,
                                --	 con_fechaLlegada          = @date_hospedajeFechaLlegada,
                                con_comentario            = '$json->hospedaje_comentario',
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->hospedaje_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 4: // CASETAS

                // { CALL sp_update_ecnTur_conceptoCasetas_25022016(?,?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_numEconomico          = '$json->casetas_numero',
                                con_cantidad              = $json->casetas_cantidad,
                                con_precioUnitario        = $json->casetas_precio,
                                con_TOTAL                 = $json->casetas_total,
                                con_comentario            = '$json->casetas_comentario',
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->casetas_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 5: // COMBUSTIBLES

                // { CALL sp_update_ecnTur_conceptoCombustibles_25022016(?,?,?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_numEconomico          = '$json->combustible_numero',
                                con_rendimiento		      = $json->combustible_rendimiento,
                                con_kmViaje			      = $json->combustible_km,
                                con_precioUnitario        = $json->combustible_precio,
                                con_TOTAL                 = $json->combustible_total,
                                con_comentario            = '$json->combustible_comentario',
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->combustible_modificacion'

                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 6: // CELULAR/TEL

                // { CALL sp_update_ecnTur_conceptoTelefonia_25022016(?,?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_celular			      = '$json->telefonia_numero',
                                con_cantidad			  = $json->telefonia_cantidad,
                                con_precioUnitario        = $json->telefonia_precio,
                                con_TOTAL                 = $json->telefonia_total,
                                con_comentario            = '$json->telefonia_comentario',
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->telefonia_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 7: // LAVANDERIA

                // { CALL sp_update_ecnTur_conceptoLavanderia_26022016(?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_cantidad			  = $json->lavanderia_cantidad,
                                con_precioUnitario        = $json->lavanderia_precio,
                                con_TOTAL                 = $json->lavanderia_total,
                                con_comentario            = '$json->lavanderia_comentario',
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->lavanderia_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 8: // TAXI

                // { CALL sp_update_ecnTur_conceptoTaxi_26022016(?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                --	 con_origen			  = @txt_TaxiOrigen,
                                --	 con_destino		  = @txt_TaxiDestino,
                                con_cantidad			  = $json->taxi_cantidad,
                                con_precioUnitario        = $json->taxi_precio,
                                con_TOTAL                 = $json->taxi_total,
                                con_comentario            = '$json->taxi_comentario',
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->taxi_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 9: // ESTACIONAMIENTO

                // { CALL sp_update_ecnTur_conceptoEstacionamiento_26022016(?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_cantidad			  = $json->estacionamiento_cantidad,
                                con_precioUnitario        = $json->estacionamiento_precio,
                                con_TOTAL                 = $json->estacionamiento_total,
                                con_comentario            = '$json->estacionamiento_comentario',
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->estacionamiento_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            case 10: // VARIOS

                // { CALL sp_update_ecnTur_conceptoVarios_18042016(?,?,?,?,?,?,?,?,?,?,?,?,?,?) }

                $query1 = " UPDATE tbl_conceptos 
                            SET 
                                con_cantidad			  = $json->varios_cantidad ,
                                con_precioUnitario        = $json->varios_precio,
                                con_descripcion           = '$json->varios_descripcion',

                                con_cantidad2			  = $json->varios_cantidad2,
                                con_precioUnitario2       = $json->varios_precio2,
                                con_descripcion2          = '$json->varios_descripcion2',

                                con_cantidad3			  = $json->varios_cantidad3,
                                con_precioUnitario3       = $json->varios_precio3,
                                con_descripcion3          = '$json->varios_descripcion3',

                                con_comentario            = '$json->varios_comentario',
                                con_TOTAL                 = $json->varios_total,
                                con_modificado            = 1,
                                con_modificadoComentarios = '$json->varios_modificacion'
                            WHERE 
                                con_sol_id = $sol_id AND 
                                con_tipoSolicitud = $tipo;";
                $result1 = $this->db->query($query1) ? 1 : 0;

                break;
            default:
                $result1 = 2;
                break;
        }

        if($result1 == 1){
            $query2 = "UPDATE tbl_tur_solicitudes SET sol_modificado = 1 WHERE sol_id = $sol_id";
            $result2 = $this->db->query($query2) ? 1 : 0;
        }else
            $result2 = 2;

        return $this->response->setStatuscode(200)->setJSON(array($result1, $result2));  
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // MODULO: INFORMES REGISTRADOS

    // NOTAS
    //  METODO: IR_gasto_acciones() | Relacionados: IR_getInformes()
    //      APROBACIÓN Y RECHAZO POR PARTE DEL JEFE DIRECTO
    //      $tipo: 3 | APROBACIÓN DE SOLICITUD
    //          - gas_estado = 5 
    //          - gas_excedido = 0 
    //      $tipo: 4 | RECHAZO DE SOLICITUD
    //          - gas_estado = 1 
    //          - gas_excedido = 0 
    //      
    //      Una vez que el Jefe directo de su aprobación, el usuario que no sea el solicitante y jefe directo del solicitante
    //      podra aprobar o rechazar dicha solicitud.
    //      $tipo: 1 | APROBACIÓN DE SOLICITUD
    //          - gas_estado = 2 
    //      $tipo: 2 | RECHAZO DE SOLICITUD
    //          - gas_estado = 3 

    //  METODO: IR_getInformes()
    //      PARA LA TABLA tbl_tur_gastos
    //      "segmento"  |  (gas_estado)  | (gas_excedido) | Nombre
    //        ?????     |        0       :        0       : ?????
    //        ?????     |        1       :      0 y 1     : ?????
    //          2       |        2       :        0       : INFORMES APROBADOS 
    //          3       |        3       :        0       : INFORMES RECHAZADOS  
    //        ?????     |        4       :      ?????     : ?????                   | NO HAY EN EXISTENCIA
    //          1       |        5       :      0 y 1     : INFORMES PENDIENTES     | INDICANDO EL "gas_excedido = 0"
    //
    //      PARA LA TABLA tbl_tur_gastos_adeudo
    //      "segmento"  | (deuda_estado) |     (status)   | Nombre
    //          5       |       0        :   0, 1 y NULL  : INFORMES SIN ENVIAR     | INDICANDO EL "status = 1"
    //          4       |       1        :   0, 1 y NULL  : INFORMES RECIBIDOS      | INDICANDO EL "status = 1"
    //        ?????     |       2        :       0 y 1    : ?????
    //          6       |       3        :     1 y NULL   : INFORMES APROBADOS      | INDICANDO EL "status = 1"

////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // MODULO: INFORMES REGISTRADOS

    // Tabla para cada informe
    public function IR_getInformes(){
        $json = $this->request->getJSON();
        $search = $json->search;

        if(empty($this->db->connID))
            $this->db->initialize();
        
        switch($json->segment){
            case 1: // INFORMES PENDIENTES

                // {CALL sp_GET_ecnTur_gastos_detalle_03032016}

                $query = "  SELECT 
                                t0.gas_id,
                                t1.sol_id,
                                t0.gas_fechaElaboracion,
                                FORMAT(t0.gas_fechaElaboracion, 'dd/MM/yyyy') AS gas_fechaElaboracion_n,
                                t0.gas_nombreInforme AS gas_nombreInforme,
                                CONCAT(t2.nombres, ' ', t2.apellido_p) AS solicitante,
                                t0.gas_claveInforme AS gas_claveInforme,
                                t0.gas_proyecto AS gas_proyecto,
                                t1.sol_departamentos_id,
                                t1.sol_geografica_id,
                                CONCAT(t3.nombres, ' ', t3.apellido_p) AS lider,
                                (SELECT SUM(T4.con_TOTAL) FROM tbl_conceptos T4 WHERE T4. con_sol_id=t1.sol_id ) AS solicitado,
                                FORMAT((SELECT SUM(T4.con_TOTAL) FROM tbl_conceptos T4 WHERE T4.con_sol_id=t1.sol_id ), 'C') AS solicitado_n,
                                t2.id_departamentos,
                                t2.id_region
                            FROM 
                                tbl_tur_gastos t0 LEFT JOIN
                                tbl_tur_solicitudes t1 ON t0.gas_sol_id=t1.sol_id LEFT JOIN
                                Colaboradores t2 ON t1.sol_nombre_solicitante=t2.id_colaborador LEFT JOIN
                                Colaboradores t3 ON t1.sol_jefeDirecto = t3.id_colaborador
                            WHERE 
                                t0.gas_estado=5   AND
                                t0.gas_excedido=0  AND
                                (
                                    t0.gas_nombreInforme LIKE ('%$search%') OR
                                    CONCAT(t2.nombres, ' ', t2.apellido_p) LIKE ('%$search%') OR
                                    t0.gas_claveInforme LIKE ('%$search%') OR
                                    FORMAT(t0.gas_fechaElaboracion, 'dd/MM/yyyy') LIKE ('%$search%')
                                )
                            ORDER BY gas_fechaTerminarInforme DESC";

                $result = sqlsrv_query($this->db->connID, $query);

                $lista = array();
                while($row = sqlsrv_fetch_array($result)){
                    $lista[] = $row;
                }
                return $this->response->setStatusCode(200)->setJSON($lista);
            case 2: // INFORMES APROBADOS

                // {CALL sp_GET_ecnTur_gastos_detalleAPROB_03032016}

                $query = "  SELECT 
                                t0.gas_id,
                                t1.sol_id,
                                t0.gas_fechaElaboracion ,
                                FORMAT(t0.gas_fechaElaboracion, 'dd/MM/yyyy') AS gas_fechaElaboracion_n,
                                t0.gas_nombreInforme AS gas_nombreInforme,
                                CONCAT(t2.nombres, ' ', t2.apellido_p) AS solicitante,
                                t0.gas_claveInforme AS gas_claveInforme,
                                t0.gas_proyecto AS gas_proyecto,
                                t1.sol_departamentos_id,
                                t1.sol_geografica_id,
                                CONCAT(t3.nombres, ' ', t3.apellido_p) AS lider,
                                (SELECT SUM(T4.con_TOTAL) FROM tbl_conceptos T4 WHERE T4. con_sol_id=t1.sol_id ) AS solicitado,
                                FORMAT((SELECT SUM(T4.con_TOTAL) FROM tbl_conceptos T4 WHERE T4.con_sol_id=t1.sol_id ), 'C') AS solicitado_n,
                                t2.id_departamentos,
                                t2.id_region
                            FROM 
                                tbl_tur_gastos t0 LEFT JOIN
                                tbl_tur_solicitudes t1 ON t0.gas_sol_id=t1.sol_id LEFT JOIN
                                Colaboradores t2 ON t1.sol_nombre_solicitante=t2.id_colaborador LEFT JOIN
                                Colaboradores t3 ON t1.sol_jefeDirecto = t3.id_colaborador
                            WHERE 
                                t0.gas_estado=2   AND
                                YEAR(t1.sol_fechaElaboracion) >= 2018 AND
                                (
                                    t0.gas_nombreInforme LIKE ('%$search%') OR
                                    CONCAT(t2.nombres, ' ', t2.apellido_p) LIKE ('%$search%') OR
                                    t0.gas_claveInforme LIKE ('%$search%') OR
                                    FORMAT(t0.gas_fechaElaboracion, 'dd/MM/yyyy') LIKE ('%$search%')
                                )
                            ORDER BY gas_id DESC";

                $result = sqlsrv_query($this->db->connID, $query);

                $lista = array();
                while($row = sqlsrv_fetch_array($result)){
                    $lista[] = $row;
                }
                return $this->response->setStatusCode(200)->setJSON($lista);
            case 3: // INFORMES RECHAZADOS

                // {CALL sp_GET_ecnTur_gastos_detalleRECHA_03032016}

                $query = "  SELECT 
                                t0.gas_id,
                                t1.sol_id,
                                t0.gas_fechaElaboracion ,
                                FORMAT(t0.gas_fechaElaboracion, 'dd/MM/yyyy') AS gas_fechaElaboracion_n,
                                t0.gas_nombreInforme AS gas_nombreInforme,
                                CONCAT(t2.nombres, ' ', t2.apellido_p) AS solicitante,
                                t0.gas_claveInforme AS gas_claveInforme,
                                t0.gas_proyecto AS gas_proyecto,
                                t1.sol_departamentos_id,
                                t1.sol_geografica_id,
                                CONCAT(t3.nombres, ' ', t3.apellido_p) AS lider,
                                (SELECT SUM(T4.con_TOTAL) FROM tbl_conceptos T4 WHERE T4. con_sol_id=t1.sol_id ) AS solicitado,
                                FORMAT((SELECT SUM(T4.con_TOTAL) FROM tbl_conceptos T4 WHERE T4.con_sol_id=t1.sol_id ), 'C') AS solicitado_n,
                                t2.id_departamentos,
                                t2.id_region
                            FROM 
                                tbl_tur_gastos t0 LEFT JOIN
                                tbl_tur_solicitudes t1 ON t0.gas_sol_id=t1.sol_id LEFT JOIN
                                Colaboradores t2 ON t1.sol_nombre_solicitante=t2.id_colaborador LEFT JOIN
                                Colaboradores t3 ON t1.sol_jefeDirecto = t3.id_colaborador
                            WHERE 
                                t0.gas_estado=3  AND
                                (
                                    t0.gas_nombreInforme LIKE ('%$search%') OR
                                    CONCAT(t2.nombres, ' ', t2.apellido_p) LIKE ('%$search%') OR
                                    t0.gas_claveInforme LIKE ('%$search%') OR
                                    FORMAT(t0.gas_fechaElaboracion, 'dd/MM/yyyy') LIKE ('%$search%')
                                )
                            ORDER BY gas_id DESC";

                $result = sqlsrv_query($this->db->connID, $query);

                $lista = array();
                while($row = sqlsrv_fetch_array($result)){
                    $lista[] = $row;
                }
                return $this->response->setStatusCode(200)->setJSON($lista);
            case 4: // INFORMES RECIBIDOS

                $query = "  SELECT 
                                T0.* ,
                                T1.gas_fechaElaboracion,
                                FORMAT(T1.gas_fechaElaboracion, 'dd/MM/yyyy') AS gas_fechaElaboracion_n,
                                t2.sol_departamentos_id,
                                t2.sol_geografica_id,
                                CONCAT(T3.nombres, ' ', T3.apellido_p) AS solicitante,
                                T1.gas_nombreInforme AS nombre_informe,
                                T1.gas_claveInforme AS gas_claveInforme,
                                T2.sol_proyectoServicioOpcion AS proy,
                                T1.gas_id,
                                FORMAT(T0.deuda_monto, 'C') AS deuda_monto_n
                            FROM 
                                tbl_tur_gastos_adeudo T0  LEFT JOIN
                                tbl_tur_gastos  T1 ON T0.deuda_informe_id=T1.gas_id LEFT JOIN
                                tbl_tur_solicitudes T2 ON T1.gas_sol_id=T2.sol_id LEFT JOIN
                                Colaboradores T3 ON T2.sol_nombre_solicitante=T3.id_colaborador
                            WHERE 
                                T0.deuda_estado=1 AND 
                                T0.status=1 AND 
                                T0.create_date >= '2018-11-08' AND
                                (
                                    T1.gas_nombreInforme LIKE ('%$search%') OR
                                    CONCAT(T3.nombres, ' ', T3.apellido_p) LIKE ('%$search%') OR
                                    T1.gas_claveInforme LIKE ('%$search%') OR
                                    FORMAT(T1.gas_fechaElaboracion, 'dd/MM/yyyy') LIKE ('%$search%')
                                )
                            ORDER BY T1.gas_id DESC";

                $result = sqlsrv_query($this->db->connID, $query);

                $lista = array();
                while($row = sqlsrv_fetch_array($result)){
                    $lista[] = $row;
                }
                return $this->response->setStatusCode(200)->setJSON($lista);
            case 5: // INFORMES SIN ENVIAR

                $query = "  SELECT 
                                T0.* ,
                                T1.gas_fechaElaboracion,
                                FORMAT(T1.gas_fechaElaboracion, 'dd/MM/yyyy') AS gas_fechaElaboracion_n,
                                t2.sol_departamentos_id,
                                t2.sol_geografica_id,
                                CONCAT(T3.nombres, ' ', T3.apellido_p) AS solicitante,
                                T1.gas_nombreInforme AS nombre_informe,
                                T1.gas_claveInforme AS gas_claveInforme,
                                T2.sol_proyectoServicioOpcion AS proy,
                                T1.gas_id,
                                FORMAT(T0.deuda_monto, 'C') AS deuda_monto_n
                            FROM 
                                tbl_tur_gastos_adeudo T0  LEFT JOIN
                                tbl_tur_gastos  T1 ON T0.deuda_informe_id=T1.gas_id LEFT JOIN
                                tbl_tur_solicitudes T2 ON T1.gas_sol_id=T2.sol_id LEFT JOIN
                                Colaboradores T3 on T2.sol_nombre_solicitante=T3.id_colaborador
                            WHERE 
                                T0.deuda_estado=0 AND 
                                T0.status=1 AND 
                                T0.create_date >= '2018-11-08' AND
                                (
                                    T1.gas_nombreInforme LIKE ('%$search%') OR
                                    CONCAT(T3.nombres, ' ', T3.apellido_p) LIKE ('%$search%') OR
                                    T1.gas_claveInforme LIKE ('%$search%') OR
                                    FORMAT(T1.gas_fechaElaboracion, 'dd/MM/yyyy') LIKE ('%$search%')
                                )
                            ORDER BY T1.gas_id DESC";

                $result = sqlsrv_query($this->db->connID, $query);

                $lista = array();
                while($row = sqlsrv_fetch_array($result)){
                    $lista[] = $row;
                }
                return $this->response->setStatusCode(200)->setJSON($lista);
            case 6: // INFORMES APROBADOS

                $query = "  SELECT 
                                T0.* ,
                                T1.gas_fechaElaboracion,
                                FORMAT(T1.gas_fechaElaboracion, 'dd/MM/yyyy') AS gas_fechaElaboracion_n,
                                t2.sol_departamentos_id,
                                t2.sol_geografica_id,
                                CONCAT(T3.nombres, ' ', T3.apellido_p) AS solicitante,
                                T1.gas_nombreInforme AS nombre_informe,
                                T1.gas_claveInforme AS gas_claveInforme,
                                T2.sol_proyectoServicioOpcion AS proy,
                                T1.gas_id,
                                FORMAT(T0.deuda_monto, 'C') AS deuda_monto_n
                            FROM 
                                tbl_tur_gastos_adeudo T0  LEFT JOIN
                                tbl_tur_gastos  T1 ON T0.deuda_informe_id=T1.gas_id LEFT JOIN
                                tbl_tur_solicitudes T2 ON T1.gas_sol_id=T2.sol_id LEFT JOIN
                                Colaboradores T3 ON T2.sol_nombre_solicitante=T3.id_colaborador
                            WHERE 
                                T0.deuda_estado=3 AND 
                                T0.status=1 AND 
                                T0.create_date >= '2018-11-08' AND
                                (
                                    T1.gas_nombreInforme LIKE ('%$search%') OR
                                    CONCAT(T3.nombres, ' ', T3.apellido_p) LIKE ('%$search%') OR
                                    T1.gas_claveInforme LIKE ('%$search%') OR
                                    FORMAT(T1.gas_fechaElaboracion, 'dd/MM/yyyy') LIKE ('%$search%')
                                )
                            ORDER BY T1.gas_id DESC";

                $result = sqlsrv_query($this->db->connID, $query);

                $lista = array();
                while($row = sqlsrv_fetch_array($result)){
                    $lista[] = $row;
                }
                return $this->response->setStatusCode(200)->setJSON($lista);
            default:
                return $this->response->setStatusCode(200)->setJSON(-1);
        }

    }

    // Listado de los gastos del informe seleccionado en "Informes-registrados"
    // a "ver-gastos"
    public function IR_getGastos(){
        $json = $this->request->getJSON();
        $gas_id = $json->gas_id;

        // { CALL sp_GET_ecnTur_Aprobargasto_detalle_03032016(?) }
        /* [Listado de tipos de gastos] Rellena el listado */
        $query1 = " SELECT 
                        t0.*,
                        t2.sol_fechaSalida ,
                        IIF(DATEDIFF(DAY,t2.sol_fechaSalida,t0.gasd_fechaTransaccion) >= 0 ,1,0) AS dif,
                        FORMAT(t0.gasd_fechaTransaccion, 'dd/MM/yyyy') AS gasd_fechaTransaccion_n,
                        CASE t0.gasd_tipoGasto
                            WHEN 1 THEN 'Casetas'
                            WHEN 2 THEN 'Combustibles y lubricantes'
                            WHEN 3 THEN 'Mantenimiento de equipo de transporte'
                            WHEN 4 THEN 'Arrendamiento de automóviles'
                            WHEN 5 THEN 'Autobús'
                            WHEN 6 THEN 'Boletos de avión'
                            WHEN 7 THEN 'Taxi'
                            WHEN 8 THEN 'Comidas foraneas y excepciones (GDL, MTY, CDMX)'
                            WHEN 9 THEN 'Comidas locales'
                            WHEN 10 THEN 'Agua'
                            WHEN 11 THEN 'Electricidad'
                            WHEN 12 THEN 'Mantenimiento de equipo computo'
                            WHEN 13 THEN 'Mantenimiento de equipo de oficina'
                            WHEN 14 THEN 'Mantenimiento de local'
                            WHEN 15 THEN 'Mantenimiento de oficina'
                            WHEN 16 THEN 'Teléfono'
                            WHEN 17 THEN 'Telefonía móvil'
                            WHEN 18 THEN 'Análisis clínicos'
                            WHEN 19 THEN 'Artículos de cafeteria'
                            WHEN 20 THEN 'Capacitación'
                            WHEN 21 THEN 'Correo, mensajería y fletes'
                            WHEN 22 THEN 'Materiales y herramientas'
                            WHEN 23 THEN 'Papelería y artículos de oficina'
                            WHEN 24 THEN 'Hospedaje'
                            WHEN 25 THEN 'Lavanderia'
                            WHEN 26 THEN 'Teléfono de hotel'
                            WHEN 27 THEN 'Gastos de vehiculos'
                            WHEN 28 THEN 'Gastos de edificio y mantenimiento'
                            WHEN 29 THEN 'Monitoreo'
                            WHEN 30 THEN 'Varios'
                            ELSE ''
                        END gasd_tipoGasto_n,
                        FORMAT(t0.gasd_monto, 'C') AS gasd_monto_n
                    FROM 
                        tbl_tur_gastosDetalle t0 LEFT JOIN
                        tbl_tur_gastos t1 ON t0.gasd_informe=t1.gas_id LEFT JOIN
                        tbl_tur_solicitudes t2 ON t1.gas_sol_id=t2.sol_id
                    WHERE 
                        t0.gasd_informe = $gas_id AND 
                        t0.gasd_status = 1;";
        $result1 = $this->db->query($query1)->getResult();

        
        /* 
            [Listado de tipos de gastos] 
            Obtención del identificador de la solicitud y utilización de estados en las condiciones 
        */
        $query2 = "SELECT * FROM tbl_tur_gastos WHERE gas_id = $gas_id;";
        $result2 = $this->db->query($query2)->getResult();
        $row2 = $result2[0];

        $sol_id = $row2->gas_sol_id;

        // { CALL sp_GET_ecnTur_conTOTAL_03032016(?) }
        /* [Listado de tipos de gastos] Resultado del total solicitado */
        $query3 = " SELECT 
                        SUM(con_TOTAL) AS asistente_TOTAL,
                        FORMAT(SUM(con_TOTAL), 'C') AS asistente_TOTAL_n
                    FROM tbl_conceptos 
                    WHERE con_sol_id= $sol_id;";
        $result3 = $this->db->query($query3)->getResult();
        $row3 = $result3[0];

        /* 
            [Listado de tipos de gastos] 
            Obtención de identificadores para la utilización en las condiciones 
        */
        $query4 = " SELECT 
                        sol_solicitante_colaborador_id,
                        sol_jefeDirecto 
                    FROM
                        tbl_tur_solicitudes 
                    WHERE sol_id = $sol_id;";
        $result4 = $this->db->query($query4)->getResult();
        $row4 = $result4[0];

        /* 
            [Listado de tipos de gastos] 
            Obtención categoria de tipos de gastos y el monto total de estos.
        */
        $query5 = " SELECT 
                        T0.TIPO_GASTO,
                        T0.MONTO AS MONTO,
                        FORMAT(T0.MONTO, 'C') AS MONTO_n
                    FROM
                        (
                            SELECT 
                                CASE T0.gasd_tipoComprobante
                                WHEN 1 THEN 
                                    CASE t0.gasd_tipoGasto
                                        WHEN 8 THEN 'COMIDAS FORANEAS'
                                        WHEN 9 THEN 'COMIDAS LOCALES'
                                        WHEN 5 THEN 'AUTOBUS'
                                        WHEN 24 THEN 'HOSPEDAJE'
                                        WHEN 1 THEN 'CASETAS'
                                        WHEN 2 THEN 'COMBUSTIBLE'
                                        WHEN 17 THEN 'CELULAR'
                                        WHEN 25 THEN 'LAVANDERIA'
                                        WHEN 30 THEN 'VARIOS'
                                    END
                                    ELSE
                                        'NO DEDUCIBLE'
                                END AS TIPO_GASTO,
                                SUM(T0.gasd_monto) AS MONTO
                            FROM tbl_tur_gastosDetalle T0 
                            WHERE 
                                T0.gasd_informe = $gas_id and 
                                T0.gasd_status = 1 
                            GROUP BY 
                                T0.gasd_tipoGasto,
                                T0.gasd_tipoComprobante
                        ) T0";
        $result5 = $this->db->query($query5)->getResult();
        
                            
        return $this->response->setStatusCode(200)->setJSON(array($result1, $row2, $row3, $row4, $result5));
    }

    // Información de cada gasto seleccionado en "ver-gastos"
    public function IR_getDetallesGastos(){
        $json = $this->request->getJSON();
        $gas_id = $json->gas_id;
        
        // { CALL sp_GET_ecnTur_Aprobargasto_detalles_03032016(?) }
        $query1 = " SELECT 
                        *,
                        FORMAT(gasd_fechaTransaccion, 'dd/MM/yyyy') AS gasd_fechaTransaccion_n,
                        FORMAT(gasd_monto, 'C') AS gasd_monto_n,
                        FORMAT(gasd_mxIVAMontoMXN, 'C') AS gasd_mxIVAMontoMXN_n,
                        CASE gasd_moneda
                            WHEN 1 THEN 'MXN' 
                            WHEN 2 THEN 'USD' 
                            WHEN 3 THEN 'EUR' 
                            WHEN 4 THEN 'SOL'
                            ELSE ''
                        END AS gasd_moneda_n,
                        CASE gasd_tipoComprobante
                            WHEN 1 THEN 'Recibo de impuestos'
                            WHEN 2 THEN 'Recibo'
                            ELSE ''
                        END AS gasd_tipoComprobante_n,
                        CASE gasd_tipoGasto
                            WHEN 1 THEN 'Casetas'
                            WHEN 2 THEN 'Combustibles y lubricantes'
                            WHEN 3 THEN 'Mantenimiento de equipo de transporte'
                            WHEN 4 THEN 'Arrendamiento de automóviles'
                            WHEN 5 THEN 'Autobús'
                            WHEN 6 THEN 'Boletos de avión'
                            WHEN 7 THEN 'Taxi'
                            WHEN 8 THEN 'Comidas foraneas y excepciones (GDL, MTY, CDMX)'
                            WHEN 9 THEN 'Comidas locales'
                            WHEN 10 THEN 'Agua'
                            WHEN 11 THEN 'Electricidad'
                            WHEN 12 THEN 'Mantenimiento de equipo computo'
                            WHEN 13 THEN 'Mantenimiento de equipo de oficina'
                            WHEN 14 THEN 'Mantenimiento de local'
                            WHEN 15 THEN 'Mantenimiento de oficina'
                            WHEN 16 THEN 'Teléfono'
                            WHEN 17 THEN 'Telefonía móvil'
                            WHEN 18 THEN 'Análisis clínicos'
                            WHEN 19 THEN 'Artículos de cafeteria'
                            WHEN 20 THEN 'Capacitación'
                            WHEN 21 THEN 'Correo, mensajería y fletes'
                            WHEN 22 THEN 'Materiales y herramientas'
                            WHEN 23 THEN 'Papelería y artículos de oficina'
                            WHEN 24 THEN 'Hospedaje'
                            WHEN 25 THEN 'Lavanderia'
                            WHEN 26 THEN 'Teléfono de hotel'
                            WHEN 27 THEN 'Gastos de vehiculos'
                            WHEN 28 THEN 'Gastos de edificio y mantenimiento'
                            WHEN 29 THEN 'Monitoreo'
                            WHEN 30 THEN 'Varios'
                            ELSE ''
                        END AS gasd_tipoGasto_n
                    FROM tbl_tur_gastosDetalle 
                    WHERE 
                        gasd_id = $gas_id AND 
                        gasd_status = 1";

        // { CALL sp_get_asistentes_gasto28032016(?) }
        $query2 = " SELECT B.asisInforme_asis_id,B.asisInforme_tipo AS TIPO,
                        CASE 
                            WHEN B.asisInforme_tipo = 1 THEN (SELECT asis_nombre FROM tbl_tur_asistentes TA where TA.asis_id = B.asisInforme_asis_id) 
                            WHEN B.asisInforme_tipo = 2 THEN (SELECT nombres+' '+apellido_p FROM Colaboradores TA where TA.id_colaborador = B.asisInforme_asis_id)  
                            ELSE 'Revisar'
                        END AS ASISTENTE
                    FROM tbl_tur_asistentesInforme B 
                    WHERE B.asisInforme_gasd_id = $gas_id";

        // { CALL sp_get_ecnTur_anexos_gasto04042016(?) }
        $query3 = "SELECT tur_anexo_nombre FROM tbl_tur_anexos WHERE tur_anexo_gastoId = $gas_id;";

        // { CALL sp_get_ecnTur_excepciones_gasto13052016(?) }
        $query4 = " SELECT 
                        CASE exgas_excepcion  
                            WHEN '0' THEN ''
                            ELSE exgas_excepcion
                        END AS exgas_excepcion
                    FROM tbl_tur_excepcionGastos 
                    WHERE exgas_gasd_id = $gas_id;";

        $result1 = $this->db->query($query1)->getResult();
        $result2 = $this->db->query($query2)->getResult();
        $result3 = $this->db->query($query3)->getResult();
        $result4 = $this->db->query($query4)->getResult();

        $row1 = $result1[0];

        return $this->response->setStatusCode(200)->setJSON(array($row1, $result2, $result3, $result4));
    }

    // Aprobación y rechazo del jefe directo
    // Aprobación y rechazo diferente del solicitante y jefe directo del solicitante
    public function IR_gasto_acciones(){
        $json = $this->request->getJSON();
        $tipo = $json->tipo;
        $id = $json->id;
        $comment = $json->comment;
        $user = $json->user;

        $from = "intranet@ecnautomation.com";
        switch($tipo){
            case 1: // Aprobar gasto
        
                // { CALL sp_GET_ecnTur_AprobGasto_29032016(?,?,?) }
                $update1 = "UPDATE 
                                tbl_tur_gastos 
                            SET 
                                gas_estado = 2, 
                                gas_comentario_rechazo = '$comment', 
                                gas_fecha_aprobacion = GETDATE() 
                            where 
                                gas_id = $id";
        
                if($this->db->query($update1)){
                    $query1 = "SELECT * FROM tbl_tur_gastos WHERE gas_id = $id";
                    $result1 = $this->db->query($query1)->getResult()[0];
                    $id_solicitud = $result1->gas_sol_id;
                    $fecha_comprobacion = date_format(date_create($result1->gas_fechaTerminarInforme),'d-m-Y');
                    
                    $query3 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id_solicitud";
                    $result3 = $this->db->query($query3)->getResult()[0];
                    $id_sol = $result3->sol_nombre_solicitante;
        
                    $query2 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_sol";
                    $result2 = $this->db->query($query2)->getResult()[0];
                    $email = $result2->email;
                    $nombre_sol = $result2->nombres;
                    
                    $insert1 = "INSERT INTO tbl_logCyV (id_registro, id_usuario, tipo_accion, id_mod) 
                                VALUES ($id, $user, 7, 2)";
                    $result_insert1 = $this->db->query($insert1);
        
                    // REVISAR SI TIENE REEMBOLSO Y REGISTRAR
                    $query4 = "SELECT SUM(con_TOTAL) as solicitado FROM tbl_conceptos WHERE con_sol_id = $id_solicitud";
                    $result4 = $this->db->query($query4)->getResult()[0];
                    $solicitado = $result4->solicitado;
        
                    $query5 = "SELECT SUM(gasd_monto) as comprobado FROM tbl_tur_gastosDetalle WHERE gasd_informe = $id and gasd_status = 1";
                    $result5 = $this->db->query($query5)->getResult()[0];
                    $total_comprobado = $result5->comprobado;
        
                    if ($total_comprobado > $solicitado) {
                        $reembolso = $total_comprobado-$solicitado;
                        if ($reembolso > 50) {
                        // INSERT DE SOLICITUD DE REEMBOLSO
                            $insert2 = "INSERT INTO tbl_reembolsos (tipo,id_solicitud,id_comprobacion,monto) 
                                        VALUES (1, $id_solicitud, $id, $reembolso)";
                            $this->db->query($insert2);
                        }
                    }
        
                    $to = $email;
                    $subject = "Comprobación de viáticos aprobada";
                    $message = "<html lang='en'>
                                    <head>
                                      <meta charset='UTF-8'>
                                      <title>Titutlo</title>
                                    </head>
                                    <body>";
          
                    $message .="<table>
                                <tr>
                                  <td style='width:100px;'></td>
                                  <td style='width:1100px;'>";
          
                    $message .= "    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                     <hr>";
          
                    $message .= "    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Commprobación de gasto</h1>
                                     <br>";
          
                    $message .= "    <h3 style=' font-family:Helvetica;'>Estimado ".$nombre_sol."</h3>
                                     <h4 style=' font-family:Helvetica;'>Le informamos que la comprobación de viáticos que registro el dia ".$fecha_comprobacion." ha sido aprobada por el departamento de finanzas.</h4>";
                    $message .= "    <br>
                                     <h3 style=' font-family:Helvetica;' >Monto aprobado:</h3><h2 style=' font-family:Helvetica;'><b>$ ".$solicitado."</b></h2>
                                     <h4 style=' font-family:Helvetica;'>Comentarios: ".$result1->gas_comentario_rechazo."</h4>  
                                     <hr style=' color:#D8D8D8;'>";
          
                    $message3 ="
                                    <br>
                                    <br>";
          
                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>";
                                  
                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>";
          
                    $message3 .="  </td>
                                  <td style='width:100px;'></td>
                                </tr>
                                </table>";
          
                    $message3 .= "</body>
                                 </html>";
          
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $from;
                 //   echo strlen($message2)."\r\n";
                 //   echo $message.$message2.$message3."\r\n";
                    $messFinal = $message.$message3;
                  
                   mail($to,$subject, $messFinal,$headers);
        
                   return $this->response->setStatusCode(200)->setJSON(1);
                }else
                    return $this->response->setStatusCode(200)->setJSON(0);

            case 2: // Rechazar gasto

                // { CALL sp_GET_ecnTur_RechazarGasto_29032016(?,?,?) }
                $update = " UPDATE 
                                tbl_tur_gastos 
                            SET 
                                gas_estado = 3, 
                                gas_comentario_rechazo = '$comment', 
                                gas_fecha_aprobacion = GETDATE()  
                            where 
                                gas_id = $id";

                if($this->db->query($update)){
                    $query1 = "SELECT * FROM tbl_tur_gastos WHERE gas_id = $id";
                    $result1 = $this->db->query($query1)->getResult()[0];
                    $id_solicitud = $result1->gas_sol_id;
                    $fecha_comprobacion = date_format(date_create($result1->gas_fechaTerminarInforme),'d-m-Y');
    
                    $query4 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id_solicitud";
                    $result4 = $this->db->query($query4)->getResult()[0];
                    $id_sol = $result4->sol_nombre_solicitante;
    
                    $insert1 = "INSERT INTO tbl_logCyV (id_registro,id_usuario,tipo_accion,id_mod) VALUES ($id, $user, 8, 2)";
                    $this->db->query($insert1);
                    
                    $query2 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_sol";
                    $result2 = $this->db->query($query2)->getResult()[0];
                    $email = $result2->email;
                    $nombre_sol = $result2->nombres;
    
                    //$delete = "DELETE FROM tbl_tur_gastos_adeudo WHERE deuda_informe_id = $id";
                    //$this->db->query($delete)->getResult();
    
                    $query3 = "SELECT SUM(gasd_monto) AS comprobado FROM tbl_tur_gastosDetalle WHERE gasd_informe = $id and gasd_status = 1";
                    $result3 = $this->db->query($query3)->getResult()[0];

                    $to = $email;
                    $subject = "Comprobación de viáticos rechazada";
                    $message = "<html lang='en'>
                                    <head>
                                        <meta charset='UTF-8'>
                                        <title>Titutlo</title>
                                    </head>
                                    <body>";

                    $message .="<table>
                                <tr>
                                    <td style='width:100px;'></td>
                                    <td style='width:1100px;'>";

                    $message .= "    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                    <hr>";

                    $message .= "    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Commprobación de gasto</h1>
                                    <br>";

                    $message .= "    <h3 style=' font-family:Helvetica;'>Estimado $nombre_sol</h3>
                                    <h4 style=' font-family:Helvetica;'>Le informamos que la comprobación de viáticos que registro el dia $fecha_comprobacion ha sido rechazada por el departamento de finanzas.</h4>";
                    $message .= "    <br>
                                    <h3 style=' font-family:Helvetica;' >Monto rechazado:</h3><h2 style=' font-family:Helvetica;'><b>$ ".$result3->comprobado."</b></h2>
                                    <h4 style=' font-family:Helvetica;'>Comentarios: ".$result1->gas_comentario_rechazo."</h4>  
                                    <hr style=' color:#D8D8D8;'>";

                    $message3 ="
                                    <br>
                                    <br>";

                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>";
                                    
                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>";

                    $message3 .="  </td>
                                    <td style='width:100px;'></td>
                                </tr>
                                </table>";

                    $message3 .= "</body>
                                </html>";

                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $from;
                //   echo strlen($message2)."\r\n";
                //   echo $message.$message2.$message3."\r\n";
                    $messFinal = $message.$message3;
                    
                    mail($to,$subject, $messFinal,$headers);
                
                    return $this->response->setStatusCode(200)->setJSON(1);
                }else
                    return $this->response->setStatusCode(200)->setJSON(0);
            case 3: // Aprobar gasto por lider

                // { CALL sp_GET_ecnTur_AprobGastoLider_28042016(?,?) }
                $update = " UPDATE tbl_tur_gastos 
                            SET 
                                gas_estado = 5, 
                                gas_excedido = 0, 
                                gas_excedido_coment = '$comment',
                                gas_fecha_aprobacion_lider = GETDATE() 
                            WHERE gas_id = $id";
                if($this->db->query($update)){
                    
                    $query1 = "SELECT * FROM tbl_tur_gastos WHERE gas_id = $id";
                    $result1 = $this->db->query($query1)->getResult()[0];
                    $id_solicitud = $result1->gas_sol_id;
                    $fecha_comprobacion = date_format(date_create($result1->gas_fechaTerminarInforme),'d-m-Y');
    
                    $query3 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id_solicitud";
                    $result3 = $this->db->query($query3)->getResult()[0];
                    $id_sol = $result3->sol_nombre_solicitante;
                    
                    
                    $query2 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_sol";
                    $result2 = $this->db->query($query2)->getResult()[0];
                    $email = $result2->email;
                    $nombre_sol = $result2->nombres;
    
                    $to = $email;
                    $subject = "Comprobación de viáticos excedida";
                    $message = "<html lang='en'>
                                    <head>
                                        <meta charset='UTF-8'>
                                        <title>Titutlo</title>
                                    </head>
                                    <body>";
    
                    $message .="<table>
                                <tr>
                                    <td style='width:100px;'></td>
                                    <td style='width:1100px;'>";
    
                    $message .= "    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                    <hr>";
    
                    $message .= "    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Commprobación de gasto</h1>
                                    <br>";
    
                    $message .= "    <h3 style=' font-family:Helvetica;'>Estimado ".$nombre_sol."</h3>
                                    <h4 style=' font-family:Helvetica;'>Le informamos que la comprobación de viáticos excedida que registro el dia ".$fecha_comprobacion." ha sido aprobada por su líder.</h4>";
                    $message .= "    <br>
                                    <h4 style=' font-family:Helvetica;'>Comentarios: ".$result1->gas_excedido_coment."</h4>  
                                    <hr style=' color:#D8D8D8;'>";
    
                    $message3 ="
                                    <br>
                                    <br>";
    
                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>";
                                    
                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>";
    
                    $message3 .="  </td>
                                    <td style='width:100px;'></td>
                                </tr>
                                </table>";
    
                    $message3 .= "</body>
                                </html>";
    
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $from;
                    
                    $messFinal = $message.$message3;
                
                    mail($to,$subject, $messFinal,$headers);
    
                    return $this->response->setStatusCode(200)->setJSON(1);
                }else
                    return $this->response->setStatusCode(200)->setJSON(0);
            case 4: // Rechazar gasto por lider

                // { CALL sp_GET_ecnTur_RechazarGastoLider_28042016(?,?) }
                $update = " UPDATE tbl_tur_gastos 
                            SET 
                                gas_estado = 1,
                                gas_excedido = 0,
                                gas_excedido_coment = '$comment'  
                            WHERE gas_id = $id";
    
                if ($this->db->query($update)) {
                    $query1 = "SELECT * FROM tbl_tur_gastos WHERE gas_id = $id";
                    $result1 = $this->db->query($query1)->getResult()[0];
                    $id_solicitud = $result1->gas_sol_id;
                    $fecha_comprobacion = date_format(date_create($result1->gas_fechaTerminarInforme),'d-m-Y');
                    $excedido_coment = $result1->gas_excedido_coment;
    
                    $query3 = "SELECT * FROM tbl_tur_solicitudes WHERE sol_id = $id_solicitud";
                    $result3= $this->db->query($query3)->getResult()[0];
                    $id_sol = $result3->sol_nombre_solicitante;
                    
                    
                    $query2 = "SELECT * FROM Colaboradores WHERE id_colaborador = $id_sol";
                    $result2 = $this->db->query($query2)->getResult()[0];
                    $email = $result2->email;
                    $nombre_sol = $result2->nombres;
    
                    $to = $email;
                    $subject = "Comprobación de viáticos rechazada";
                    $message = "<html lang='en'>
                                    <head>
                                        <meta charset='UTF-8'>
                                        <title>Titutlo</title>
                                    </head>
                                    <body>";
    
                    $message .="<table>
                                <tr>
                                    <td style='width:100px;'></td>
                                    <td style='width:1100px;'>";
    
                    $message .= "    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:80%; height:80%;'/>
                                    <hr>";
    
                    $message .= "    <h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'>Commprobación de gasto excedida</h1>
                                    <br>";
    
                    $message .= "    <h3 style=' font-family:Helvetica;'>Estimado $nombre_sol</h3>
                                    <h4 style=' font-family:Helvetica;'>Le informamos que la comprobación de viáticos excedida que registro el dia $fecha_comprobacion ha sido rechazada por su líder.</h4>";
                    $message .= "    <br>
                                    <h4 style=' font-family:Helvetica;'>Comentarios: $excedido_coment</h4>  
                                    <hr style=' color:#D8D8D8;'>";
    
                    $message3 ="
                                    <br>
                                    <br>";
    
                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>© 2016 ecn.com.mx. Todos los derechos reservados ecn.</p>";
                                    
                    $message3 .="     <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>";
    
                    $message3 .="  </td>
                                    <td style='width:100px;'></td>
                                </tr>
                                </table>";
    
                    $message3 .= "</body>
                                </html>";
    
                    
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= "From:" . $from;
                    //   echo strlen($message2)."\r\n";
                    //   echo $message.$message2.$message3."\r\n";
                    $messFinal = $message.$message3;
                    
                    mail($to,$subject, $messFinal,$headers);
    
    
                    return $this->response->setStatusCode(200)->setJSON(1);
                }else
                    return $this->response->setStatusCode(200)->setJSON(0);
        }
        
    }

    // Genera pdf de comprobación
    public function IR_genera_pdf_comprobacion(){
        $json = $this->request->getJSON();

        $id = $json->sol_id;
        $url = $json->url;

        $html = "";

        if($id){
            $query1="SELECT 
                        T0.sol_id,
                        t0.sol_origen,
                        t0.sol_destino,
                        t0.sol_duracion,
                        t0.sol_fechaSalida,
                        t0.sol_fechallegada,
                        t0.sol_departamentos_id,
                        t0.sol_geografica_id,
                        t0.sol_proyectoServicioOpcion,
                        t0.sol_cliente,
                        t0.sol_motivo,
                        t0.sol_nombre_solicitud,
                        t0.sol_duracion,
                        t0.sol_comentarioGeneral,
                        IIF(T0.sol_cuentaDePago = 1, 'Personal','Empresarial') AS tipo_pago,
                        T1.nombres+' '+T1.apellido_p+' '+T1.apellido_m AS solicitante,
                        T1.email,
                        CASE t0.sol_tipo_viaje 
                            WHEN 1 THEN 'Nacional'
                            WHEN 2 THEN 'Extranjero'
                        END AS tipo_viaje,
                        CASE T1.ID_DEPARTAMENTOS
                            WHEN 17 THEN 'FP-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 7 THEN 'FP-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 18 THEN 'FS-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 8 THEN 'FS-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 13 THEN 'GF-'+CAST(t0.sol_id AS VARCHAR)
                            WHEN 5 THEN 'GF-'+CAST(t0.sol_id AS VARCHAR)
                        ELSE 'FU-'+CAST(t0.sol_id AS VARCHAR)
                        END AS codigo
                    FROM
                        tbl_tur_solicitudes T0 LEFT JOIN
                        Colaboradores T1 ON T0.sol_nombre_solicitante=T1.id_colaborador LEFT JOIN
                        tbl_tur_gastos t2 ON t2.gas_sol_id=t0.sol_id
                    WHERE t2.gas_id = $id";
            
        
            $query2="SELECT 
                        gasd_fechaTransaccion as fecha,
                        gasd_cdCompra as cd,
                        gasd_establecimiento as establecimiento,
                        gasd_monto as monto,
                        case gasd_tipoGasto
                            when 1 then 'Casetas'
                            when 2 then 'Combustibles y lubricantes'
                            when 3 then 'Mantenimiento de equipo de transporte'
                            when 4 then 'Arrendamiento de automóviles'
                            when 5 then 'Autobús'
                            when 6 then 'Boletos de avión'
                            when 7 then 'Taxi'
                            when 8 then 'Comidas foraneas'
                            when 9 then 'Comidas locales'
                            when 10 then 'Agua'
                            when 11 then 'Electricidad'
                            when 12 then 'Mantenimiento de equipo computo'
                            when 13 then 'Mantenimiento de equipo de oficina'
                            when 14 then 'Mantenimiento de local'
                            when 15 then 'Mantenimiento de oficina'
                            when 16 then 'Teléfono'
                            when 17 then 'Telefonía móvil'
                            when 18 then 'Análisis clínicos'
                            when 19 then 'Artículos de cafeteria'
                            when 20 then 'Capacitación'
                            when 21 then 'Correo, mensajería y fletes'
                            when 22 then 'Materiales y herramientas'
                            when 23 then 'Papelería y artículos de oficina'
                            when 24 then 'Hospedaje'
                            when 25 then 'Lavanderia'
                            when 26 then 'Teléfono de hotel'
                            when 27 then 'Gastos de vehiculos'
                            when 28 then 'Gastos de edificio y mantenimiento'
                            when 29 then 'Monitoreo'
                            when 30 then 'Varios'
                        end as tipo_gasto
                    FROM tbl_tur_gastosDetalle 
                    WHERE 
                        gasd_informe = $id AND 
                        gasd_status=1
                    ORDER BY fecha";
            
        
            $result1 = $this->db->query($query1)->getResult()[0];
            $result2 = $this->db->query($query2)->getResult();
            $total_comp=0;
        
        
        
            $sol_origen = $result1->sol_origen;
            $sol_destino = $result1->sol_destino;
            $sol_duracion = $result1->sol_duracion;
            $sol_fechaSalida = date_format(date_create($result1->sol_fechaSalida),'d-m-Y');
            $sol_fechallegada = date_format(date_create($result1->sol_fechallegada),'d-m-Y');
            $sol_departamentos_id = $result1->sol_departamentos_id;
            $sol_geografica_id = $result1->sol_geografica_id;
            $sol_proyectoServicioOpcion = $result1->sol_proyectoServicioOpcion;
            $sol_cliente = $result1->sol_cliente;
            $sol_motivo = $result1->sol_motivo;
            $sol_comentarioGeneral = $result1->sol_comentarioGeneral;
            $solicitante = $result1->solicitante;
            $email = $result1->email;
            $codigo = $result1->codigo;
            $sol_nombre = $result1->sol_nombre_solicitud;
            $tipo_viaje = $result1->tipo_viaje;
            $duracion = $result1->sol_duracion;
            $tipo_pago = $result1->tipo_pago;
        
            
            $html=" <html>
                        <head>
                            <style>
                                body{
                                    font-family: 'sofiapro', sans-serif !important;
                                    font-size: 10pt;
                                    color: #58595b;
                                }
                            </style>
                        </head>
                        <body>
                            <table style='width:100%;'>
                                <tr>
                                <td style='width:60%; '><img src='http://{$url}/assets/images/logo-2020.png' style='width:30%;'></td>
                                <td style='width:40%; text-align:right; border-right:2px solid #EE8624; padding-right:15px;'><h1 style='color:#EE8624; '>{$codigo}</h1><h3>COMPROBACIÓN DE VIÁTICOS</h3></td>
                                </tr>
                            </table>
                            <br>
                            <div>
                                <h3 class='solicitante'>SOLICITANTE</h3>
                                <p style='line-height:-8px;'><b>Nombre: </b>{$solicitante}</p>
                                <p style='line-height:-8px;'><b>Email: </b>{$email}</p>
                        
                                <h3 class='solicitante' style='margin-bottom:1px;'>DETALLE DE LA COMPROBACIÓN</h3>
                                <table style='width:100%; margin-top:-5px;'>
                                    <tr>
                                        <td style='width:50%;'>
                                            <p style='line-height:-7px;'><b>Nombre: </b>{$sol_nombre}</p>
                                            <p style='line-height:-7px;'><b>Origen: </b>{$sol_origen}</p>
                                            <p style='line-height:-7px;'><b>Destino: </b>{$sol_destino}</p>
                                            <p style='line-height:-7px;'><b>Fecha de salida: </b>{$sol_fechaSalida}</p>
                                            <p style='line-height:-7px;'><b>Fecha de regreso: </b>{$sol_fechallegada}</p>
                                            <p style='line-height:-7px;'><b>Duración: </b>{$duracion} días</p>
                                        </td>
                                        <td style='width:50%;'>
                                            <p style='line-height:-7px;'><b>Proyecto: </b>{$sol_proyectoServicioOpcion}</p>
                                            <p style='line-height:-7px;'><b>Cargo a: </b>{$sol_departamentos_id} - {$sol_geografica_id}</p>
                                            <p style='line-height:-7px;'><b>Destino: </b>{$sol_destino}</p>
                                            <p style='line-height:-7px;'><b>Tipo de viaje: </b>{$tipo_viaje}</p>
                                            <p style='line-height:-7px;'><b>Tipo de pago: </b>{$tipo_pago}</p>
                                            <p style='line-height:-7px;'><b>Motivo del viaje: </b>{$sol_motivo}</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <br>
                ";
        
        
            $html.="        <table style='width:100%;  border-collapse:collapse; '>  
                                <tr>
                                    <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Concepto</b></td>
                                    <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Establecimiento</b></td>
                                    <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Ciudad</b></td>
                                    <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Fecha de transacción</b></td>
                                    <td style='border-bottom:2px solid #3259A5; color:#3259A5;'><b>Total</b></td>
                                </tr>";
                
            for ($i=0; $i < count($result2); $i++){
        
                $fecha = date_format(date_create($result2[$i]->fecha),'d-m-Y');
                $tipo_gasto = $result2[$i]->tipo_gasto;
                $monto = '$'.number_format($result2[$i]->monto,2,'.',',');
                $total_comp+=$result2[$i]->monto;
                $establecimiento = $result2[$i]->establecimiento;
                $cd = $result2[$i]->cd;
        
                $html.="        <tr>
                                    <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$tipo_gasto} </td>
                                    <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$establecimiento} </td>
                                    <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$cd} </td>
                                    <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$fecha} </td>
                                    <td style='border-bottom:0.3spx solid #919091; padding:4px;'> {$monto} </td>
                                </tr>";
        
            }
        
            $total_comp='$'.number_format($total_comp,2,'.',',');
            $html.="            <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td style='border-bottom:0.3spx solid #3259A5; padding:4px;'> <h3>TOTAL</h3></td>
                                    <td style='border-bottom:0.3spx solid #3259A5; padding:4px;'> <H3>{$total_comp} </H3></td>
                                </tr>";
        
            $html.="        </table>";
        
            $footer="       <hr style='color:#EE8624; height:2px;'>
                            <table style='width:100%;'>
                                <tr>
                                    <td style='width:55%;'><img src='http://{$url}/assets/images/industrias.png' style='width:35%;'></td>
                                    <td style='width:45%; text-align:center;'>&copy; 2020 - Todos los derechos reservados - ecn</td>
                                </tr>
                            </table>";
    
            $html.= $footer;

            $html.="    </body>
                    </html>";

        }
        
        $pdf = new Mpdf([
            'debug' => true,
            'mode' => 'utf-8'
        ]);
        
        $pdf->debug = true;
        $pdf->WriteHTML($html);
        //$pdf->Output('files/propuestasalarial/propuesta-salarial-' . (($sol_id || $sol_id != '') ? $sol_id : '0') . '_solicitud.pdf', 'F');
        //$result2 = array(["filename" =>'propuesta-salarial-' . (($propuestaID || $propuestaID != '') ? $propuestaID : '0') . '_' . (($result->nombre_comp || $result->nombre_comp != '') ? $result->nombre_comp : 'nombre') . '.pdf']);
        
        return $this->response->setStatusCode(200)->setContentType('application/pdf')->sendBody($pdf->Output());
        //return $this->response->setStatusCode(200)->setJSON(array($html));
    }

    // Regresa al informe en "Informes pendientes"
    // desde "Informes Aprobados"
    // ***** "Informes Rechazados"
    public function IR_gastoRegresar(){
        $json = $this->request->getJSON();
        $id = $json->id;
        $comment = $json->comment;
        $from = "intranet@ecnautomation.com";

        $update = " UPDATE tbl_tur_gastos
                    SET 
                        gas_estado = 5
                    WHERE
                        gas_id = $id;";
                        
        if($this->db->query($update)){
            
			$query="SELECT 
                        T1.sol_nombre_solicitud,
                        T1.sol_id,
                        T2.email
                    FROM
                        tbl_tur_gastos T0 LEFT JOIN
                        tbl_tur_solicitudes T1 ON T0.gas_sol_id=T1.sol_id LEFT JOIN
                        Colaboradores T2 ON T1.sol_nombre_solicitante=T2.id_colaborador
                    WHERE T0.gas_id = $id";
            $result = $this->db->query($query)->getResult()[0];
            $sol_id = $result->sol_id;
            $email = $result->email;

            $asunto="Comprobación de viáticos regresada";
            $mensaje="<!DOCTYPE html>
                        <html lang='en'>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Document</title>
                        </head>
                        <body>
                            <table style='border-spacing:0px;'>
                                <tr style=''>
                                    <td style='width:150px;'></td>
                                    <td style='width:250px; border:solid 1px #E6E6E6; background-color:#E6E6E6; padding:3px; border-top-left-radius:8px;'><img src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt=''></td>
                                    <td style='width:250px; font-family:helvetica; font-size:1.15em; text-align:center; border:solid 1px #E6E6E6; background-color:#E6E6E6; padding:5px; border-top-right-radius:8px;'>Comprobación de viáticos</td>
                                    <td style='width:150px;'></td>
                                </tr>
                                <tr>
                                    <td style='width:150px;'></td>
                                    <td style='width:500px; border:solid 1px #E6E6E6; padding:10px;' colspan='2'>";

            $mensaje.="<p style='font-family:Helvetica;'>Estimado colaborador: </p>
                    <p style='font-family:Helvetica;'>Se le informa que el departamento de finanzas ha regresado a pendiente la comprobación de viáticos #{$sol_id} y dejo el siguiente mensaje:'{$comment}', favor de revisarla.</p>";
        
            $mensaje.="</td>
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
            $message = $mensaje;
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
            $headers .= "From: Intranet ECN <" . $from.">";

                //echo $message2;

            mail($to,$asunto,$message,$headers);
            return $this->response->setStatusCode(200)->setJSON(1);
        }else
            return $this->response->setStatusCode(200)->setJSON(0);
    }


    // Modal "ModalAdeudo"

    public function IR_gasto_montoAdeudo(){
        $json = $this->request->getJSON();
        $id = $json->id;

        $query = "SELECT monto_regresado FROM tbl_tur_gastos_adeudo WHERE deuda_informe_id=$id";
        $result = $this->db->query($query)->getResult()[0];

        return $this->response->setStatusCode(200)->setJSON($result);
    }
    public function IR_gastoDepositado(){
        $json = $this->request->getJSON();
        $id = $json->id;
        $monto = $json->monto;
        // { CALL sp_GET_ecnTur_DepositarAdeudo_29042016(?,?) }
        $query = "  UPDATE tbl_tur_gastos_adeudo 
                    SET 
                        deuda_estado = 3,
                        monto_regresado = $monto 
                    WHERE deuda_id = $id";
        $result = $this->db->query($query)->getResult();
    }
    ////////////////////////////////////////

    // Rechaza el informe
    public function IR_gastoOcultar(){
        $json = $this->request->getJSON();
        $id = $json->id;
        $user = $json->user;

        $query1 = "UPDATE tbl_tur_gastos_adeudo SET deuda_estado=2 WHERE deuda_id=$id";
        if($this->db->query($query1)){
            $query2 = "INSERT INTO tbl_logCyV (id_registro,id_usuario,tipo_accion,id_mod) VALUES ($id,$user,9,2)";
            $this->db->query($query2);

            return $this->response->setStatusCode(200)->setJSON(1);
        }else
            return $this->response->setStatusCode(200)->setJSON(0);
    }

    // Anexos relacionados con el informe seleccionado en "Informes-registrados"
    public function IR_gastoAnexos(){
        $json = $this->request->getJSON();
        $id = $json->id;

        $query1 = " SELECT tur_anexo_id,tur_anexo_nombre 
                    FROM tbl_tur_anexos 
                    WHERE 
                        tur_anexo_informeId=$id AND 
                        tur_tipo=5 AND 
                        tur_estado=1;";
        $result1 = $this->db->query($query1)->getResult();

        $query2 = " SELECT deuda_estado 
                    FROM tbl_tur_gastos_adeudo 
                    WHERE deuda_informe_id = $id;";
        $result2 = $this->db->query($query2)->getResult()[0];

        $deuda_estado = $result2->deuda_estado;
        $lista_anexos = array();
        if($result1)
            for ($i = 0; $i < sizeof($result1); $i++) 
                $lista_anexos[] = array(
                    "nombre" =>  $result1[$i]->tur_anexo_nombre
                );
        
        // ¿La deuda se puede eliminar si el estado es 0 o 2?
        $deuda_eliminar =  ($deuda_estado == 0 || $deuda_estado == 2) ? 1 : 0;

        return $this->response->setStatusCode(200)->setJSON(array($deuda_eliminar, $lista_anexos));

    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //  MODULO: SOLICITUDES EN DEPÓSITO

    // Obtiene las tablas "Por depositar" y "Depositadas"
    public function SD_getSolicitudes(){
        $json = $this->request->getJSON();
        $search = $json->search;
        $type = $json->type;

        $query = "";
        
        if(empty($this->db->connID))
            $this->db->initialize();

        switch($type){
            case 1: // Por depositar
                // { CALL sp_GET_ecnTur_SolicitudesFin5_27042016 }
                $query = "  SELECT 
                                t0.* ,
                                CONCAT(t1.nombres,' ',t1.apellido_p) AS nombre,
                                CONCAT(t2.nombres,' ',t2.apellido_p) AS nombreAprob,
                                (SELECT SUM(con_TOTAL) FROM tbl_conceptos WHERE con_sol_id= t0.sol_id) AS sumaTotal,
                                t1.id_departamentos,
                                t1.id_region,
                                IIF(T0.sol_proyectoServicioOpcion = '0','',T0.sol_proyectoServicioOpcion) AS  proy,
                                t0.sol_motivo,
                                IIF(t0.sol_cuentaDePago = 1,'Personal','Empresarial') AS pago,
                                CASE T1.ID_DEPARTAMENTOS
                                    WHEN 5 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN 7 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN 8 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN 13 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN 18 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN 17 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                    ELSE 'FU'+CAST(t0.sol_id AS VARCHAR)
                                END AS codigo,
                                t1.cuenta_bbva AS 'bbva',
                                t1.clabe AS 'cuentabancaria',

                                CASE sol_cuentaEmpresarial
                                    WHEN 1 THEN 'Santander'
                                    WHEN 2 THEN 'Bancomer'
                                    WHEN 3 THEN 'Banamex'
                                    WHEN 4 THEN 'Banco Azteca'
                                    WHEN 5 THEN 'Banorte'
                                    WHEN 6 THEN 'HSBC'
                                    WHEN 7 THEN 'Banco del Bajío'
                                    WHEN 8 THEN 'Scotiabank'
                                    WHEN 9 THEN 'Sin definir'
                                    ELSE 'Sin registro'
                                END AS sol_cuentaEmpresarial_n,
                                FORMAT((SELECT SUM(con_TOTAL) FROM tbl_conceptos WHERE con_sol_id= t0.sol_id), 'C') AS sumaTotal_n,
                                CASE 
                                    WHEN ( ISNULL(sol_cuentaPersonal, '') = '' OR sol_cuentaPersonal = '0' ) THEN 'Sin registro'
                                    ELSE sol_cuentaPersonal
                                END sol_cuentaPersonal_n,
                                CASE sol_cuentaDePago
                                    WHEN 1 THEN 'Personal'
                                    WHEN 2 THEN 'Empresarial'
                                    ELSE '--'
                                END sol_cuentaDePago_n
                            FROM 
                                tbl_tur_solicitudes t0 LEFT JOIN
                                Colaboradores t1 ON t0.sol_nombre_solicitante=t1.id_colaborador LEFT JOIN
                                Colaboradores t2 ON t0.sol_jefeDirecto=t2.id_colaborador
                            WHERE 
                                t0.sol_retenida = 2 AND 
                                t0.sol_estatus = 1 AND
                                (
                                    t0.sol_nombre_solicitud LIKE ('%$search%') OR
                                    CONCAT(t2.nombres,' ',t2.apellido_p) LIKE ('%$search%') OR
                                    (CASE T1.ID_DEPARTAMENTOS
                                        WHEN 5 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 7  THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 8  THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 13 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 18 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 17 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                        ELSE 'FU'+CAST(t0.sol_id AS VARCHAR)
                                    END) LIKE ('%$search%')
                                )
                            ORDER BY t0.sol_fecha_aprobacion DESC";
                break;
            case 2: // Depositados
                // { CALL sp_GET_ecnTur_SolicitudesFin4_27042016 }
                $query = "  SELECT 
                                t0.* ,
                                CONCAT(t1.nombres,' ',t1.apellido_p) AS nombre,
                                CONCAT(t2.nombres,' ',t2.apellido_p) AS nombreAprob,
                                (SELECT SUM(con_TOTAL) FROM tbl_conceptos WHERE con_sol_id= t0.sol_id) AS sumaTotal,
                                t1.id_departamentos,
                                t1.id_region,
                                IIF(T0.sol_proyectoServicioOpcion = '0','',T0.sol_proyectoServicioOpcion) AS  proy,
                                t0.sol_motivo,
                                IIF(t0.sol_cuentaDePago = 1,'Personal','Empresarial') AS pago,
                                CASE T1.ID_DEPARTAMENTOS
                                    WHEN    17  THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN    7   THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN    18  THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN    8   THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN    13  THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                    WHEN    5   THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                    ELSE 'FU'+CAST(t0.sol_id AS VARCHAR)
                                END AS codigo,
                                t1.cuenta_bbva AS 'bbva',
                                t1.clabe AS 'cuentabancaria',

                                FORMAT(t0.sol_fecha_deposito, 'dd/MM/yyyy') AS fecha_deposito_n,
                                CASE sol_cuentaEmpresarial
                                    WHEN 1 THEN 'Santander'
                                    WHEN 2 THEN 'Bancomer'
                                    WHEN 3 THEN 'Banamex'
                                    WHEN 4 THEN 'Banco Azteca'
                                    WHEN 5 THEN 'Banorte'
                                    WHEN 6 THEN 'HSBC'
                                    WHEN 7 THEN 'Banco del Bajío'
                                    WHEN 8 THEN 'Scotiabank'
                                    WHEN 9 THEN 'Sin definir'
                                    ELSE 'Sin registro'
                                END AS sol_cuentaEmpresarial_n,
                                FORMAT((SELECT SUM(con_TOTAL) FROM tbl_conceptos WHERE con_sol_id= t0.sol_id), 'C') AS sumaTotal_n,
                                CASE 
                                    WHEN ( ISNULL(sol_cuentaPersonal, '') = '' OR sol_cuentaPersonal = '0' ) THEN 'Sin registro'
                                    ELSE sol_cuentaPersonal
                                END sol_cuentaPersonal_n,
                                CASE sol_cuentaDePago
                                    WHEN 1 THEN 'Personal'
                                    WHEN 2 THEN 'Empresarial'
                                    ELSE '--'
                                END sol_cuentaDePago_n

                            FROM 
                                tbl_tur_solicitudes t0 LEFT JOIN
                                Colaboradores t1 ON t0.sol_nombre_solicitante=t1.id_colaborador LEFT JOIN
                                Colaboradores t2 ON t0.sol_jefeDirecto=t2.id_colaborador
                            WHERE 
                                t0.sol_retenida=3 AND 
                                t0.sol_estatus=1 AND  
                                t0.sol_fechaElaboracion>='2018-10-01' AND
                                (
                                    t0.sol_nombre_solicitud LIKE ('%$search%') OR
                                    FORMAT(t0.sol_fecha_deposito, 'dd/MM/yyyy') LIKE ('%$search%') OR
                                    FORMAT(t0.sol_fecha_deposito, 'yyyy-MM-dd') LIKE ('%$search%') OR
                                    (CASE T1.ID_DEPARTAMENTOS
                                        WHEN 17 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 7 THEN 'FP'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 18 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 8 THEN 'FS'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 13 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                        WHEN 5 THEN 'GF'+CAST(t0.sol_id AS VARCHAR)
                                        ELSE 'FU'+CAST(t0.sol_id AS VARCHAR)
                                    END) LIKE ('%$search%')
                                ) 
                            ORDER BY t0.sol_fecha_aprobacion DESC";
                break;
        }
        // $return = sqlsrv_query($this->db->connID, $query);

        // $result = array();
        // while($row = sqlsrv_fetch_array($return)) $result[] = $row;
        $result = $this->db->query($query)->getResult();
        
        return $this->response->setStatusCode(200)->setJSON($result);
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //  MODULO: GENERAR EXTRACTO DE VIATICOS
    public function GE_generarArchivo(){
        $json = $this->request->getJSON();
        $fInicio = $json->inicio;
        $fFinal = $json->final;

        $fileName = 'asistencia.xlsx';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Intranet ECN")
                                     ->setLastModifiedBy("Intranet ECN")
                                     ->setTitle("Extracto de viaticos")
                                     ->setSubject("Extracto de viaticos")
                                     ->setDescription("Extracto de viaticos")
                                     ->setKeywords("office 2007 openxml php")
                                     ->setCategory("Test result file");

        
        if(empty($this->db->connID))
            $this->db->initialize();

        $cont = 1;
        $cont2 = 1;
        $cont3=1;
        $cont5=1;
        $cont6=1;
        $cont7=1;
        $cont9=1;
        $cont11=3;
        $cont13=1;

        // Pagina 1 | Encabezados 1
        $spreadsheet->setActiveSheetIndex(0)
                ->setTitle("ECN")
                ->setCellValue("A$cont", "DocNum")
                ->setCellValue("B$cont", "DocType")
                ->setCellValue("C$cont", "HandWritten")
                ->setCellValue("D$cont", "Printed")
                ->setCellValue("E$cont", "DocDate")
                ->setCellValue("F$cont", "DocDueDate")
                ->setCellValue("G$cont", "CardCode")
                ->setCellValue("H$cont", "CardName")
                ->setCellValue("I$cont", "Address")
                ->setCellValue("J$cont", "NumAtCard")
                ->setCellValue("K$cont", "DocCurrency")
                ->setCellValue("L$cont", "DocRate")
                ->setCellValue("M$cont", "DocTotal")
                ->setCellValue("N$cont", "Reference1")
                ->setCellValue("O$cont", "Reference2")
                ->setCellValue("P$cont", "Comments")
                ->setCellValue("Q$cont", "JournalMemo")
                ->setCellValue("R$cont", "PaymentGroupCode")
                ->setCellValue("S$cont", "DocTime")
                ->setCellValue("T$cont", "SalesPersonCode")
                ->setCellValue("U$cont", "TransportationCode")
                ->setCellValue("V$cont", "Confirmed")
                ->setCellValue("W$cont", "ImportFileNum")
                ->setCellValue("X$cont", "SummeryType")
                ->setCellValue("Y$cont", "ContactPersonCode")
                ->setCellValue("Z$cont", "ShowSCN")

                ->setCellValue("AA$cont", "Series")
                ->setCellValue("AB$cont", "TaxDate")
                ->setCellValue("AC$cont", "PartialSupply")
                ->setCellValue("AD$cont", "DocObjectCode")
                ->setCellValue("AE$cont", "ShipToCode")
                ->setCellValue("AF$cont", "Indicator")
                ->setCellValue("AG$cont", "FederalTaxID")
                ->setCellValue("AH$cont", "DiscountPercent")
                ->setCellValue("AI$cont", "PaymentReference")
                ->setCellValue("AJ$cont", "DocTotalFc")
                ->setCellValue("AK$cont", "Form1099")
                ->setCellValue("AL$cont", "Box1099")
                ->setCellValue("AM$cont", "RevisionPo")
                ->setCellValue("AN$cont", "RequriedDate")
                ->setCellValue("AO$cont", "CancelDate")
                ->setCellValue("AP$cont", "BlockDunning")
                ->setCellValue("AQ$cont", "Pick")
                ->setCellValue("AR$cont", "PaymentMethod")
                ->setCellValue("AS$cont", "PaymentBlock")
                ->setCellValue("AT$cont", "PaymentBlockEntry")
                ->setCellValue("AU$cont", "CentralBankIndicator")
                ->setCellValue("AV$cont", "MaximumCashDiscount")
                ->setCellValue("AW$cont", "Project")
                ->setCellValue("AX$cont", "ExemptionValidityDateFrom")
                ->setCellValue("AY$cont", "ExemptionValidityDateTo")
                ->setCellValue("AZ$cont", "WareHouseUpdateType")

                ->setCellValue("BA$cont", "Rounding")
                ->setCellValue("BB$cont", "ExternalCorrectedDocNum")
                ->setCellValue("BC$cont", "InternalCorrectedDocNum")
                ->setCellValue("BD$cont", "DeferredTax")
                ->setCellValue("BE$cont", "TaxExemptionLetterNum")
                ->setCellValue("BF$cont", "AgentCode")
                ->setCellValue("BG$cont", "NumberOfInstallments")
                ->setCellValue("BH$cont", "ApplyTaxOnFirstInstallment")
                ->setCellValue("BI$cont", "VatDate")
                ->setCellValue("BJ$cont", "DocumentsOwner")
                ->setCellValue("BK$cont", "FolioPrefixString")
                ->setCellValue("BL$cont", "FolioNumber")
                ->setCellValue("BM$cont", "DocumentSubType")
                ->setCellValue("BN$cont", "BPChannelCode")
                ->setCellValue("BO$cont", "BPChannelContact")
                ->setCellValue("BP$cont", "Address2")
                ->setCellValue("BQ$cont", "PayToCode")
                ->setCellValue("BR$cont", "ManualNumber")
                ->setCellValue("BS$cont", "UseShpdGoodsAct")
                ->setCellValue("BT$cont", "IsPayToBank")
                ->setCellValue("BU$cont", "PayToBankCountry")
                ->setCellValue("BV$cont", "PayToBankCode")
                ->setCellValue("BW$cont", "PayToBankAccountNo")
                ->setCellValue("BX$cont", "PayToBankBranch")
                ->setCellValue("BY$cont", "BPL_IDAssignedToInvoice")
                ->setCellValue("BZ$cont", "DownPayment")

                ->setCellValue("CA$cont", "ReserveInvoice")
                ->setCellValue("CB$cont", "LanguageCode")
                ->setCellValue("CC$cont", "TrackingNumber")
                ->setCellValue("CD$cont", "PickRemark")
                ->setCellValue("CE$cont", "ClosingDate")
                ->setCellValue("CF$cont", "SequenceCode")
                ->setCellValue("CG$cont", "SequenceSerial")
                ->setCellValue("CH$cont", "SeriesString")
                ->setCellValue("CI$cont", "SubSeriesString")
                ->setCellValue("CJ$cont", "SequenceModel")
                ->setCellValue("CK$cont", "UseCorrectionVATGroup")
                ->setCellValue("CL$cont", "DownPaymentAmount")
                ->setCellValue("CM$cont", "DownPaymentPercentage")
                ->setCellValue("CN$cont", "DownPaymentType")
                ->setCellValue("CO$cont", "DownPaymentAmountSC")
                ->setCellValue("CP$cont", "DownPaymentAmountFC")
                ->setCellValue("CQ$cont", "VatPercent")
                ->setCellValue("CR$cont", "ServiceGrossProfitPercent")
                ->setCellValue("CS$cont", "OpeningRemarks")
                ->setCellValue("CT$cont", "ClosingRemarks")
                ->setCellValue("CU$cont", "RoundingDiffAmount")
                ->setCellValue("CV$cont", "ControlAccount")
                ->setCellValue("CW$cont", "InsuranceOperation347")
                ->setCellValue("CX$cont", "ArchiveNonremovableSalesQuotation")
                ->setCellValue("CY$cont", "U_Zona")
                ->setCellValue("CZ$cont", "U_Oficina");

        // El indice de la fila aumento en 1
        // cont = 2;
        $cont+= 1;
        
        // Pagina 1 | Encabezados 2
        $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue("A$cont", "DocNum")
                ->setCellValue("B$cont", "DocType")
                ->setCellValue("C$cont", "HandWritten")
                ->setCellValue("D$cont", "Printed")
                ->setCellValue("E$cont", "DocDate")
                ->setCellValue("F$cont", "Vencimiento")
                ->setCellValue("G$cont", "Codigo Prov.")
                ->setCellValue("H$cont", "CardName")
                ->setCellValue("I$cont", "Address")
                ->setCellValue("J$cont", "No. Factura")
                ->setCellValue("K$cont", "Moneda")
                ->setCellValue("L$cont", "Tipo de Cambio")
                ->setCellValue("M$cont", "Total")
                ->setCellValue("N$cont", "Ref1")
                ->setCellValue("O$cont", "Ref2")
                ->setCellValue("P$cont", "Comentarios")
                ->setCellValue("Q$cont", "JrnlMemo")
                ->setCellValue("R$cont", "GroupNum")
                ->setCellValue("S$cont", "DocTime")
                ->setCellValue("T$cont", "Empleado")
                ->setCellValue("U$cont", "TrnspCode")
                ->setCellValue("V$cont", "Confirmed")
                ->setCellValue("W$cont", "ImportEnt")
                ->setCellValue("X$cont", "SummryType")
                ->setCellValue("Y$cont", "CntctCode")
                ->setCellValue("Z$cont", "ShowSCN")

                ->setCellValue("AA$cont", "Series")
                ->setCellValue("AB$cont", "Fecha Factura")
                ->setCellValue("AC$cont", "PartSupply")
                ->setCellValue("AD$cont", "ObjType")
                ->setCellValue("AE$cont", "ShipToCode")
                ->setCellValue("AF$cont", "Indicator")
                ->setCellValue("AG$cont", "LicTradNum")
                ->setCellValue("AH$cont", "DiscPrcnt")
                ->setCellValue("AI$cont", "PaymentRef")
                ->setCellValue("AJ$cont", "DocTotalFC")
                ->setCellValue("AK$cont", "Form1099")
                ->setCellValue("AL$cont", "Box1099")
                ->setCellValue("AM$cont", "RevisionPo")
                ->setCellValue("AN$cont", "ReqDate")
                ->setCellValue("AO$cont", "CancelDate")
                ->setCellValue("AP$cont", "BlockDunn")
                ->setCellValue("AQ$cont", "Pick")
                ->setCellValue("AR$cont", "PeyMethod")
                ->setCellValue("AS$cont", "PayBlock")
                ->setCellValue("AT$cont", "PayBlckRef")
                ->setCellValue("AU$cont", "CntrlBnk")
                ->setCellValue("AV$cont", "MaxDscn")
                ->setCellValue("AW$cont", "Project")
                ->setCellValue("AX$cont", "FromDate")
                ->setCellValue("AY$cont", "ToDate")
                ->setCellValue("AZ$cont", "UpdInvnt")

                ->setCellValue("BA$cont", "Rounding")
                ->setCellValue("BB$cont", "CorrExt")
                ->setCellValue("BC$cont", "CorrInv")
                ->setCellValue("BD$cont", "DeferrTax")
                ->setCellValue("BE$cont", "LetterNum")
                ->setCellValue("BF$cont", "AgentCode")
                ->setCellValue("BG$cont", "Installmnt")
                ->setCellValue("BH$cont", "VATFirst")
                ->setCellValue("BI$cont", "VatDate")
                ->setCellValue("BJ$cont", "OwnerCode")
                ->setCellValue("BK$cont", "FolioPref")
                ->setCellValue("BL$cont", "FolioNum")
                ->setCellValue("BM$cont", "DocSubType")
                ->setCellValue("BN$cont", "BPChCode")
                ->setCellValue("BO$cont", "BPChCntc")
                ->setCellValue("BP$cont", "Address2")
                ->setCellValue("BQ$cont", "PayToCode")
                ->setCellValue("BR$cont", "ManualNum")
                ->setCellValue("BS$cont", "UseShpdGd")
                ->setCellValue("BT$cont", "IsPaytoBnk")
                ->setCellValue("BU$cont", "BnkCntry")
                ->setCellValue("BV$cont", "BankCode")
                ->setCellValue("BW$cont", "BnkAccount")
                ->setCellValue("BX$cont", "BnkBranch")
                ->setCellValue("BY$cont", "BPLId")
                ->setCellValue("BZ$cont", "DpmPrcnt")

                ->setCellValue("CA$cont", "isIns")
                ->setCellValue("CB$cont", "LangCode")
                ->setCellValue("CC$cont", "TrackNo")
                ->setCellValue("CD$cont", "PickRmrk")
                ->setCellValue("CE$cont", "ClsDate")
                ->setCellValue("CF$cont", "SeqCode")
                ->setCellValue("CG$cont", "Serial")
                ->setCellValue("CH$cont", "SeriesStr")
                ->setCellValue("CI$cont", "SubStr")
                ->setCellValue("CJ$cont", "Model")
                ->setCellValue("CK$cont", "UseCorrVat")
                ->setCellValue("CL$cont", "DpmAmnt")
                ->setCellValue("CM$cont", "DpmPrcnt")
                ->setCellValue("CN$cont", "Posted")
                ->setCellValue("CO$cont", "DpmAmntSC")
                ->setCellValue("CP$cont", "DpmAmntFC")
                ->setCellValue("CQ$cont", "VatPercent")
                ->setCellValue("CR$cont", "SrvGpPrcnt")
                ->setCellValue("CS$cont", "Header")
                ->setCellValue("CT$cont", "Footer")
                ->setCellValue("CU$cont", "RoundDif")
                ->setCellValue("CV$cont", "CtlAccount")
                ->setCellValue("CW$cont", "InsurOp347")
                ->setCellValue("CX$cont", "IgnRelDoc")
                ->setCellValue("CY$cont", "Zona")
                ->setCellValue("CZ$cont", "Oficina");

        
        // Crea la pagina 2
        $spreadsheet->createSheet(1);

        // Pagina 2 | Encabezados 1
        $spreadsheet->setActiveSheetIndex(1)
                ->setTitle("ENC A")
                ->setCellValue("A$cont3", "JdtNum")
                ->setCellValue("B$cont3", "ReferenceDate")
                ->setCellValue("C$cont3", "Memo")
                ->setCellValue("D$cont3", "Reference")
                ->setCellValue("E$cont3", "Reference2")
                ->setCellValue("F$cont3", "TransactionCode")
                ->setCellValue("G$cont3", "ProjectCode")
                ->setCellValue("H$cont3", "TaxDate")
                ->setCellValue("I$cont3", "Indicator")
                ->setCellValue("J$cont3", "UseAutoStorno")
                ->setCellValue("K$cont3", "StornoDate")
                ->setCellValue("L$cont3", "VatDate")
                ->setCellValue("M$cont3", "Series")
                ->setCellValue("N$cont3", "StampTax")
                ->setCellValue("O$cont3", "DueDate")
                ->setCellValue("P$cont3", "AutoVAT")
                ->setCellValue("Q$cont3", "ReportEU")
                ->setCellValue("R$cont3", "Report347")
                ->setCellValue("S$cont3", "LocationCode")
                ->setCellValue("T$cont3", "BlockDunningLetter")
                ->setCellValue("U$cont3", "AutomaticWT")
                ->setCellValue("V$cont3", "Corisptivi");
        
        // El indice de la fila aumento en 1
        // cont3 = 2;
        $cont3+= 1;

        // Pagina 2 | Encabezados 2
        $spreadsheet->setActiveSheetIndex(1)
                ->setCellValue("A$cont3", "JdtNum")
                ->setCellValue("B$cont3", "RefDate")
                ->setCellValue("C$cont3", "Memo")
                ->setCellValue("D$cont3", "Ref1")
                ->setCellValue("E$cont3", "Ref2")
                ->setCellValue("F$cont3", "TransCode")
                ->setCellValue("G$cont3", "Project")
                ->setCellValue("H$cont3", "TaxDate")
                ->setCellValue("I$cont3", "Indicator")
                ->setCellValue("J$cont3", "AutoStorno")
                ->setCellValue("K$cont3", "StornoDate")
                ->setCellValue("L$cont3", "VatDate")
                ->setCellValue("M$cont3", "Series")
                ->setCellValue("N$cont3", "StampTax")
                ->setCellValue("O$cont3", "AutoVAT")
                ->setCellValue("P$cont3", "ReportEU")
                ->setCellValue("Q$cont3", "Report347")
                ->setCellValue("R$cont3", "Location")
                ->setCellValue("S$cont3", "CreatedBy")
                ->setCellValue("T$cont3", "BlockDunn")
                ->setCellValue("U$cont3", "AutoWT")
                ->setCellValue("V$cont3", "Corisptivi");
        
        // Crea la pagina 3
        $spreadsheet->createSheet(2);

        // Pagina 3 | Encabezados 1
        $spreadsheet->setActiveSheetIndex(2)
                ->setTitle("Proveedores faltantes")
                ->setCellValue("A$cont5", "DocNum")
                ->setCellValue("B$cont5", "DocType")
                ->setCellValue("C$cont5", "HandWritten")
                ->setCellValue("D$cont5", "Printed")
                ->setCellValue("E$cont5", "DocDate")
                ->setCellValue("F$cont5", "DocDueDate")
                ->setCellValue("G$cont5", "CardCode")
                ->setCellValue("H$cont5", "CardName")
                ->setCellValue("I$cont5", "Address")
                ->setCellValue("J$cont5", "NumAtCard")
                ->setCellValue("K$cont5", "DocCurrency")
                ->setCellValue("L$cont5", "DocRate")
                ->setCellValue("M$cont5", "DocTotal")
                ->setCellValue("N$cont5", "Reference1")
                ->setCellValue("O$cont5", "Reference2")
                ->setCellValue("P$cont5", "Comments")
                ->setCellValue("Q$cont5", "JournalMemo")
                ->setCellValue("R$cont5", "PaymentGroupCode")
                ->setCellValue("S$cont5", "DocTime")
                ->setCellValue("T$cont5", "SalesPersonCode")
                ->setCellValue("U$cont5", "TransportationCode")
                ->setCellValue("V$cont5", "Confirmed")
                ->setCellValue("W$cont5", "ImportFileNum")
                ->setCellValue("X$cont5", "SummeryType")
                ->setCellValue("Y$cont5", "ContactPersonCode")
                ->setCellValue("Z$cont5", "ShowSCN")

                ->setCellValue("AA$cont5", "Series")
                ->setCellValue("AB$cont5", "TaxDate")
                ->setCellValue("AC$cont5", "PartialSupply")
                ->setCellValue("AD$cont5", "DocObjectCode")
                ->setCellValue("AE$cont5", "ShipToCode")
                ->setCellValue("AF$cont5", "Indicator")
                ->setCellValue("AG$cont5", "FederalTaxID")
                ->setCellValue("AH$cont5", "DiscountPercent")
                ->setCellValue("AI$cont5", "PaymentReference")
                ->setCellValue("AJ$cont5", "DocTotalFc")
                ->setCellValue("AK$cont5", "Form1099")
                ->setCellValue("AL$cont5", "Box1099")
                ->setCellValue("AM$cont5", "RevisionPo")
                ->setCellValue("AN$cont5", "RequriedDate")
                ->setCellValue("AO$cont5", "CancelDate")
                ->setCellValue("AP$cont5", "BlockDunning")
                ->setCellValue("AQ$cont5", "Pick")
                ->setCellValue("AR$cont5", "PaymentMethod")
                ->setCellValue("AS$cont5", "PaymentBlock")
                ->setCellValue("AT$cont5", "PaymentBlockEntry")
                ->setCellValue("AU$cont5", "CentralBankIndicator")
                ->setCellValue("AV$cont5", "MaximumCashDiscount")
                ->setCellValue("AW$cont5", "Project")
                ->setCellValue("AX$cont5", "ExemptionValidityDateFrom")
                ->setCellValue("AY$cont5", "ExemptionValidityDateTo")
                ->setCellValue("AZ$cont5", "WareHouseUpdateType")

                ->setCellValue("BA$cont5", "Rounding")
                ->setCellValue("BB$cont5", "ExternalCorrectedDocNum")
                ->setCellValue("BC$cont5", "InternalCorrectedDocNum")
                ->setCellValue("BD$cont5", "DeferredTax")
                ->setCellValue("BE$cont5", "TaxExemptionLetterNum")
                ->setCellValue("BF$cont5", "AgentCode")
                ->setCellValue("BG$cont5", "NumberOfInstallments")
                ->setCellValue("BH$cont5", "ApplyTaxOnFirstInstallment")
                ->setCellValue("BI$cont5", "VatDate")
                ->setCellValue("BJ$cont5", "DocumentsOwner")
                ->setCellValue("BK$cont5", "FolioPrefixString")
                ->setCellValue("BL$cont5", "FolioNumber")
                ->setCellValue("BM$cont5", "DocumentSubType")
                ->setCellValue("BN$cont5", "BPChannelCode")
                ->setCellValue("BO$cont5", "BPChannelContact")
                ->setCellValue("BP$cont5", "Address2")
                ->setCellValue("BQ$cont5", "PayToCode")
                ->setCellValue("BR$cont5", "ManualNumber")
                ->setCellValue("BS$cont5", "UseShpdGoodsAct")
                ->setCellValue("BT$cont5", "IsPayToBank")
                ->setCellValue("BU$cont5", "PayToBankCountry")
                ->setCellValue("BV$cont5", "PayToBankCode")
                ->setCellValue("BW$cont5", "PayToBankAccountNo")
                ->setCellValue("BX$cont5", "PayToBankBranch")
                ->setCellValue("BY$cont5", "BPL_IDAssignedToInvoice")
                ->setCellValue("BZ$cont5", "DownPayment")

                ->setCellValue("CA$cont5", "ReserveInvoice")
                ->setCellValue("CB$cont5", "LanguageCode")
                ->setCellValue("CC$cont5", "TrackingNumber")
                ->setCellValue("CD$cont5", "PickRemark")
                ->setCellValue("CE$cont5", "ClosingDate")
                ->setCellValue("CF$cont5", "SequenceCode")
                ->setCellValue("CG$cont5", "SequenceSerial")
                ->setCellValue("CH$cont5", "SeriesString")
                ->setCellValue("CI$cont5", "SubSeriesString")
                ->setCellValue("CJ$cont5", "SequenceModel")
                ->setCellValue("CK$cont5", "UseCorrectionVATGroup")
                ->setCellValue("CL$cont5", "DownPaymentAmount")
                ->setCellValue("CM$cont5", "DownPaymentPercentage")
                ->setCellValue("CN$cont5", "DownPaymentType")
                ->setCellValue("CO$cont5", "DownPaymentAmountSC")
                ->setCellValue("CP$cont5", "DownPaymentAmountFC")
                ->setCellValue("CQ$cont5", "VatPercent")
                ->setCellValue("CR$cont5", "ServiceGrossProfitPercent")
                ->setCellValue("CS$cont5", "OpeningRemarks")
                ->setCellValue("CT$cont5", "ClosingRemarks")
                ->setCellValue("CU$cont5", "RoundingDiffAmount")
                ->setCellValue("CV$cont5", "ControlAccount")
                ->setCellValue("CW$cont5", "InsuranceOperation347")
                ->setCellValue("CX$cont5", "ArchiveNonremovableSalesQuotation")
                ->setCellValue("CY$cont5", "U_Zona")
                ->setCellValue("CZ$cont5", "U_Oficina");

        // El indice de la fila aumento en 1
        // cont5 = 2;
        $cont5+= 1;

        // Pagina 3 | Encabezados 2
        $spreadsheet->setActiveSheetIndex(2)
                ->setCellValue("A$cont5", "DocNum")
                ->setCellValue("B$cont5", "DocType")
                ->setCellValue("C$cont5", "HandWritten")
                ->setCellValue("D$cont5", "Printed")
                ->setCellValue("E$cont5", "DocDate")
                ->setCellValue("F$cont5", "Vencimiento")
                ->setCellValue("G$cont5", "Codigo Prov.")
                ->setCellValue("H$cont5", "CardName")
                ->setCellValue("I$cont5", "Address")
                ->setCellValue("J$cont5", "No. Factura")
                ->setCellValue("K$cont5", "Moneda")
                ->setCellValue("L$cont5", "Tipo de Cambio")
                ->setCellValue("M$cont5", "Total")
                ->setCellValue("N$cont5", "Ref1")
                ->setCellValue("O$cont5", "Ref2")
                ->setCellValue("P$cont5", "Comentarios")
                ->setCellValue("Q$cont5", "JrnlMemo")
                ->setCellValue("R$cont5", "GroupNum")
                ->setCellValue("S$cont5", "DocTime")
                ->setCellValue("T$cont5", "Empleado")
                ->setCellValue("U$cont5", "TrnspCode")
                ->setCellValue("V$cont5", "Confirmed")
                ->setCellValue("W$cont5", "ImportEnt")
                ->setCellValue("X$cont5", "SummryType")
                ->setCellValue("Y$cont5", "CntctCode")
                ->setCellValue("Z$cont5", "ShowSCN")

                ->setCellValue("AA$cont5", "Series")
                ->setCellValue("AB$cont5", "Fecha Factura")
                ->setCellValue("AC$cont5", "PartSupply")
                ->setCellValue("AD$cont5", "ObjType")
                ->setCellValue("AE$cont5", "ShipToCode")
                ->setCellValue("AF$cont5", "Indicator")
                ->setCellValue("AG$cont5", "LicTradNum")
                ->setCellValue("AH$cont5", "DiscPrcnt")
                ->setCellValue("AI$cont5", "PaymentRef")
                ->setCellValue("AJ$cont5", "DocTotalFC")
                ->setCellValue("AK$cont5", "Form1099")
                ->setCellValue("AL$cont5", "Box1099")
                ->setCellValue("AM$cont5", "RevisionPo")
                ->setCellValue("AN$cont5", "ReqDate")
                ->setCellValue("AO$cont5", "CancelDate")
                ->setCellValue("AP$cont5", "BlockDunn")
                ->setCellValue("AQ$cont5", "Pick")
                ->setCellValue("AR$cont5", "PeyMethod")
                ->setCellValue("AS$cont5", "PayBlock")
                ->setCellValue("AT$cont5", "PayBlckRef")
                ->setCellValue("AU$cont5", "CntrlBnk")
                ->setCellValue("AV$cont5", "MaxDscn")
                ->setCellValue("AW$cont5", "Project")
                ->setCellValue("AX$cont5", "FromDate")
                ->setCellValue("AY$cont5", "ToDate")
                ->setCellValue("AZ$cont5", "UpdInvnt")

                ->setCellValue("BA$cont5", "Rounding")
                ->setCellValue("BB$cont5", "CorrExt")
                ->setCellValue("BC$cont5", "CorrInv")
                ->setCellValue("BD$cont5", "DeferrTax")
                ->setCellValue("BE$cont5", "LetterNum")
                ->setCellValue("BF$cont5", "AgentCode")
                ->setCellValue("BG$cont5", "Installmnt")
                ->setCellValue("BH$cont5", "VATFirst")
                ->setCellValue("BI$cont5", "VatDate")
                ->setCellValue("BJ$cont5", "OwnerCode")
                ->setCellValue("BK$cont5", "FolioPref")
                ->setCellValue("BL$cont5", "FolioNum")
                ->setCellValue("BM$cont5", "DocSubType")
                ->setCellValue("BN$cont5", "BPChCode")
                ->setCellValue("BO$cont5", "BPChCntc")
                ->setCellValue("BP$cont5", "Address2")
                ->setCellValue("BQ$cont5", "PayToCode")
                ->setCellValue("BR$cont5", "ManualNum")
                ->setCellValue("BS$cont5", "UseShpdGd")
                ->setCellValue("BT$cont5", "IsPaytoBnk")
                ->setCellValue("BU$cont5", "BnkCntry")
                ->setCellValue("BV$cont5", "BankCode")
                ->setCellValue("BW$cont5", "BnkAccount")
                ->setCellValue("BX$cont5", "BnkBranch")
                ->setCellValue("BY$cont5", "BPLId")
                ->setCellValue("BZ$cont5", "DpmPrcnt")

                ->setCellValue("CA$cont5", "isIns")
                ->setCellValue("CB$cont5", "LangCode")
                ->setCellValue("CC$cont5", "TrackNo")
                ->setCellValue("CD$cont5", "PickRmrk")
                ->setCellValue("CE$cont5", "ClsDate")
                ->setCellValue("CF$cont5", "SeqCode")
                ->setCellValue("CG$cont5", "Serial")
                ->setCellValue("CH$cont5", "SeriesStr")
                ->setCellValue("CI$cont5", "SubStr")
                ->setCellValue("CJ$cont5", "Model")
                ->setCellValue("CK$cont5", "UseCorrVat")
                ->setCellValue("CL$cont5", "DpmAmnt")
                ->setCellValue("CM$cont5", "DpmPrcnt")
                ->setCellValue("CN$cont5", "Posted")
                ->setCellValue("CO$cont5", "DpmAmntSC")
                ->setCellValue("CP$cont5", "DpmAmntFC")
                ->setCellValue("CQ$cont5", "VatPercent")
                ->setCellValue("CR$cont5", "SrvGpPrcnt")
                ->setCellValue("CS$cont5", "Header")
                ->setCellValue("CT$cont5", "Footer")
                ->setCellValue("CU$cont5", "RoundDif")
                ->setCellValue("CV$cont5", "CtlAccount")
                ->setCellValue("CW$cont5", "InsurOp347")
                ->setCellValue("CX$cont5", "IgnRelDoc")
                ->setCellValue("CY$cont5", "Zona")
                ->setCellValue("CZ$cont5", "Oficina");

        // Crea la pagina 4
        $spreadsheet->createSheet(3);

        // Pagina 4 | Encabezados 1
        $spreadsheet->setActiveSheetIndex(3)
                ->setTitle("LIN A")
                ->setCellValue("A$cont7", "ParentKey")
                ->setCellValue("B$cont7", "LineNum")
                ->setCellValue("C$cont7", "Line_ID")
                ->setCellValue("D$cont7", "AccountCode")
                ->setCellValue("E$cont7", "Debit")
                ->setCellValue("F$cont7", "Credit")
                ->setCellValue("G$cont7", "FCDebit")
                ->setCellValue("H$cont7", "FCCredit")
                ->setCellValue("I$cont7", "FCCurrency")
                ->setCellValue("J$cont7", "DueDate")
                ->setCellValue("K$cont7", "ShortName")
                ->setCellValue("L$cont7", "ContraAccount")
                ->setCellValue("M$cont7", "LineMemo")
                ->setCellValue("N$cont7", "ReferenceDate1")
                ->setCellValue("O$cont7", "ReferenceDate2")
                ->setCellValue("P$cont7", "Reference1")
                ->setCellValue("Q$cont7", "Reference2")
                ->setCellValue("R$cont7", "ProjectCode")
                ->setCellValue("S$cont7", "CostingCode")
                ->setCellValue("T$cont7", "TaxDate")
                ->setCellValue("U$cont7", "BaseSum")
                ->setCellValue("V$cont7", "TaxGroup")
                ->setCellValue("W$cont7", "DebitSys")
                ->setCellValue("X$cont7", "CreditSys")
                ->setCellValue("Y$cont7", "VatDate")
                ->setCellValue("Z$cont7", "VatLine")

                ->setCellValue("AA$cont7", "SystemBaseAmount")
                ->setCellValue("AB$cont7", "VatAmount")
                ->setCellValue("AC$cont7", "SystemVatAmount")
                ->setCellValue("AD$cont7", "GrossValue")
                ->setCellValue("AE$cont7", "AdditionalReference")
                ->setCellValue("AF$cont7", "CostingCode2")
                ->setCellValue("AG$cont7", "CostingCode3")
                ->setCellValue("AH$cont7", "CostingCode4")
                ->setCellValue("AI$cont7", "TaxCode")
                ->setCellValue("AJ$cont7", "TaxPostAccount")
                ->setCellValue("AK$cont7", "CostingCode5")
                ->setCellValue("AL$cont7", "LocationCode")
                ->setCellValue("AM$cont7", "ControlAccount")
                ->setCellValue("AN$cont7", "WTLiable")
                ->setCellValue("AO$cont7", "WTRow")
                ->setCellValue("AP$cont7", "PaymentBlock")
                ->setCellValue("AQ$cont7", "BlockReason");

        // El indice de la fila aumento en 1
        // cont4 = 2;
        $cont7+= 1;

        // Pagina 4 | Encabezados 2
        $spreadsheet->setActiveSheetIndex(3)
                ->setCellValue("A$cont7", "JdtNum")
                ->setCellValue("B$cont7", "LineNum")
                ->setCellValue("C$cont7", "Line_ID")
                ->setCellValue("D$cont7", "Account")
                ->setCellValue("E$cont7", "Debit")
                ->setCellValue("F$cont7", "Credit")
                ->setCellValue("G$cont7", "FCDebit")
                ->setCellValue("H$cont7", "FCCredit")
                ->setCellValue("I$cont7", "FCCurrency")
                ->setCellValue("J$cont7", "DueDate")
                ->setCellValue("K$cont7", "ShortName")
                ->setCellValue("L$cont7", "ContraAct")
                ->setCellValue("M$cont7", "LineMemo")
                ->setCellValue("N$cont7", "RefDate")
                ->setCellValue("O$cont7", "Ref2Date")
                ->setCellValue("P$cont7", "Ref1")
                ->setCellValue("Q$cont7", "Ref2")
                ->setCellValue("R$cont7", "Project")
                ->setCellValue("S$cont7", "ProfitCode")
                ->setCellValue("T$cont7", "TaxDate")
                ->setCellValue("U$cont7", "BaseSum")
                ->setCellValue("V$cont7", "VatGroup")
                ->setCellValue("W$cont7", "SYSDeb")
                ->setCellValue("X$cont7", "SYSCred")
                ->setCellValue("Y$cont7", "VatDate")
                ->setCellValue("Z$cont7", "VatLine")

                ->setCellValue("AA$cont7", "SYSBaseSum")
                ->setCellValue("AB$cont7", "VatAmount")
                ->setCellValue("AC$cont7", "SYSVatSum")
                ->setCellValue("AD$cont7", "GrossValue")
                ->setCellValue("AE$cont7", "Ref3Line")
                ->setCellValue("AF$cont7", "OcrCode2")
                ->setCellValue("AG$cont7", "OcrCode3")
                ->setCellValue("AH$cont7", "OcrCode4")
                ->setCellValue("AI$cont7", "TaxCode")
                ->setCellValue("AJ$cont7", "TaxPostAcc")
                ->setCellValue("AK$cont7", "OcrCode5")
                ->setCellValue("AL$cont7", "Location")
                ->setCellValue("AM$cont7", "Account")
                ->setCellValue("AN$cont7", "WTLiable")
                ->setCellValue("AO$cont7", "WTLine")
                ->setCellValue("AP$cont7", "PayBlock")
                ->setCellValue("AQ$cont7", "PayBlckRef");

        // Crea la pagina 5
        $spreadsheet->createSheet(4);

        // Pagina 5 | Encabezados 1
        $spreadsheet->setActiveSheetIndex(4)
                ->setTitle("LIN")
                ->setCellValue("A$cont9", "ParentKey")
                ->setCellValue("B$cont9", "LineNum")
                ->setCellValue("C$cont9", "ItemCode")
                ->setCellValue("D$cont9", "ItemDescription")
                ->setCellValue("E$cont9", "Quantity")
                ->setCellValue("F$cont9", "ShipDate")
                ->setCellValue("G$cont9", "Price")
                ->setCellValue("H$cont9", "PriceAfterVAT")
                ->setCellValue("I$cont9", "Currency")
                ->setCellValue("J$cont9", "Rate")
                ->setCellValue("K$cont9", "DiscountPercent")
                ->setCellValue("L$cont9", "VendorNum")
                ->setCellValue("M$cont9", "SerialNum")
                ->setCellValue("N$cont9", "WarehouseCode")
                ->setCellValue("O$cont9", "SalesPersonCode")
                ->setCellValue("P$cont9", "CommisionPercent")
                ->setCellValue("Q$cont9", "TreeType")
                ->setCellValue("R$cont9", "AccountCode")
                ->setCellValue("S$cont9", "UseBaseUnits")
                ->setCellValue("T$cont9", "SupplierCatNum")
                ->setCellValue("U$cont9", "CostingCode")
                ->setCellValue("V$cont9", "ProjectCode")
                ->setCellValue("W$cont9", "BarCode")
                ->setCellValue("X$cont9", "VatGroup")
                ->setCellValue("Y$cont9", "Height1")
                ->setCellValue("Z$cont9", "Hight1Unit")

                ->setCellValue("AA$cont9", "Height2")
                ->setCellValue("AB$cont9", "Height2Unit")
                ->setCellValue("AC$cont9", "Lengh1")
                ->setCellValue("AD$cont9", "Lengh1Unit")
                ->setCellValue("AE$cont9", "Lengh2")
                ->setCellValue("AF$cont9", "Lengh2Unit")
                ->setCellValue("AG$cont9", "Weight1")
                ->setCellValue("AH$cont9", "Weight1Unit")
                ->setCellValue("AI$cont9", "Weight2")
                ->setCellValue("AJ$cont9", "Weight2Unit")
                ->setCellValue("AK$cont9", "Factor1")
                ->setCellValue("AL$cont9", "Factor2")
                ->setCellValue("AM$cont9", "Factor3")
                ->setCellValue("AN$cont9", "Factor4")
                ->setCellValue("AO$cont9", "BaseType")
                ->setCellValue("AP$cont9", "BaseEntry")
                ->setCellValue("AQ$cont9", "BaseLine")
                ->setCellValue("AR$cont9", "Volume")
                ->setCellValue("AS$cont9", "VolumeUnit")
                ->setCellValue("AT$cont9", "Width1")
                ->setCellValue("AU$cont9", "Width1Unit")
                ->setCellValue("AV$cont9", "Width2")
                ->setCellValue("AW$cont9", "Width2Unit")
                ->setCellValue("AX$cont9", "Address")
                ->setCellValue("AY$cont9", "TaxCode")
                ->setCellValue("AZ$cont9", "TaxType")

                ->setCellValue("BA$cont9", "TaxLiable")
                ->setCellValue("BB$cont9", "BackOrder")
                ->setCellValue("BC$cont9", "FreeText")
                ->setCellValue("BD$cont9", "ShippingMethod")
                ->setCellValue("BE$cont9", "CorrectionInvoiceItem")
                ->setCellValue("BF$cont9", "CorrInvAmountToStock")
                ->setCellValue("BG$cont9", "CorrInvAmountToDiffAcct")
                ->setCellValue("BH$cont9", "WTLiable")
                ->setCellValue("BI$cont9", "DeferredTax")
                ->setCellValue("BJ$cont9", "MeasureUnit")
                ->setCellValue("BK$cont9", "UnitsOfMeasurment")
                ->setCellValue("BL$cont9", "LineTotal")
                ->setCellValue("BM$cont9", "TaxPercentagePerRow")
                ->setCellValue("BN$cont9", "ConsumerSalesForecast")
                ->setCellValue("BO$cont9", "ExciseAmount")
                ->setCellValue("BP$cont9", "CountryOrg")
                ->setCellValue("BQ$cont9", "SWW")
                ->setCellValue("BR$cont9", "TransactionType")
                ->setCellValue("BS$cont9", "DistributeExpense")
                ->setCellValue("BT$cont9", "ShipToCode")
                ->setCellValue("BU$cont9", "RowTotalFC")
                ->setCellValue("BV$cont9", "CFOPCode")
                ->setCellValue("BW$cont9", "CSTCode")
                ->setCellValue("BX$cont9", "Usage")
                ->setCellValue("BY$cont9", "TaxOnly")
                ->setCellValue("BZ$cont9", "UnitPrice")

                ->setCellValue("CA$cont9", "LineStatus")
                ->setCellValue("CB$cont9", "LineType")
                ->setCellValue("CC$cont9", "COGSCostingCode")
                ->setCellValue("CD$cont9", "COGSAccountCode")
                ->setCellValue("CE$cont9", "ChangeAssemlyBoMWarehouse")
                ->setCellValue("CF$cont9", "GrossBuyPrice")
                ->setCellValue("CG$cont9", "GrossBase")
                ->setCellValue("CH$cont9", "GrossProfitTotalBasePrice")
                ->setCellValue("CI$cont9", "CostingCode2")
                ->setCellValue("CJ$cont9", "CostingCode3")
                ->setCellValue("CK$cont9", "CostingCode4")
                ->setCellValue("CL$cont9", "CostingCode5")
                ->setCellValue("CM$cont9", "ItemDetails")
                ->setCellValue("CN$cont9", "LocationCode")
                ->setCellValue("CO$cont9", "ActualDeliveryDate")
                ->setCellValue("CP$cont9", "ExLineNo");

        // El indice de la fila aumento en 1
        // cont5 = 2;
        $cont9+= 1;

        // Pagina 5 | Encabezados 2
        $spreadsheet->setActiveSheetIndex(4)
                ->setCellValue("A$cont9", "DocNum")
                ->setCellValue("B$cont9", "LineNum")
                ->setCellValue("C$cont9", "ItemCode")
                ->setCellValue("D$cont9", "Descripcion")
                ->setCellValue("E$cont9", "Quantity")
                ->setCellValue("F$cont9", "ShipDate")
                ->setCellValue("G$cont9", "Subtotal")
                ->setCellValue("H$cont9", "PriceAfVAT")
                ->setCellValue("I$cont9", "Moneda")
                ->setCellValue("J$cont9", "Tipo de Cambio")
                ->setCellValue("K$cont9", "DiscPrcnt")
                ->setCellValue("L$cont9", "VendorNum")
                ->setCellValue("M$cont9", "SerialNum")
                ->setCellValue("N$cont9", "WhsCode")
                ->setCellValue("O$cont9", "Vendedor")
                ->setCellValue("P$cont9", "Commission")
                ->setCellValue("Q$cont9", "TreeType")
                ->setCellValue("R$cont9", "AcctCode")
                ->setCellValue("S$cont9", "UseBaseUn")
                ->setCellValue("T$cont9", "SubCatNum")
                ->setCellValue("U$cont9", "Centro de Costo")
                ->setCellValue("V$cont9", "Proyecto")
                ->setCellValue("W$cont9", "CodeBars")
                ->setCellValue("X$cont9", "VatGroup")
                ->setCellValue("Y$cont9", "Height1")
                ->setCellValue("Z$cont9", "Hght1Unit")

                ->setCellValue("AA$cont9", "Height2")
                ->setCellValue("AB$cont9", "Hght2Unit")
                ->setCellValue("AC$cont9", "Length1")
                ->setCellValue("AD$cont9", "Len1Unit")
                ->setCellValue("AE$cont9", "length2")
                ->setCellValue("AF$cont9", "Len2Unit")
                ->setCellValue("AG$cont9", "Weight1")
                ->setCellValue("AH$cont9", "Wght1Unit")
                ->setCellValue("AI$cont9", "Weight2")
                ->setCellValue("AJ$cont9", "Wght2Unit")
                ->setCellValue("AK$cont9", "Factor1")
                ->setCellValue("AL$cont9", "Factor2")
                ->setCellValue("AM$cont9", "Factor3")
                ->setCellValue("AN$cont9", "Factor4")
                ->setCellValue("AO$cont9", "BaseType")
                ->setCellValue("AP$cont9", "BaseEntry")
                ->setCellValue("AQ$cont9", "BaseLine")
                ->setCellValue("AR$cont9", "Volume")
                ->setCellValue("AS$cont9", "VolUnit")
                ->setCellValue("AT$cont9", "Width1")
                ->setCellValue("AU$cont9", "Wdth1Unit")
                ->setCellValue("AV$cont9", "Width2")
                ->setCellValue("AW$cont9", "Wdth2Unit")
                ->setCellValue("AX$cont9", "Address")
                ->setCellValue("AY$cont9", "Codigo IVA")
                ->setCellValue("AZ$cont9", "TaxType")

                ->setCellValue("BA$cont9", "TaxStatus")
                ->setCellValue("BB$cont9", "BackOrdr")
                ->setCellValue("BC$cont9", "FreeTxt")
                ->setCellValue("BD$cont9", "TrnsCode")
                ->setCellValue("BE$cont9", "CEECFlag")
                ->setCellValue("BF$cont9", "ToStock")
                ->setCellValue("BG$cont9", "ToDiff")
                ->setCellValue("BH$cont9", "WtLiable")
                ->setCellValue("BI$cont9", "DeferrTax")
                ->setCellValue("BJ$cont9", "unitMsr")
                ->setCellValue("BK$cont9", "NumPerMsr")
                ->setCellValue("BL$cont9", "Subtotal")
                ->setCellValue("BM$cont9", "VatPrcnt")
                ->setCellValue("BN$cont9", "ConsumeFCT")
                ->setCellValue("BO$cont9", "ExciseAmt")
                ->setCellValue("BP$cont9", "CountryOrg")
                ->setCellValue("BQ$cont9", "SWW")
                ->setCellValue("BR$cont9", "TranType")
                ->setCellValue("BS$cont9", "DistribExp")
                ->setCellValue("BT$cont9", "ShipToCode")
                ->setCellValue("BU$cont9", "TotalFrgn")
                ->setCellValue("BV$cont9", "CFOPCode")
                ->setCellValue("BW$cont9", "CSTCode")
                ->setCellValue("BX$cont9", "Usage")
                ->setCellValue("BY$cont9", "TaxOnly")
                ->setCellValue("BZ$cont9", "PriceBefDi")

                ->setCellValue("CA$cont9", "LineStatus")
                ->setCellValue("CB$cont9", "LineType")
                ->setCellValue("CC$cont9", "CogsOcrCod")
                ->setCellValue("CD$cont9", "CogsAcct")
                ->setCellValue("CE$cont9", "ChgAsmBoMW")
                ->setCellValue("CF$cont9", "GrossBuyPr")
                ->setCellValue("CG$cont9", "GrossBase")
                ->setCellValue("CH$cont9", "GPTtlBasPr")
                ->setCellValue("CI$cont9", "OcrCode2")
                ->setCellValue("CJ$cont9", "OcrCode3")
                ->setCellValue("CK$cont9", "OcrCode4")
                ->setCellValue("CL$cont9", "OcrCode5")
                ->setCellValue("CM$cont9", "Text")
                ->setCellValue("CN$cont9", "LocCode")
                ->setCellValue("CO$cont9", "ActDelDate")
                ->setCellValue("CP$cont9", "ExLineNo");

        // Crea la pagina 6
        $spreadsheet->createSheet(5);

        // Pagina 6 | Encabezados 1
        $spreadsheet->setActiveSheetIndex(5)
                ->setTitle("LIN B")
                ->setCellValue("A$cont13", "ParentKey")
                ->setCellValue("B$cont13", "LineNum")
                ->setCellValue("C$cont13", "ItemCode")
                ->setCellValue("D$cont13", "ItemDescription")
                ->setCellValue("E$cont13", "Quantity")
                ->setCellValue("F$cont13", "ShipDate")
                ->setCellValue("G$cont13", "Price")
                ->setCellValue("H$cont13", "PriceAfterVAT")
                ->setCellValue("I$cont13", "Currency")
                ->setCellValue("J$cont13", "Rate")
                ->setCellValue("K$cont13", "DiscountPercent")
                ->setCellValue("L$cont13", "VendorNum")
                ->setCellValue("M$cont13", "SerialNum")
                ->setCellValue("N$cont13", "WarehouseCode")
                ->setCellValue("O$cont13", "SalesPersonCode")
                ->setCellValue("P$cont13", "CommisionPercent")
                ->setCellValue("Q$cont13", "TreeType")
                ->setCellValue("R$cont13", "AccountCode")
                ->setCellValue("S$cont13", "UseBaseUnits")
                ->setCellValue("T$cont13", "SupplierCatNum")
                ->setCellValue("U$cont13", "CostingCode")
                ->setCellValue("V$cont13", "ProjectCode")
                ->setCellValue("W$cont13", "BarCode")
                ->setCellValue("X$cont13", "VatGroup")
                ->setCellValue("Y$cont13", "Height1")
                ->setCellValue("Z$cont13", "Hight1Unit")

                ->setCellValue("AA$cont13", "Height2")
                ->setCellValue("AB$cont13", "Height2Unit")
                ->setCellValue("AC$cont13", "Lengh1")
                ->setCellValue("AD$cont13", "Lengh1Unit")
                ->setCellValue("AE$cont13", "Lengh2")
                ->setCellValue("AF$cont13", "Lengh2Unit")
                ->setCellValue("AG$cont13", "Weight1")
                ->setCellValue("AH$cont13", "Weight1Unit")
                ->setCellValue("AI$cont13", "Weight2")
                ->setCellValue("AJ$cont13", "Weight2Unit")
                ->setCellValue("AK$cont13", "Factor1")
                ->setCellValue("AL$cont13", "Factor2")
                ->setCellValue("AM$cont13", "Factor3")
                ->setCellValue("AN$cont13", "Factor4")
                ->setCellValue("AO$cont13", "BaseType")
                ->setCellValue("AP$cont13", "BaseEntry")
                ->setCellValue("AQ$cont13", "BaseLine")
                ->setCellValue("AR$cont13", "Volume")
                ->setCellValue("AS$cont13", "VolumeUnit")
                ->setCellValue("AT$cont13", "Width1")
                ->setCellValue("AU$cont13", "Width1Unit")
                ->setCellValue("AV$cont13", "Width2")
                ->setCellValue("AW$cont13", "Width2Unit")
                ->setCellValue("AX$cont13", "Address")
                ->setCellValue("AY$cont13", "TaxCode")
                ->setCellValue("AZ$cont13", "TaxType")

                ->setCellValue("BA$cont13", "TaxLiable")
                ->setCellValue("BB$cont13", "BackOrder")
                ->setCellValue("BC$cont13", "FreeText")
                ->setCellValue("BD$cont13", "ShippingMethod")
                ->setCellValue("BE$cont13", "CorrectionInvoiceItem")
                ->setCellValue("BF$cont13", "CorrInvAmountToStock")
                ->setCellValue("BG$cont13", "CorrInvAmountToDiffAcct")
                ->setCellValue("BH$cont13", "WTLiable")
                ->setCellValue("BI$cont13", "DeferredTax")
                ->setCellValue("BJ$cont13", "MeasureUnit")
                ->setCellValue("BK$cont13", "UnitsOfMeasurment")
                ->setCellValue("BL$cont13", "LineTotal")
                ->setCellValue("BM$cont13", "TaxPercentagePerRow")
                ->setCellValue("BN$cont13", "ConsumerSalesForecast")
                ->setCellValue("BO$cont13", "ExciseAmount")
                ->setCellValue("BP$cont13", "CountryOrg")
                ->setCellValue("BQ$cont13", "SWW")
                ->setCellValue("BR$cont13", "TransactionType")
                ->setCellValue("BS$cont13", "DistributeExpense")
                ->setCellValue("BT$cont13", "ShipToCode")
                ->setCellValue("BU$cont13", "RowTotalFC")
                ->setCellValue("BV$cont13", "CFOPCode")
                ->setCellValue("BW$cont13", "CSTCode")
                ->setCellValue("BX$cont13", "Usage")
                ->setCellValue("BY$cont13", "TaxOnly")
                ->setCellValue("BZ$cont13", "UnitPrice")

                ->setCellValue("CA$cont13", "LineStatus")
                ->setCellValue("CB$cont13", "LineType")
                ->setCellValue("CC$cont13", "COGSCostingCode")
                ->setCellValue("CD$cont13", "COGSAccountCode")
                ->setCellValue("CE$cont13", "ChangeAssemlyBoMWarehouse")
                ->setCellValue("CF$cont13", "GrossBuyPrice")
                ->setCellValue("CG$cont13", "GrossBase")
                ->setCellValue("CH$cont13", "GrossProfitTotalBasePrice")
                ->setCellValue("CI$cont13", "CostingCode2")
                ->setCellValue("CJ$cont13", "CostingCode3")
                ->setCellValue("CK$cont13", "CostingCode4")
                ->setCellValue("CL$cont13", "CostingCode5")
                ->setCellValue("CM$cont13", "ItemDetails")
                ->setCellValue("CN$cont13", "LocationCode")
                ->setCellValue("CO$cont13", "ActualDeliveryDate")
                ->setCellValue("CP$cont13", "ExLineNo");
        
        // El indice de la fila aumento en 1
        // cont13 = 2;
        $cont13+= 1;

        // Pagina 6 | Encabezados 2
        $spreadsheet->setActiveSheetIndex(5)
                ->setCellValue("A$cont13", "DocNum")
                ->setCellValue("B$cont13", "LineNum")
                ->setCellValue("C$cont13", "ItemCode")
                ->setCellValue("D$cont13", "Descripcion")
                ->setCellValue("E$cont13", "Quantity")
                ->setCellValue("F$cont13", "ShipDate")
                ->setCellValue("G$cont13", "Subtotal")
                ->setCellValue("H$cont13", "PriceAfVAT")
                ->setCellValue("I$cont13", "Moneda")
                ->setCellValue("J$cont13", "Tipo de Cambio")
                ->setCellValue("K$cont13", "DiscPrcnt")
                ->setCellValue("L$cont13", "VendorNum")
                ->setCellValue("M$cont13", "SerialNum")
                ->setCellValue("N$cont13", "WhsCode")
                ->setCellValue("O$cont13", "Vendedor")
                ->setCellValue("P$cont13", "Commission")
                ->setCellValue("Q$cont13", "TreeType")
                ->setCellValue("R$cont13", "AcctCode")
                ->setCellValue("S$cont13", "UseBaseUn")
                ->setCellValue("T$cont13", "SubCatNum")
                ->setCellValue("U$cont13", "Centro de Costo")
                ->setCellValue("V$cont13", "Proyecto")
                ->setCellValue("W$cont13", "CodeBars")
                ->setCellValue("X$cont13", "VatGroup")
                ->setCellValue("Y$cont13", "Height1")
                ->setCellValue("Z$cont13", "Hght1Unit")

                ->setCellValue("AA$cont13", "Height2")
                ->setCellValue("AB$cont13", "Hght2Unit")
                ->setCellValue("AC$cont13", "Length1")
                ->setCellValue("AD$cont13", "Len1Unit")
                ->setCellValue("AE$cont13", "length2")
                ->setCellValue("AF$cont13", "Len2Unit")
                ->setCellValue("AG$cont13", "Weight1")
                ->setCellValue("AH$cont13", "Wght1Unit")
                ->setCellValue("AI$cont13", "Weight2")
                ->setCellValue("AJ$cont13", "Wght2Unit")
                ->setCellValue("AK$cont13", "Factor1")
                ->setCellValue("AL$cont13", "Factor2")
                ->setCellValue("AM$cont13", "Factor3")
                ->setCellValue("AN$cont13", "Factor4")
                ->setCellValue("AO$cont13", "BaseType")
                ->setCellValue("AP$cont13", "BaseEntry")
                ->setCellValue("AQ$cont13", "BaseLine")
                ->setCellValue("AR$cont13", "Volume")
                ->setCellValue("AS$cont13", "VolUnit")
                ->setCellValue("AT$cont13", "Width1")
                ->setCellValue("AU$cont13", "Wdth1Unit")
                ->setCellValue("AV$cont13", "Width2")
                ->setCellValue("AW$cont13", "Wdth2Unit")
                ->setCellValue("AX$cont13", "Address")
                ->setCellValue("AY$cont13", "Codigo IVA")
                ->setCellValue("AZ$cont13", "TaxType")

                ->setCellValue("BA$cont13", "TaxStatus")
                ->setCellValue("BB$cont13", "BackOrdr")
                ->setCellValue("BC$cont13", "FreeTxt")
                ->setCellValue("BD$cont13", "TrnsCode")
                ->setCellValue("BE$cont13", "CEECFlag")
                ->setCellValue("BF$cont13", "ToStock")
                ->setCellValue("BG$cont13", "ToDiff")
                ->setCellValue("BH$cont13", "WtLiable")
                ->setCellValue("BI$cont13", "DeferrTax")
                ->setCellValue("BJ$cont13", "unitMsr")
                ->setCellValue("BK$cont13", "NumPerMsr")
                ->setCellValue("BL$cont13", "Subtotal")
                ->setCellValue("BM$cont13", "VatPrcnt")
                ->setCellValue("BN$cont13", "ConsumeFCT")
                ->setCellValue("BO$cont13", "ExciseAmt")
                ->setCellValue("BP$cont13", "CountryOrg")
                ->setCellValue("BQ$cont13", "SWW")
                ->setCellValue("BR$cont13", "TranType")
                ->setCellValue("BS$cont13", "DistribExp")
                ->setCellValue("BT$cont13", "ShipToCode")
                ->setCellValue("BU$cont13", "TotalFrgn")
                ->setCellValue("BV$cont13", "CFOPCode")
                ->setCellValue("BW$cont13", "CSTCode")
                ->setCellValue("BX$cont13", "Usage")
                ->setCellValue("BY$cont13", "TaxOnly")
                ->setCellValue("BZ$cont13", "PriceBefDi")

                ->setCellValue("CA$cont13", "LineStatus")
                ->setCellValue("CB$cont13", "LineType")
                ->setCellValue("CC$cont13", "CogsOcrCod")
                ->setCellValue("CD$cont13", "CogsAcct")
                ->setCellValue("CE$cont13", "ChgAsmBoMW")
                ->setCellValue("CF$cont13", "GrossBuyPr")
                ->setCellValue("CG$cont13", "GrossBase")
                ->setCellValue("CH$cont13", "GPTtlBasPr")
                ->setCellValue("CI$cont13", "OcrCode2")
                ->setCellValue("CJ$cont13", "OcrCode3")
                ->setCellValue("CK$cont13", "OcrCode4")
                ->setCellValue("CL$cont13", "OcrCode5")
                ->setCellValue("CM$cont13", "Text")
                ->setCellValue("CN$cont13", "LocCode")
                ->setCellValue("CO$cont13", "ActDelDate")
                ->setCellValue("CP$cont13", "ExLineNo");

        // { CALL sp_GET_ecnTur_reporte_extracto_14102016(?,?) }
        $query = "  SELECT  
                        G.gas_fecha_aprobacion,
                        D.gasd_id,
                        F.fac_id AS fac_id,
                        GETDATE() AS DocDate,
                        DATEADD(DAY,15,GETDATE()) AS Vencimiento,
                        (SELECT TOP 1 CardCode FROM [SAPSERVER].[SBO_ECN].[dbo].[OCRD]  WHERE LicTradNum=F.fac_emisorRFC  COLLATE DATABASE_DEFAULT AND frozenfor='N' ORDER BY CreateDate ASC) AS CodigoProv ,
                        F.fac_emisorRFC AS RFC,
                        F.fac_emisorNombre AS Name_RS,
                        CASE F.fac_folio
                            WHEN 'Xml sin folio' THEN (SELECT RIGHT(F.fac_ComplementoUUID, 6))
                            ELSE RIGHT(F.fac_folio,10) 
                        END AS Folio,
                        CASE ISNULL(F.fac_id,0)
                            WHEN 0 THEN D.gasd_monto 
                            else F.fac_total
                        END as total,
                        SUBSTRING(G.gas_claveInforme,1,2) + SUBSTRING(G.gas_claveInforme,5,10) AS ClaveInf,
                        C.nombres + ' ' + C.apellido_p + ' ' + C.apellido_m AS nombre,
                        CASE  S.sol_proyectoServicioOpcion
                            WHEN '0' THEN 'GASTO'
                            else S.sol_proyectoServicioOpcion
                        END +' '+
                        'F/'+ CASE F.fac_folio
                            WHEN 'Xml sin folio' THEN (SELECT RIGHT(F.fac_ComplementoUUID, 6))
                            WHEN '' THEN (SELECT RIGHT(F.fac_ComplementoUUID, 6))
                            ELSE F.fac_folio
                        END +' '+
                        CASE D.gasd_tipoGasto
                            WHEN 8 THEN 'CONSUMO'
                            WHEN 9 THEN 'CONSUMO'
                            WHEN 5 THEN 'VIATICOS T'
                            WHEN 24 THEN 'HOSPEDAJE'
                            WHEN 1 THEN 'CASETAS'
                            WHEN 2 THEN 'COMBUSTIBLE'
                            WHEN 17 THEN 'CELULAR'
                            WHEN 25 THEN 'LAVANDERIA'
                            WHEN 30 THEN 'VARIOS'
                        END AS comentario,
                        CASE  S.sol_proyectoServicioOpcion
                            WHEN '0' THEN 'GASTO'
                            ELSE S.sol_proyectoServicioOpcion
                        END +' '+
                        'F/'+ CASE F.fac_folio
                            WHEN 'Xml sin folio' THEN (SELECT RIGHT(F.fac_ComplementoUUID, 6))
                            ELSE F.fac_folio
                        END +' '+
                        S.sol_origen +' - '+S.sol_destino AS comentario2,
                        F.fac_ComplementoFechaTimbrado AS fecha_timbrado,
                        CASE  S.sol_proyectoServicioOpcion
                            WHEN '0' THEN 'GASTO'
                            ELSE  S.sol_proyectoServicioOpcion
                            END+' '+CASE ISNULL(F.fac_id,0)
                            WHEN 0 THEN 'NO DEDUCIBLE'
                        END AS coment2,
                        --IIF(S.sol_proyectoServicioOpcion = '0' OR S.sol_proyectoServicioOpcion like 'D-%' ,'0', S.sol_proyectoServicioOpcion) as proy,
                        S.sol_proyectoServicioOpcion AS proy,
                        S.sol_departamentos_id AS depto,
                        S.sol_geografica_id AS geo,
                        F.fac_subTotal AS subtotal,
                        F.fac_impuestosTotalImpuestosTrasladados AS impuestos,
                        IIF(F.fac_TrasladosImpuesto='',NULL,F.fac_TrasladosImpuesto) AS impues,
                        CONVERT(FLOAT,F.fac_TrasladosImporte) AS impuesto_importe,
                        D.gasd_tipoGasto as tipoGasto,
                        F.fac_noReferencia as OtrosImp,
                        CONVERT(FLOAT,F.fac_impuestosTotalImpuestosRetenidos) AS imp_ret,
                        CONVERT(FLOAT,F.fac_descuento) AS descuento,
                        F.fac_noOrdenCompra AS ieps,
                        F.fac_emisorRfc AS RFC2,
                        S.sol_nombre_solicitante AS solicitante,
                        S.sol_origen AS origen,
                        S.sol_destino  AS destino,
                        --C.id_vendedorSap as slpCode,
                        isnull(T2.slpcode,-1) AS slpCode,
                        IIF(S.sol_proyectoServicioOpcion = '0' OR S.sol_proyectoServicioOpcion LIKE 'D-%' ,6, 5) AS tipo_cuenta
                        
                    FROM tbl_tur_gastosDetalle D 
                        LEFT JOIN tbl_tur_gastos G ON D.gasd_informe=G.gas_id 
                    --LEFT JOIN [SAPSERVER].[SBO_ECN].[dbo].[OCRD] P ON D.gasd_RFC=P.LicTradNum  COLLATE DATABASE_DEFAULT
                        LEFT JOIN tbl_tur_facXML F ON D.gasd_id = F.fac_gasd_id
                        LEFT JOIN tbl_tur_solicitudes S ON G.gas_sol_id=S.sol_id
                        LEFT JOIN Colaboradores C ON S.sol_nombre_solicitante=C.id_colaborador 
                        LEFT JOIN SYN_OSLP T2 ON C.n_colaborador=T2.U_ID_ECN COLLATE DATABASE_DEFAULT AND T2.active='Y'
                        
                    WHERE 
                        G.gas_estado=2 AND 
                        (G.gas_fecha_aprobacion BETWEEN '$fInicio' AND '$fFinal' ) AND 
                        D.gasd_tipoGasto != 30 AND 
                        D.gasd_status=1 

                    ORDER BY g.gas_claveInforme";
        
        $result = sqlsrv_query($this->db->connID, $query);

        $cont+= 1;
        $cont3+= 1;
        $cont5+= 1;
        $cont7+= 1;
        $cont9+= 1;
        $cont13+= 1;

        $contlinA = 3;
        $contlinNumA=1;

        
        while($row = sqlsrv_fetch_array($result)){

            $docdate = date_format(date_create($row['DocDate']),'Ymd');
            $Vencimiento = date_format(date_create($row['Vencimiento']),'Ymd');
            $CodigoProv = $row['CodigoProv'];
            $Folio = $row['Folio'];
            $total = $row['total'];
            $ClaveInf = $row['ClaveInf'];
            $nombre = $row['nombre'];
            $comentario = $row['comentario'];
            
            $fecha_timbrado = $row['fecha_timbrado'];
            $coment2 = $row['coment2'];
            $RFC = $row['RFC'];
            $proy = $row['proy'];
            $depto = $row['depto'];
            $geo = $row['geo'];
            $tipoGasto = $row['tipoGasto'];
            $subtotal = $row['subtotal'];
            $impuestos = $row['impuestos'];
            $impues = $row['impues'];
            $impuesto_importe = $row['impuesto_importe'];
            $OtrosImp = $row['OtrosImp'];
            $ieps = $row['ieps'];
            $solicitante = $row['solicitante'];
            $RS = $row['Name_RS'];
            $origen = strtoupper($row['origen']);
            $destino = strtoupper($row['destino']);
            $comentario2 = strtoupper($row['comentario2']);
            $imp_ret = $row['imp_ret'];
            $descuento = $row['descuento'];
            $tipo_cuenta = $row['tipo_cuenta'];

            $slpCode = $row['slpCode'];

            $i=1;

            $nombre = str_replace("Á","A", $nombre);
            $nombre = str_replace("É","E", $nombre);
            $nombre = str_replace("Í","I", $nombre);
            $nombre = str_replace("Ó","O", $nombre);
            $nombre = str_replace("Ú","U", $nombre);
            $nombre = str_replace("Ü","U", $nombre);

            $nombre = str_replace("á","a", $nombre);
            $nombre = str_replace("é","e", $nombre);
            $nombre = str_replace("í","i", $nombre);
            $nombre = str_replace("ó","o", $nombre);
            $nombre = str_replace("ú","u", $nombre);
            $nombre = str_replace("ü","u", $nombre);

            $words=explode(" ",$nombre);
            $inits='';

            foreach($words as $word) $inits.=strtoupper(substr($word,0,1));

            $comentarios1 = $ClaveInf.' '.$inits.' '.$comentario;
            $comentarios = $ClaveInf.' '.$inits.' '.$comentario2;
            $comentarios2 = $ClaveInf.' '.$inits.' '.$coment2;

            if ($proy == '0') 
                $proyecto = '';
            else 
                $proyecto = $proy;
            
            if (is_null($Folio) && is_null($fecha_timbrado)) {

                ///// FUE SOLAMENTE RECIBO ///
                $docdate = str_replace('-','', $docdate);
                if ($proy == '0') {
                    // $tipo_cuenta=6;
                    $proyecto = '';
                }else{
                    //$tipo_cuenta=5;
                    $proyecto = $proy;
                }

                $query2 = "SELECT cuenta FROM tbl_relacionCuentasSAP WHERE tipo_gasto=0 and tipo=$tipo_cuenta";
                $result2 = sqlsrv_query($this->db->connID, $query2);
                $row2 = sqlsrv_fetch_array($result2);
                if (is_null($row2['cuenta'])) 
                    $cuenta='--';
                else
                    $cuenta = $row2['cuenta'];

                $query3 = "SELECT cta_SAP FROM tbl_cuentasColaborador WHERE cta_colaborador_id=$solicitante";
                $result3 = sqlsrv_query($this->db->connID,$query3, array(), array( "Scrollable" => 'static' ));
                $rwcount=sqlsrv_num_rows($result3);
                $row3 = sqlsrv_fetch_array($result3);
                if($rwcount>0){
                    if (is_null($row3['cta_SAP'])) 
                        $cuenta_colaborador='--';
                    else
                        $cuenta_colaborador = $row3['cta_SAP'];
                }else
                    $cuenta_colaborador='--';
                
                $spreadsheet->setActiveSheetIndex(1)
                        ->setCellValue('A'.$cont2, $cont7) // jdtNum
                        ->setCellValue('B'.$cont2, $docdate) // refDate
                        ->setCellValue('C'.$cont2, $comentarios2) // Memo
                        ->setCellValue('D'.$cont2, $comentarios2) // ref1
                        ->setCellValue('E'.$cont2, 'V_INTRANET') // ref2
                        ->setCellValue('H'.$cont2, $docdate) // TaxDate
                        ->setCellValue('O'.$cont2, $docdate); // DueDate
        
        
                for ($i=1; $i <= 2 ; $i++) { 
                    // DueDate
                    $spreadsheet->setActiveSheetIndex(3)
                            ->setCellValue('A'.$contlinA, $cont7)
                            ->setCellValue('B'.$contlinA, $i);

                    if ($i == 1) {
                        $spreadsheet->setActiveSheetIndex(3)
                                ->setCellValue('E'.$contlinA, number_format($total,2, '.', ''))
                                ->setCellValue('D'.$contlinA, $cuenta);
                    }else{
                        $spreadsheet->setActiveSheetIndex(3)
                                ->setCellValue('F'.$contlinA, number_format($total,2, '.', ''))
                                ->setCellValue('D'.$contlinA, $cuenta_colaborador);
                    }

                    $spreadsheet->setActiveSheetIndex(3)
                            ->setCellValue('M'.$contlinA, $comentarios2)
                            ->setCellValue('N'.$contlinA, $docdate)
                            ->setCellValue('P'.$contlinA, $comentarios2)
                            ->setCellValue('Q'.$contlinA, $comentarios2);

                    if ($proy != '0') {
                        $spreadsheet->setActiveSheetIndex(3)
                                ->setCellValue('R'.$contlinA, $proy);
                    }

                    $spreadsheet->setActiveSheetIndex(3)
                            ->setCellValue('S'.$contlinA, $depto)
                            ->setCellValue('AE'.$contlinA, 'ND')
                            ->setCellValue('AF'.$contlinA, $geo);

                    $contlinA=$contlinA +1;
                }
                $i=1;
        
                $cont2++;
                $cont7++;
            
            }else{
                if (is_null($CodigoProv)) {

                    /// PROVEEDIRES FALTANTES
                    $fecha_timbrado = substr($row['fecha_timbrado'],0,10);
                    $fecha_timbrado = str_replace('-','', $fecha_timbrado);
        
                    $spreadsheet->setActiveSheetIndex(2)
                            ->setCellValue('A'.$cont5, $cont6) // DocNum
                            ->setCellValue('B'.$cont5, 'dDocument_Service') // DocType
                            ->setCellValue('E'.$cont5, $docdate) // DocDate
                            ->setCellValue('F'.$cont5, $Vencimiento) // DocDueDate
                            ->setCellValue('G'.$cont5, $RFC) // CardCode
                            ->setCellValue('H'.$cont5, $RS) // CardName
                            ->setCellValue('J'.$cont5, $Folio) // NumAtCard
                            ->setCellValue('K'.$cont5, 'MXP') // currency
                            ->setCellValue('M'.$cont5, number_format($total,2, '.', '')) // DocTotal 
                            ->setCellValue('O'.$cont5, 'V_INTRANET') // REF2
                            ->setCellValue('P'.$cont5, $comentarios) // comentarios
                            ->setCellValue('Q'.$cont5, $comentarios1) // jrnlmemo
                            ->setCellValue('T'.$cont5, $slpCode) // SLPCODE
                            ->setCellValue('AB'.$cont5, $fecha_timbrado); // jrnlmemo
  
                    $fecha_timbrado = substr($row['fecha_timbrado'],0,10);
                    $fecha_timbrado = str_replace('-','', $fecha_timbrado);

                    $query4 = "SELECT cuenta FROM tbl_relacionCuentasSAP where tipo_gasto=$tipoGasto and tipo=$tipo_cuenta";
                    $result4 = sqlsrv_query($this->db->connID,$query4);
                    $row = sqlsrv_fetch_array($result4);
                    if (is_null($row['cuenta']))
                        $cuenta='--';
                    else
                        $cuenta = $row['cuenta'];

                        // NO SE USA | AHORRO DE TIEMPO
                    
                    //    $query5 = "SELECT cuenta FROM tbl_relacionCuentasSAP where tipo_gasto=1000 and tipo=$tipo_cuenta";
                    //    $result5 = sqlsrv_query($this->db->connID,$query5);
                    //    $row = sqlsrv_fetch_array($result5);
                    //    if (is_null($row['cuenta'])) 
                    //        $cuenta_otrosImpuestos='--';
                    //    else
                    //        $cuenta_otrosImpuestos = $row['cuenta'];
                    

                    if ($tipoGasto == 24) {
                        if ($OtrosImp == '0') {

                            $base = (floatval($impuesto_importe))/(0.16);
                            if ($impuesto_importe == 0 || $impuesto_importe == '0') {
                                   
                                $linea=1;
                                $spreadsheet->setActiveSheetIndex(5)
                                        ->setCellValue('A'.$cont13, $cont6)
                                        ->setCellValue('B'.$cont13, $linea)
                                        ->setCellValue('D'.$cont13, $comentarios)
                                        ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                        ->setCellValue('I'.$cont13, 'MXP')
                                        ->setCellValue('O'.$cont13, $slpCode)
                                        ->setCellValue('R'.$cont13, $cuenta)
                                        ->setCellValue('U'.$cont13, $depto)
                                        ->setCellValue('V'.$cont13, $proyecto)
                                        ->setCellValue('AY'.$cont13, 'V0')
                                        ->setCellValue('BC'.$cont13, $comentarios)
                                        ->setCellValue('BL'.$cont13,number_format($subtotal,2, '.', ''))
                                        ->setCellValue('CI'.$cont13, $geo);

                                $cont13 = $cont13+1;

                            }else{
                                if (number_format($subtotal,1, '.', '') == number_format($base,1, '.', '')) {
                                    $linea=1;
                                    $spreadsheet->setActiveSheetIndex(5)
                                            ->setCellValue('A'.$cont13, $cont6)
                                            ->setCellValue('B'.$cont13, $linea)
                                            ->setCellValue('D'.$cont13, $comentarios)
                                            ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                            ->setCellValue('I'.$cont13, 'MXP')
                                            ->setCellValue('O'.$cont13, $slpCode)
                                            ->setCellValue('R'.$cont13, $cuenta)
                                            ->setCellValue('U'.$cont13, $depto)
                                            ->setCellValue('V'.$cont13, $proyecto)
                                            ->setCellValue('AY'.$cont13, 'V2')
                                            ->setCellValue('BC'.$cont13, $comentarios)
                                            ->setCellValue('BL'.$cont13,number_format($subtotal,2, '.', ''))
                                            ->setCellValue('CI'.$cont13, $geo);

                                    $cont13 = $cont13+1;
                                }
                            }
                        }else{
                            $base = number_format($impuesto_importe,2, '.', '')/(0.16);

                            if (number_format($subtotal,0, '.', '') == number_format($base,0, '.', '')) {
                                for ($i=1; $i <=2; $i++) {
                                    $spreadsheet->setActiveSheetIndex(5)
                                            ->setCellValue('A'.$cont13, $cont6)
                                            ->setCellValue('B'.$cont13, $i)
                                            ->setCellValue('D'.$cont13, $comentarios)
                                            ->setCellValue('I'.$cont13, 'MXP')
                                            ->setCellValue('O'.$cont13, $slpCode)
                                            ->setCellValue('U'.$cont13, $depto);

                                    if ($i == 1) {
                                        $spreadsheet->setActiveSheetIndex(5)
                                                ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                                ->setCellValue('R'.$cont13, $cuenta)
                                                ->setCellValue('V'.$cont13, $proyecto)
                                                ->setCellValue('AY'.$cont13, 'V2')
                                                ->setCellValue('BL'.$cont13, number_format($subtotal,2, '.', ''));
                                    }else{
                                        $spreadsheet->setActiveSheetIndex(5)
                                                ->setCellValue('G'.$cont13, number_format($OtrosImp,2, '.', ''))
                                                ->setCellValue('R'.$cont13, $cuenta)
                                                ->setCellValue('V'.$cont13, $proyecto)
                                                ->setCellValue('AY'.$cont13, 'VE')
                                                ->setCellValue('BL'.$cont13, number_format($OtrosImp,2, '.', ''));
                                    }

                                    $spreadsheet->setActiveSheetIndex(5)
                                            ->setCellValue('BC'.$cont13, $comentarios)
                                            ->setCellValue('CI'.$cont13, $geo);

                                    $cont13 = $cont13+1;
                                }
                                   $i=1;
                            }else{
                                  $diferencia = (number_format($subtotal,2, '.', '')-number_format($base,2, '.', ''));
                                  for ($i=1; $i <=3 ; $i++) { 
                                    $spreadsheet->setActiveSheetIndex(5)
                                            ->setCellValue('A'.$cont13, $cont6)
                                            ->setCellValue('B'.$cont13, $i)
                                            ->setCellValue('D'.$cont13, $comentarios)
                                            ->setCellValue('I'.$cont13, 'MXP')
                                            ->setCellValue('O'.$cont13, $slpCode)
                                            ->setCellValue('U'.$cont13, $depto);

                                    if ($i == 1) {
                                        $spreadsheet->setActiveSheetIndex(5)
                                                ->setCellValue('G'.$cont13, number_format($base,2, '.', ''))
                                                ->setCellValue('AY'.$cont13, 'V2')
                                                ->setCellValue('R'.$cont13, $cuenta)
                                                ->setCellValue('V'.$cont13, $proyecto)
                                                ->setCellValue('BL'.$cont13, number_format($base,2, '.', ''));
                                    }elseif ($i == 2) {
                                        $spreadsheet->setActiveSheetIndex(5)
                                                ->setCellValue('G'.$cont13, number_format($diferencia,2, '.', ''))
                                                ->setCellValue('R'.$cont13, $cuenta)
                                                ->setCellValue('V'.$cont13, $proyecto)
                                                ->setCellValue('AY'.$cont13, 'V0')
                                                ->setCellValue('BL'.$cont13, number_format($diferencia,2, '.', ''));
                                    }elseif ($i == 3) {
                                        $spreadsheet->setActiveSheetIndex(5)
                                                ->setCellValue('G'.$cont13, number_format($OtrosImp,2, '.', ''))
                                                ->setCellValue('R'.$cont13, $cuenta)
                                                ->setCellValue('V'.$cont13, $proyecto)
                                                ->setCellValue('AY'.$cont13, 'VE')
                                                ->setCellValue('BL'.$cont13, number_format($OtrosImp,2, '.', ''));
                                    }

                                    $spreadsheet->setActiveSheetIndex(5)
                                            ->setCellValue('BC'.$cont13, $comentarios)
                                            ->setCellValue('CI'.$cont13, $geo);

                                    $cont13 = $cont13+1;

                                }
                                $i=1;
                            }
                        }
                    }elseif ($tipoGasto == 2) {
                        $base = number_format($impuesto_importe,2, '.', '')/(0.16);

                         for ($i=1; $i <=2 ; $i++) {
                            $spreadsheet->setActiveSheetIndex(5)
                                    ->setCellValue('A'.$cont13, $cont6)
                                    ->setCellValue('B'.$cont13, $i)
                                    ->setCellValue('D'.$cont13, $comentarios)
                                    ->setCellValue('I'.$cont13, 'MXP')
                                    ->setCellValue('O'.$cont13, $slpCode)
                                    ->setCellValue('U'.$cont13, $depto);

                            if ($i == 1) {
                                $spreadsheet->setActiveSheetIndex(5)
                                        ->setCellValue('G'.$cont13, number_format($base,2, '.', ''))
                                        ->setCellValue('R'.$cont13, $cuenta)
                                        ->setCellValue('V'.$cont13, $proyecto)
                                        ->setCellValue('AY'.$cont13, 'V2')
                                        ->setCellValue('BL'.$cont13, number_format($base,2, '.', ''));

                            }elseif($i == 2){
                                $spreadsheet->setActiveSheetIndex(5)
                                        ->setCellValue('G'.$cont13, number_format($ieps,2, '.', ''))
                                        ->setCellValue('R'.$cont13, $cuenta)
                                        ->setCellValue('V'.$cont13, $proyecto)
                                        ->setCellValue('AY'.$cont13, 'VE')
                                        ->setCellValue('BL'.$cont13, number_format($ieps,2, '.', ''));
                            }

                            $spreadsheet->setActiveSheetIndex(5)
                                    ->setCellValue('BC'.$cont13, $comentarios)
                                    ->setCellValue('CI'.$cont13, $geo);

                            $cont13 = $cont13+1;
                        }
                        $i=1;
                    }else{

                        if (floatval($total) == floatval($subtotal) ) {
                            $spreadsheet->setActiveSheetIndex(5)
                                    ->setCellValue('A'.$cont13, $cont6)
                                    ->setCellValue('B'.$cont13, '1')
                                    ->setCellValue('D'.$cont13, $comentarios)
                                    ->setCellValue('I'.$cont13, 'MXP')
                                    ->setCellValue('O'.$cont13, $slpCode)
                                    ->setCellValue('U'.$cont13, $depto)
                                    ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                    ->setCellValue('R'.$cont13, $cuenta)
                                    ->setCellValue('V'.$cont13, $proyecto)
                                    ->setCellValue('AY'.$cont13, 'V0')
                                    ->setCellValue('BL'.$cont13,number_format($subtotal,2, '.', ''))
                                    ->setCellValue('BC'.$cont13, $comentarios)
                                    ->setCellValue('CI'.$cont13, $geo);
                             
                            $cont13 = $cont13+1;

                        }else{
                            $base = number_format($impuesto_importe,2, '.', '')/(0.16);

                            if (number_format($base,0, '.', '') == number_format($subtotal,0, '.', '')) {
                                $spreadsheet->setActiveSheetIndex(5)
                                        ->setCellValue('A'.$cont13, $cont6)
                                        ->setCellValue('B'.$cont13, '1')
                                        ->setCellValue('D'.$cont13, $comentarios)
                                        ->setCellValue('I'.$cont13, 'MXP')
                                        ->setCellValue('O'.$cont13, $slpCode)
                                        ->setCellValue('U'.$cont13, $depto)
                                        ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                        ->setCellValue('R'.$cont13, $cuenta)
                                        ->setCellValue('V'.$cont13, $proyecto)
                                        ->setCellValue('AY'.$cont13, 'V2')
                                        ->setCellValue('BL'.$cont13,number_format($subtotal,2, '.', ''))
                                        ->setCellValue('BC'.$cont13, $comentarios)
                                        ->setCellValue('CI'.$cont13, $geo);

                                $cont13 = $cont13+1;
                            }else{
                                if ($impues == 'IEPS') {
                                    if ($impuestos == 0 || $impuestos =='0') {
                                        if (floatval($total) != floatval($subtotal)) {
                                            $spreadsheet->setActiveSheetIndex(5)
                                                    ->setCellValue('A'.$cont13, $cont6)
                                                    ->setCellValue('B'.$cont13, '1')
                                                    ->setCellValue('D'.$cont13, $comentarios)
                                                    ->setCellValue('I'.$cont13, 'MXP')
                                                    ->setCellValue('O'.$cont13, $slpCode)
                                                    ->setCellValue('U'.$cont13, $depto)
                                                    ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                                    ->setCellValue('R'.$cont13, $cuenta)
                                                    ->setCellValue('V'.$cont13, $proyecto)
                                                    ->setCellValue('AY'.$cont13, 'V2')
                                                    ->setCellValue('BL'.$cont13, number_format($subtotal,2, '.', ''))
                                                    ->setCellValue('BC'.$cont13, $comentarios)
                                                    ->setCellValue('CI'.$cont13, $geo);

                                            $cont13 = $cont13+1;
                                        }    
                                    }elseif(number_format($impuestos,0, '.', '') == number_format($impuesto_importe,0, '.', '')){

                                        for ($i=1; $i <=2 ; $i++) {
                                            $spreadsheet->setActiveSheetIndex(5)
                                                    ->setCellValue('A'.$cont13, $cont6)
                                                    ->setCellValue('B'.$cont13, $i)
                                                    ->setCellValue('D'.$cont13, $comentarios)
                                                    ->setCellValue('I'.$cont13, 'MXP')
                                                    ->setCellValue('O'.$cont13, $slpCode)
                                                    ->setCellValue('U'.$cont13, $depto);

                                            if ($i == 1) {
                                                $spreadsheet->setActiveSheetIndex(5)
                                                        ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                                        ->setCellValue('R'.$cont13, $cuenta)
                                                        ->setCellValue('V'.$cont13, $proyecto)
                                                        ->setCellValue('AY'.$cont13, 'V0')
                                                        ->setCellValue('BL'.$cont13, number_format($subtotal,2, '.', ''));

                                            }elseif ($i == 2) {
                                                $spreadsheet->setActiveSheetIndex(5)
                                                        ->setCellValue('G'.$cont13, number_format($impuesto_importe,2, '.', ''))
                                                        ->setCellValue('R'.$cont13, $cuenta)
                                                        ->setCellValue('V'.$cont13, $proyecto)
                                                        ->setCellValue('AY'.$cont13, 'VE')
                                                        ->setCellValue('BL'.$cont13, number_format($impuesto_importe,2, '.', ''));
                                            }
                                            $spreadsheet->setActiveSheetIndex(5)
                                                    ->setCellValue('BC'.$cont13, $comentarios)
                                                    ->setCellValue('CI'.$cont13, $geo);

                                            $cont13+=1;

                                        }

                                        $i=1;

                                    }else{

                                        $base = number_format($impuestos,2, '.', '')/0.16;
                                        $diferencia = (number_format($subtotal,2, '.', '')-number_format($base,2, '.', ''));

                                        for ($i=1; $i <=3 ; $i++) {
                                            $spreadsheet->setActiveSheetIndex(5)
                                                    ->setCellValue('A'.$cont13, $cont6)
                                                    ->setCellValue('B'.$cont13, $i)
                                                    ->setCellValue('D'.$cont13, $comentarios)
                                                    ->setCellValue('I'.$cont13, 'MXP')
                                                    ->setCellValue('O'.$cont13, $slpCode)
                                                    ->setCellValue('U'.$cont13, $depto);

                                            if ($i == 1) {
                                                $spreadsheet->setActiveSheetIndex(5)
                                                        ->setCellValue('G'.$cont13, number_format($base,2, '.', ''))
                                                        ->setCellValue('R'.$cont13, $cuenta)
                                                        ->setCellValue('V'.$cont13, $proyecto)
                                                        ->setCellValue('AY'.$cont13, 'V2')
                                                        ->setCellValue('BL'.$cont13, number_format($base,2, '.', ''));

                                            }elseif ($i == 2) {
                                                $spreadsheet->setActiveSheetIndex(5)
                                                        ->setCellValue('G'.$cont13, number_format($diferencia,2, '.', ''))
                                                        ->setCellValue('R'.$cont13, $cuenta)
                                                        ->setCellValue('V'.$cont13, $proyecto)
                                                        ->setCellValue('AY'.$cont13, 'V0')
                                                        ->setCellValue('BL'.$cont13, number_format($diferencia,2, '.', ''));

                                            }elseif ($i == 3) {
                                                $spreadsheet->setActiveSheetIndex(5)
                                                        ->setCellValue('G'.$cont13, number_format($impuesto_importe,2, '.', ''))
                                                        ->setCellValue('R'.$cont13, $cuenta)
                                                        ->setCellValue('V'.$cont13, $proyecto)
                                                        ->setCellValue('AY'.$cont13, 'VE')
                                                        ->setCellValue('BL'.$cont13, number_format($impuesto_importe,2, '.', ''));
                                            }


                                            $spreadsheet->setActiveSheetIndex(5)
                                                    ->setCellValue('BC'.$cont13, $comentarios)
                                                    ->setCellValue('CI'.$cont13, $geo);

                                            $cont13 = $cont13+1;

                                        }
                                        $i=1;

                                    }      

                                }else{

                                    if (number_format($impuestos,0, '.', '') == number_format($impuesto_importe,0, '.', '')) {

                                        $base = (number_format($impuesto_importe,2, '.', '')/0.16);
                                        $diferencia = (number_format($subtotal,2, '.', '')- number_format($base,2, '.', ''));

                                        if (number_format($base,1, '.', '') != number_format($subtotal,1, '.', '')) {

                                            for ($i=1; $i <=2 ; $i++) { 
                                                $spreadsheet->setActiveSheetIndex(5)
                                                        ->setCellValue('A'.$cont13, $cont6)
                                                        ->setCellValue('B'.$cont13, 1)
                                                        ->setCellValue('D'.$cont13, $comentarios)
                                                        ->setCellValue('I'.$cont13, 'MXP')
                                                        ->setCellValue('O'.$cont13, $slpCode)
                                                        ->setCellValue('U'.$cont13, $depto);

                                                if ($i == 1) {
                                                    $spreadsheet->setActiveSheetIndex(5)
                                                            ->setCellValue('G'.$cont13, number_format($base,2, '.', ''))
                                                            ->setCellValue('R'.$cont13, $cuenta)
                                                            ->setCellValue('AY'.$cont13, 'V2')
                                                            ->setCellValue('BL'.$cont13, number_format($base,2, '.', ''));

                                                }else{
                                                    $spreadsheet->setActiveSheetIndex(5)
                                                            ->setCellValue('G'.$cont13, number_format($diferencia,2, '.', ''))
                                                            ->setCellValue('R'.$cont13, $cuenta)
                                                            ->setCellValue('AY'.$cont13, 'V0')
                                                            ->setCellValue('BL'.$cont13, number_format($diferencia,2, '.', ''));
                                                }


                                                $spreadsheet->setActiveSheetIndex(5)
                                                        ->setCellValue('BC'.$cont13, $comentarios)
                                                        ->setCellValue('CI'.$cont13, $geo);

                                                $cont13 = $cont13+1;
                                            }
                                        }else{
                                            $spreadsheet->setActiveSheetIndex(5)
                                                    ->setCellValue('A'.$cont13, $cont6)
                                                    ->setCellValue('B'.$cont13, $i)
                                                    ->setCellValue('D'.$cont13, $comentarios)
                                                    ->setCellValue('I'.$cont13, 'MXP')
                                                    ->setCellValue('O'.$cont13, $slpCode)
                                                    ->setCellValue('U'.$cont13, $depto)
                                                    ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                                    ->setCellValue('R'.$cont13, $cuenta)
                                                    ->setCellValue('V'.$cont13, $proyecto)
                                                    ->setCellValue('AY'.$cont13, 'V2')
                                                    ->setCellValue('BL'.$cont13, number_format($subtotal,2, '.', ''))
                                                    ->setCellValue('BC'.$cont13, $comentarios)
                                                    ->setCellValue('CI'.$cont13, $geo);

                                            $cont13 = $cont13+1;
                                        }
                                    }else{
                                        $spreadsheet->setActiveSheetIndex(5)
                                                ->setCellValue('A'.$cont13, $cont6)
                                                ->setCellValue('B'.$cont13, $i)
                                                ->setCellValue('D'.$cont13, $comentarios)
                                                ->setCellValue('I'.$cont13, 'MXP')
                                                ->setCellValue('O'.$cont13, $slpCode)
                                                ->setCellValue('U'.$cont13, $depto)
                                                ->setCellValue('G'.$cont13, number_format($subtotal,2, '.', ''))
                                                ->setCellValue('R'.$cont13, $cuenta)
                                                ->setCellValue('V'.$cont13, $proyecto)
                                                ->setCellValue('AY'.$cont13, 'V2')
                                                ->setCellValue('BL'.$cont13, number_format($subtotal,2, '.', ''))
                                                ->setCellValue('BC'.$cont13, $comentarios)
                                                ->setCellValue('CI'.$cont13, $geo);

                                        $cont13 = $cont13+1;  
                                    }
                                }
                            } 
                        }
                    }

                    $cont5 = $cont5+1;
                    $cont6 = $cont6+1;
                
                }else{
                    
                    /// FACTURA CON XML ///
        
                    $fecha_timbrado = substr($row['fecha_timbrado'],0,10);
                    $fecha_timbrado = str_replace('-','', $fecha_timbrado);
        
                    $query6 = "SELECT cuenta FROM tbl_relacionCuentasSAP where tipo_gasto=$tipoGasto and tipo=$tipo_cuenta";
                    $result6 = sqlsrv_query($this->db->connID,$query6);
                    $row = sqlsrv_fetch_array($result6);
                    if (is_null($row['cuenta'])) {
                        $cuenta='--';
                    }else{
                        $cuenta = $row['cuenta'];
                    }
                        // NO SE USA | AHORRO DE TIEMPO
                    
                    //    $query6 = "SELECT cuenta FROM tbl_relacionCuentasSAP where tipo_gasto=1000 and tipo=$tipo_cuenta";
                    //    $result6 = sqlsrv_query($this->db->connID,$query6);
                    //    $row = sqlsrv_fetch_array($result6);
                    //    if (is_null($row['cuenta'])) {
                    //        $cuenta_otrosImpuestos='--';
                    //    }else{
                    //        $cuenta_otrosImpuestos = $row['cuenta'];
                    //    }
                    
                    
                    $spreadsheet->setActiveSheetIndex(0)
                            ->setCellValue('A'.$cont, $cont2) // DocNum
                            ->setCellValue('B'.$cont, 'dDocument_Service') // DocType
                            ->setCellValue('E'.$cont, $docdate) // DocDate
                            ->setCellValue('F'.$cont, $Vencimiento) // DocDueDate
                            ->setCellValue('G'.$cont, $CodigoProv) // CardCode
                            ->setCellValue('J'.$cont, $Folio) // NumAtCard
                            ->setCellValue('K'.$cont, 'MXP') // currency
                            ->setCellValue('M'.$cont, number_format($total,2, '.', '')) // DocTotal
                            ->setCellValue('O'.$cont, 'V_INTRANET') // REF2
                            ->setCellValue('P'.$cont, $comentarios) // comentarios
                            ->setCellValue('Q'.$cont, $comentarios1) // jrnlmemo
                            ->setCellValue('T'.$cont, $slpCode) // SLPCODE
                            ->setCellValue('AB'.$cont, $fecha_timbrado); // jrnlmemo
        
                    if ($tipoGasto == 24) {
                        if ($OtrosImp == '0' || $OtrosImp == 0) {

                            $base = (number_format($impuesto_importe,2, '.', ''))/(0.16);
    
                            if ($impuesto_importe == 0 || $impuesto_importe == '0') {
                                $linea=1;
                                            
                                $spreadsheet->setActiveSheetIndex(4)
                                        ->setCellValue('A'.$cont11, $cont2)
                                        ->setCellValue('B'.$cont11, $linea)
                                        ->setCellValue('D'.$cont11, $comentarios)
                                        ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                        ->setCellValue('I'.$cont11, 'MXP')
                                        ->setCellValue('O'.$cont11, $slpCode)
                                        ->setCellValue('R'.$cont11, $cuenta)
                                        ->setCellValue('U'.$cont11, $depto)
                                        ->setCellValue('V'.$cont11, $proyecto)
                                        ->setCellValue('AY'.$cont11, 'V0')
                                        ->setCellValue('BC'.$cont11, $comentarios)
                                        ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''))
                                        ->setCellValue('CI'.$cont11, $geo);

                                $cont11 = $cont11+1;
                            }else{
    
                                if (number_format($subtotal,0, '.', '') == number_format($base,0, '.', '')) {
                                    $linea=1;
                                    $spreadsheet->setActiveSheetIndex(4)
                                            ->setCellValue('A'.$cont11, $cont2)
                                            ->setCellValue('B'.$cont11, $linea)
                                            ->setCellValue('D'.$cont11, $comentarios)
                                            ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                            ->setCellValue('I'.$cont11, 'MXP')
                                            ->setCellValue('O'.$cont11, $slpCode)
                                            ->setCellValue('R'.$cont11, $cuenta)
                                            ->setCellValue('U'.$cont11, $depto)
                                            ->setCellValue('V'.$cont11, $proyecto)
                                            ->setCellValue('AY'.$cont11, 'V2')
                                            ->setCellValue('BC'.$cont11, $comentarios)
                                            ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''))
                                            ->setCellValue('CI'.$cont11, $geo);

                                    $cont11 = $cont11+1;
                                }
                            }
                            
                        }else{
        
                            $base = number_format($impuesto_importe,2, '.', '')/(0.16);
        
                            if (number_format($subtotal,0, '.', '') == number_format($base,0, '.', '')) {
                                for ($i=1; $i <=2; $i++) {
                                    $spreadsheet->setActiveSheetIndex(4)
                                            ->setCellValue('A'.$cont11, $cont2)
                                            ->setCellValue('B'.$cont11, $i)
                                            ->setCellValue('D'.$cont11, $comentarios)
                                            ->setCellValue('I'.$cont11, 'MXP')
                                            ->setCellValue('O'.$cont11, $slpCode)
                                            ->setCellValue('U'.$cont11, $depto);
        
                                    if ($i == 1) {
                                        $spreadsheet->setActiveSheetIndex(4)
                                                ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                                ->setCellValue('R'.$cont11, $cuenta)
                                                ->setCellValue('V'.$cont11, $proyecto)
                                                ->setCellValue('AY'.$cont11, 'V2')
                                                ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''));

                                    }else{
                                        $spreadsheet->setActiveSheetIndex(4)
                                                ->setCellValue('G'.$cont11, number_format($OtrosImp,2, '.', ''))
                                                ->setCellValue('R'.$cont11, $cuenta)
                                                ->setCellValue('V'.$cont11, $proyecto)
                                                ->setCellValue('AY'.$cont11, 'VE')
                                                ->setCellValue('BL'.$cont11, number_format($OtrosImp,2), '.', '');
                                    }
        
                                    $spreadsheet->setActiveSheetIndex(4)
                                            ->setCellValue('BC'.$cont11, $comentarios)
                                            ->setCellValue('CI'.$cont11, $geo);

                                    $cont11 = $cont11+1;
                                }
                                $i=1;

                            }else{
                                $diferencia = (number_format($subtotal,2, '.', '')-number_format($base,2, '.', ''));
                                for ($i=1; $i <=3 ; $i++) {
                                    $spreadsheet->setActiveSheetIndex(4)
                                            ->setCellValue('A'.$cont11, $cont2)
                                            ->setCellValue('B'.$cont11, $i)
                                            ->setCellValue('D'.$cont11, $comentarios)
                                            ->setCellValue('I'.$cont11, 'MXP')
                                            ->setCellValue('O'.$cont11, $slpCode)
                                            ->setCellValue('U'.$cont11, $depto);
        
                                    if ($i == 1) {
                                        $spreadsheet->setActiveSheetIndex(4)
                                                ->setCellValue('G'.$cont11, number_format($base,2, '.', ''))
                                                ->setCellValue('AY'.$cont11, 'V2')
                                                ->setCellValue('R'.$cont11, $cuenta)
                                                ->setCellValue('V'.$cont11, $proyecto)
                                                ->setCellValue('BL'.$cont11, number_format($base,2, '.', ''));

                                    }elseif ($i == 2) {
                                        $spreadsheet->setActiveSheetIndex(4)
                                                ->setCellValue('G'.$cont11, number_format($diferencia,2, '.', ''))
                                                ->setCellValue('R'.$cont11, $cuenta)
                                                ->setCellValue('V'.$cont11, $proyecto)
                                                ->setCellValue('AY'.$cont11, 'V0')
                                                ->setCellValue('BL'.$cont11, number_format($diferencia,2, '.', ''));

                                    }elseif ($i == 3) {
                                        $spreadsheet->setActiveSheetIndex(4)
                                                ->setCellValue('G'.$cont11, number_format($OtrosImp,2, '.', ''))
                                                ->setCellValue('R'.$cont11, $cuenta)
                                                ->setCellValue('V'.$cont11, $proyecto)
                                                ->setCellValue('AY'.$cont11, 'VE')
                                                ->setCellValue('BL'.$cont11, number_format($OtrosImp,2, '.', ''));
                                    }
        
                                    $spreadsheet->setActiveSheetIndex(4)
                                            ->setCellValue('BC'.$cont11, $comentarios)
                                            ->setCellValue('CI'.$cont11, $geo);
        
                                    $cont11 = $cont11+1;
        
                                }
                                $i=1;
                            }
                        }
                    }elseif ($tipoGasto == 2) {
                        $base = number_format($impuesto_importe,2, '.', '')/(0.16);

                        for ($i=1; $i <=2 ; $i++) {
                            $spreadsheet->setActiveSheetIndex(4)
                                    ->setCellValue('A'.$cont11, $cont2)
                                    ->setCellValue('B'.$cont11, $i)
                                    ->setCellValue('D'.$cont11, $comentarios)
                                    ->setCellValue('I'.$cont11, 'MXP')
                                    ->setCellValue('O'.$cont11, $slpCode)
                                    ->setCellValue('U'.$cont11, $depto);

                            if ($i == 1) {
                                $spreadsheet->setActiveSheetIndex(4)
                                        ->setCellValue('G'.$cont11, number_format($base,2, '.', ''))
                                        ->setCellValue('R'.$cont11, $cuenta)
                                        ->setCellValue('V'.$cont11, $proyecto)
                                        ->setCellValue('AY'.$cont11, 'V2')
                                        ->setCellValue('BL'.$cont11, number_format($base,2, '.', ''));

                            }elseif($i == 2){
                                $spreadsheet->setActiveSheetIndex(4)
                                        ->setCellValue('G'.$cont11, number_format($ieps,2, '.', ''))
                                        ->setCellValue('R'.$cont11, $cuenta)
                                        ->setCellValue('V'.$cont11, $proyecto)
                                        ->setCellValue('AY'.$cont11, 'VE')
                                        ->setCellValue('BL'.$cont11, number_format($ieps,2, '.', ''));
                            }
        
                            $spreadsheet->setActiveSheetIndex(4)
                                    ->setCellValue('BC'.$cont11, $comentarios)
                                    ->setCellValue('CI'.$cont11, $geo);
        
                            $cont11 = $cont11+1;
                        }
                        $i=1;
                    }else{
        
                        if (floatval($total) == floatval($subtotal) ) {
                            $spreadsheet->setActiveSheetIndex(4)
                                    ->setCellValue('A'.$cont11, $cont2)
                                    ->setCellValue('B'.$cont11, '1')
                                    ->setCellValue('D'.$cont11, $comentarios)
                                    ->setCellValue('I'.$cont11, 'MXP')
                                    ->setCellValue('O'.$cont11, $slpCode)
                                    ->setCellValue('U'.$cont11, $depto)
                                    ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                    ->setCellValue('R'.$cont11, $cuenta)
                                    ->setCellValue('V'.$cont11, $proyecto)
                                    ->setCellValue('AY'.$cont11, 'V0')
                                    ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''))
                                    ->setCellValue('BC'.$cont11, $comentarios)
                                    ->setCellValue('CI'.$cont11, $geo);
                            
                            $cont11 = $cont11+1;
        
                        }else{
                            $base = number_format($impuesto_importe,2, '.', '')/(0.16);

                            if (number_format($base,0, '.', '') == number_format($subtotal,0, '.', '')) {
                                $spreadsheet->setActiveSheetIndex(4)
                                        ->setCellValue('A'.$cont11, $cont2)
                                        ->setCellValue('B'.$cont11, '1')
                                        ->setCellValue('D'.$cont11, $comentarios)
                                        ->setCellValue('I'.$cont11, 'MXP')
                                        ->setCellValue('O'.$cont11, $slpCode)
                                        ->setCellValue('U'.$cont11, $depto)
                                        ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                        ->setCellValue('R'.$cont11, $cuenta)
                                        ->setCellValue('V'.$cont11, $proyecto)
                                        ->setCellValue('AY'.$cont11, 'V2')
                                        ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''))
                                        ->setCellValue('BC'.$cont11, $comentarios)
                                        ->setCellValue('CI'.$cont11, $geo);

                                $cont11 = $cont11+1;
                            }else{
                                if ($impues == 'IEPS') {
                                    if ($impuestos == 0 || $impuestos =='0') {
                                        if (floatval($total) != floatval($subtotal)) {
                                            $spreadsheet->setActiveSheetIndex(4)
                                                    ->setCellValue('A'.$cont11, $cont2)
                                                    ->setCellValue('B'.$cont11, '1')
                                                    ->setCellValue('D'.$cont11, $comentarios)
                                                    ->setCellValue('I'.$cont11, 'MXP')
                                                    ->setCellValue('O'.$cont11, $slpCode)
                                                    ->setCellValue('U'.$cont11, $depto)
                                                    ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                                    ->setCellValue('R'.$cont11, $cuenta)
                                                    ->setCellValue('V'.$cont11, $proyecto)
                                                    ->setCellValue('AY'.$cont11, 'V2')
                                                    ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''))
                                                    ->setCellValue('BC'.$cont11, $comentarios)
                                                    ->setCellValue('CI'.$cont11, $geo);

                                            $cont11 = $cont11+1;
                                        }    
                                    }elseif(number_format($impuestos,0, '.', '') == number_format($impuesto_importe,0, '.', '')){
        
                                        for ($i=1; $i <=2 ; $i++) {
                                            $spreadsheet->setActiveSheetIndex(4)
                                                    ->setCellValue('A'.$cont11, $cont2)
                                                    ->setCellValue('B'.$cont11, $i)
                                                    ->setCellValue('D'.$cont11, $comentarios)
                                                    ->setCellValue('I'.$cont11, 'MXP')
                                                    ->setCellValue('O'.$cont11, $slpCode)
                                                    ->setCellValue('U'.$cont11, $depto);
        
                                            if ($i == 1) {           
                                                $spreadsheet->setActiveSheetIndex(4)
                                                        ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                                        ->setCellValue('R'.$cont11, $cuenta)
                                                        ->setCellValue('V'.$cont11, $proyecto)
                                                        ->setCellValue('AY'.$cont11, 'V0')
                                                        ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''));

                                            }elseif ($i == 2) {
                                                $spreadsheet->setActiveSheetIndex(4)
                                                        ->setCellValue('G'.$cont11, number_format($impuesto_importe,2, '.', ''))
                                                        ->setCellValue('R'.$cont11, $cuenta)
                                                        ->setCellValue('V'.$cont11, $proyecto)
                                                        ->setCellValue('AY'.$cont11, 'VE')
                                                        ->setCellValue('BL'.$cont11, number_format($impuesto_importe,2, '.', ''));
                                            }

                                            $spreadsheet->setActiveSheetIndex(4)
                                                    ->setCellValue('BC'.$cont11, $comentarios)
                                                    ->setCellValue('CI'.$cont11, $geo);

                                            $cont11 = $cont11+1;
                                        }
                                        
                                        $i=1;
                                    }else{
        
                                        $base = number_format($impuestos,2, '.', '')/0.16;
                                        $diferencia = (number_format($subtotal,2, '.', '')-number_format($base,2, '.', ''));

                                        for ($i=1; $i <=3 ; $i++) {
                                            $spreadsheet->setActiveSheetIndex(4)
                                                    ->setCellValue('A'.$cont11, $cont2)
                                                    ->setCellValue('B'.$cont11, $i)
                                                    ->setCellValue('D'.$cont11, $comentarios)
                                                    ->setCellValue('I'.$cont11, 'MXP')
                                                    ->setCellValue('O'.$cont11, $slpCode)
                                                    ->setCellValue('U'.$cont11, $depto);
        
                                            if ($i == 1) {          
                                                $spreadsheet->setActiveSheetIndex(4)
                                                        ->setCellValue('G'.$cont11, number_format($base,2, '.', ''))
                                                        ->setCellValue('R'.$cont11, $cuenta)
                                                        ->setCellValue('V'.$cont11, $proyecto)
                                                        ->setCellValue('AY'.$cont11, 'V2')
                                                        ->setCellValue('BL'.$cont11, number_format($base,2, '.', ''));

                                            }elseif ($i == 2) {
                                                $spreadsheet->setActiveSheetIndex(4)
                                                        ->setCellValue('G'.$cont11, number_format($diferencia,2, '.', ''))
                                                        ->setCellValue('R'.$cont11, $cuenta)
                                                        ->setCellValue('V'.$cont11, $proyecto)
                                                        ->setCellValue('AY'.$cont11, 'V0')
                                                        ->setCellValue('BL'.$cont11, number_format($diferencia,2, '.', ''));

                                            }elseif ($i == 3) {
                                                $spreadsheet->setActiveSheetIndex(4)
                                                        ->setCellValue('G'.$cont11, number_format($impuesto_importe,2, '.', ''))
                                                        ->setCellValue('R'.$cont11, $cuenta)
                                                        ->setCellValue('V'.$cont11, $proyecto)
                                                        ->setCellValue('AY'.$cont11, 'VE')
                                                        ->setCellValue('BL'.$cont11, number_format($impuesto_importe,2, '.', ''));
                                            }
        
                                            $spreadsheet->setActiveSheetIndex(4)
                                                    ->setCellValue('BC'.$cont11, $comentarios)
                                                    ->setCellValue('CI'.$cont11, $geo);
        
                                            $cont11 = $cont11+1;
        
                                        }
                                        
                                        $i=1;
                                    }      
        
                                }else{
        
                                    if (number_format($impuestos,0, '.', '') == number_format($impuesto_importe,0, '.', '')) {
                                        $base = (number_format($impuesto_importe,2, '.', '')/0.16);
                                        $diferencia = (  number_format($subtotal,2, '.', '') - number_format($base,2, '.', '') );
        
                                        if (number_format($base,1, '.', '') != number_format($subtotal,1, '.', '')) {

                                            for ($i=1; $i <=2 ; $i++) { 
                                                $spreadsheet->setActiveSheetIndex(4)
                                                        ->setCellValue('A'.$cont11, $cont2)
                                                        ->setCellValue('B'.$cont11, $i)
                                                        ->setCellValue('D'.$cont11, $comentarios)
                                                        ->setCellValue('I'.$cont11, 'MXP')
                                                        ->setCellValue('O'.$cont11, $slpCode)
                                                        ->setCellValue('U'.$cont11, $depto)
                                                        ->setCellValue('V'.$cont11, $proyecto);
        
                                                if ($i == 1) {
                                                    $spreadsheet->setActiveSheetIndex(4)
                                                            ->setCellValue('G'.$cont11, number_format($base,2, '.', ''))
                                                            ->setCellValue('R'.$cont11, $cuenta)
                                                            ->setCellValue('AY'.$cont11, 'V2')
                                                            ->setCellValue('BL'.$cont11, number_format($base,2, '.', ''));
                                                }else{
                                                        $spreadsheet->setActiveSheetIndex(4)
                                                                ->setCellValue('G'.$cont11, number_format($diferencia,2, '.', ''))
                                                                ->setCellValue('R'.$cont11, $cuenta)
                                                                ->setCellValue('BL'.$cont11, number_format($diferencia,2, '.', ''))
                                                                ->setCellValue('AY'.$cont11, 'V0');
                                                }
                                                $spreadsheet->setActiveSheetIndex(4)
                                                        ->setCellValue('BC'.$cont11, $comentarios)
                                                        ->setCellValue('CI'.$cont11, $geo);
                                                
                                                $cont11 = $cont11+1;
                                            }
                                        }else{

                                            $spreadsheet->setActiveSheetIndex(4)
                                            ->setCellValue('A'.$cont11, $cont2)
                                            ->setCellValue('B'.$cont11, 1)
                                            ->setCellValue('D'.$cont11, $comentarios)
                                            ->setCellValue('I'.$cont11, 'MXP')
                                            ->setCellValue('O'.$cont11, $slpCode)
                                            ->setCellValue('U'.$cont11, $depto)
                                            ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                            ->setCellValue('R'.$cont11, $cuenta)
                                            ->setCellValue('V'.$cont11, $proyecto)
                                            ->setCellValue('AY'.$cont11, 'V2')
                                            ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''))
                                            ->setCellValue('BC'.$cont11, $comentarios)
                                            ->setCellValue('CI'.$cont11, $geo);

                                            $cont11 = $cont11+1;
                                        }
                                    }else{
        
                                        $spreadsheet->setActiveSheetIndex(4)
                                                ->setCellValue('A'.$cont11, $cont2)
                                                ->setCellValue('B'.$cont11, 1)
                                                ->setCellValue('D'.$cont11, $comentarios)
                                                ->setCellValue('I'.$cont11, 'MXP')
                                                ->setCellValue('O'.$cont11, $slpCode)
                                                ->setCellValue('U'.$cont11, $depto)
                                                ->setCellValue('G'.$cont11, number_format($subtotal,2, '.', ''))
                                                ->setCellValue('R'.$cont11, $cuenta)
                                                ->setCellValue('V'.$cont11, $proyecto)
                                                ->setCellValue('AY'.$cont11, 'V2')
                                                ->setCellValue('BL'.$cont11, number_format($subtotal,2, '.', ''))
                                                ->setCellValue('BC'.$cont11, $comentarios)
                                                ->setCellValue('CI'.$cont11, $geo);

                                        $cont11 = $cont11+1;  
                                    }           
                                }         
                            }  
                        }     
                    }
        
                    $cont2 = $cont2+1;
                    $cont = $cont+1;
        
                }
            }
        }
        

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        
        // Redirect output to a client’s web browser (Excel2007)
        //header('Content-Type: application/vnd.ms-excel');
        /* 
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        */

        //$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter = new Xlsx($spreadsheet);
        $objWriter->save('php://output');
        return  $this->response->setStatusCode(200)->setContentType('application/vnd.ms-excel')->sendBody($objWriter);
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //  MODULO: SALDOS EN VIATICOS DE COLABORADORES
    
    public function SVC_getColaboradores(){
        $json = $this->request->getJSON();
        $search = $json->search;

        if(empty($this->db->connID))
            $this->db->initialize();

        // COLABORADORES
        /*
            SELECT 
                n_colaborador,
                CONCAT(nombres,' ',apellido_p) AS name 
            FROM Colaboradores 
            WHERE 
                estado = 1
            ORDER BY apellido_p
        */

        // { CALL SAPSERVER.SBO_ECN.dbo.SP_get_SaldoViaticos_18012017(?) }
        /* SELECT 
                T0.[SlpName]
                ,SUM(T2.[Debit])-SUM(T2.[Credit])  as saldo_mxn
            FROM OSLP T0 
                INNER JOIN JDT1 T2 ON T0.[U_CuentaCont] = T2.[Account] 
                INNER JOIN OACT T3 ON T2.[Account] = T3.[AcctCode] 
            GROUP BY T0.[SlpName] 
        */

        $query="SELECT 
                    C.n_colaborador,
                    T0.[U_ID_ECN] AS onSAP,
                    CONCAT(C.nombres,' ',C.apellido_p) AS name,
                    SUM(ISNULL(T2.[Debit], 0))-SUM(ISNULL(T2.[Credit], 0)) AS saldo_mxn
                FROM 
                    [plataformaecn1].[dbo].Colaboradores C
                    LEFT JOIN [SBO_ECN].[dbo].OSLP T0 ON T0.[U_ID_ECN] = C.n_colaborador COLLATE DATABASE_DEFAULT
                    LEFT JOIN [SBO_ECN].[dbo].JDT1 T2 ON T0.[U_CuentaCont] = T2.[Account] 
                    LEFT JOIN [SBO_ECN].[dbo].OACT T3 ON T2.[Account] = T3.[AcctCode] 
                WHERE 
                    estado = 1 AND
                    (
                        CONCAT(C.nombres, ' ', C.apellido_p) LIKE ('%$search%') OR
                        C.n_colaborador LIKE ('%$search%')
                    )
                GROUP BY C.n_colaborador, C.nombres, C.apellido_p, T0.[U_ID_ECN]
                ORDER BY C.n_colaborador, C.apellido_p";
        $result=sqlsrv_query($this->db->connID,$query);
        $colab=array();

        while ($row=sqlsrv_fetch_array($result)) {
            $isNegative = $row['saldo_mxn'] < 0 ? true : false;
            $saldo = ($isNegative ? '-$'.number_format($row['saldo_mxn']*(-1),2,'.',',') :
                                    '$'.number_format($row['saldo_mxn'],2,'.',','));

            array_push($colab, array(
                'colaborador'=>$row['n_colaborador'],
                'name'=>$row['name'],
                'saldo'=>$saldo,
                'onSAP'=>($row['onSAP'] == NULL ? 0 : 1)
            ));
        }
        
        return $this->response->setStatusCode(200)->setJSON($colab);
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // MODULO: VIATICOS - HOSPEDAJE

    public function VH_getViaticosHospedaje(){
        $json = $this->request->getJSON();

        $search = $json->search;

        if(empty($this->db->connID))
            $this->db->initialize();

        $query1 = " SELECT 
                        q1.id_cabecera, 
                        c.nombres,
                        c.apellido_p,
                        c.apellido_m,
                        q3.id_colaborador, 
                        q3.proyecto_servicio, 
                        q3.prjcode, 
                        q3.prj_presupuesto, 
                        q3.lider_id, 
                        q3.lider_autoriza, 
                        q3.estado 
                    FROM 
                        serviciosyreservaciones_compras q1 LEFT JOIN 
                        serviciosyreservaciones_cabeceras q3 ON q1.id_cabecera=q3.id LEFT JOIN 
                        colaboradores c ON c.id_colaborador=q3.id_colaborador 
                    WHERE 
                        q1.tipo_info = 6 AND 
                        q1.tipo_solicitud = 2 AND 
                        q1.status = 1 AND 
                        q3.status = 1
                    ORDER BY q1.fecha DESC";
        $return1 = sqlsrv_query($this->db->connID, $query1);

        $list = array();

        while($fet = sqlsrv_fetch_array($return1)){
            $idcabecera = isset($fet['id_cabecera']) ? $fet['id_cabecera'] : 0;

            // Cotizaciones
            $query2 = " SELECT 
                            t.*, 
                            t.h_ciudad as origen, 
                            CASE t.h_tipo
                                WHEN 1 THEN 'Hab. sencilla' 
                                WHEN 2 THEN 'Hab. doble' 
                                WHEN 3 THEN 'Hab. triple' 
                                WHEN 4 THEN 'Hab. cuádruple' 
                                WHEN 5 THEN 'Casa ECN' 
                                ELSE 'No especificado' 
                            END AS h_tipo, 
                            CONVERT(varchar(10),t.fecha_salida,21) AS fecha_salida,
                            FORMAT(t.fecha_salida, 'dd/MM/yyyy') AS fecha_salida_n, 
                            CONVERT(varchar(10),t.fecha_llegada,21) AS fecha_llegada,
                            FORMAT(t.fecha_llegada, 'dd/MM/yyyy') AS fecha_llegada_n
                        FROM serviciosyreservaciones_cotizaciones t  
                        WHERE 
                            t.status = 1 AND 
                            t.tipo_solicitud = 2 AND 
                            t.id_cabecera = $idcabecera";
            $return2 = sqlsrv_query($this->db->connID, $query2);
            $row_cot = sqlsrv_fetch_array($return2);

            $query3 = " SELECT * 
                        FROM serviciosyreservaciones_compras 
                        WHERE 
                            id_cabecera = $idcabecera AND 
                            tipo_solicitud = 2 AND 
                            status = 1 AND 
                            tipo_info IN (6,2,4)
                        ORDER BY id";
            $return3 = sqlsrv_query($this->db->connID, $query3);

            $viaticos = '';
            $total_mxn = 0;
            $total_usd = 0;

            $desglose_extra = array();
            $desglose_compra = array();

            while($row = sqlsrv_fetch_array($return3)){
                $vicol = 0;
                $Rtipo_info = isset($row['tipo_info']) ? $row['tipo_info'] : 0;
                $Rconcepto_extra = isset($row['concepto_extra']) ? $row['concepto_extra'] : '';
                $Rconcepto = isset($row['concepto']) ? $row['concepto'] : '';
                $Rmonto = isset($row['monto']) ? $row['monto'] : 0;
                $Rmoneda = isset($row['moneda']) ? $row['moneda'] : '';
                $Rviaticos = isset($row['viaticos']) ? $row['viaticos'] : 0;
                
                switch($Rtipo_info){
                    case 2: // Cargos extras
                        array_push($desglose_extra, array(  'concepto' => $Rconcepto_extra, 
                                                            'monto' => number_format($Rmonto, 2)." ".$Rmoneda));
                        if($Rmoneda == 'MXN')
                            $total_mxn+= $Rmonto;
                        else
                            $total_usd+= $Rmonto;
                        break;
                    case 4: // Precio de compra
                        array_push($desglose_compra, array( 'concepto' => $Rconcepto, 
                                                            'monto' => number_format($Rmonto, 2)." ".$Rmoneda));
                        if($Rmoneda == 'MXN')
                            $total_mxn+= $Rmonto;
                        else
                            $total_usd+= $Rmonto;
                        break;
                    case 6: // Cargado a viaticos
                        if($Rviaticos == 1){
                            $vicol = 1;
                            $viaticos = 1;
                        }else
                            $viaticos = 0;
                        break;
                }
            }
            
            if($total_mxn == 0){
                if($total_usd == 0)
                    $total = "0 MXN";
                else
                    $total = number_format($total_usd,2)." USD";
            }else{
                if($total_usd == 0)
                    $total = number_format($total_mxn,2)." MXN";
                else
                    $total = number_format($total_mxn,2)." MXN + ".number_format($total_usd,2)." USD";
            }

            $desglose = array();

            for($i = 0; $i < sizeof($desglose_compra); $i++)
                array_push($desglose, $desglose_compra[$i]);

            for($o = 0; $o < sizeof($desglose_extra); $o++)
                array_push($desglose, $desglose_extra[$o]);
            
            
            $Rnombre = isset($fet['nombres']) ? $fet['nombres'] : '';
            $Rapellido_p = isset($fet['apellido_p']) ? $fet['apellido_p'] : '';
            $Rapellido_m = isset($fet['apellido_m']) ? $fet['apellido_m'] : '';
            $Rciudad = isset($row_cot['h_ciudad']) ? $row_cot['h_ciudad'] : '';
            $Rtipo = isset($row_cot['h_tipo']) ? $row_cot['h_tipo'] : '';
            $Rfecha_salida = isset($row_cot['fecha_salida_n']) ? $row_cot['fecha_salida_n'] : '';
            $Rfecha_llegada = isset($row_cot['fecha_llegada_n']) ? $row_cot['fecha_llegada_n'] : '';

            $var = array(
                'solicitud' => $idcabecera,
                'vicol' => $vicol,
                'depositado' => $viaticos == 1 ? 'Si' : 'No',
                'colab' => "$Rnombre $Rapellido_p $Rapellido_m",
                'destino' => "$Rciudad " . ($Rtipo ? "($Rtipo)" : '') . " \n Del $Rfecha_salida al $Rfecha_llegada",
                'cantidad' => $total,
                'desglose' => $desglose
            );

            array_push($list, $var);
        }

        return $this->response->setStatusCode(200)->setJSON($list);
    }

    public function VH_getServiciosReservaciones(){
        $json = $this->request->getJSON();
        
        if(empty($this->db->connID))
            $this->db->initialize();
            
        $sol_id = $json->sol_id;
        $colaborador_id = $json->user;
        $colaborador_n = $json->user_n;

        $query1 = " SELECT 
                        o.estado
                        , o.estado AS edo
                        , CASE 
                            WHEN o.status=0 
                            THEN ' - Borrada' 
                            ELSE '' 
                        END AS status
                        , CONVERT(varchar(19),o.fecha,21) AS fecha
                        , CASE 
                            WHEN o.urgencia_creador=0 
                            THEN '' 
                            ELSE (
                                    SELECT 'Creado por: '+ c.apellido_p +' '+ c.apellido_m +' '+ c.nombres +'\n' 
                                    FROM Colaboradores c 
                                    WHERE c.id_colaborador=o.urgencia_creador
                                ) 
                        END AS urgencia_creador
                        , o.colab_celular
                        , CASE 
                            WHEN o.proyecto_servicio=0 THEN 'No' 
                            WHEN o.proyecto_servicio=1 THEN 'Si' 
                            ELSE 'Error' 
                        END AS proyecto_servicio
                        , CASE 
                            WHEN o.prj_presupuesto=0 THEN 'No' 
                            WHEN o.prj_presupuesto=1 THEN 'Si' 
                            ELSE '' 
                        END AS prj_presupuesto
                        , CASE o.motivo
                            WHEN 1 THEN 'Promoción' 
                            WHEN 2 THEN 'Puesta en marcha'
                            WHEN 3 THEN 'Venta' 
                            WHEN 4 THEN 'Visita corporativa' 
                            WHEN 5 THEN 'Capacitación' 
                            WHEN 6 THEN 'Reunión gerencial/anual ECN' 
                            WHEN 7 THEN 'Ejecución' 
                            ELSE '' 
                        END AS motivo
                        , o.prjcode
                        , t.nombres
                        , t.apellido_p
                        , t.apellido_m
                        , o.id_colaborador
                        , t.email
                        , CONVERT(varchar(10),t.fecha_nacimiento,21) fecha_nacimiento
                        , FORMAT(t.fecha_nacimiento, 'dd/MM/yyyy') fecha_nacimiento_n
                        , j.puesto
                        
                        , o.departamento AS departamentos_codigo
                        , o.geografica AS zonaGeografica_codigo
                        
                        , (
                            SELECT c.apellido_p +' '+ c.apellido_m +' '+ c.nombres 
                            FROM Colaboradores c 
                            WHERE c.id_colaborador=o.lider_id
                        ) AS autoriza
                        , (
                            SELECT c.email 
                            FROM Colaboradores c 
                            WHERE c.id_colaborador=o.lider_id
                        ) AS lider_email
                        , o.lider_id
                        , o.lider_celular
                        , ISNULL(o.req_contraloria,0) req_contraloria
                        , o.contralor_id
                        , (
                            SELECT c.email 
                            FROM Colaboradores c 
                            WHERE c.id_colaborador=o.contralor_id
                        ) AS contralor_email
                        , (
                            SELECT c.apellido_p +' '+ c.apellido_m +' '+ c.nombres 
                            FROM Colaboradores c 
                            WHERE c.id_colaborador=o.contralor_id
                        ) AS contralor_nombre
                        , ISNULL(o.estado_solicnv,0) estado_solicnv
                        , o.solicitante_nuevo
                        , (
                            SELECT c.apellido_p +' '+ c.apellido_m +' '+ c.nombres 
                            FROM Colaboradores c 
                            WHERE c.id_colaborador=o.solicitante_nuevo
                        ) AS solicitante_nuevo_nombre
                        , o.solicitante_original
                        , (
                            SELECT c.apellido_p +' '+ c.apellido_m +' '+ c.nombres 
                            FROM Colaboradores c 
                            WHERE c.id_colaborador=o.solicitante_original
                        ) solicitante_original_nombre
                        , ISNULL(o.reagenda_vuelo,0) reagenda_vuelo
                        , ISNULL(o.reagenda_hosp,0) reagenda_hosp
                    FROM 
                        serviciosyreservaciones_cabeceras o 
                        LEFT JOIN Colaboradores t ON t.id_colaborador=o.id_colaborador 
                        LEFT JOIN Puestos j ON t.id_puesto=j.id_puesto 
                    WHERE 
                        o.id = $sol_id ";
        $result1 = $this->db->query($query1)->getResult()[0];

        $div_cambionombre = '';
        if(!is_null($result1->solicitante_original)){
            if($result1->solicitante_original != $result1->id_colaborador)
                $div_cambionombre = "Se autorizó cambio de nombre del solicitante, de ".$result1->solicitante_original_nombre." a ".$result1->apellido_p." ".$result1->apellido_m." ".$result1->nombres.".";
        }

        $urge = "";
        $urgencia = 0;
        if($result1->urgencia_creador != ''){
            $urge = "<span class='text-danger'>[Urgente]</span>";
            $urgencia = 1;
        }

        $envio_contraloria = 0;

        // { CALL SAPSERVER.SBO_ECN.dbo.SP_get_SaldoViaticos_18012017(?) }
        $query_s = "SELECT 
                        T0.[SlpName]
                        ,SUM(T2.[Debit])-SUM(T2.[Credit]) AS saldo_mxn
                    FROM 
                        --OSLP T0 INNER JOIN 
                        --JDT1 T2 ON T0.[U_CuentaCont] = T2.[Account] INNER JOIN 
                        --OACT T3 ON T2.[Account] = T3.[AcctCode] 
                        [SBO_ECN].[dbo].[OSLP] T0 INNER JOIN 
                        [SBO_ECN].[dbo].[JDT1] T2 ON T0.[U_CuentaCont] = T2.[Account] INNER JOIN 
                        [SBO_ECN].[dbo].[OACT] T3 ON T2.[Account] = T3.[AcctCode] 
                    WHERE T0.[U_ID_ECN] = '$colaborador_n'
                    GROUP BY T0.[SlpName]";
        $result_s = $this->db->query($query_s)->getResult();
        
        $saldo_viaticos = sizeof($result_s) == 1 ? number_format($result_s[0]->saldo_mxn, 2) : 0;
        
    
        $solicitud_errordias = 0;
        $solicitud_errordias_v = 0;
        $solicitud_errordias_h = 0;
        
        $v_errores = 0;
        $v_errores_txt = array();
        $h_errores = 0;
        $h_errores_txt = array();
        $a_errores = 0;
        $a_errores_txt = array();

        $query_errores = "  SELECT TOP (1) * 
                            FROM serviciosyreservaciones_errores 
                            WHERE id_cabecera = $sol_id 
                            ORDER BY fecha DESC;";
        $result_errores = $this->db->query($query_errores)->getResult()[0];

        $error_prj_presupuesto = 0;
        if($result1->proyecto_servicio == 1){
            if(is_null($result_errores->prj_presupuesto)){
                if($result1->prj_presupuesto == 'No'){
                    $error_prj_presupuesto = 1;
                    $h_errores++;
                    $v_errores++;
                    $a_errores++;
                    array_push($h_errores_txt, array('data'=>"- El proyecto no cuenta con presupuesto.", 'error'=> 1, 'extra'=>''));
                    array_push($v_errores_txt, array('data'=>"- El proyecto no cuenta con presupuesto.", 'error'=> 1, 'extra'=>''));
                    array_push($a_errores_txt, array('data'=>"- El proyecto no cuenta con presupuesto.", 'error'=> 1, 'extra'=>''));
                }
            }else{
                if($result1->prj_presupuesto == 'No'){
                    $error_prj_presupuesto=1;
                    $h_errores++;
                    $v_errores++;
                    $a_errores++;
                }
                if($result_errores->prj_presupuesto == 1){
                    array_push($h_errores_txt, array('data'=>"- El proyecto no cuenta con presupuesto.", 'error'=> 1, 'extra'=>''));
                    array_push($v_errores_txt, array('data'=>"- El proyecto no cuenta con presupuesto.", 'error'=> 1, 'extra'=>''));
                    array_push($a_errores_txt, array('data'=>"- El proyecto no cuenta con presupuesto.", 'error'=> 1, 'extra'=>''));
                }
            }
        }

        $error_viaticos_pend=0;
        if(is_null($result_errores->viaticos_pend)){
            if($saldo_viaticos>=1){
                $error_viaticos_pend=1;
                $v_errores++;
                $h_errores++;
                array_push($h_errores_txt, array('data'=> "- El colaborador tenía saldo de viáticos pendiente al crear la solicitud.", 'error'=> 1, 'extra'=>"Actualmente tiene saldo pendiente."));
                array_push($v_errores_txt, array('data'=>"- El colaborador tenía saldo de viáticos pendiente al crear la solicitud.", 'error'=> 1, 'extra'=>'Actualmente tiene saldo pendiente.'));
            }
        }else{
            if($saldo_viaticos>=1){
                $error_viaticos_pend=1;
                $v_errores++;
                $h_errores++;
            }
            if($result_errores->viaticos_pend == 1){
                array_push($h_errores_txt, array('data'=> "- El colaborador tenía saldo de viáticos pendiente al crear la solicitud.", 'error'=> 1, 'extra'=>"Actualmente tiene saldo pendiente."));
                array_push($v_errores_txt, array('data'=>"- El colaborador tenía saldo de viáticos pendiente al crear la solicitud.", 'error'=> 1, 'extra'=>'Actualmente tiene saldo pendiente.'));
            }
        }

    
        //--vuelos
        $error_v_dias_semanasanta=0;
        $error_v_dias_verano=0;
        $error_v_dias_navidad=0;
        $error_v_dias_internacional=0;
        $error_v_dias_nacional=0;

        $result_v_cot = [];

        $query_v = "SELECT 
                        id, 
                        estado, 
                        justificacion,
                        CASE internacional
                            WHEN 0 THEN 'No' 
                            WHEN 1 THEN 'Si'
                            ELSE '' 
                        END AS internacional, 
                        CASE v_tipo
                            WHEN 1 THEN 'Sencillo' 
                            WHEN 2 THEN 'Redondo' 
                            ELSE '' 
                        END AS v_tipo, 
                        v_tipo AS v_tipo_id, 
                        (
                            SELECT t.nombre +', '+t.padre+', '+t.padre2 
                            FROM serviciosyreservaciones_info t 
                            WHERE t.id=origen 
                        ) AS origen, 
                        (
                            SELECT t.nombre +', '+t.padre+', '+t.padre2 
                            FROM serviciosyreservaciones_info t 
                            WHERE t.id=destino 
                        ) AS destino, 
                        v_inicio, 
                        v_fin, 
                        CONVERT(varchar(10),inicio_entrada,21) inicio_entrada, 
                        FORMAT(inicio_entrada, 'dd/MM/yyyy') inicio_entrada_n, 
                        CONVERT(varchar(10),fin_salida,21) fin_salida, 
                        FORMAT(fin_salida, 'dd/MM/yyyy') AS fin_salida_n,
                        CONVERT(varchar(19),fecha,21) fecha 
                    FROM serviciosyreservaciones_detalle 
                    WHERE 
                        status = 1 AND 
                        id_cabecera = $sol_id AND 
                        tipo_solicitud = 1 AND 
                        estado NOT IN (0,12)";
        $result_v = $this->db->query($query_v)->getResult();

        if(sizeof($result_v) > 0){
            $v_cuenta = sizeof($result_v);
            $result_v = $result_v[0];
            $id_v = $result_v->id;
            $estado_v = $result_v->estado;
            $v_tipo = $result_v->v_tipo;
            $v_justificacion = $result_v->justificacion;
            $v_internacional = $result_v->internacional;
            $v_origen = $result_v->origen;
            $v_destino = $result_v->destino;
            $v_inicio = $result_v->v_inicio;
            $v_fin = $result_v->v_fin;
            $v_inicio_entrada = $result_v->inicio_entrada;
            $v_inicio_entrada_n = $result_v->inicio_entrada_n;
            $v_fin_salida = $result_v->fin_salida;
            $v_fin_salida_n = $result_v->fin_salida_n;
            $v_fecha = $result_v->fecha;
        }else{
            $v_cuenta = 0;
            $id_v = 0;
            $estado_v = 0;

            $v_tipo = '';
            $v_justificacion = null;
            $v_internacional = '';
            $v_origen = null;
            $v_destino = null;
            $v_inicio = '';
            $v_fin = '';
            $v_inicio_entrada = '';
            $v_inicio_entrada_n = '';
            $v_fin_salida = '';
            $v_fin_salida_n = '';
            $v_fecha = '';
        }

        if(($estado_v==14)||($estado_v==17))
            $display_btn_cancel_v = 1; // display: 1: none 0: visible
        else
            $display_btn_cancel_v = 0;
        
        if($v_cuenta == 0){
            $v_option = 1; // <option value="1">Vuelo</option>
            $div_v_cotiz = 1; // display: 1: none 0: visible
            $btn_v_cotiz = 1; // disabled: 1 | enable: 0
        }else{
            $v_option = "";
            $str_inicio = strtotime($result_v->inicio_entrada);
            $inicio_anio = date('Y',$str_inicio);
            $inicio_mes = date('m',$str_inicio);
            $str_fin = strtotime($result_v->fin_salida);
            $fin_anio = date('Y',$str_fin);
            $fin_mes = date('m',$str_fin);
            $verano1 = strtotime($inicio_anio.'-07-01');
            $verano2 = strtotime($inicio_anio.'-08-31');
            $diciembre1 = strtotime($inicio_anio.'-12-18');
            $diciembre2 = strtotime($inicio_anio+1 .'-01-10');
            $v_difdias = $this->VH_errorFechaAnticipacion($result_v->inicio_entrada,$result_v->fecha);   
            if($inicio_anio == $fin_anio){
                $query_ss=" SELECT MIN(fecha_festiva) AS miercoles 
                            FROM tbl_vacaciones_diasfestivos 
                            WHERE 
                                tipo = 4 AND 
                                year(fecha_festiva) = '$inicio_anio'";
                $result_ss = $this->db->query($query_ss)->getResult()[0];
                if(!is_null($result_ss->miercoles)){
                    $miercoles = strtotime(date_format(date_create($result_ss->miercoles), "Y-m-d"));
                    $semanasanta1 = strtotime('-2 day', $miercoles);
                    $semanasanta2 = strtotime('+21 day', $semanasanta1);
                    if(($str_inicio>=$semanasanta1)&&($str_inicio<$semanasanta2)){
                        if($v_difdias<45){
                            $v_errores++;
                            $solicitud_errordias++;
                            $solicitud_errordias_v++;
                            $error_v_dias_semanasanta = 1;
                            array_push($v_errores_txt, array('data'=> "La solicitud no cumple con los 45 días de anticipación para semana santa.", 'error'=> 1, 'extra'=> ''));
                        }
                    }else if(($str_fin>=$semanasanta1)&&($str_fin<$semanasanta2)){
                        if($v_difdias<45){
                            $v_errores++;
                            $solicitud_errordias++;
                            $solicitud_errordias_v++;
                            $error_v_dias_semanasanta = 1;
                            array_push($v_errores_txt, array('data'=> "La solicitud no cumple con los 45 días de anticipación para semana santa.", 'error'=> 1, 'extra'=> ''));
                        }
                    }
                }
            }

            if(($str_inicio>=$verano1)&&($str_inicio<=$verano2)){
                if($v_difdias<45){
                    $v_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_v++;
                    $error_v_dias_verano=1;
                    array_push($v_errores_txt, array('data'=> "- La solicitud no cumple con los 45 días de anticipación para vuelos en verano.", 'error'=> 1, 'extra'=> ''));
                }
            }else if(($str_fin>=$verano1)&&($str_fin<=$verano2)){
                if($v_difdias<45){
                    $v_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_v++;
                    $error_v_dias_verano=1;
                    array_push($v_errores_txt, array('data'=> "- La solicitud no cumple con los 45 días de anticipación para vuelos en verano.", 'error'=> 1, 'extra'=> ''));
                }
            }
            if(($str_inicio>=$diciembre1)&&($str_inicio<=$diciembre2)){
                if($v_difdias<45){
                    $v_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_v++;
                    $error_v_dias_navidad=1;
                    array_push($v_errores_txt, array('data'=> "- La solicitud no cumple con los 45 días de anticipación para vuelos en temporada navideña.", 'error'=> 1, 'extra'=> ''));
                }
            }else if(($str_fin>=$diciembre1)&&($str_fin<=$diciembre2)){
                if($v_difdias<45){
                    $v_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_v++;
                    $error_v_dias_navidad=1;
                    array_push($v_errores_txt, array('data'=> "- La solicitud no cumple con los 45 días de anticipación para vuelos en temporada navideña.", 'error'=> 1, 'extra'=> ''));
                }
            }

            if($result_v->internacional == 'Si'){
                if($v_difdias<30){
                    $v_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_v++;
                    $error_v_dias_internacional = 1;
                    array_push($v_errores_txt, array('data'=> "- La solicitud no cumple con los 30 días de anticipación para vuelos internacionales.", 'error'=> 1, 'extra'=> ''));
                }
            }else{
                if($v_difdias<15){
                    $v_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_v++;
                    $error_v_dias_nacional = 1;
                    array_push($v_errores_txt, array('data'=> "- La solicitud no cumple con los 15 días de anticipación para vuelos nacionales.", 'error'=> 1, 'extra'=> ''));
                }
            }
            if($result_v->internacional == 'Si')
                $envio_contraloria++;
            
            $query_v_cot1 = "  SELECT 
                                    t.*, 
                                    j.nombre AS origen, 
                                    j.padre AS origen_estado, 
                                    j.padre2 AS origen_pais, 
                                    jj.nombre AS destino, 
                                    jj.padre AS destino_estado, 
                                    jj.padre2 AS destino_pais, 
                                    CONVERT(varchar(10),t.fecha_salida,21) AS fecha_salida,
                                    FORMAT(t.fecha_salida, 'dd/MM/yyyy') AS fecha_salida_n,
                                    CONVERT(varchar(10),t.fecha_llegada,21) AS fecha_llegada,
                                    FORMAT(t.fecha_llegada, 'dd/MM/yyyy') AS fecha_llegada_n,
                                    FORMAT(t.precio, 'C') precio_n,
	                                'false' AS 'check'
                                FROM 
                                    serviciosyreservaciones_cotizaciones t LEFT JOIN 
                                    serviciosyreservaciones_info j ON t.origen=j.id LEFT JOIN 
                                    serviciosyreservaciones_info jj ON t.destino=jj.id 
                                WHERE 
                                    t.status = 1 AND 
                                    t.tipo_solicitud = 1 AND 
                                    t.id_cabecera = $sol_id AND 
                                    t.estado = 2 
                                ORDER BY t.ida_venida";
            $result_v_cot = $this->db->query($query_v_cot1)->getResult();
            $v_cotizaciones = sizeof($result_v_cot);
            if($v_cotizaciones>=1)
                $v_inputs_cotiz = 1; // display: 1: none | 0: visible

            else{
                $query_v_cot2 = "  SELECT 
                                    t.*, 
                                    j.nombre AS origen, 
                                    j.padre AS origen_estado, 
                                    j.padre2 AS origen_pais, 
                                    jj.nombre AS destino, 
                                    jj.padre AS destino_estado, 
                                    jj.padre2 AS destino_pais, 
                                    CONVERT(varchar(10),t.fecha_salida,21) AS fecha_salida,
                                    FORMAT(t.fecha_salida, 'dd/MM/yyyy') AS fecha_salida_n,
                                    CONVERT(varchar(10),t.fecha_llegada,21) AS fecha_llegada,
                                    FORMAT(t.fecha_llegada, 'dd/MM/yyyy') AS fecha_llegada_n,
                                    FORMAT(t.precio, 'C') precio_n,
	                                'false' AS 'check'
                                FROM 
                                    serviciosyreservaciones_cotizaciones t LEFT JOIN 
                                    serviciosyreservaciones_info j ON t.origen=j.id LEFT JOIN 
                                    serviciosyreservaciones_info jj ON t.destino=jj.id 
                                WHERE 
                                    t.status = 1 AND 
                                    t.tipo_solicitud = 1 AND 
                                    t.id_cabecera = $sol_id 
                                ORDER BY t.ida_venida";
                $result_v_cot = $this->db->query($query_v_cot2)->getResult();
                $v_cotizaciones = sizeof($result_v_cot);
                //$row_v_cot=sqlsrv_fetch_array($qry_v_cot)
                $v_inputs_cotiz=0;
            }
            if($colaborador_id == 1050){
                $div_v_cotiz = 0;
                $btn_v_cotiz = $v_cotizaciones>=1 ? 0 : 1; // disabled: 1: true | 0: false
                
            }else{
                $div_v_cotiz = $v_cotizaciones>=1 ? 0 : 1; // display: 1: none | 0: visible
                $btn_v_cotiz = 1; // disabled: 1: true | 0: false
            }
        }

        $div_v_error = $v_errores>=1 ? 0 : ( $result_errores->viaticos_pend == 1 ? 0 : 1 ); // display: 1: none | 0: visible



        //--hospedaje
        $error_h_dias_semanasanta = 0;
        $error_h_dias_verano = 0;
        $error_h_dias_navidad = 0;
        $error_h_dias_internacional = 0;
        $error_h_dias_nacional = 0;

        $result_h_cot = [];

        $query_h = "  SELECT 
                        id, 
                        CASE internacional 
                            WHEN 0 THEN 'No' 
                            WHEN 1 THEN 'Si' 
                            ELSE '' 
                        END AS internacional, 
                        CASE h_tipo
                            WHEN 1 THEN 'Individual' 
                            WHEN 2 THEN 'Doble' 
                            WHEN 3 THEN 'Triple' 
                            WHEN 4 THEN 'Cuádruple' 
                            WHEN 5 THEN 'Casa ECN' 
                            ELSE '' 
                        END AS h_tipo, 
                        h_tipo AS h_tipo_id,
                        h_ciudad, 
                        CONVERT(varchar(10),inicio_entrada,21) inicio_entrada, 
                        FORMAT(inicio_entrada, 'dd/MM/yyyy') inicio_entrada_n, 
                        CONVERT(varchar(10),fin_salida,21) fin_salida, 
                        FORMAT(fin_salida, 'dd/MM/yyyy') fin_salida_n, 
                        CONVERT(varchar(19),fecha,21) fecha, 
                        estado
                    FROM serviciosyreservaciones_detalle 
                    WHERE 
                        status = 1 AND 
                        id_cabecera = $sol_id AND 
                        tipo_solicitud = 2 AND 
                        estado NOT IN (0,12)";
        $result_h = $this->db->query($query_h)->getResult();
        $h_cuenta = sizeof($result_h);
        $result_h = isset($result_h[0]) ? $result_h[0] : [];
        $id_h = isset($result_h->id) ? $result_h->id : 0;
        $estado_h = isset($result_h->estado) ? $result_h->estado : 0;
        if(($estado_h == 14)||($estado_h == 17))
            $display_btn_cancel_h = 1; // disabled: 1: none | 0: visible
        else
            $display_btn_cancel_h = 0;
        
        if($h_cuenta == 0){
            $h_option = 2; // <option value="2">Hospedaje</option>
            $div_h_cotiz = 1; // display: 1: none | 0 visible
            $btn_h_cotiz = 1; // disabled: 1: true | 0: false
        }else{
            $h_option = 0; // disabled: 1: true | 0: false
            $str_inicio = strtotime($result_h->inicio_entrada);
            $inicio_anio = date('Y',$str_inicio);
            $inicio_mes = date('m',$str_inicio);
            $str_fin = strtotime($result_h->fin_salida);
            $fin_anio = date('Y',$str_fin);
            $fin_mes = date('m',$str_fin);
            $verano1 = strtotime($inicio_anio.'-07-01');
            $verano2 = strtotime($inicio_anio.'-08-31');
            $diciembre1 = strtotime($inicio_anio.'-12-18');
            $diciembre2 = strtotime($inicio_anio+1 .'-01-10');
            $h_difdias = $this->VH_errorFechaAnticipacion($result_h->inicio_entrada,$result_h->fecha);
            if($inicio_anio == $fin_anio){
                // ss_s
                $query_ss ="SELECT min(fecha_festiva) AS miercoles 
                            FROM tbl_vacaciones_diasfestivos 
                            WHERE 
                                tipo = 4 AND 
                                year(fecha_festiva) = '$inicio_anio'";
                $result_ss = $this->db->query($query_ss)->getResult()[0]; // ss_r
                if(!is_null($result_ss->miercoles)){
                    $miercoles = strtotime(date_format(date_create($result_ss->miercoles), "Y-m-d"));
                    $semanasanta1 = strtotime('-2 day', $miercoles);
                    $semanasanta2 = strtotime('+21 day', $semanasanta1);
                    if(($str_inicio>=$semanasanta1)&&($str_inicio<$semanasanta2)){
                        if($h_difdias<20){
                            $h_errores++;
                            $solicitud_errordias++;
                            $solicitud_errordias_h++;
                            $error_h_dias_semanasanta=1;
                            array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 20 días de anticipación para semana santa.", 'error'=> 1, 'extra'=>""));
                        }
                    }else if(($str_fin>=$semanasanta1)&&($str_fin<$semanasanta2)){
                        if($h_difdias<20){
                            $h_errores++;
                            $solicitud_errordias++;
                            $solicitud_errordias_h++;
                            $error_h_dias_semanasanta=1;
                            array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 20 días de anticipación para semana santa.", 'error'=> 1, 'extra'=>""));
                        }
                    }
                }
            }
            if(($str_inicio>=$verano1)&&($str_inicio<=$verano2)){
                if($h_difdias<20){
                    $h_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_h++;
                    $error_h_dias_verano = 1;
                    array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 20 días de anticipación para vuelos en verano.", 'error'=> 1, 'extra'=>""));
                }
            }else if(($str_fin>=$verano1)&&($str_fin<=$verano2)){
                if($h_difdias<20){
                    $h_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_h++;
                    $error_h_dias_verano = 1;
                    array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 20 días de anticipación para vuelos en verano.", 'error'=> 1, 'extra'=>""));
                }
            }
            if(($str_inicio>=$diciembre1)&&($str_inicio<=$diciembre2)){
                if($h_difdias<20){
                    $h_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_h++;
                    $error_h_dias_navidad = 1;
                    array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 20 días de anticipación para vuelos en temporada navideña.", 'error'=> 1, 'extra'=>""));
                }
            }else if(($str_fin>=$diciembre1)&&($str_fin<=$diciembre2)){
                if($h_difdias<20){
                    $h_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_h++;
                    $error_h_dias_navidad = 1;
                    array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 20 días de anticipación para vuelos en temporada navideña.", 'error'=> 1, 'extra'=>""));
                }
            }

            if($result_h->internacional == 'Si'){
                if($h_difdias<15){
                    $h_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_h++;
                    $error_h_dias_internacional=1;
                    array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 15 días de anticipación para hospedajes internacionales.", 'error'=> 1, 'extra'=>""));
                }
            }else{
                if($h_difdias<5){
                    $h_errores++;
                    $solicitud_errordias++;
                    $solicitud_errordias_h++;
                    $error_h_dias_nacional=1;
                    array_push($h_errores_txt, array('data'=> "- La solicitud no cumple con los 5 días de anticipación para hospedajes nacionales.", 'error'=> 1, 'extra'=>""));
                }
            }
            if($result_h->internacional == 'Si')
                $envio_contraloria++;

            $query_h_cot = "  SELECT 
                                t.*, 
                                t.h_ciudad as origen, 
                                CASE t.h_tipo
                                    WHEN 1 THEN 'Hab. sencilla' 
                                    WHEN 2 THEN 'Hab. doble' 
                                    WHEN 3 THEN 'Hab. triple' 
                                    WHEN 4 THEN 'Hab. cuádruple' 
                                    WHEN 5 THEN 'Casa ECN' 
                                    ELSE 'No especificado' 
                                END AS h_tipo, 
                                CONVERT(varchar(10),t.fecha_salida,21) AS fecha_salida,
                                FORMAT(t.fecha_salida, 'dd/MM/yyyy') fecha_salida_n, 
                                CONVERT(varchar(10),t.fecha_llegada,21) AS fecha_llegada,
                                FORMAT(t.fecha_llegada, 'dd/MM/yyyy') fecha_llegada_n,
                                FORMAT(t.precio, 'C') precio_n,
	                            'false' AS 'check'
                            FROM serviciosyreservaciones_cotizaciones t 
                            WHERE 
                                t.status = 1 AND 
                                t.tipo_solicitud = 2 AND 
                                t.estado = 2 AND 
                                t.id_cabecera = $sol_id";
            $result_h_cot = $this->db->query($query_h_cot)->getResult();
            $h_cotizaciones = sizeof($result_h_cot);
            if($h_cotizaciones>=1)
                $h_inputs_cotiz = 1; // display: 1: none | 0: visible

            else{
                $query_h_cot2 = "  SELECT 
                                    t.*, 
                                    t.h_ciudad AS origen, 
                                    CASE t.h_tipo
                                        WHEN 1 THEN 'Hab. sencilla' 
                                        WHEN 2 THEN 'Hab. doble' 
                                        WHEN 3 THEN 'Hab. triple' 
                                        WHEN 4 THEN 'Hab. cuádruple' 
                                        WHEN 5 THEN 'Casa ECN' 
                                        else 'No especificado' 
                                    END AS h_tipo, 
                                    CONVERT(varchar(10),t.fecha_salida,21) AS fecha_salida,
                                    FORMAT(t.fecha_salida, 'dd/MM/yyyy') fecha_salida_n, 
                                    CONVERT(varchar(10),t.fecha_llegada,21) AS fecha_llegada,
                                    FORMAT(t.fecha_llegada, 'dd/MM/yyyy') fecha_llegada_n,
                                    FORMAT(t.precio, 'C') precio_n,
	                                'false' AS 'check' 
                                FROM serviciosyreservaciones_cotizaciones t  
                                WHERE 
                                    t.status = 1 AND 
                                    t.tipo_solicitud = 2 AND 
                                    t.id_cabecera = $sol_id";
                $result_h_cot = $this->db->query($query_h_cot2)->getResult();
                $h_cotizaciones = sizeof($result_h_cot);
                //$row_h_cot=sqlsrv_fetch_array($qry_h_cot)
                $h_inputs_cotiz = 0;
            }
            if($colaborador_id == 1050){
                $div_h_cotiz = 0; // disabled: 1: true | 0: false
                $btn_h_cotiz = $h_cotizaciones>=1 ? 0 : 1; // disabled: 1: true | 0: false

            }else{
                $div_h_cotiz = $h_cotizaciones>=1 ? 0 : 1; // disabled: 1: true | 0: false
                $div_h_cotiz = 1;
            }
        }

        $div_h_error = $h_errores>=1 ? 0 : // disabled: 1: true | 0: false  
                            ( $result_errores->viaticos_pend == 1 ? 0 : 1 ); // display: 1: none | 0: visible

                            
        //--auto
        $error_dias_autos=0;

        $result_a_cot = [];

        $query_a="  SELECT 
                        id, 
                        CASE a_tipo
                            WHEN 1 THEN 'Camioneta' 
                            WHEN 2 THEN 'Compacto' 
                            WHEN 3 THEN 'Pick up 4x4' 
                            ELSE '' 
                        END AS a_tipo, 
                        a_tipo AS a_tipo_id, 
                        CASE a_lugar_entrega
                            WHEN 1 THEN 'Aeropuerto' 
                            WHEN 2 THEN 'Agencia de renta' 
                            WHEN 3 THEN 'Central autobús' 
                            WHEN 4 THEN 'Hotel' 
                            WHEN 5 THEN 'Oficina' 
                            ELSE '' 
                        END AS a_lugar_entrega,
                        a_lugar_entrega AS a_lugar_entrega_id, 
                        CONVERT(varchar(10),inicio_entrada,21) inicio_entrada,
                        FORMAT(inicio_entrada, 'dd/MM/yyyy') inicio_entrada_n, 
                        CONVERT(varchar(10),fin_salida,21) fin_salida,  
                        FORMAT(fin_salida, 'dd/MM/yyyy') fin_salida_n, 
                        CONVERT(varchar(19),fecha,21) fecha, 
                        estado 
                    FROM serviciosyreservaciones_detalle 
                    WHERE 
                        status = 1 AND 
                        id_cabecera = $sol_id AND 
                        tipo_solicitud = 3 AND 
                        estado NOT IN (0,12)";
        $result_a = $this->db->query($query_a)->getResult();
        $a_cuenta = sizeof($result_a);
        $result_a = isset($result_a[0]) ? $result_a[0] : [];
        $id_a = isset($result_a->id) ? $result_a->id : 0;
        $estado_a = isset($result_a->estado) ? $result_a->estado : 0;
        if(($estado_a==14)||($estado_a==17))
            $display_btn_cancel_a = 1; // display: 1: none | 0: visible
        else
            $display_btn_cancel_a = 0;
        
        if($a_cuenta == 0){
            $a_option = 3; // <option value="3">Renta de auto</option>
            $div_a_cotiz = 1; // display: 1: none | 0: visible
            $btn_a_cotiz = 1; // disabled: 1: true | 0: false
        }else{
            $a_option = 0;
            $a_difdias = $this->VH_errorFechaAnticipacion($result_a->inicio_entrada,$result_a->fecha);
            if($a_difdias<3){
                $a_errores++;
                $solicitud_errordias++;
                $error_dias_autos = 1;
                array_push($a_errores_txt, array('data'=> "- La solicitud no cumple con los 3 días de anticipación.", 'error'=> 1, 'extra'=>""));
            }
            
            $query_a_cot="SELECT 
                            *, 
                            CONVERT(varchar(10),fecha_salida,21) AS fecha_salida, 
                            FORMAT(fecha_salida, 'dd/MM/yyyy') AS fecha_salida_n, 
                            CONVERT(varchar(10),fecha_llegada,21) AS fecha_llegada,
                            FORMAT(fecha_llegada, 'dd/MM/yyyy') AS fecha_llegada_n,
                            FORMAT(precio, 'C') AS precio_n
                        FROM serviciosyreservaciones_cotizaciones 
                        WHERE 
                            status = 1 AND 
                            tipo_solicitud = 3 AND 
                            estado = 2 AND 
                            id_cabecera = $sol_id";
            $result_a_cot = $this->db->query($query_a_cot)->getResult();
            $a_cotizaciones=sizeof($result_a_cot);
            if($a_cotizaciones>=1)
                $a_inputs_cotiz = 1; // display: 1: none | 0: visible

            else{
                $query_a_cot = "SELECT 
                                    *, 
                                    CONVERT(varchar(10),fecha_salida,21) AS fecha_salida, 
                                    FORMAT(fecha_salida, 'dd/MM/yyyy') AS fecha_salida_n, 
                                    CONVERT(varchar(10),fecha_llegada,21) AS fecha_llegada,
                                    FORMAT(fecha_llegada, 'dd/MM/yyyy') AS fecha_llegada_n,
                                    FORMAT(precio, 'C') AS precio_n
                                FROM serviciosyreservaciones_cotizaciones 
                                WHERE 
                                    status = 1 AND 
                                    tipo_solicitud = 3 AND 
                                    id_cabecera = $sol_id ";
                $result_a_cot = $this->db->query($query_a_cot)->getResult();
                $a_cotizaciones = sizeof($result_a_cot);
                //$row_a_cot=sqlsrv_fetch_array($qry_a_cot)
                $a_inputs_cotiz = 0;
            }
            if($colaborador_id==1050){
                $div_a_cotiz = 0; // disabled: 1: true | 0: false
                $btn_a_cotiz = $a_cotizaciones>=1 ? 0 : 1; // disabled: 1: true | 0: false
            }else{
                $div_a_cotiz = $a_cotizaciones>=1 ? 0 : 1; // display: 1: none | 0: visible
                $btn_a_cotiz = 1; // disabled: 1: true | 0: false
            }
        }
    
        $div_a_error = $a_errores>=1 ? 0 : ( $result_errores->viaticos_pend == 1 ? 0 : 1 ); // display: 1: none | 0: visible


        if($result1->edo==10 || $result1->edo==11){
            $div_compras = 0; // display: 1: none | 0: visible
            $div_compras_capturar = $colaborador_id == 1050 ? 0 : 1; // display: 1: none | 0: visible
        }else{
            $div_compras = 1; // display: 1: none | 0: visible
            $div_compras_capturar = 1; // display: 1: none | 0: visible
        }
        
        $total_errores = $v_errores+$h_errores+$a_errores;
        $autoriza_contralor = $envio_contraloria>=1 ? 1 : ( $total_errores>=1 ? 1 : 0);
        
        $totalsol_hechas = $v_cuenta + $h_cuenta + $a_cuenta;

        
        /*  Si el siguiente if es un si colocar el boton
            <button class="btn btn-primary btn-lg dim" type="button" data-toggle="modal" data-target="#terminar_enviarlider" id="btn_terminar_enviarlider">
                Terminar <i class="fa fa-paper-plane"></i>
            </button>
        */
        $btn_terminar_enviarlider = 0;
        if(($totalsol_hechas>=1)&&($result1->edo==1))
            $btn_terminar_enviarlider = 1;
        

        $btn_imprimir_sol=0;
        switch($result1->estado_solicnv){
            case 0 : $estado_solicnv = ""; break; // Vacío
            case 1 : $estado_solicnv = "Esperando autorización de cambio de nombre (con el líder)"; $btn_imprimir_sol++; break; // Lider
            case 2 : $estado_solicnv = "Esperando autorización de cambio de nombre (con contraloría)"; $btn_imprimir_sol++; break; // Contraloría
                
        }
        switch($result1->reagenda_vuelo){
            case 0 : $estado_reagendarvuelo = ""; break; // Vacío
            case 1 : $estado_reagendarvuelo = "Esperando autorización para reagendar Vuelo (con el líder)"; $btn_imprimir_sol++; break;  // Lider
            case 2 : $estado_reagendarvuelo = "Esperando autorización para reagendar Vuelo (con contraloría)"; $btn_imprimir_sol++; break; // Contraloría
                
        }
        switch($result1->reagenda_hosp){
            case 0 : $estado_reagendarhosp = ""; break; // Vacío
            case 1 : $estado_reagendarhosp = "Esperando autorización para reagendar Hospedaje (con el líder)"; $btn_imprimir_sol++; break;  // Lider
            case 2 : $estado_reagendarhosp = "Esperando autorización para reagendar Hospedaje (con contraloría)"; $btn_imprimir_sol++; break; // Contraloría
        }
        /*  Si el siguiente if es un si colocar el enlace
            <a href='php/servicios_reservaciones/vuelos_pdf.php?tp=1&id_cabecera=".$sol_id."' class='btn btn_default btn-xs' target='_blank' ><i class='fa fa-print'></i> Imprimir solicitud</a>
        */
        $btn_imprimir="";
        if(($btn_imprimir_sol==0)&&(($result1->edo==4)||($result1->edo==6)||($result1->edo==8)||($result1->edo==9)||($result1->edo==10)||($result1->edo==11)||($result1->edo==15)||($result1->edo==16))&&($colaborador_id==1050))
            $btn_imprimir="php/servicios_reservaciones/vuelos_pdf.php?tp=1&id_cabecera=".$sol_id;
        
        
        $internacional_inge = 0;
        if(isset($result_h->internacional))
            if($result_h->internacional == 'Si')
                $internacional_inge = 1;
        elseif(isset($result_v->internacional))
            if($result_v->internacional == 'Si')
                $internacional_inge = 1;
        

        ///////////////////
        // ENCUESTA

        $encuesta = $this->SR_getEncuesta($sol_id);
        $aerolinea = [];

        if($colaborador_id == 1050){
            $result_vuelos = [];
            $vuelo_count = 0;
            if(sizeof($result_v_cot) > 0){
                $result_vuelos = $result_v_cot;
                $vuelo_count = sizeof($result_v_cot);
            }
            
            for($i = 0; $i < $vuelo_count; $i++){
                $aerolinea_txt = $this->VH_aerolinea($result_vuelos[$i]->aerolinea);
                $aerolinea[] = $aerolinea_txt;
            }
        }
        
        $nombre_colab_solicitante_P = isset($result1->apellido_p) ? $result1->apellido_p : '';
        $nombre_colab_solicitante_M = isset($result1->apellido_m) ? $result1->apellido_m : '';
        $nombre_colab_solicitante_N = isset($result1->nombres) ? $result1->nombres : '';
        
        $datos_sol_estado = isset($result1->estado) ? ( isset($result1->status) ? $this->VH_estadoSolicitud($result1->estado).' '.$result1->status : $this->VH_estadoSolicitud($result1->estado) ) : 
                                ( isset($result1->status) ? $result1->status : 0 );

        $datos_solicitante = array(
            'nombre_colab_solicitante' => $nombre_colab_solicitante_P.' '.$nombre_colab_solicitante_M.' '.$nombre_colab_solicitante_N,
            'solicitante_nuevo_nombre' => isset($result1->solicitante_nuevo_nombre) ? $result1->solicitante_nuevo_nombre : null,
            'solicitante_nuevo' => isset($result1->solicitante_nuevo) ? $result1->solicitante_nuevo : null,
            'celular' => isset($result1->colab_celular) ? $result1->colab_celular : null,
            'estado' => $datos_sol_estado,
            'estado_solicnv' => isset($result1->estado_solicnv) ? $result1->estado_solicnv : null,
            'fecha' => isset($result1->fecha) ? $result1->fecha : null,
            'c_email' => isset($result1->email) ? $result1->email : null,
            'f_nacimiento' => isset($result1->fecha_nacimiento) ? $result1->fecha_nacimiento : null,
            'f_nacimiento_n' => isset($result1->fecha_nacimiento_n) ? $result1->fecha_nacimiento_n : null,
            'c_puesto' => isset($result1->puesto) ? $result1->puesto : null,
            'n_depto' => isset($result1->departamentos_codigo) ? $result1->departamentos_codigo : null,
            'urgencia_creador' => isset($result1->urgencia_creador) ? $result1->urgencia_creador : null,
            'proyecto_servicio' => isset($result1->proyecto_servicio) ? $result1->proyecto_servicio : '',
            'prj_presupuesto' => isset($result1->prj_presupuesto) ? $result1->prj_presupuesto : null,
            'PrjCode' => isset($result1->prjcode) ? $result1->prjcode : null,
            'motivo' => isset($result1->motivo) ? $result1->motivo : null,
            'edo' => isset($result1->edo) ? $result1->edo : null,
            'autoriza' => isset($result1->autoriza) ? $result1->autoriza : null,
            'lider_email' => isset($result1->lider_email) ? $result1->lider_email : null,
            'lider_id' => isset($result1->lider_id) ? $result1->lider_id : null,
            'lider_celular' => isset($result1->lider_celular) ? $result1->lider_celular : null,
            'req_contraloria' => isset($result1->req_contraloria) ? $result1->req_contraloria : null,
            'n_nr' => isset($result1->zonaGeografica_codigo) ? $result1->zonaGeografica_codigo : null,
            'contralor_id' => isset($result1->contralor_id) ? $result1->contralor_id : null,
            'id_colaborador' => isset($result1->id_colaborador) ? $result1->id_colaborador : null,
            'reagenda_vuelo' => isset($result1->reagenda_vuelo) ? $result1->reagenda_vuelo : null,
            'reagenda_hosp' => isset($result1->reagenda_hosp) ? $result1->reagenda_hosp : null,

            'div_cambionombre' => $div_cambionombre,
            'urgencia' => $urgencia,
            'envio_contraloria' => $envio_contraloria,

            'div_compras' => $div_compras,
            'div_compras_capturar' => $div_compras_capturar,
            'total_errores' => $total_errores,
            'autoria_contralor' => $autoriza_contralor,

            'btn_terminar_enviarlider' => $btn_terminar_enviarlider,
            'btn_imprimir_sol' => $btn_imprimir_sol,
            'totalsol_hechas' => $totalsol_hechas,

            'estado_solicnv' => $estado_solicnv,
            'estado_reagendarvuelo' => $estado_reagendarvuelo,
            'estado_reagendarhosp' => $estado_reagendarhosp,

            'btn_impimir' => $btn_imprimir,
            'internacional_inge' => $internacional_inge
        );

        $datos_vuelos = array(
            'id_v' => $id_v,
            'estado_v' => $estado_v,
            'display_btn_cancel_v' => $display_btn_cancel_v,
            'v_cuenta' => $v_cuenta,

            'v_option' => $v_option,
            'div_v_cotiz' => isset($div_v_cotiz) ? $div_v_cotiz : 0,
            'btn_v_cotiz' => isset($btn_v_cotiz) ? $btn_v_cotiz : 0,
            'v_inputs_cotiz' => isset($v_inputs_cotiz) ? $v_inputs_cotiz : 0,

            'v_tipo' => $v_tipo,
            'justificacion' => $v_justificacion,
            'internacional' => $v_internacional,
            'origen' => $v_origen,
            'destino' => $v_destino,
            'v_inicio' => $v_inicio,
            'v_fin' => $v_fin,
            'inicio_entrada' => $v_inicio_entrada,
            'inicio_entrada_n' => $v_inicio_entrada_n,
            'fin_salida' => $v_fin_salida,
            'fin_salida_n' => $v_fin_salida_n,
            'fecha' => $v_fecha
        );

        $datos_hospedaje = array(
            'id_h' => $id_h,
            'estado_h' => $estado_h,
            'h_tipo' => isset($result_h->h_tipo) ? $result_h->h_tipo : null,
            'h_ciudad' => isset($result_h->h_ciudad) ? $result_h->h_ciudad : null,
            'internacional' => isset($result_h->internacional) ? $result_h->internacional : null,
            'inicio_entrada' => isset($result_h->inicio_entrada) ? $result_h->inicio_entrada : null,
            'inicio_entrada_n' => isset($result_h->inicio_entrada_n) ? $result_h->inicio_entrada_n : null,
            'fin_salida' => isset($result_h->fin_salida) ? $result_h->fin_salida : null,
            'fin_salida_n' => isset($result_h->fin_salida_n) ? $result_h->fin_salida_n : null,
            'display_btn_cancel_h' => $display_btn_cancel_h,
            'h_cuenta' => $h_cuenta,

            'h_option' => $h_option,
            'div_h_cotiz' => isset($div_h_cotiz) ? $div_h_cotiz : null,
            'btn_h_cotiz' => isset($btn_h_cotiz) ? $btn_h_cotiz : null,
            'h_inputs_cotiz' => isset($h_inputs_cotiz) ? $h_inputs_cotiz : null
        );

        $datos_auto = array(
            'id_a' => $id_a,
            'estado_a' => $estado_a,
            'display_btn_cancel_a' => $display_btn_cancel_a,
            'a_cuenta' => $a_cuenta,
            
            'a_option' => $a_option,
            'div_a_cotiz' => isset($div_a_cotiz) ? $div_a_cotiz : null,
            'btn_a_cotiz' => isset($btn_a_cotiz) ? $btn_a_cotiz : null,
            'a_inputs_cotiz' => isset($a_inputs_cotiz) ? $a_inputs_cotiz : null,

            'a_tipo' => isset($result_a->a_tipo) ? $result_a->a_tipo : null,
            'a_lugar_entrega' => isset($result_a->a_lugar_entrega) ? $result_a->a_lugar_entrega : null,
            'inicio_entrada' => isset($result_a->inicio_entrada) ? $result_a->inicio_entrada : null,
            'inicio_entrada_n' => isset($result_a->inicio_entrada_n) ? $result_a->inicio_entrada_n : null,
            'fin_salida' => isset($result_a->fin_salida) ? $result_a->fin_salida : null,
            'fin_salida_n' => isset($result_a->fin_salida_n) ? $result_a->fin_salida_n : null,
        );

        $datos_errores = array(
            'solicitud_errordias' => $solicitud_errordias,
            'solicitud_errordias_v' => $solicitud_errordias_v,
            'solicitud_errordias_h' => $solicitud_errordias_h,
            'error_div_compras' => $div_compras,

            'v_errores' => $v_errores,
            'v_errores_txt' => $v_errores_txt,
            'h_errores' => $h_errores,
            'h_errores_txt' => $h_errores_txt,
            'a_errores' => $a_errores,
            'a_errores_txt' => $a_errores_txt,

            'error_prj_presupuesto' => $error_prj_presupuesto,
            'error_viaticos_pend' => $error_viaticos_pend,

            //-- vuelos
            'error_v_dias_semanasanta' => $error_v_dias_semanasanta,
            'error_v_dias_verano' => $error_v_dias_verano,
            'error_v_dias_navidad' => $error_v_dias_navidad,
            'error_v_dias_internacional' => $error_v_dias_internacional,
            'error_v_dias_nacional' => $error_v_dias_nacional,
            'div_v_error' => $div_v_error,

            //-- hospedaje
            'error_h_dias_semanasanta' => $error_h_dias_semanasanta,
            'error_h_dias_verano' => $error_h_dias_verano,
            'error_h_dias_navidad' => $error_h_dias_navidad,
            'error_h_dias_internacional' => $error_h_dias_internacional,
            'error_h_dias_nacional' => $error_h_dias_nacional,
            'cotiz_h_error' => $div_h_error,

            //-- auto
            'error_dias_autos' => $error_dias_autos,
            'div_a_error' => $div_a_error
        );


        $general = array(
            'solicitante' => $datos_solicitante,
            'vuelos' => $datos_vuelos,
            'hospedaje' => $datos_hospedaje,
            'auto' => $datos_auto,
            'errores' => $datos_errores,

            'hospedaje_cotizacion' => sizeof($result_h_cot) > 0 ? $result_h_cot : [],
            'vuelos_cotizacion' => sizeof($result_v_cot) > 0 ? $result_v_cot : [],
            'autos_cotizacion' => sizeof($result_a_cot) > 0 ? $result_a_cot : [],

            'aerolinea' => $aerolinea,
            'encuesta' => $encuesta,
            'totalsol_hechas' => $totalsol_hechas
        );


        //////////////////////////////
        // HISTORIAL

        $list = array();
        $query = "  SELECT 
                        t.id, 
                        t.id_creador, 
                        CASE 
                            WHEN t.id_creador = $colaborador_id THEN 1 
                            ELSE 0 
                        END AS alineacion, 
                        CONVERT(varchar(19),t.fecha,21) AS fecha, 
                        FORMAT(t.fecha, 'dd/MM/yyyy HH:mm') AS fecha_n,
                        t.tipo_modificacion, 
                        t.nuevo_estado, 
                        t.comentario, 
                        c.apellido_p+' '+c.apellido_m+' '+c.nombres AS nombre_creador, 
                        CASE 
                            WHEN t.tipo_solicitud=1 THEN 'vuelo' 
                            WHEN t.tipo_solicitud=2 THEN 'hospedaje' 
                            WHEN t.tipo_solicitud=3 THEN 'renta de auto' 
                            ELSE '' 
                        END AS tipo_solicitud 
                    FROM 
                        serviciosyreservaciones_comentarios t LEFT JOIN 
                        Colaboradores c ON t.id_creador=c.id_colaborador 
                    WHERE 
                        t.status=1 AND 
                        t.id_cabecera=$sol_id 
                    ORDER BY t.id";
        $qry_comm = $this->db->query($query);

        if($qry_comm){
            $qry_comm = $qry_comm->getResult();
            foreach($qry_comm as $row_comm){
                $clase_align_com = $row_comm->alineacion == 1 ? 1 : 0;
    
                array_push($list, array(
                    'tipo_m' => $row_comm->tipo_modificacion,
                    'tipo_s' => $row_comm->tipo_solicitud,
                    'estado' => $row_comm->nuevo_estado,
                    'fecha' => $row_comm->fecha,
                    'fecha_n' => $row_comm->fecha_n,
                    'nombre_creador' => $row_comm->nombre_creador,
                    'align' => $clase_align_com,
                    'comentario' => nl2br($row_comm->comentario)
                ));
            }
        }


        // END HISTORIAL
        //////////////////////////////

        //////////////////////////////
        // REAGENDAR VUELO

        $reag_vuelo = " SELECT 
                            id, 
                            estado, 
                            justificacion, 
                            CASE internacional
                                WHEN 0 THEN 'No' 
                                WHEN 1 THEN 'Si' 
                                ELSE '' 
                            END AS internacional, 
                            CASE v_tipo
                                WHEN 1 THEN 'Sencillo' 
                                WHEN 2 THEN 'Redondo' 
                                ELSE '' 
                            END AS v_tipo, 
                            v_tipo AS v_tipo_id, 
                            (
                                SELECT t.nombre + ', ' + t.padre + ', ' + t.padre2 
                                FROM serviciosyreservaciones_info t 
                                WHERE t.id = origen
                            ) AS v_origen, 
                            (
                                SELECT t.nombre + ', ' + t.padre + ', ' + t.padre2 
                                FROM serviciosyreservaciones_info t 
                                WHERE t.id = destino
                            ) AS v_destino, 
                            v_inicio, 
                            v_fin, 
                            CONVERT(varchar(10), inicio_entrada, 21) AS inicio_entrada, 
                            CONVERT(varchar(10), fin_salida, 21) AS fin_salida, 
                            CONVERT(varchar(19), fecha, 21) AS fecha 
                        FROM serviciosyreservaciones_detalle 
                        WHERE 
                            status = 1 AND 
                            id_cabecera = $sol_id AND 
                            tipo_solicitud = 1 AND
                            estado = 17;";
        $resultado_vuelo = $this->db->query($reag_vuelo)->getResult();
        if(sizeof($resultado_vuelo) > 0){
            $row_vuelo = $resultado_vuelo[0];
            $r_id_v = $row_vuelo->id;
            $r_justif_v = $row_vuelo->justificacion;
            $r_internacional_v = $row_vuelo->internacional;
            $r_tipo_v = $row_vuelo->v_tipo;
            $r_origen_v = $row_vuelo->v_origen;
            $r_destino_v = $row_vuelo->v_destino;
            $r_inicio_entrada_pais_v = $row_vuelo->inicio_entrada;
            $r_fin_salida_pais_v = $row_vuelo->fin_salida;
            $r_inicio_v = $row_vuelo->v_inicio;
            $r_fin_v = $row_vuelo->v_fin;
        }else{
            $r_id_v = NULL;
            $r_justif_v = NULL;
            $r_internacional_v = NULL;
            $r_tipo_v = NULL;
            $r_origen_v = NULL;
            $r_destino_v = NULL;
            $r_inicio_entrada_pais_v = NULL;
            $r_fin_salida_pais_v = NULL;
            $r_inicio_v = NULL;
            $r_fin_v = NULL;
        }

        $reag_vuelo_list = array(
            'Vid' => $r_id_v,
            'Vjustif' => $r_justif_v,
            'Vinternacional' => $r_internacional_v,
            'Vtipo' => $r_tipo_v,
            'Vorigen' => $r_origen_v,
            'Vdestino' => $r_destino_v,
            'Vinicio_entrada_pais' => $r_inicio_entrada_pais_v,
            'Vfin_salida_pais' => $r_fin_salida_pais_v,
            'Vinicio' => $r_inicio_v,
            'Vfin' => $r_fin_v
        );


        // END REAGENDAR VUELO
        //////////////////////////////

        //////////////////////////////
        // REAGENDAR HOSPEDAJE

        $reag_hosp = "  SELECT 
                            id, 
                            justificacion, 
                            id_cabecera, 
                            CASE internacional
                                WHEN 0 THEN 'No' 
                                WHEN 1 THEN 'Si' 
                                ELSE '' 
                            END AS internacional, 
                            CASE h_tipo
                                WHEN 1 THEN 'Individual' 
                                WHEN 2 THEN 'Doble' 
                                WHEN 3 THEN 'Triple'
                                WHEN 4 THEN 'Cuádruple' 
                                WHEN 5 THEN 'Casa ECN' 
                                ELSE '' 
                            END AS h_tipo,
                            h_tipo AS h_tipo_id, 
                            h_ciudad, 
                            CONVERT(varchar(10), inicio_entrada, 21) inicio_entrada, 
                            CONVERT(varchar(10), fin_salida, 21) fin_salida, 
                            CONVERT(varchar(19), fecha, 21) fecha, 
                            estado 
                        FROM serviciosyreservaciones_detalle 
                        WHERE 
                            status = 1 AND 
                            id_cabecera = $sol_id AND 
                            tipo_solicitud = 2 AND 
                            estado = 17";
        $resultado_hosp = $this->db->query($reag_hosp)->getResult();
        if(sizeof($resultado_hosp) > 0){
            $row_hosp = $resultado_hosp[0];
            $r_id_h = $row_hosp->id;
            $r_justif_h = $row_hosp->justificacion;
            $r_internacional_h = $row_hosp->internacional;
            $r_tipo_h = $row_hosp->h_tipo;
            $r_inicio_entrada_pais_h = $row_hosp->inicio_entrada;
            $r_fin_salida_pais_h = $row_hosp->fin_salida;
            $r_ciudad_h = $row_hosp->h_ciudad;
        }else{
            $r_id_h = NULL;
            $r_justif_h = NULL;
            $r_internacional_h = NULL;
            $r_tipo_h = NULL;
            $r_inicio_entrada_pais_h = NULL;
            $r_fin_salida_pais_h = NULL;
            $r_ciudad_h = NULL;
        }

        $reag_hosp_list = array(
            'Hid' => $r_id_h,
            'Hjustif' => $r_justif_h,
            'Hinternacional' => $r_internacional_h,
            'Htipo' => $r_tipo_h,
            'Hinicio_entrada_pais' => $r_inicio_entrada_pais_h,
            'Hfin_salida_pais' => $r_fin_salida_pais_h,
            'Hciudad' => $r_ciudad_h
        );

        // END REAGENDAR HOSPEDAJE
        //////////////////////////////

        return $this->response->setStatusCode(200)->setJSON(array($general, $list, $reag_vuelo_list, $reag_hosp_list));

    }

        public function VH_errorFechaAnticipacion($fecha1, $fecha_registro){
            $fecha_1 = date_create($fecha1);
            $fecha_reg = date_create($fecha_registro);
            $dif2 = $fecha_1->diff($fecha_reg);
            $dias_diferencia = $dif2->format('%a');
            return $dias_diferencia;
        }

        public function VH_estadoSolicitud($estado){
            switch($estado){
                case 0 :    $estado_nom = "Cancelada"; break;
                case 1 :    $estado_nom = "Borrador (sin enviar)"; break;
                case 2 :    $estado_nom = "Esperando autorización del líder"; break;
                case 3 :    $estado_nom = "Devuelta al colaborador, líder solicita cambios"; break;
                case 4 :    $estado_nom = "Cotizando (aprobada por el lider)"; break;
                case 5 :    $estado_nom = "Aprobada por el lider, esperando aprobación de contraloría"; break;
                case 6 :    $estado_nom = "Cotizando (aprobada por contraloría)"; break;
                case 7 :    $estado_nom = "Devuelta al colaborador, contraloría solicita cambios"; break;
                case 8 :    $estado_nom = "Cotizaciones enviadas al líder para su selección"; break;
                case 9 :    $estado_nom = "Cotizaciones seleccionadas (en proceso de compra)"; break; //donde lo uso??
                case 10 :   $estado_nom = "Cotizaciones seleccionadas para su compra"; break;
                case 11 :   $estado_nom = "Comprado"; break;
                case 15 :   $estado_nom = "Cotizando"; break; //urgente maura
                case 16 :   $estado_nom = "Cotizando"; break;
                case 18 :   $estado_nom = "Aprobada por el lider, esperando aprobación de Arturo Freydig"; break;
                case 19 :   $estado_nom = "Cotizando (aprobada por Arturo Freydig)"; break;
                case 20 :   $estado_nom = "Devuelta al colaborador, Arturo Freydig solicita cambios"; break;
                default :   $estado_nom = "No especificado"; break;
            }
            return $estado_nom;
        }

        public function VH_aerolinea($aerolinea){
            switch($aerolinea){
                case 1 :    return "VIVA AEROBUS";
                case 2 :    return "VOLARIS";
                case 3 :    return "AEROMÉXICO";
                case 4 :    return "INTERJET";
                case 5 :    return "AEROMAR";
                case 6 :    return "AIRFRANCE";
                case 7 :    return "AMERICAN AIRLINES";
                case 8 :    return "ASSIST CARD / TRAVELO";
                case 9 :    return "AVIANCA";
                case 10 :   return "BRITISH AIRWAYS";
                case 11 :   return "CALAFIA AIRLINES";
                case 12 :   return "JETSMART";
                case 13 :   return "LATAM AIRLINES";
                case 14 :   return "PERUVIAN AIRLINES";
                case 15 :   return "PRIORITY PASS";
                case 16 :   return "SKY AIRLINES";
                case 17 :   return "SKY AIRLINES/JETSMART";
                case 18 :   return "SOUTHWEST";
                case 19 :   return "TAR";
                case 20 :   return "UNITED AIRLINES";
                case 21 :   return "VIVA AIR";
                default :   return "No especificado";
            }
        }


    // SUBMODULO: SERVICIOS - RESERVACIONES

    /*
    Status 
        1 activa
        2 "borrada"

    Estados de la solicitud
        0 Cancelada
        1 Borrador (sin enviar)
        2 Esperando autorización del líder 
        3 Devuelta al colaborador, líder solicita cambios
        4 Aprobada por el lider, enviada a asesor (cotizando)
        5 Aprobada por el lider, esperando aprobación de contraloría
        6 Aprobada por contraloría, enviada a asesor (cotizando)
        7 Devuelta al colaborador, contraloría solicita cambios
        8 Cotizaciones enviadas al líder para su selección
        9 Cotizaciones seleccionadas (en proceso de compra)
        10 Cotizaciones seleccionadas para su compra
        11 Comprado
        13 solicitud completada
        15 Cotizando
        16 Cotizando
        17 Recotizando
        18Aprobada por el lider, esperando aprobación de Arturo Freydig
        19Cotizando (aprobada por Arturo Freydig)
        20Devuelta al colaborador, Arturo Freydig solicita cambios 

        12 solicitud reemplazada
        13 solicitud completada
        14 ya no qiso el servicio  //servicio reagendado, ya cuando maura lo habia recibido

    tipos de modificacion del log-comentarios //no actualizado, no confiar
        0 Solicitud cancelada por 
        1 Solicitud completada // Solicitud enviada al líder //comentario de plan de trabajo //
        2 comentario de solicitud tardía
        3 Aprobado por el líder
        4 El líder solicitó modificaciones
        5 colaborador edita los servicios de la solicitud con un nuevo id de solicitud por ser una nueva version, 
            se guarda id del nuevo aqi y se cambia estado del anterior
        6 comentario al reagendar version
        7 devuelta por contralor
        8 aceptada por contralor
        9 cotizaciones enviadas al líder
        10 lider solicita mas cotizaciones de vuelos
        11 cotización guardada
        12 Cotizaciones enviadas al asesor para su compra
        13 recotizando
        14 ya no quiso el servicio
        15 cambio de nombre
        16 cambio de nombre solo notificación
        18Aprobada por el lider, esperando aprobación de Arturo Freydig
        19Cotizando (aprobada por Arturo Freydig)
        20Devuelta al colaborador, Arturo Freydig solicita cambios

    Estados de cotizaciones
        0 rechazada
        1 activa
        2 aceptada
    */

        //*****
        public function SR_DATA_contraloria($folio){
            $sql="  SELECT 
                        a.id_colaborador, 
                        s.id_region, 
                        d.codigo_region 
                    FROM 
                        serviciosyreservaciones_cabeceras a LEFT JOIN 
                        Colaboradores s ON a.id_colaborador = s.id_colaborador LEFT JOIN 
                        Regiones d ON s.id_region=d.id_region 
                    WHERE a.id = $folio";
            $qry=$this->db->query($sql)->getResult();
            if($qry){
                $row = $qry[0];
                switch($row->codigo_region){
                    case 'MTY' : 
                        $e_contra = "alopez@ecn.com.mx"; 
                        $n_contra = "Alberto López"; 
                        $id_contra = 1194; 
                        break;
                    case 'BAJ' : 
                        $e_contra = "kneri@ecn.com.mx"; 
                        $n_contra = "Kaori Neri"; 
                        $id_contra = 1199; 
                        break;
                    case 'DCU' : 
                        $e_contra = "nvega@ecn.com.mx"; 
                        $n_contra = "Nataliah Vega"; 
                        $id_contra = 1153; 
                        break;
                    case 'LAG' : 
                        $e_contra = "esantibanez@ecn.com.mx"; 
                        $n_contra = "Elías Santibañez"; 
                        $id_contra = 271; 
                        break;
                    case 'OCC' : 
                        $e_contra = "amacias@ecn.com.mx"; 
                        $n_contra = "Ana Macías"; 
                        $id_contra = 199; 
                        break;
                    case 'PE' : 
                        $e_contra = "clopez@pe.ecnautomation.com"; 
                        $n_contra = "Connie López"; 
                        $id_contra = 1025; 
                        break;
                    case 'AUT' : 
                        $e_contra = "kelvir@ecnautomation.com"; 
                        $n_contra = "Karly Elvir"; 
                        $id_contra = 399; 
                        break;
                    default : 
                        $e_contra = "aperez@ecn.com.mx"; 
                        $n_contra = "Adriana Pérez"; 
                        $id_contra = 48; 
                        break;
                }
            }else{
                $e_contra = "aperez@ecn.com.mx"; 
                $n_contra="Adriana Pérez"; 
                $id_contra=48;
            }
            return array($e_contra, $id_contra, $n_contra);
        }

        // Relleno por modal como: selects, etc.
        public function SR_modalsEstructura(){
            $json = $this->request->getJSON();

            $sol_id = $json->sol_id;
            $tipo = $json->tipo;
            $data = $json->data;

            $list = [];

            switch($tipo){
                case 1: // Select para lugares: Ciudades, estados
                    $query = "  SELECT * 
                                FROM serviciosyreservaciones_info 
                                WHERE 
                                    status = 1 AND 
                                    tipo = 1 
                                ORDER BY nombre";
                    $result = $this->db->query($query);
                    if($result){
                        $res = $result->getResult();
                        $list = $res;
                    }
                    break;
                case 2: // Select para aerolineas/cotizaciones
                    $tipo_sol = $data->tipo_sol;
                    $query = "  SELECT 
                                    id, 
                                    aerolinea, 
                                    (SELECT t.nombre FROM serviciosyreservaciones_info t WHERE t.id = origen ) origen, 
                                    (SELECT t.nombre FROM serviciosyreservaciones_info t WHERE t.id = destino ) destino, 
                                    precio, 
                                    FORMAT(precio, 'C') precio_n,
                                    moneda 
                                FROM serviciosyreservaciones_cotizaciones 
                                WHERE 
                                    id_cabecera = $sol_id AND 
                                    tipo_solicitud = ".($tipo_sol ? $tipo_sol : 'null')." AND 
                                    estado = 2 AND
                                    status = 1;";
                    $result = $this->db->query($query);
                    if($result){
                        $res = $result->getResult();
                        foreach($res as $row){
                            $aerolinea = $row->aerolinea;
                            $row->aerolinea = $this->VH_aerolinea($aerolinea);
                            $list[] = $row;
                        }
                    }
                    break;
                case 3: // Select colaboradores
                    $query = "  SELECT 
                                    id_colaborador AS id,
                                    n_colaborador,
                                    nombres, 
                                    apellido_p, 
                                    apellido_m,
                                    CONCAT(apellido_p, ' ', apellido_m, ' ', nombres) AS nomCompleto
                                FROM Colaboradores 
                                WHERE estado = 1 
                                ORDER BY apellido_p";
                    $result = $this->db->query($query);
                    if($result){
                        $res = $result->getResult();
                        $list = $res;
                    }
                    break;
            }
            
            return $this->response->setStatusCode(200)->setJSON($list);
        }

        // Encuesta: Obtiene la calificación sobre el vuelo
        public function SR_getEncuesta($sol_id){

            $sql_encserv = "SELECT COUNT(*) AS cuenta 
                            FROM serviciosyreservaciones_encuesta 
                            WHERE 
                                id_cabecera = $sol_id AND 
                                status = 1;";
            $return = $this->db->query($sql_encserv)->getResult();
            if(sizeof($return) > 0)
                return ($return[0]->cuenta);
            else
                return (0);
        }

        public function SR_getConcepto($no_concepto){
            switch($no_concepto){
                case 1 :       return "Cargo extra por cambio de horario";
                case 2 :       return "Cargo extra por cambio de fecha";
                case 3 :       return "Cargo extra por cambio de nombre";
                case 4 :       return "Cargo extra por equipaje";
                case 5 :       return "Cargo extra por cambio de vuelo";
                case 6 :       return "Cargo extra por ascenso de clase";
                case 7 :       return "Cargo extra por asiento";
                case 8 :       return "Cargo extra por cambio de asiento";
                case 9 :       return "Cargo extra por cancelación";
                case 10 :      return "Cargo extra por no show";
                default :      return "";
            }
        }

        public function SR_getMotivo($no_motivo){
            switch($no_motivo){
                case 1:            return "Motivos de salud";
                case 2:            return "Cambios en la agenda del cliente";
                case 3:            return "Petición del colaborador";
                case 4:            return "Cambios en la agenda del proyecto";
                default:           return "";
            }
        }


    // ACCIONES // Acciones ubicadas en el encabezado de los segmentos: Vuelos | Hospedajes | Auto
        // Revisa errores, guardar detalle de servicio si hay al menos un error se insertara contralor
        public function SR_ACCIONES_editarServicio(){
            $json = $this->request->getJSON();
            
            $hoy = date('Y-m-d H:i:s');
            $tipo_solicitud = $json->tipo_sol;
            $folio_cabecera = $json->folio_cabecera;
            $filio_detalle = $json->filio_detalle;

            switch($tipo_solicitud){
                case 1: // Vuelo

                    $v_tipo = $json->tipo;
                    $v_origen = $json->origen;
                    $v_destino = $json->destino;
                    $inicio_entrada = $json->f_salida;
                    $fin_salida = $json->f_regreso;
                    $v_inicio = $json->v_salida;
                    $v_fin = $json->v_regreso;
                    $v_internacional = $json->internacional;
                    
                    $sql = "UPDATE serviciosyreservaciones_detalle 
                            SET 
                                fecha = '$hoy', 
                                v_tipo = $v_tipo, 
                                origen = $v_origen, 
                                destino = $v_destino, 
                                v_inicio = '$v_inicio', 
                                v_fin = '$v_fin', 
                                inicio_entrada = '$inicio_entrada', 
                                fin_salida = '$fin_salida', 
                                internacional = $v_internacional 
                            where 
                                id_cabecera = $folio_cabecera and 
                                id = $filio_detalle and 
                                tipo_solicitud = 1";
                    
                    if($this->db->query($sql))
                        return $this->response->setStatusCode(200)->setJSON(1);
                    else
                        return $this->response->setStatusCode(200)->setJSON(0);

                case 2: // Hospedaje

                    $h_tipo = $json->tipo;
                    $h_ciudad = $json->ciudad;
                    $inicio_entrada = $json->f_entrada;
                    $fin_salida = $json->f_salida;
                    $h_internacional = $json->internacional;

                    $sql = "UPDATE serviciosyreservaciones_detalle 
                            SET 
                                fecha = '$hoy', 
                                h_tipo = $h_tipo, 
                                h_ciudad = '$h_ciudad', 
                                inicio_entrada = '$inicio_entrada', 
                                fin_salida = '$fin_salida', 
                                internacional = $h_internacional 
                            WHERE 
                                id_cabecera = $folio_cabecera AND 
                                id = $filio_detalle AND 
                                tipo_solicitud = 2";

                    if($this->db->query($sql))
                        return $this->response->setStatusCode(200)->setJSON(1);
                    else
                        return $this->response->setStatusCode(200)->setJSON(0);

                    break;
                case 3: // Auto
                    
                    $a_tipo = $json->tipo;
                    $a_lugar_entrega = $json->lugar;
                    $inicio_entrada = $json->entrada;
                    $fin_salida = $json->salida;
                    
                    $sql = "UPDATE serviciosyreservaciones_detalle 
                            SET 
                                fecha = '$hoy', 
                                a_tipo = $a_tipo, 
                                a_lugar_entrega = $a_lugar_entrega, 
                                inicio_entrada = '$inicio_entrada', 
                                fin_salida = '$fin_salida' 
                            WHERE 
                                id_cabecera = $folio_cabecera AND 
                                id = $filio_detalle AND 
                                tipo_solicitud = 3";
                    
                    if($this->db->query($sql))
                        return $this->response->setStatusCode(200)->setJSON(1);
                    else
                        return $this->response->setStatusCode(200)->setJSON(0);

                    break;
                default: // Si no es ninguno de esos, regresa error
                    return $this->response->setStatusCode(200)->setJSON(0);
            }
        }
        // Guarda nueva versión de servicio
        public function SR_ACCIONES_versionServicio(){
            $json = $this->request->getJSON();
            
            $hoy = date('Y-m-d H:i:s');
            $tipo_solicitud = $json->tipo_sol;
            $folio_cabecera = $json->folio_cabecera;
            $filio_detalle = $json->filio_detalle;

            $qry_res = 0;

            switch($tipo_solicitud){
                case 1: // Vuelo
                    $v_tipo = $json->tipo;
                    $v_origen = $json->origen;
                    $v_destino = $json->destino;
                    $inicio_entrada = $json->f_salida;
                    $fin_salida = $json->f_regreso;
                    $v_inicio = $json->v_salida;
                    $v_fin = $json->v_regreso;
                    $v_internacional = $json->v_internacional;
                    
                    $sql = "UPDATE serviciosyreservaciones_detalle 
                            SET estado = 12 
                            WHERE 
                                id_cabecera = $folio_cabecera AND 
                                id = $filio_detalle AND 
                                tipo_solicitud = $tipo_solicitud;";
                    if($this->db->query($sql)){
                        $sql1 = "INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, v_tipo, origen, destino, v_inicio, v_fin, inicio_entrada, fin_salida, estado, internacional) 
                                    VALUES ($folio_cabecera, $tipo_solicitud, '$hoy', $v_tipo, $v_origen, $v_destino, '$v_inicio', '$v_fin', '$inicio_entrada', '$fin_salida', 1, $v_internacional)";
                        if($this->db->query($sql1)) $qry_res = 1;
                    }
                    break;
                case 2: // Hospedaje
                    $tipo = $json->tipo;
                    $ciudad = $json->ciudad;
                    $entrada = $json->f_entrada;
                    $salida = $json->f_salida;
                    $internacional = $json->internacional;
                    
                    $sql = "UPDATE serviciosyreservaciones_detalle 
                            SET estado = 12 
                            WHERE 
                                id_cabecera = $folio_cabecera AND 
                                id = $filio_detalle AND 
                                tipo_solicitud = $tipo_solicitud;";
                    if($this->db->query($sql)){
                        $sql1 = "INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, h_tipo, h_ciudad, inicio_entrada, fin_salida, estado, internacional) 
                                    VALUES ($folio_cabecera, $tipo_solicitud, '$hoy', $tipo, '$ciudad', '$entrada', '$salida', 1, $internacional)";
                        if($this->db->query($sql1)) $qry_res = 1;
                    }
                    break;
                case 3: // Auto
                    
                    $a_tipo = $json->tipo;
                    $a_lugar_entrega = $json->lugar;
                    $inicio_entrada = $json->entrada;
                    $fin_salida = $json->salida;
                    
                    $sql = "UPDATE serviciosyreservaciones_detalle 
                            SET estado = 12 
                            WHERE
                                id_cabecera = $folio_cabecera AND 
                                id = $filio_detalle AND 
                                tipo_solicitud = 3;";
                    if($this->db->query($sql)){
                        $sql1 = "INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, a_tipo, a_lugar_entrega, inicio_entrada, fin_salida, estado) 
                                    VALUES ($folio_cabecera, 3, '$hoy', $a_tipo, $a_lugar_entrega, '$inicio_entrada', '$fin_salida', 1)";
                        if($this->db->query($sql1))
                            $qry_res = 1;
                    }

                    break;
            }

            return $this->response->setStatusCode(200)->setJSON($qry_res);

        }
        // Sirve para: Vuelos | Hospedajes | Auto
        public function SR_ACCIONES_eliminarDetalle(){
            $json = $this->request->getJSON();

            $folio_cabecera = $json->folio_cabecera;
            $tipo_solicitud = $json->tipo_solicitud;
            $folio_detalleservicio = $json->id_detalle;
            
            $sql = "UPDATE serviciosyreservaciones_detalle 
                    SET 
                        estado = 0, 
                        status = 0 
                    WHERE 
                        tipo_solicitud = $tipo_solicitud AND 
                        id_cabecera = $folio_cabecera AND 
                        id = $folio_detalleservicio;";

            if($this->db->query($sql)) return $this->response->setStatusCode(200)->setJSON(1);
            else return $this->response->setStatusCode(200)->setJSON(0);
        }
        public function SR_ACCIONES_masCotizVuelos(){
            $json = $this->request->getJSON();

            $hoy = date('Y-m-d H:i:s');

            $id_cabecera = $json->id_cabecera;
            //$id_detalle = $json->id_detalle;
            //$edo = $json->edo;
            $colaborador_id = $json->colaborador_id;
            
            $to = "mtrejo@ecn.com.mx";
            
            $sql = "UPDATE serviciosyreservaciones_cabeceras 
                    SET estado = 16 
                    WHERE id = $id_cabecera;";
            if($this->db->query($sql)){
                $sql1 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                            VALUES ($id_cabecera, $colaborador_id, '$hoy', 10, 16, 'El líder solicitó más cotizaciones de vuelos')";
                $this->db->query($sql1);
                
                $subject = "El líder solicitó más cotizaciones de vuelos";
                $mensaje = "<html lang='es'>
                                        <head>
                                            <meta charset='UTF-8'>
                                            <title>Titutlo</title>
                                        </head>
                                        <body>
                                            <table>
                                                <tr>
                                                    <td style='width:50px;'></td>
                                                    <td style='width:600px;'>
                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                        <hr>
                                                        <br>
                                                        <p style='font-family:Helvetica;'><br>
                                                            Buen día, el líder solicitó más cotizaciones de vuelos para la <b>solicitud #".$id_cabecera."</b><br>
                                                            Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones' de Intranet.<br>
                                                        </p>
                                                        <p style=' font-family:Helvetica;'>
                                                            <br><br>
                                                            Saludos.
                                                        </p>
                                                        <br><br><br>
                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                    </td>
                                                    <td style='width:50px;'></td>
                                                </tr>
                                            </table>
                                        </body>
                                        </html>";

                $message = $mensaje;
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                //$headers .= "CC:" . $concopia;
                //echo $to.$subject.$message.$headers;

                try{
                    mail($to,$subject,$message,$headers);
                    return $this->response->setStatusCode(200)->setJSON(1);
                }catch(\Exception $e){
                    return $this->response->setStatusCode(200)->setJSON(3);
                }
            }else
                return $this->response->setStatusCode(200)->setJSON(2);
        }
        // Sirve para: Vuelos | Hospedajes | Auto
        public function SR_ACCIONES_recotizar(){
            $json = $this->request->getJSON();
            
            $hoy = date('Y-m-d H:i:s');
            $tipo_sol = $json->tipo_sol;
            $comentario = $json->comentario;
            $id_cabecera = $json->id_cabecera;
            //$id_detalle = $json->id_detalle;
            $sql = "UPDATE serviciosyreservaciones_cabeceras 
                    SET estado = 16 
                    WHERE id = $id_cabecera";
            if($this->db->query($sql)){
                $sql2 = "   UPDATE serviciosyreservaciones_cotizaciones 
                            SET estado = 1 
                            WHERE 
                                id_cabecera = $id_cabecera AND 
                                tipo_solicitud = $tipo_sol AND 
                                estado IN (1,2)";
                if($this->db->query($sql2)){
                    $sql1 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_solicitud, tipo_modificacion, nuevo_estado, comentario) 
                                VALUES ($id_cabecera, 1050, '$hoy', $tipo_sol, 13, 16, '$comentario')";
                    $this->db->query($sql1);
                    return $this->response->setStatusCode(200)->setJSON(1);
                }else
                    return $this->response->setStatusCode(200)->setJSON(2);    
                
            }else
                return $this->response->setStatusCode(200)->setJSON(0);
            
        }
        // Sirve para: Vuelos | Hospedajes | Auto
        public function SR_ACCIONES_cancelarServicio(){
            $json = $this->request->getJSON();

            $hoy = date('Y-m-d H:i:s');

            $colaborador_id = $json->colaborador_id;
            $tipo_sol = $json->tipo_sol;
            $comentario = $json->comentario;
            $id_cabecera = $json->id_cabecera;
            $id_detalle = $json->id_detalle;

            $sql = "UPDATE serviciosyreservaciones_detalle 
                    SET estado = 14 
                    WHERE 
                        id_cabecera = $id_cabecera AND 
                        tipo_solicitud = $tipo_sol AND 
                        id = $id_detalle";
            if($this->db->query($sql)){
                $sql2 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_solicitud, tipo_modificacion, nuevo_estado, comentario) 
                            VALUES ($id_cabecera, $colaborador_id, '$hoy', $tipo_sol, 14, 14, '$comentario')";
                if($this->db->query($sql2))
                    return $this->response->setStatusCode(200)->setJSON(1);
                else
                    return $this->response->setStatusCode(200)->setJSON(2);
            }else
                return $this->response->setStatusCode(200)->setJSON(0);
        }
        // Sirve para: Vuelos | Hospedajes
        public function SR_ACCIONES_reagendar(){

            //colaborador lo solicita
            //se inserta nuevo detalle con estado 17
            //se actualiza cabecera con etapa 1 en campo reagenda_vuelo reagenda_hosp
            //se inserta comentario
            //se avisa aprobacion lider

            $json = $this->request->getJSON();

            $hoy = date('Y-m-d H:i:s');
            $tipo_solicitud = $json->tipo_solicitud;

            $folio_cabecera = $json->folio_cabecera;
            $filio_detalle = $json->filio_detalle;
            $lider_id = $json->lider_id;
            $colaborador_id = $json->colaborador_id;

            switch($tipo_solicitud){
                case 1:

                    $v_tipo = $json->v_tipo;
                    $v_origen = $json->v_origen;
                    $v_destino = $json->v_destino;
                    $inicio_entrada = $json->inicio_entrada;
                    $fin_salida = $json->fin_salida;
                    $v_inicio = $json->v_inicio;
                    $v_internacional = $json->v_internacional;
                    $v_fin = $json->v_fin;
                    $justif_v = $json->justif_v;
    
                    $mensaje = "";
    
                    if($lider_id == $colaborador_id){
                        $sql = "INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, v_tipo, origen, destino, v_inicio, v_fin, inicio_entrada, fin_salida, estado, internacional, justificacion) 
                                VALUES ($folio_cabecera, $tipo_solicitud, '$hoy', $v_tipo, $v_origen, $v_destino, '$v_inicio', '$v_fin', '$inicio_entrada', '$fin_salida', 1, $v_internacional,'$justif_v')";
                        $sql2 = "UPDATE serviciosyreservaciones_cabeceras 
                                    SET 
                                        reagenda_vuelo = 0, 
                                        estado = 16 
                                    WHERE id = $folio_cabecera";
                        $sql3 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                    VALUES ($folio_cabecera, 1, $colaborador_id, '$hoy', 23, '$justif_v')";
                        $sql4 = "UPDATE serviciosyreservaciones_detalle SET estado = 12 WHERE id = $filio_detalle";
                        $to_sql = "SELECT c.email 
                                    FROM 
                                        Colaboradores c LEFT JOIN 
                                        serviciosyreservaciones_cabeceras t ON c.id_colaborador = t.id_colaborador 
                                    WHERE t.id = $folio_cabecera";
                        $to_qr = $this->db->query($to_sql)->getResult();
                        if(sizeof($to_qr)){
                            $to_row = $to_qr[0];
                            $to = $to_row->email;
                            $subject = "Tu líder reagendó tu solicitud de servicios y reservaciones";
                            $mensaje = "<html lang='es'>
                                        <head>
                                            <meta charset='UTF-8'>
                                            <title>Titutlo</title>
                                        </head>
                                        <body>
                                            <table>
                                                <tr>
                                                    <td style='width:50px;'></td>
                                                    <td style='width:600px;'>
                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                        <hr>
                                                        <br>
                                                        <p style='font-family:Helvetica;'><br>
                                                            Buen día, tu líder reagendó el vuelo de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                        </p>
                                                        <p style=' font-family:Helvetica;'>
                                                            <br><br>
                                                            Saludos.
                                                        </p>
                                                        <br><br><br>
                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                    </td>
                                                    <td style='width:50px;'></td>
                                                </tr>
                                            </table>
                                        </body>
                                    </html>";
                            }
                        
                    }else{
                        $sql = "INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, v_tipo, origen, destino, v_inicio, v_fin, inicio_entrada, fin_salida, estado, internacional, justificacion) 
                                VALUES ($folio_cabecera, $tipo_solicitud, '$hoy', $v_tipo, $v_origen, $v_destino, '$v_inicio', '$v_fin', '$inicio_entrada', '$fin_salida', 17, $v_internacional,'$justif_v')";
                        $sql2 = "UPDATE serviciosyreservaciones_cabeceras SET reagenda_vuelo = 1 WHERE id = $folio_cabecera";
                        $sql3 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                    VALUES ($folio_cabecera, 1, $colaborador_id, '$hoy', 17, '$justif_v')";
                        $to_sql = "SELECT email FROM Colaboradores WHERE id_colaborador = $lider_id";
                        $to_qr = $this->db->query($to_sql)->getResult();
                        if(sizeof($to_qr)){
                            $to_row = $to_qr[0];
                            $to = $to_row->email;//lider 
                            $subject = "Se requiere su autorización para reagendar solicitud de servicios y reservaciones";
                            $mensaje = "<html lang='es'>
                                        <head>
                                            <meta charset='UTF-8'>
                                            <title>Titutlo</title>
                                        </head>
                                        <body>
                                            <table>
                                                <tr>
                                                    <td style='width:50px;'></td>
                                                    <td style='width:600px;'>
                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                        <hr>
                                                        <br>
                                                        <p style='font-family:Helvetica;'><br>
                                                            Buen día, se pide su autorización para reagendar el vuelo de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                            Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones' de Intranet.<br>
                                                        </p>
                                                        <p style=' font-family:Helvetica;'>
                                                            <br><br>
                                                            Saludos.
                                                        </p>
                                                        <br><br><br>
                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                    </td>
                                                    <td style='width:50px;'></td>
                                                </tr>
                                            </table>
                                        </body>
                                    </html>";
                        
                        }
                    }
                    
                    if(!empty($mensaje)){
                        if($this->db->query($sql)){
                            if($this->db->query($sql2)){
                                if($this->db->query($sql3)){
                                    $message = $mensaje;
                                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                    $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                    //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                    //$headers .= "CC:" . $concopia;
                                    //echo $to.$subject.$message.$headers;
    
                                    if($lider_id == $colaborador_id){
                                        $this->db->query($sql4);
                                        $sql_0 = "UPDATE serviciosyreservaciones_cotizaciones 
                                                    SET estado = 1 
                                                    WHERE 
                                                        id_cabecera = $folio_cabecera AND 
                                                        tipo_solicitud = 2 AND 
                                                        estado IN (1,2)";
                                        $this->db->query($sql_0);
                                    }
                                    
                                    try{
                                        mail($to,$subject,$message,$headers);
                                        return $this->response->setStatusCode(200)->setJSON(1);
                                    }catch(\Exception $e){
                                        return $this->response->setStatusCode(200)->setJSON(4);
                                    }
                                }else
                                    return $this->response->setStatusCode(200)->setJSON(3);
                            }else
                                return $this->response->setStatusCode(200)->setJSON(2);
                        }else
                            return $this->response->setStatusCode(200)->setJSON(0);
                    }else
                        return $this->response->setStatusCode(200)->setJSON(5);

                    break;
                case 2:

                    //coaborador lo solicita
                    //se inserta nuevo detalle con estado 17
                    //se actualiza cabecera con etapa 1 en campo reagenda_vuelo reagenda_hosp
                    //se inserta comentario
                    //se avisa aprobacion lider

                    $h_tipo = $json->h_tipo;
                    $h_ciudad = $json->h_ciudad;
                    $inicio_entrada = $json->inicio_entrada;
                    $fin_salida = $json->fin_salida;
                    $h_internacional = $json->h_internacional;
                    $justif_h = $json->justif_h;
                                    
                    $mensaje = "";

                    if($lider_id == $colaborador_id){
                        $sql = "INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, h_tipo, h_ciudad, inicio_entrada, fin_salida, estado, internacional, justificacion) 
                                VALUES ($folio_cabecera, 2, '$hoy', $h_tipo, '$h_ciudad', '$inicio_entrada', '$fin_salida', 1, $h_internacional, '$justif_h')";
                        $sql2 = "UPDATE serviciosyreservaciones_cabeceras 
                                    SET 
                                        reagenda_hosp = 0, 
                                        estado = 16 
                                    WHERE id = $folio_cabecera";
                        $sql3 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                    VALUES ($folio_cabecera, 2, $colaborador_id, '$hoy', 18, '$justif_h')";
                        $sql4 = "UPDATE serviciosyreservaciones_detalle SET estado = 12 WHERE id = $filio_detalle";
                        $to_sql = "SELECT c.email 
                                    FROM 
                                        Colaboradores c LEFT JOIN 
                                        serviciosyreservaciones_cabeceras t ON c.id_colaborador = t.id_colaborador 
                                    WHERE t.id = $folio_cabecera";
                        $to_qr = $this->db->query($to_sql)->getResult();
                        if(sizeof($to_qr)){
                            $to_row = $to_qr[0];
                            $to = $to_row->email;
                            $subject = "Tu líder reagendó tu solicitud de servicios y reservaciones";
                            $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                Buen día, tu líder reagendó el hospedaje de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                        </html>";
                        
                        }
                    }else{
                        $sql = "INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, h_tipo, h_ciudad, inicio_entrada, fin_salida, estado, internacional, justificacion) 
                                VALUES ($folio_cabecera, 2, '$hoy', $h_tipo, '$h_ciudad', '$inicio_entrada', '$fin_salida', 17, $h_internacional, '$justif_h')";
                        $sql2 = "UPDATE serviciosyreservaciones_cabeceras SET reagenda_hosp = 1 WHERE id = $folio_cabecera";
                        $sql3 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                    VALUES ($folio_cabecera, 2, $colaborador_id, '$hoy', 17, '$justif_h')";
                        $to_sql = "SELECT email FROM Colaboradores WHERE id_colaborador = $lider_id";
                        $to_qr = $this->db->query($to_sql)->getResult();
                        if(sizeof($to_qr)){
                            $to_row = $to_qr[0];
                            $to = $to_row->email;//lider 
                            $subject = "Se requiere su autorización para reagendar solicitud de servicios y reservaciones";
                            $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                Buen día, se pide su autorización para reagendar el hospedaje de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                                Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones' de Intranet.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                        </html>";
                        }
                    }
                    if(!empty($mensaje)){
                        if($this->db->query($sql)){
                            if($this->db->query($sql2)){
                                if($this->db->query($sql3)){
                                    $message = $mensaje;
                                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                    $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                    //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                    //$headers .= "CC:" . $concopia;
                                    //echo $to.$subject.$message.$headers;
                                    
                                    if($lider_id == $colaborador_id){
                                        $this->db->query($sql4);
                                        $sql_0 = "UPDATE serviciosyreservaciones_cotizaciones 
                                                    SET estado = 1 
                                                    WHERE 
                                                        id_cabecera = $folio_cabecera AND 
                                                        tipo_solicitud = 2 AND 
                                                        estado IN (1,2)";
                                        $this->db->query($sql_0);
                                    }
                                    
                                    try{
                                        mail($to,$subject,$message,$headers);
                                        return $this->response->setStatusCode(200)->setJSON(1);
                                    }catch(\Exception $e){
                                        return $this->response->setStatusCode(200)->setJSON(4);
                                    }
                                }else
                                return $this->response->setStatusCode(200)->setJSON(3);
                            }else
                            return $this->response->setStatusCode(200)->setJSON(2);
                        }else
                        return $this->response->setStatusCode(200)->setJSON(0);
                    }else
                        return $this->response->setStatusCode(200)->setJSON(5);

                    break;
                case 3:
                    break;
            }
        }
    // END ACCIONES //

    // TODOS: VUELOS | HOSPEDAJES | AUTOS
        // Selección para una cotización
        public function SR_selectCotizacion(){
            //revisara qe  vuelos seleccione 2 opciones o segun lo necesario
            //selecciona qe esten seleccionadas las opciones
            //en cada servici oqe se selecciona se agrega a comentarios
            //cuenta de qe servicios pidio
            //revisa si ya se selecciono una cotizacion para cada servicio
            //cuando ya selecciona cotizacion para todos la solicitud cambia de estado
            //cuando cambia de estado se agrega comentario y se manda mensaje a maura
            //vistas de estados ya seleccionados
            //borrar boton y selects al actualizar tablas de cotizaciones

            $json = $this->request->getJSON();

            
            $hoy = date('Y-m-d H:i:s');

            $id_cabecera = $json->id_cabecera;
            //$id_detalle = $json->id_detalle;
            //$edo = $json->edo;
            $tipo_sol = $json->tipo_sol; 
            $tipo_vuelo = $json->tipo_vuelo; //solo vuelos : redondo o sencillo
            $colaborador_id = $json->colaborador_id;
            $opc1 = $json->opc1;
            $opc2 = $json->opc2;

            $resultado = 0;
            
            switch ($tipo_sol){
                case 1 :
                    
                    if($tipo_vuelo == 1){
                        $resultado = 1;
                        $sql_0 = "  UPDATE serviciosyreservaciones_cotizaciones 
                                    SET estado = 2 
                                    WHERE 
                                        id IN ($opc1) AND 
                                        tipo_solicitud = 1 AND 
                                        id_cabecera = $id_cabecera";
                        $sql_1 = "  INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                    VALUES ($id_cabecera, $colaborador_id, '$hoy', 11, 9, 'Cotización de vuelo seleccionada para su compra')";
                    }else{
                        if($opc2 != 0){
                            $resultado = 1;
                            $sql_0 = "  UPDATE serviciosyreservaciones_cotizaciones 
                                        SET estado = 2 
                                        WHERE 
                                            id IN ($opc1, $opc2) AND 
                                            tipo_solicitud = 1 AND 
                                            id_cabecera = $id_cabecera";
                            $sql_1 = "  INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                        VALUES ($id_cabecera, $colaborador_id, '$hoy', 11, 9, 'Cotizaciones de vuelo seleccionadas para su compra')";
                        }
                    }                
                    break;
                case 2 :
                    
                    $resultado = 1;
                    $sql_0 = "  UPDATE serviciosyreservaciones_cotizaciones 
                                SET estado = 2 
                                WHERE 
                                    id = $opc1 AND 
                                    tipo_solicitud = 2 AND 
                                    id_cabecera = $id_cabecera";
                    $sql_1 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                VALUES ($id_cabecera, $colaborador_id, '$hoy', 11, 9, 'Cotización de hospedaje seleccionada para su reserva')";
                    break;
                case 3 :
                    $resultado = 1;
                    $sql_0 = "  UPDATE serviciosyreservaciones_cotizaciones 
                                SET estado = 2 
                                WHERE 
                                    id = $opc1 AND 
                                    tipo_solicitud = 3 AND 
                                    id_cabecera = $id_cabecera";
                    $sql_1 = "  INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                VALUES ($id_cabecera, $colaborador_id, '$hoy', 11, 9, 'Cotización de auto seleccionada para su arrendamiento')";
                    break;
            }
            
            if($resultado == 1){
                if($this->db->query($sql_0)) //se actualiza el estado de la cotización
                    $resultado = 11;
                else
                    $resultado = 2;
                
                
                if($resultado == 11){
                    $this->db->query($sql_1); //se inserta comentario de notificacion

                    $sql = "SELECT 
                                (SELECT COUNT(*) FROM serviciosyreservaciones_detalle c WHERE c.status = 1 AND c.id_cabecera = $id_cabecera AND c.tipo_solicitud = 1 AND c.estado NOT IN (0,12,14)) AS vuelos
                                , (SELECT COUNT(*) FROM serviciosyreservaciones_detalle c WHERE c.status = 1 AND c.id_cabecera = $id_cabecera AND c.tipo_solicitud = 2 AND c.estado NOT IN (0,12,14)) AS hosp
                                , (SELECT COUNT(*) FROM serviciosyreservaciones_detalle c WHERE c.status = 1 AND c.id_cabecera = $id_cabecera AND c.tipo_solicitud = 3 AND c.estado NOT IN (0,12,14)) AS auto
                                , (SELECT COUNT(*) FROM serviciosyreservaciones_cotizaciones c WHERE c.status = 1 AND c.id_cabecera = $id_cabecera AND c.tipo_solicitud = 1 AND c.estado = 2) AS vuelos_cot
                                , (SELECT COUNT(*) FROM serviciosyreservaciones_cotizaciones c WHERE c.status = 1 AND c.id_cabecera = $id_cabecera AND c.tipo_solicitud = 2 AND c.estado = 2) AS hosp_cot
                                , (SELECT COUNT(*) FROM serviciosyreservaciones_cotizaciones c WHERE c.status = 1 AND c.id_cabecera = $id_cabecera AND c.tipo_solicitud = 3 AND c.estado = 2) AS auto_cot";
                    $qry = $this->db->query($sql)->getResult();

                    if(sizeof($qry) > 0){
                        $row = $qry[0];
                        $cant_v = $row->vuelos;
                        $cant_h = $row->hosp;
                        $cant_a = $row->auto;
                        $cot_v = $row->vuelos_cot;
                        $cot_h = $row->hosp_cot;
                        $cot_a = $row->auto_cot;
                    }else{
                        $cant_v = 0;
                        $cant_h = 0;
                        $cant_a = 0;
                        $cot_v = 0;
                        $cot_h = 0;
                        $cot_a = 0;
                    }
                        
                    if($cant_v >= 1){
                        if($cot_v > 0) $vuelo_sel = 1;
                        else $vuelo_sel = 0;
                    }else $vuelo_sel = 1;
                    
                    if($cant_h >= 1){
                        if($cot_h > 0) $hosp_sel = 1;
                        else $hosp_sel = 0;
                    }else $hosp_sel = 1;
                    
                    if($cant_a >= 1){
                        if($cot_a > 0) $auto_sel = 1;
                        else $auto_sel = 0;
                    }else $auto_sel = 1;
                    
                    
                    if(($vuelo_sel + $hosp_sel + $auto_sel) >= 3){
                        //cuando ya selecciona cotizacion para todos la solicitud cambia de estado
                        $sql_2="UPDATE serviciosyreservaciones_cabeceras 
                                SET estado = 10 
                                WHERE id = $id_cabecera";
                        if($this->db->query($sql_2)){
                            $sql_3="INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                    VALUES ($id_cabecera, $colaborador_id, '$hoy', 12, 10, 'Cotizaciones enviadas al asesor para su compra')";
                            if($this->db->query($sql_3)){
                                //cuando cambia de estado se agrega comentario y se manda mensaje a maura
                                $to = "mtrejo@ecn.com.mx";
                                $subject = "Cotizaciones seleccionadas para su compra";
                                $mensaje = "<html lang='es'>
                                                        <head>
                                                            <meta charset='UTF-8'>
                                                            <title>Titutlo</title>
                                                        </head>
                                                        <body>
                                                            <table>
                                                                <tr>
                                                                    <td style='width:50px;'></td>
                                                                    <td style='width:600px;'>
                                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                        <hr>
                                                                        <br>
                                                                        <p style='font-family:Helvetica;'><br>
                                                                            Buen día, el líder ya seleccionó las cotizaciones más adecuadas a comprar para la <b>solicitud #".$id_cabecera."</b><br>
                                                                            Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones' de Intranet.<br>
                                                                        </p>
                                                                        <p style=' font-family:Helvetica;'>
                                                                            <br><br>
                                                                            Saludos.
                                                                        </p>
                                                                        <br><br><br>
                                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                                    </td>
                                                                    <td style='width:50px;'></td>
                                                                </tr>
                                                            </table>
                                                        </body>
                                                        </html>";

                                $message = $mensaje;
                                $headers  = 'MIME-Version: 1.0' . "\r\n";
                                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                //$headers .= "CC:" . $concopia;
                                //echo $to.$subject.$message.$headers;

                                try{
                                    mail($to,$subject,$message,$headers);
                                    $resultado = 111;
                                }catch(\Exception $e){
                                    $resultado = 5;
                                }
                            }else
                                $resultado = 4;
                        }else
                            $resultado = 3;
                    }
                }
            }
            
            // echo $vuelo_sel + $hosp_sel + $auto_sel;
            // echo $resultado ;
            return $this->response->setStatusCode(200)->setJSON($resultado);

        }
        // Eliminar cotización
        public function SR_eliminarCotizacion(){
            $json = $this->request->getJSON();

            $id_cabecera = isset($json->sol_id) ? $json->sol_id : 'NULL';
            $id_detalle = isset($json->detalle_id) ? $json->detalle_id : 'NULL';
            $id_cotiz = isset($json->cotizacion_id) ? $json->cotizacion_id : 'NULL';
            $tipo_sol = isset($json->tipo_sol) ? $json->tipo_sol : 0;

            $query = "";

            switch($tipo_sol){
                case 1:
                    $query = "  UPDATE serviciosyreservaciones_cotizaciones 
                                SET status = 0 
                                WHERE 
                                    tipo_solicitud = $tipo_sol AND 
                                    id_cabecera = $id_cabecera AND 
                                    id_detalle = $id_detalle AND
                                    id = $id_cotiz;";
                    break;
                case 2:
                case 3:
                    $query = "  UPDATE serviciosyreservaciones_cotizaciones 
                                SET status = 0 
                                WHERE 
                                    tipo_solicitud = $tipo_sol AND 
                                    id_cabecera = $id_cabecera AND 
                                    id = $id_cotiz;";
                    break;
            }

            try{
                if($this->db->query($query))
                    return $this->response->setStatusCode(200)->setJSON(1);
                else
                    return $this->response->setStatusCode(200)->setJSON(0);
            }catch(\Exception $e){
                return $this->response->setStatusCode(200)->setJSON(0);
            }

        }
        // Listado de cotizaciones
        public function SR_verCotizaciones(){
            $json = $this->request->getJSON();

            $sol_id = $json->sol_id;
            $tipo_sol = $json->tipo_sol;

            $query = "";
            switch($tipo_sol){
                case 1:
                    $query = "SELECT 
                                    t.*, 
                                    j.nombre AS origen, 
                                    j.padre AS origen_estado, 
                                    j.padre2 AS origen_pais, 
                                    jj.nombre AS destino, 
                                    jj.padre AS destino_estado, 
                                    jj.padre2 AS destino_pais, 
                                    convert(varchar(10),t.fecha_salida,21) AS fecha_salida, 
                                    FORMAT(t.fecha_salida, 'dd/MM/yyyy') AS fecha_salida_n, 
                                    convert(varchar(10),t.fecha_llegada,21) AS fecha_llegada,
                                    FORMAT(t.fecha_llegada, 'dd/MM/yyyy') AS fecha_llegada_n,
                                    FORMAT(t.precio, 'C') precio_n,
                                    'false' AS 'check' 
                                FROM 
                                    serviciosyreservaciones_cotizaciones t LEFT JOIN 
                                    serviciosyreservaciones_info j ON t.origen = j.id LEFT JOIN 
                                    serviciosyreservaciones_info jj ON t.destino = jj.id 
                                WHERE 
                                    t.status = 1 AND 
                                    t.tipo_solicitud = 1 AND 
                                    t.id_cabecera = $sol_id 
                                ORDER BY t.ida_venida";
                    break;
                case 2:
                    $query = "  SELECT 
                                    t.*, 
                                    t.h_ciudad AS origen, 
                                    CASE t.h_tipo
                                        WHEN 1 THEN 'Hab. sencilla' 
                                        WHEN 2 THEN 'Hab. doble' 
                                        WHEN 3 THEN 'Hab. triple' 
                                        WHEN 4 THEN 'Hab. cuádruple' 
                                        WHEN 5 THEN 'Casa ECN' 
                                        else 'No especificado' 
                                    END AS h_tipo, 
                                    CONVERT(varchar(10),t.fecha_salida,21) AS fecha_salida,
                                    FORMAT(t.fecha_salida, 'dd/MM/yyyy') fecha_salida_n, 
                                    CONVERT(varchar(10),t.fecha_llegada,21) AS fecha_llegada,
                                    FORMAT(t.fecha_llegada, 'dd/MM/yyyy') fecha_llegada_n,
                                    FORMAT(t.precio, 'C') precio_n,
                                    'false' AS 'check' 
                                FROM serviciosyreservaciones_cotizaciones t  
                                WHERE 
                                    t.status = 1 AND 
                                    t.tipo_solicitud = 2 AND 
                                    t.id_cabecera = $sol_id";
                    break;
                case 3:
                    $query = "  SELECT 
                                    *, 
                                    CONVERT(varchar(10), fecha_salida, 21) AS fecha_salida, 
                                    FORMAT(fecha_salida, 'dd/MM/yyyy') fecha_salida_n, 
                                    CONVERT(varchar(10), fecha_llegada, 21) AS fecha_llegada,
                                    FORMAT(fecha_llegada, 'dd/MM/yyyy') fecha_llegada_n,
                                    FORMAT(precio, 'C') precio_n,
                                    'false' AS 'check' 
                                FROM serviciosyreservaciones_cotizaciones 
                                WHERE 
                                    status = 1 AND 
                                    tipo_solicitud = 3 AND 
                                    id_cabecera = $sol_id 
                                ORDER BY ida_venida;";
                    break;
            }

            try{
                $result = $this->db->query($query)->getResult();
                
                return $this->response->setStatusCode(200)->setJSON(array(1, $result));
            }catch(\Exception $e){
                return $this->response->setStatusCode(200)->setJSON(array(0, []));
            }
        }
        // Agregar cotización
        public function SR_agregarCotizacion(){
            $json = $this->request->getJSON();

            $hoy = date('Y-m-d');

            $id_cabecera = isset($json->id_cabecera) ? $json->id_cabecera : 'NULL';
            $id_detalle = isset($json->id_detalle) ? $json->id_detalle : 'NULL';
            $colaborador_id = isset($json->colaborador_id) ? $json->colaborador_id : 'NULL';
            $tipo_sol = isset($json->tipo_sol) ? $json->tipo_sol : 0;

            $sql = "";

            switch($tipo_sol){
                case 1:
                    $origen = isset($json->origen) ? $json->origen : 'NULL';
                    $fecha_salida = isset($json->f_salida) ? $json->f_salida : 'NULL';
                    $hora_salida = isset($json->h_salida) ? $json->h_salida : 'NULL';
        
                    $destino = isset($json->destino) ? $json->destino : 'NULL';
                    $fecha_llegada = isset($json->f_llegada) ? $json->f_llegada : 'NULL';
                    $hora_llegada = isset($json->h_llegada) ? $json->h_llegada : 'NULL';
                    $aerolinea = isset($json->aerolinea) ? $json->aerolinea : 'NULL';
                    $precio = isset($json->precio) ? $json->precio : 'NULL';
                    $escalas = isset($json->escalas) ? $json->escalas : 'NULL';
                    $ida_venida = isset($json->ida_venida) ? $json->ida_venida : 'NULL';
                    $moneda = isset($json->moneda) ? $json->moneda : 'NULL';
        
                    if(!isset($json->comentario) || is_null($json->comentario))
                        $comentario = "NULL";
                    else
                        $comentario = str_replace("'", "''", $json->comentario);

                    $sql = "INSERT INTO serviciosyreservaciones_cotizaciones (id_cabecera, id_detalle, tipo_solicitud, id_creador, fecha, estado, origen, destino, fecha_salida, hora_salida, fecha_llegada, hora_llegada, escalas, aerolinea, ida_venida, precio, comentario, moneda) 
                            VALUES ($id_cabecera, $id_detalle, 1, $colaborador_id, '$hoy', 1, $origen, $destino, '$fecha_salida', '$hora_salida', '$fecha_llegada', '$hora_llegada',$escalas, $aerolinea, $ida_venida, $precio, '$comentario','$moneda')";
                
                    break;
                case 2:
                    $hotel_h = isset($json->h_hotel) ? $json->h_hotel : 'NULL';
                    $tipo_h = isset($json->h_tipo) ? $json->h_tipo : 'NULL';
                    $origen_h = isset($json->h_origen) ? $json->h_origen : 'NULL';
                    $fecha_entrada = isset($json->f_entrada) ? $json->f_entrada : 'NULL';
                    $fecha_salida = isset($json->f_salida) ? $json->f_salida : 'NULL';
                    $precio = isset($json->precio) ? $json->precio : 'NULL';
                    $moneda = isset($json->moneda) ? $json->moneda : 'NULL';
        
                    if(!isset($json->comentario) || is_null($json->comentario))
                        $comentario = "NULL";
                    else
                        $comentario = str_replace("'", "''", $json->comentario);
        
                    $sql = "  INSERT INTO serviciosyreservaciones_cotizaciones 
                                    (id_cabecera, id_detalle, tipo_solicitud, id_creador, fecha, estado, h_ciudad, fecha_salida, fecha_llegada, precio, comentario, moneda, hotel, h_tipo) VALUES 
                                    ($id_cabecera, $id_detalle, 2, $colaborador_id, '$hoy', 1, '$origen_h', '$fecha_entrada', '$fecha_salida', $precio, '$comentario','$moneda', '$hotel_h', $tipo_h)";        
                    break;
                case 3:
                    $arrendadora = isset($json->arrendadora) ? $json->arrendadora : 'NULL';
                    $fecha_recepcion = isset($json->f_recepcion) ? $json->f_recepcion : 'NULL';
                    $fecha_entrega = isset($json->f_entrega) ? $json->f_entrega : 'NULL';
                    $precio = isset($json->precio) ? $json->precio : 'NULL';
                    $moneda = isset($json->moneda) ? $json->moneda : 'NULL';
        
                    if(!isset($json->comentario) || is_null($json->comentario))
                        $comentario = "NULL";
                    else
                        $comentario = str_replace("'", "''", $json->comentario);

                    $sql = "INSERT INTO serviciosyreservaciones_cotizaciones (id_cabecera, id_detalle, tipo_solicitud, id_creador, fecha, estado, arrendadora, fecha_salida, fecha_llegada, precio, comentario, moneda) 
                            VALUES ($id_cabecera, $id_detalle, 3, $colaborador_id, '$hoy', 1, '$arrendadora', '$fecha_recepcion', '$fecha_entrega', $precio, '$comentario','$moneda');";
                    break;
            }

            try{
                if($this->db->query($sql))
                    return $this->response->setStatusCode(200)->setJSON(1);
                else
                    return $this->response->setStatusCode(200)->setJSON(0);
            }catch(\Exception $e){
                return $this->response->setStatusCode(200)->setJSON(0);
            }
        }

        // Acciones de compras/gastos extras
        public function SR_extraCompra(){

            $json = $this->request->getJSON();
            
            $hoy = date('Y-m-d H:i:s');
            $tipo_solicitud = isset($json->tipo_solicitud) ? $json->tipo_solicitud : 'NULL';
            $tipo_info = isset($json->tipo_info) ? $json->tipo_info : 'NULL';

            $id_cabecera = isset($json->id_cabecera) ? $json->id_cabecera : 'NULL';
            $monto =  isset($json->monto) ? $json->monto : 'NULL';
            $moneda_extra =  isset($json->moneda_extra) ? $json->moneda_extra : 'NULL';
            $comentario = isset($json->comentario) ? $json->comentario : 'NULL';
            $motivo =  isset($json->motivo) ? $json->motivo : 'NULL';
            $concepto_extra =  isset($json->concepto_extra) ? $json->concepto_extra : 'NULL';
            $moneda =  isset($json->moneda) ? $json->moneda : 'NULL';
            $vinculo = isset($json->vinculo) ? $json->vinculo : 'NULL';
            $viaticos = isset($json->viaticos) ? $json->viaticos : 'NULL';
            $concepto = isset($json->concepto) ? $json->concepto : 'NULL';
            $titulo_archivo = isset($json->titulo_archivo) ? $json->titulo_archivo : 'NULL';

            $monto_dias = isset($json->monto_dias) ? $json->monto_dias : 'NULL';

            $linea_extra = 0;
            $sql0 = '';
            $sql1 = '';

            
            switch($tipo_info){
                case 2: // Cargos extras

                    $sql0 = "INSERT INTO serviciosyreservaciones_compras (id_cabecera, tipo_solicitud, fecha, tipo_info, monto, moneda, motivo, concepto_extra, comentario, id_cotizacion) 
                            VALUES ($id_cabecera, $tipo_solicitud, '$hoy', $tipo_info, $monto, '$moneda', $motivo, $concepto_extra, '$comentario', $vinculo);";
                    break;
                case 3: // Comentario

                    $sql0 = "INSERT INTO serviciosyreservaciones_compras (id_cabecera, tipo_solicitud, fecha, tipo_info, comentario) 
                            VALUES ($id_cabecera, $tipo_solicitud, '$hoy', $tipo_info, '$comentario')";
                    break;
                case 4: // Precio de compra - precio con dias anticipados

                    $sql0 = "INSERT INTO serviciosyreservaciones_compras (id_cabecera, tipo_solicitud, fecha, tipo_info, concepto, monto, moneda, id_cotizacion) 
                            VALUES ($id_cabecera, $tipo_solicitud, '$hoy', $tipo_info, '$concepto', $monto, '$moneda', $vinculo)";
                    if($monto_dias == 0)
                        $linea_extra = 0;
                    else{
                        $sql1 = "   INSERT INTO serviciosyreservaciones_compras (id_cabecera, tipo_solicitud, fecha, tipo_info, concepto, monto, moneda, id_cotizacion) 
                                    VALUES ($id_cabecera, $tipo_solicitud, '$hoy', 7, '$concepto', $monto_dias, '$moneda_extra', $vinculo)";
                        $linea_extra=1;
                    }

                    break;
                case 5: // Precio convenio

                    $sql0 = "INSERT INTO serviciosyreservaciones_compras (id_cabecera, tipo_solicitud, fecha, tipo_info, concepto, monto, moneda, id_cotizacion) 
                            VALUES ($id_cabecera, $tipo_solicitud, '$hoy', $tipo_info, '$concepto', $monto, '$moneda', $vinculo)";

                    break;
                case 6: // Va cargado a viaticos

                    $sql0 = "INSERT INTO serviciosyreservaciones_compras (id_cabecera, tipo_solicitud, fecha, tipo_info, viaticos) 
                            VALUES ($id_cabecera, $tipo_solicitud, '$hoy', $tipo_info, $viaticos)";

                    break;
            }

            if(!empty($sql0)){
                if( !($this->db->query($sql0)) )
                    return $this->response->setStatusCode(200)->setJSON(0);
                if($linea_extra == 1){
                    if(!empty($sql1)){
                        if($this->db->query($sql1))
                            return $this->response->setStatusCode(200)->setJSON(1);
                        else
                            return $this->response->setStatusCode(200)->setJSON(2);
                    }else
                        return $this->response->setStatusCode(200)->setJSON(2);
                }else
                    return $this->response->setStatusCode(200)->setJSON(1);
            }else
                return $this->response->setStatusCode(200)->setJSON(0);

        }
        // Listado de compras/gastos
        public function SR_verCompras(){

            $json = $this->request->getJSON();

            $id_cabecera = $json->id_cabecera;
            $tipo_solicitud = $json->tipo_solicitud;
            //$colaborador_id = $json->colaborador_id;
            
            //viaticos
            $sql = "SELECT 
                        *,
                        FORMAT(monto, 'C') monto_n
                    FROM serviciosyreservaciones_compras 
                    WHERE 
                        id_cabecera = $id_cabecera AND 
                        tipo_solicitud = $tipo_solicitud AND 
                        status = 1";
            $qry = $this->db->query($sql);
            if($qry){
                $result = $qry->getResult();

                $viaticos_info = 0;//para bloqear el campo para volver a seleccionar esto de viaticos
                $total_mxn = 0;
                $total_usd = 0;

                $list_archivosAdjuntos = array();
                $list_cargosExtras = array();
                $list_comment = array();
                $list_precioCompra = array();
                $cargadoViaticos = '';
                $list_asesor = array();

                $list = array();

                foreach($result as $row){
                    $tipo_info = $row->tipo_info;
                    
                    switch($tipo_info){
                        //archivo adjunto
                        case 1 : 
                            $list_archivosAdjuntos[] = array(
                                'id' => $row->id,
                                'comentario' => $row->comentario,
                                'concepto' => $row->concepto,
                                'ext_archivo' => $row->ext_archivo
                            );
                            //$archivos_adjuntos.='<li><a href="php/servicios_reservaciones/adjuntos/'.$row['comentario'].'" class="text-primary" download>'.$row['concepto'].' '.$row['ext_archivo'].'</a> '.$boton_info.'</li>';
                            break;
                        //cargos extras
                        case 2 : 

                            $list_cargosExtras[] = array(
                                'id' => $row->id,
                                'concepto_extra' => $this->SR_getConcepto($row->concepto_extra),
                                'motivo' => $this->SR_getMotivo($row->motivo),
                                'comentario' => $row->comentario,
                                'monto' => $row->monto_n,
                                'moneda' => $row->moneda
                            );
                            
                            // $cargos_extras.="<tr><td>".concepto($row['concepto_extra'])." <small>(".motivo($row['motivo']).". ".$row['comentario'].")</small></td><td>".number_format($row['monto'],2)." ".$row['moneda']." ".$boton_info."</td></tr>";
                            if($row->moneda == 'MXN') $total_mxn+=$row->monto;
                            else $total_usd+=$row->monto;
                            
                            break;
                        //comentarios
                        case 3 : 

                            $list_comment[] = array(
                                'id' => $row->id,
                                'comentario' => $row->comentario
                            );

                            //$comentarios.=$row['comentario'].' '.$boton_info.'<br>';
                            break;
                        //precio de compra
                        case 4 : 

                            $list_precioCompra[] = array(
                                'id' => $row->id,
                                'concepto' => $row->concepto,
                                'monto' => $row->monto_n,
                                'moneda' => $row->moneda
                            );
                            
                            // $precio_compra.="<tr><td>".$row['concepto']."</td><td>".number_format($row['monto'],2)." ".$row['moneda']." ".$boton_info."</td></tr>";
                            if($row->moneda == 'MXN') $total_mxn+=$row->monto;
                            else $total_usd+=$row->monto;

                            break;
                        //precio regular
                        case 5 :

                            $list_asesor[] = array(
                                'tipo' => 'PR',
                                'id' => $row->id,
                                'concepto' => $row->concepto,
                                'monto' => $row->monto_n,
                                'moneda' => $row->moneda
                            );

                            //$info_asesor.="Pública: ".$row['concepto'].". Precio: ".number_format($row['monto'],2)." ".$row['moneda']." ".$boton_info."<br>";
                            break;
                        //va cargado a viaticos
                        case 6 : 

                            $cargadoViaticos = $row->viaticos == 1 ? true : false;

                            $viaticos_info=1;
                            /*
                                if($row['viaticos']==1){
                                    $viaticos='El colaborador solicitante <span class="text-danger">debe</span><span class="text-danger"> cargarlo a viáticos</span>.';
                                }else{
                                    $viaticos='El colaborador solicitante <span class="text-danger">no debe cargarlo a viáticos</span>.';
                                }
                            */
                            break;
                        //precio de compra con los días de anticipación adecuados
                        case 7 : 

                            $list_asesor[] = array(
                                'tipo' => 'PC',
                                'id' => $row->id,
                                'concepto' => $row->concepto,
                                'monto' => $row->monto_n,
                                'moneda' => $row->moneda
                            );
                            //$info_asesor.="Concepto: ".$row['concepto']." (con días de anticipación de las políticas). Precio: ".number_format($row['monto'],2)." ".$row['moneda']." ".$boton_info."<br>";
                            break;
                    }

                }

                if($total_mxn == 0){
                    if($total_usd == 0)
                        $total = "$0.00 MXN";
                    else{
                        $total = "$".number_format($total_usd,2)." USD";
                    }
                }else{
                    if($total_usd==0){
                        $total = "$".number_format($total_mxn,2)." MXN";
                    }else{
                        $total = "$".number_format($total_mxn,2)." MXN + $".number_format($total_usd,2)." USD";
                    }
                }

                $list = array(
                    'flag' => 1,
                    'total' => $total,
                    'arcAdjuntos' => $list_archivosAdjuntos,
                    'cargosExtras' => $list_cargosExtras,
                    'comentarios' => $list_comment,
                    'precioCompra' => $list_precioCompra,
                    'viaticos' => $cargadoViaticos,
                    'viaticos_ingo' => $viaticos_info,
                    'asesor' => $list_asesor
                ); 

                return $this->response->setStatusCode(200)->setJSON(array('flag' => 1, 'list' => $list));

            }else
                return $this->response->setStatusCode(200)->setJSON(array('flag' => 0));
        }
        // Cambia es status de la compra/gasto 
        public function SR_borrarInfoCompra(){
            $json = $this->request->getJSON();

            $id_cabecera = $json->id_cabecera;
            $id_compra = $json->id_compra;
            $sql = "UPDATE serviciosyreservaciones_compras 
                    SET status = 0 
                    WHERE 
                        id = $id_compra AND 
                        id_cabecera = $id_cabecera;";

            if($this->db->query($sql))
                return $this->response->setStatusCode(200)->setJSON(1);
            else
                return $this->response->setStatusCode(200)->setJSON(0);
        }

    // END TODOS //


    // VUELOS

        public function SR_desgloseEscalas(){
            $json = $this->request->getJSON();

            $id_cot_dtll = $json->id;

            $sql1 = "SELECT  
                        ( SELECT t.nombre FROM serviciosyreservaciones_info t WHERE t.id = origen ) origen, 
                        ( SELECT t.nombre FROM serviciosyreservaciones_info t WHERE t.id = destino ) destino, 
                        CONVERT(varchar(10), fecha_salida, 21) fecha_salida, 
                        FORMAT(fecha_salida, 'dd/MM/yyyy') fecha_salida_n,
                        hora_salida, 
                        CONVERT(varchar(10), fecha_llegada, 21) fecha_llegada, 
                        FORMAT(fecha_llegada, 'dd/MM/yyyy') fecha_llegada_n,
                        hora_llegada, 
                        escalas, 
                        aerolinea 
                    FROM serviciosyreservaciones_cotizaciones 
                    WHERE id = $id_cot_dtll";
            $result1 = $this->db->query($sql1)->getResult();

            if(sizeof($result1) > 0){
                $result1 = $result1[0];
                $f_salida = $result1->fecha_salida;
                $f_salida_n = $result1->fecha_salida_n;
                $h_salida = $result1->hora_salida;
                $origen = $result1->origen;
                $f_llegada = $result1->fecha_llegada;
                $f_llegada_n = $result1->fecha_llegada_n;
                $h_llegada = $result1->hora_llegada;
                $destino = $result1->destino;
            }else{
                $f_salida = '';
                $f_salida_n = '';
                $h_salida = '';
                $origen = '';
                $f_llegada = '';
                $f_llegada_n = '';
                $h_llegada = '';
                $destino = '';
            }

            $sql2 = "SELECT 
                        ( SELECT t.nombre FROM serviciosyreservaciones_info t WHERE t.id = info_ciudad ) info_ciudad, 
                        CONVERT(varchar(10), info_fecha, 21) info_fecha, 
                        CONVERT(varchar(10), info_fecha2, 21) info_fecha2,
                        info_hora,
                        info_hora2
                    FROM serviciosyreservaciones_escalas 
                    WHERE id_ref = $id_cot_dtll";
            $result2 = $this->db->query($sql2)->getResult();

            $detalles = array(
                'f_salida' => $f_salida,
                'f_salida_n' => $f_salida_n,
                'h_salida' => $h_salida,
                'origen' => $origen,
                'f_llegada' => $f_llegada,
                'f_llegada_n' => $f_llegada_n,
                'h_llegada' => $h_llegada,
                'destino' => $destino,
            );
            
            return $this->response->setStatusCode(200)->setJSON(array($detalles, $result2));
        }
        public function SR_addFileVuelos(){

            $post = $this->request->getPost();
            $archivos = $this->request->getFile('archivo');
            return $this->response->setStatusCode(200)->setJSON(array($post, $archivos));

            $json = json_decode($post['datos']);

            $file = $this->request->getFiles();

            
            $hoy = date('Y-m-d H:i:s');
            $id_cabecera = isset($json->id_cabecera) ? $json->id_cabecera : NULL;
            $titulo_archivo = isset($json->titulo_archivo) ? $json->titulo_archivo : NULL;
            $tipo_solicitud = isset($json->tipo_solicitud) ? $json->tipo_solicitud : NULL;
            $tipo_info = isset($json->tipo_info) ? $json->tipo_info : NULL;

            switch($tipo_solicitud){
                case 1:
                    
                    if(count($_FILES['archivo']) > 0){
                        if($_FILES['archivo']){

                            $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
                            
                            $filename = time();
                            $filename.="_";
                            $titulo_archivo = str_replace(" ","_", $filename . $titulo_archivo);
                            
                            $titulo_archivo = str_replace("Á","A", $titulo_archivo);
                            $titulo_archivo = str_replace("É","E", $titulo_archivo);
                            $titulo_archivo = str_replace("Í","I", $titulo_archivo);
                            $titulo_archivo = str_replace("Ó","O", $titulo_archivo);
                            $titulo_archivo = str_replace("Ú","U", $titulo_archivo);
                            $titulo_archivo = str_replace("Ü","U", $titulo_archivo);
                            
                            $titulo_archivo = str_replace("á","a", $titulo_archivo);
                            $titulo_archivo = str_replace("é","e", $titulo_archivo);
                            $titulo_archivo = str_replace("í","i", $titulo_archivo);
                            $titulo_archivo = str_replace("ó","o", $titulo_archivo);
                            $titulo_archivo = str_replace("ú","u", $titulo_archivo);
                            $titulo_archivo = str_replace("ü","u", $titulo_archivo);
                            
                            $titulo_archivo = str_replace("Ñ","N", $titulo_archivo);
                            $titulo_archivo = str_replace("ñ","n", $titulo_archivo);
                            
                            $titulo_archivo = str_replace(",","_", $titulo_archivo);
                            $titulo_archivo = str_replace("'","_", $titulo_archivo);
                            $titulo_archivo = str_replace("$","_", $titulo_archivo);
                            $titulo_archivo = str_replace("#","_", $titulo_archivo);
                            $titulo_archivo = str_replace("%","_", $titulo_archivo);
                            $titulo_archivo = str_replace("&","_", $titulo_archivo);
                            $titulo_archivo = str_replace("/","_", $titulo_archivo);
                            $titulo_archivo = str_replace("*","_", $titulo_archivo);
                            $titulo_archivo = str_replace("-","_", $titulo_archivo);
                            
                            $archivador     = $titulo_archivo;

                            $file_name = $_FILES['archivo']['name'];
                            $_FILES['archivo']->move('path/doc/', $file_name);

                            if (true)
                                return $this->response->setStatusCode(200)->setJSON(2);
                            else{
                                return $this->response->setStatusCode(200)->setJSON(3);
                                $sql = "INSERT INTO serviciosyreservaciones_compras (id_cabecera, tipo_solicitud, fecha, tipo_info, concepto, comentario, ext_archivo) 
                                        VALUES ($id_cabecera, $tipo_solicitud, '$hoy', $tipo_info, '$titulo_archivo', '$filename', '$ext');";
                                            
                                if($this->db->query($sql))
                                    return $this->response->setStatusCode(200)->setJSON(1);
                                else
                                    return $this->response->setStatusCode(200)->setJSON(0);
                            }
                        }else
                            return $this->response->setStatusCode(200)->setJSON(2);
                    }else
                        return $this->response->setStatusCode(200)->setJSON(2);

                    break;
                case 2:
                    break;
                case 3:
                    break;
            }
        }

    // END VUELOS //

    // HOSPEDAJE //

        // Enviar correo  para lider y colaborador confirmando que 
        // se capturó la información de compra
        public function SR_enviarCompra(){
            $json = $this->request->getJSON();

            $resultado = 0;
            
            $id_cabecera = $json->id_cabecera;
            $email_colab = $json->email_colab;
            $email_llider = $json->email_lider;
            $correos = $email_colab.",".$email_llider;

            $sql = "UPDATE serviciosyreservaciones_cabeceras 
                    SET estado = 11 
                    WHERE id = $id_cabecera";
            //$sql="insert into serviciosyreservaciones_comentarios ";
            if($this->db->query($sql)){
                $mensaje = "<html lang='es'>
                    <head>
                        <meta charset='UTF-8'>
                        <title>Titutlo</title>
                    </head>
                    <body>
                        <table>
                            <tr>
                                <td style='width:50px;'></td>
                                <td style='width:600px;'>
                                    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                    <hr>
                                    <br>
                                    <p style='font-family:Helvetica;'><br>
                                        Buen día, ya esta disponible la información de compra en Intranet de los servicios en la solicitud #<b>".$id_cabecera."</b>.<br>
                                        Desde el módulo 'Servicios y reservaciones' conocerás el total, si debes cargarla a viáticos, entre otras cosas.<br>
                                    </p>
                                    <p style=' font-family:Helvetica;'>
                                        <br><br>
                                        Saludos.
                                    </p>
                                    <br><br><br>
                                    <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                        Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                </td>
                                <td style='width:50px;'></td>
                            </tr>
                        </table>
                    </body>
                </html>";

                $to = $correos;          
                $subject = "Información de compra disponible para solicitud de vuelo, auto u hospedaje";
                $message = $mensaje;
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                //$headers .= "CC:" . $concopia;
                //echo $to.$subject.$message.$headers;

                try{
                    mail($to,$subject,$message,$headers);
                    $resultado = 1;
                }catch(\Exception $e){
                    $resultado = 3;
                }

                if($resultado != 1){
                    $mensaje2 = "<html lang='es'>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Titutlo</title>
                        </head>
                        <body>
                            <table>
                                <tr>
                                    <td style='width:50px;'></td>
                                    <td style='width:600px;'>
                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                        <hr>
                                        <br>
                                        <p style='font-family:Helvetica;'><br>
                                            Buen día, Intranet no pudo notificarle al líder y al colaborador, por correo electrónico, que la informaciónde la compra de servicios (<b>#".$id_cabecera."</b>) ya se encuentra disponible.<br>
                                            Por favor contáctalos para hacérselo saber.<br>
                                        </p>
                                        <p style=' font-family:Helvetica;'>
                                            <br><br>
                                            Saludos.
                                        </p>
                                        <br><br><br>
                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                        </td>
                                        <td style='width:50px;'></td>
                                    </tr>
                                </table>
                            </body>
                        </html>";
                    $to2 = $email_colab;          
                    $subject2 = "Falló envío a correo del líder y colaborador en solicitud de vuelo, auto u hospedaje";
                    $message2 = $mensaje2;
                    $headers2  = 'MIME-Version: 1.0' . "\r\n";
                    $headers2 .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers2 .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                    //$headers2 .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";

                    try{
                        mail($to2,$subject2,$message2,$headers2);
                        $resultado = 2;
                    }catch(\Exception $e){
                        $resultado = 4;
                    }
                }
            }else
                $resultado = 0;
            

            return $this->response->setStatusCode(200)->setJSON($resultado);
        }
        // Maura envia cotizaciones al lider
        public function SR_enviarCotizLider(){
            $json = $this->request->getJSON();
            
            $hoy = date('Y-m-d H:i:s');
            $id_cabecera = $json->id_cabecera;
            $lider_id = $json->lider_id;
            
            $sql_0 = "  SELECT 
                            (
                                SELECT email 
                                FROM Colaboradores 
                                WHERE id_colaborador = $lider_id
                            ) email, 
                            (
                                SELECT c.email 
                                FROM 
                                    serviciosyreservaciones_cabeceras t LEFT JOIN 
                                    colaboradores c ON t.id_colaborador = c.id_colaborador 
                                WHERE t.id = $id_cabecera
                            ) emailcol";
            $qry_0 = $this->db->query($sql_0)->getResult();
            if(sizeof($qry_0) > 0)
                $to = $qry_0[0]->email;
            else
                $to = '';
            
            $sql = "UPDATE serviciosyreservaciones_cabeceras 
                    SET estado = 8 
                    WHERE 
                        id = $id_cabecera AND 
                        lider_id = $lider_id";
            if($this->db->query($sql)){
                $sql1 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                            VALUES ($id_cabecera, 1050, '$hoy', 9, 8, 'Cotizaciones enviadas al líder')";
                $this->db->query($sql1);
                
                $subject = "Cotizaciones listas para una solicitud de vuelo, auto u hospedaje";
                $mensaje = "<html lang='es'>
                                        <head>
                                            <meta charset='UTF-8'>
                                            <title>Titutlo</title>
                                        </head>
                                        <body>
                                            <table>
                                                <tr>
                                                    <td style='width:50px;'></td>
                                                    <td style='width:600px;'>
                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                        <hr>
                                                        <br>
                                                        <p style='font-family:Helvetica;'><br>
                                                            Buen día, el asesor de servicios y reservaciones tiene listas las cotizaciones para la <b>solicitud #".$id_cabecera."</b><br>
                                                            Como líder debes escoger la opción que mejor se acomode al propósito de su colaborador.<br>
                                                            Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones' de Intranet.<br>
                                                        </p>
                                                        <p style=' font-family:Helvetica;'>
                                                            <br><br>
                                                            Saludos.
                                                        </p>
                                                        <br><br><br>
                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                    </td>
                                                    <td style='width:50px;'></td>
                                                </tr>
                                            </table>
                                        </body>
                                        </html>";

                $message = $mensaje;
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                //$headers .= "CC:" . $concopia;
                //echo $to.$subject.$message.$headers;

                try{
                    mail($to,$subject,$message,$headers);
                    return $this->response->setStatusCode(200)->setJSON(1);
                }catch(\Exception $e){
                    return $this->response->setStatusCode(200)->setJSON(3);
                }
            }else
                return $this->response->setStatusCode(200)->setJSON(2);
        }
    
    // END HOSPEDAJE //


    // Se encuentran la mayoría de las acciones 
    // de los modales de "Servicios - Reservaciones"
    public function SR_modals_ServReserv(){
        $json = $this->request->getJSON();

        $hoy = date('Y-m-d H:i:s');

        $type = isset($json->type) ? $json->type : 0;
        switch($type){
            // Guarda la encuesta del servicio
            case 10:

                $id_cabecera = isset($json->sol_id) ? $json->sol_id : '';
                $estrellas_serv = isset($json->calificacion) ? $json->calificacion : '';
                $id_colaborador = isset($json->colaborador) ? $json->colaborador : '';
                $es_comentario_opc = isset($json->comentario) ? $json->comentario : '';
        
                $sql = "INSERT INTO serviciosyreservaciones_encuesta (id_cabecera, fecha, status, calificacion, comentario, id_colaborador) 
                        VALUES ($id_cabecera, '$hoy', 1, $estrellas_serv, '$es_comentario_opc', $id_colaborador)";
        
                if($this->db->query($sql))
                    return $this->response->setStatusCode(200)->setJSON(1);
                else
                    return $this->response->setStatusCode(200)->setJSON(0);

            // Agregar escala en vuelos
            case 100:

                $id_cot_dtll = $json->id;
                $esc_i_lugar = $json->lugar;
                $esc_i_hora = $json->inicio;
                $esc_f_hora = $json->fin;

                $sql = "INSERT INTO serviciosyreservaciones_escalas (tipo, id_ref, info_hora, info_ciudad, info_hora2) 
                        VALUES (1, $id_cot_dtll, '$esc_i_hora', $esc_i_lugar, '$esc_f_hora')";
                if($this->db->query($sql))
                    return $this->response->setStatusCode(200)->setJSON(1);
                else
                    return $this->response->setStatusCode(200)->setJSON(0);

            // Borrar escala en vuelos
            case 101:

                $id_cot_dtll = $json->id_dtll;
                $id_esc = $json->id_esc;

                $sql = "UPDATE serviciosyreservaciones_escalas 
                        SET status = 0 
                        WHERE 
                            id = $id_esc AND 
                            id_ref = $id_cot_dtll;";
                if($this->db->query($sql))
                    return $this->response->setStatusCode(200)->setJSON(1);
                else
                    return $this->response->setStatusCode(200)->setJSON(0);

                break;
                
            // terminar de registrar la solicitud y enviar al líder
            case 201:

                $id_cabecera = $json->sol_id;
                $urgencia = $json->urgencia;
                $colaborador_id = $json->colaborador_id;
                $autoriza_contralor = $json->autoriza;
                $solicitud_errordias = $json->sol_errordias;
                $error_prj_presupuesto = $json->error_prj_presupuesto;
                $error_viaticos_pend = $json->error_viaticos_pend;
                $error_v_dias_semanasanta = $json->error_v_dias_semanasanta;
                $error_v_dias_verano = $json->error_v_dias_verano;
                $error_v_dias_navidad = $json->error_v_dias_navidad;
                $error_v_dias_internacional = $json->error_v_dias_internacional;
                $error_v_dias_nacional = $json->error_v_dias_nacional;
                $error_h_dias_semanasanta = $json->error_h_dias_semanasanta;
                $error_h_dias_verano = $json->error_h_dias_verano;
                $error_h_dias_navidad = $json->error_h_dias_navidad;
                $error_h_dias_internacional = $json->error_h_dias_internacional;
                $error_h_dias_nacional = $json->error_h_dias_nacional;
                $error_dias_autos = $json->error_dias_autos;
                $internacional_inge = $json->internacional_inge;

                $plan_trabajo = $json->plan_trabajo;
                $solicitud_tardia = $json->solicitud_tardia;

                $resultado = 0; // RESULTADO FINAL / REGRESA EL VALOR DE ESTA VARIABLE

                // Encontrar cuál AUO autorizara
                $id_contralor = $this->SR_DATA_contraloria($id_cabecera)[1];
                // Definir autorizaciones y comprobar comentarios por solicitud tardía o urgente
                if(($urgencia == 1)&&($colaborador_id == 1050)){
                    $req_contraloria = 0;
                    $req_lider = 0;
                    $tardia = 0;
                }else{
                    $req_lider = 1;
                    if($solicitud_errordias >= 1){
                        $req_contraloria = 0;// $req_contraloria = 1;
                        $tardia = 1;
                    }else{
                        if($autoriza_contralor >= 1){
                            $req_contraloria = 0;// $req_contraloria = 1;
                            $tardia = 0;
                        }else{
                            $req_contraloria = 0;
                            $tardia = 0;
                        }
                    }
                }

                // Encontrar cuál AUO autorizara
                if(($internacional_inge == 1) && (isset($lider_id) != 39))
                    $id_contralor = 39;
                
                // Revisar contenido de comentario por solicitud tardia
                if($tardia == 1){
                    if(is_null($solicitud_tardia))
                        $regresar_solic = 1;
                    else{
                        $cadena = str_replace(' ', '', $solicitud_tardia);
                        if($cadena == '')
                            $regresar_solic = 1;
                        else
                            $regresar_solic = 0;
                    }
                }else
                    $regresar_solic = 0;
                
                
                // Comprueba si se regresa la solicitud o se prosigue con el cierre
                if($regresar_solic == 1)
                    $resultado = 0; // Se regresa la solicitud porqe falta llenar el campo de solicitud tardía

                else{
                    // Prosigue con la solicitud
                    // Guardar errores de la solicitud
                    $sql_errores = "INSERT INTO serviciosyreservaciones_errores (id_cabecera, fecha, prj_presupuesto, viaticos_pend, v_dias_semanasanta, v_dias_verano, v_dias_navidad, v_dias_internacional, v_dias_nacional, h_dias_semanasanta, h_dias_verano, h_dias_navidad, h_dias_internacional, h_dias_nacional, dias_autos) VALUES 
                                                ($id_cabecera, '$hoy', $error_prj_presupuesto, $error_viaticos_pend, $error_v_dias_semanasanta, $error_v_dias_verano, $error_v_dias_navidad, $error_v_dias_internacional, $error_v_dias_nacional, $error_h_dias_semanasanta, $error_h_dias_verano, $error_h_dias_navidad, $error_h_dias_internacional, $error_h_dias_nacional, $error_dias_autos)";
                    if($this->db->query($sql_errores)){
                        if($req_lider == 0){
                            // En ese caso debio ser creada con urgencia por maura y se salta a la etapa de cotización //contralor_id
                            $sql0=" INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) VALUES 
                                                                                    ($id_cabecera, $colaborador_id, '$hoy', 1, 15, '$plan_trabajo')";
                            if($this->db->query($sql0)){
                                $sql1=" UPDATE serviciosyreservaciones_cabeceras 
                                        SET 
                                            req_contraloria = $req_contraloria, 
                                            contralor_id = $id_contralor, 
                                            estado = 15 
                                        WHERE id = $id_cabecera";
                                if($this->db->query($sql1))
                                    $resultado = 4;
                                else
                                    $resultado = 2;
                                
                            }else
                                $resultado=1;
                            
                        }else{
                            // Consultar correo del líder
                            $sql="  SELECT 
                                        t.lider_id, 
                                        c.email, 
                                        j.email AS c_email, 
                                        j.nombres, 
                                        j.apellido_p, 
                                        j.apellido_m, 
                                        t.colab_celular 
                                    FROM 
                                        serviciosyreservaciones_cabeceras t LEFT JOIN 
                                        Colaboradores c ON t.lider_id=c.id_colaborador LEFT JOIN 
                                        Colaboradores j ON t.id_colaborador=j.id_colaborador 
                                    WHERE t.id=$id_cabecera";
                            $qry=$this->db->query($sql)->getResult();
                            
                            if(sizeof($qry) > 0){
                                $row = $qry[0];
                                $email_lider = $row->email;
                                $email_colab = $row->c_email;
                                $nombres = $row->nombres;
                                $apellido_p = $row->apellido_p;
                                $apellido_m = $row->apellido_m;
                                $lider_id = $row->lider_id;
                                $colab_celular = $row->colab_celular;

                                // Guardar comentarios en el log-comentarios
                                $sql0 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) VALUES 
                                                                                            ($id_cabecera, $colaborador_id, '$hoy', 1, 2, '$plan_trabajo')";
                                if($this->db->query($sql0)){
                                    if($tardia == 1){
                                        $sql0c = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) VALUES 
                                                                                                    ($id_cabecera, $colaborador_id, '$hoy', 2, 2, '$solicitud_tardia')";
                                        $this->db->query($sql0c);
                                    }
                                    
                                    // Aquí va donde se separa si el colab es lider de si mismo
                                    if($lider_id == $colaborador_id){
                                        // Encontrar cuál AUO autorizara
                                        $id_contralor = $this->SR_DATA_contraloria($id_cabecera)[1];

                                        // Actualizar campo req_contraloria en tabla de cabecera y actualizar el estado a 2 qe significa qe se envio al líder
                                        if(($req_contraloria == 0) && ($internacional_inge == 0)){ // No necesita contralor ni aprobacion del Inge
                                            $tipo_modif = 3; // Aprueba cometario
                                            $nv_estado = 4; // Aprobada por el lider
                                            $sql1_n = " UPDATE serviciosyreservaciones_cabeceras 
                                                            SET 
                                                                lider_celular = $colab_celular, 
                                                                lider_autoriza = 1,
                                                                req_contraloria = $req_contraloria, 
                                                                contralor_id = $id_contralor, 
                                                                estado = $nv_estado 
                                                            WHERE id = $id_cabecera";
                                            $to = "mtrejo@ecn.com.mx";//maura          
                                            $subject = "Nueva solicitud de vuelo, auto u hospedaje en Intranet";
                                            $mssge="Buen día, se ha solicitado y aprobado por Intranet un servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.";
                                        }else{//necesita contralor o aprobacion del inge
                                            $tipo_modif = 3;//aprueba cometario
                                            if($internacional_inge == 1){
                                                $nv_estado = 18;//falta agregar a funciones el estado
                                                $req_contraloria = 1;
                                                $id_contralor = 39;
                                            }else
                                                $nv_estado = 5;//aprobada por el lider, necesita contralor
                                            
                                            $sql1_n="   UPDATE serviciosyreservaciones_cabeceras 
                                                            SET 
                                                                lider_celular = $colab_celular, 
                                                                lider_autoriza = 1, 
                                                                estado = $nv_estado, 
                                                                req_contraloria = $req_contraloria, 
                                                                contralor_id = $id_contralor 
                                                            WHERE id = $id_cabecera";
                                            //encontrar cuál AUO autorizara o el inge
                                            if($internacional_inge == 1){
                                                $to = "arturo.freydig@ecnautomation.com";
                                            }else{
                                                $to = $this->SR_DATA_contraloria($id_cabecera)[0];
                                            }
                                            $subject = "Atender solicitud de vuelo, auto u hospedaje";
                                            $mssge = "Buen día, se necesita tu aprobación por Intranet para un servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.";
                                        }
                                        $sql0_n="INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) VALUES 
                                                                                                ($id_cabecera, $colaborador_id, '$hoy', $tipo_modif, $nv_estado, 'No se requiere autorización de líder')";
                                        if($this->db->query($sql0_n)){
                                            if($this->db->query($sql1_n)){
                                                $mensaje = "<html lang='es'>
                                                                    <head>
                                                                        <meta charset='UTF-8'>
                                                                        <title>Titutlo</title>
                                                                    </head>
                                                                    <body>
                                                                        <table>
                                                                            <tr>
                                                                                <td style='width:50px;'></td>
                                                                                <td style='width:600px;'>
                                                                                    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                                    <hr>
                                                                                    <br>
                                                                                    <p style='font-family:Helvetica;'><br>
                                                                                        ".$mssge."<br>
                                                                                        Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones'.<br>
                                                                                    </p>
                                                                                    <p style=' font-family:Helvetica;'>
                                                                                        <br><br>
                                                                                        Saludos.
                                                                                    </p>
                                                                                    <br><br><br>
                                                                                    <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                                        Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                                                </td>
                                                                                <td style='width:50px;'></td>
                                                                            </tr>
                                                                        </table>
                                                                    </body>
                                                                    </html>";

                                                $message = $mensaje;
                                                $headers  = 'MIME-Version: 1.0' . "\r\n";
                                                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                                $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                                //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                                //$headers .= "CC:" . $concopia;
                                                //echo $to.$subject.$message.$headers;
                                                
                                                try{
                                                    $resultado=4;
                                                    mail($to,$subject,$message,$headers);
                                                }catch(\Exception $e){
                                                    $resultado=3;
                                                }
                                                
                                            }else
                                                $resultado=2;
                                            
                                        }else
                                            $resultado=1;
                                        
                                    }else{
                                        if(($internacional_inge==1) && ($lider_id!=39)){
                                            $req_contraloria=1;
                                            $id_contralor=39;
                                        }
                                        //actualizar campo req_contraloria en tabla de cabecera y actualizar el estado a 2 qe significa qe se envio al líder
                                        $sql1=" UPDATE serviciosyreservaciones_cabeceras 
                                                SET     
                                                    req_contraloria = $req_contraloria, 
                                                    contralor_id = $id_contralor, 
                                                    estado = 2 
                                                WHERE id = $id_cabecera";
                                        if($this->db->query($sql1)){
                                            $mensaje = "<html lang='es'>
                                                <head>
                                                    <meta charset='UTF-8'>
                                                    <title>Titutlo</title>
                                                </head>
                                                <body>
                                                    <table>
                                                        <tr>
                                                            <td style='width:50px;'></td>
                                                            <td style='width:600px;'>
                                                                <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                <hr>
                                                                <br>
                                                                <p style='font-family:Helvetica;'><br>
                                                                    Buen día, ".$apellido_p." ".$apellido_m." ".$nombres." solicitó por Intranet algún servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.<br>
                                                                    Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones'.<br>
                                                                </p>
                                                                <p style=' font-family:Helvetica;'>
                                                                    <br><br>
                                                                    Saludos.
                                                                </p>
                                                                <br><br><br>
                                                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                    Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                            </td>
                                                            <td style='width:50px;'></td>
                                                        </tr>
                                                    </table>
                                                </body>
                                                </html>";

                                            $to = $email_lider;          
                                            $subject = "Atender solicitud de vuelo, auto u hospedaje";
                                            $message = $mensaje;
                                            $headers  = 'MIME-Version: 1.0' . "\r\n";
                                            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                            $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                            //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                            //echo $to.$subject.$message.$headers;

                                            try{
                                                mail($to,$subject,$message,$headers);
                                                $resultado=4;
                                            }catch(\Exception $e){

                                            }



                                            if($resultado != 4){
                                                $mensaje2 = "<html lang='es'>
                                                        <head>
                                                            <meta charset='UTF-8'>
                                                            <title>Titutlo</title>
                                                        </head>
                                                        <body>
                                                            <table>
                                                                <tr>
                                                                    <td style='width:50px;'></td>
                                                                    <td style='width:600px;'>
                                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                        <hr>
                                                                        <br>
                                                                        <p style='font-family:Helvetica;'><br>
                                                                            Buen día, Intranet no pudo notificarle a tu líder, por correo electrónico, que debe atender tu <b>solicitud #".$id_cabecera."</b>.<br>
                                                                            Por favor contáctalo para hacérselo saber.<br>
                                                                        </p>
                                                                        <p style=' font-family:Helvetica;'>
                                                                            <br><br>
                                                                            Saludos.
                                                                        </p>
                                                                        <br><br><br>
                                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                                    </td>
                                                                    <td style='width:50px;'></td>
                                                                </tr>
                                                            </table>
                                                        </body>
                                                        </html>";
                                                $to2 = $email_colab;          
                                                $subject2 = "Falló envío a correo del líder en solicitud de vuelo, auto u hospedaje";
                                                $message2 = $mensaje2;
                                                $headers2  = 'MIME-Version: 1.0' . "\r\n";
                                                $headers2 .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                                $headers2 .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                                //$headers2 .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";

                                                try{
                                                    $resultado=3;//$email_colab

                                                    mail($to2,$subject2,$message2,$headers2);
                                                }catch(\Exception $e){

                                                }

                                            }
                                        }else{
                                            $resultado=2;
                                        }
                                    }
                                    
                                }else
                                    $resultado=1;
                            }else
                                $resultado = 0;
                            
                        }
                    }else
                        $resultado=1;
                
                }

                return $this->response->setStatusCode(200)->setJSON($resultado);
            // vuelos revisa errores, guardar detalle de servicio si hay al menos un error se insertara contralor, 
            // hospedaje revisa errores, guardar detalle de servicio si hay al menos un error se insertara contralor,
            // autos revisa errores, guardar detalle de servicio si hay al menos un error se insertara contralor,
            case 202:

                $solicitud = $json->solicitud;
                $tipo_solicitud = $json->tipo_solicitud;

                switch($tipo_solicitud){
                    // vuelos revisa errores, guardar detalle de servicio si hay al menos un error se insertara contralor, 
                    case 1: // Vuelos

                        $tipo = $json->tipo;
                        $internacional = $json->internacional;
                        $origen = $json->origen;
                        $destino = $json->destino;
                        $fsalida = $json->fsalida;
                        $hsalida_desc = $json->hsalida_desc;
                        $hsalida_num = $json->hsalida_num;
                        $fregreso = $json->fregreso;
                        $hregreso_desc = $json->hregreso_desc;
                        $hregreso_num = $json->hregreso_num;

                        $inicio = $hsalida_desc . ' ' . $hsalida_num;
                        $fin = $hregreso_desc . ' ' . $hregreso_num;

                        $sql="  INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, v_tipo, origen, destino, v_inicio, v_fin, inicio_entrada, fin_salida, estado, internacional) 
                                                                    VALUES  ($solicitud, $tipo_solicitud, '$hoy', $tipo, $origen, $destino, '$inicio', '$fin', '$fsalida', '$fregreso', 1, $internacional)";
                        if($this->db->query($sql))
                            return $this->response->setStatusCode(200)->setJSON(1);
                        else
                            return $this->response->setStatusCode(200)->setJSON(0);

                        break;
                    // hospedaje revisa errores, guardar detalle de servicio si hay al menos un error se insertara contralor, 
                    case 2: // Hospedajes

                        $tipo = $json->tipo;
                        $ciudad = $json->ciudad;
                        $internacional = $json->internacional;
                        $entrada = $json->entrada;
                        $salida = $json->salida;

                        $sql="  INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, h_tipo, h_ciudad, inicio_entrada, fin_salida, estado, internacional) 
                                                                    VALUES  ($solicitud, $tipo_solicitud, '$hoy', $tipo, '$ciudad', '$entrada', '$salida', 1, $internacional)";
                        if($this->db->query($sql))
                            return $this->response->setStatusCode(200)->setJSON(1);
                        else
                            return $this->response->setStatusCode(200)->setJSON(0);

                        break;
                    // autos revisa errores, guardar detalle de servicio si hay al menos un error se insertara contralor,
                    case 3: // Autos

                        $tipo = $json->tipo;
                        $lugar = $json->lugar;
                        $inicio = $json->inicio;
                        $fin = $json->fin;


                        $sql="  INSERT INTO serviciosyreservaciones_detalle (id_cabecera, tipo_solicitud, fecha, a_tipo, a_lugar_entrega, inicio_entrada, fin_salida, estado) 
                                                                    VALUES  ($solicitud, $tipo_solicitud, '$hoy', $tipo, $lugar, '$inicio', '$fin', 1)";
                        if($this->db->query($sql))
                            return $this->response->setStatusCode(200)->setJSON(1);
                        else
                            return $this->response->setStatusCode(200)->setJSON(0);

                        break;
                    default:
                        return $this->response->setStatusCode(200)->setJSON(0);
                        break;
                }

                break;
            // lider atiende la solicitud, puede aprobarla o rechazarla
            case 203: 

                $id_cabecera = $json->solicitud;
                $req_contraloria = $json->contraloria;
                $colaborador_id = $json->colaboradorID; //lider        
                $celular_lider = $json->celularLider;
                $comentario_lider = $json->comentarioLider;
                $aprueba = $json->aprueba;
                $internacional_inge = $json->internacionalInge;
                
                $sql = "SELECT c.email 
                        FROM 
                            serviciosyreservaciones_cabeceras t LEFT JOIN 
                            Colaboradores c ON t.id_colaborador = c.id_colaborador 
                        WHERE t.id = $id_cabecera";
                $qry = $this->db->query($sql)->getResult();
                if(sizeof($qry) > 0)
                    $mailcol = $qry[0]->email;
                else
                    $mailcol = '';
                
                if($aprueba == 1){
                    if($req_contraloria == 0){ //no necesita contralor o al inge
                        $tipo_modif = 3; //aprueba cometario
                        $nv_estado = 4; //aprobada por el lider
                        $sql1 = "   UPDATE serviciosyreservaciones_cabeceras 
                                    SET 
                                        lider_celular = $celular_lider, 
                                        lider_autoriza = $aprueba, 
                                        estado = $nv_estado 
                                    WHERE id = $id_cabecera";
                        $to = "mtrejo@ecn.com.mx"; //maura          
                        $subject = "Nueva solicitud de vuelo, auto u hospedaje en Intranet";
                        $mssge = "Buen día, se ha solicitado y aprobado por Intranet un servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.";
                    }else{ //necesita contralor o al inge
                        $tipo_modif = 3; //aprueba cometario
                        if($internacional_inge == 1){
                            $nv_estado = 18;
                            $to = "arturo.freydig@ecnautomation.com";
                        }else{
                            $nv_estado = 5; //aprobada por el lider, necesita contralor
                            //encontrar cuál AUO autorizara
                            $to = $this->SR_DATA_contraloria($id_cabecera)[0];
                        }
                        $sql1 = "   UPDATE serviciosyreservaciones_cabeceras 
                                    SET 
                                        lider_celular = $celular_lider, 
                                        lider_autoriza = $aprueba, 
                                        estado = $nv_estado 
                                    WHERE id = $id_cabecera";
                        //$to = "acastro@ecn.com.mx"; //contralor          
                        $subject = "Atender solicitud de vuelo, auto u hospedaje";
                        $mssge = "Buen día, se necesita tu aprobación por Intranet para un servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.";
                    }
                }else{
                    $tipo_modif = 4; //rechaza comentario
                    $nv_estado = 3; //regresada al colaborador
                    $sql1 = "   UPDATE serviciosyreservaciones_cabeceras 
                                SET 
                                    lider_celular = $celular_lider, 
                                    lider_autoriza = $aprueba, 
                                    estado = $nv_estado, 
                                    req_contraloria = NULL, 
                                    contralor_id = NULL 
                                WHERE id = $id_cabecera";
                    $to = $mailcol; //colaborador          
                    $subject = "Tu líder solicita cambios a solicitud de vuelo, auto u hospedaje";
                    $mssge = "Buen día, tu lider solicitó cambios a la solicitud de servicio de vuelo, hospedaje o renta de auto con el folio #<b>".$id_cabecera."</b>, haciendo el siguiente comentario: ".$comentario_lider;
                }
                
                $sql0 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                                                     VALUES ($id_cabecera, $colaborador_id, '$hoy', $tipo_modif, $nv_estado, '$comentario_lider')";
                if($this->db->query($sql0)){
                    if($this->db->query($sql1)){
                        $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                ".$mssge."<br>
                                                                Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones'.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                            </html>";
        
                        $message = $mensaje;
                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                        //$headers .= "CC:" . $concopia;
                        //echo $to.$subject.$message.$headers;

                        try{
                            mail($to,$subject,$message,$headers);
                            $resultado = 4;
                        }catch(\Exception $e){
                            $resultado = 3;
                        }
                    }else
                        $resultado = 2;
                }else
                    $resultado = 1;
                
                return $this->response->setStatusCode(200)->setJSON($resultado);
            // reenviar solicitud
            case 204:

                $id_cabecera = $json->id_cabecera;
                $colaborador_id = $json->colaborador_id;

                $comentario_reenviar = $json->comentario_reenviar;
                $solicitud_tardia = $json->solicitud_tardia;

                $urgencia = $json->urgencia;
                $autoriza_contralor = $json->autoriza_contralor;
                $solicitud_errordias = $json->solicitud_errordias;
                //$plan_trabajo = $json->plan_trabajo;
                $error_prj_presupuesto = $json->error_prj_presupuesto;
                $error_viaticos_pend = $json->error_viaticos_pend;
                $error_v_dias_semanasanta = $json->error_v_dias_semanasanta;
                $error_v_dias_verano = $json->error_v_dias_verano;
                $error_v_dias_navidad = $json->error_v_dias_navidad;
                $error_v_dias_internacional = $json->error_v_dias_internacional;
                $error_v_dias_nacional = $json->error_v_dias_nacional;
                $error_h_dias_semanasanta = $json->error_h_dias_semanasanta;
                $error_h_dias_verano = $json->error_h_dias_verano;
                $error_h_dias_navidad = $json->error_h_dias_navidad;
                $error_h_dias_internacional = $json->error_h_dias_internacional;
                $error_h_dias_nacional = $json->error_h_dias_nacional;
                $error_dias_autos = $json->error_dias_autos;
                $internacional_inge = $json->internacional_inge;

                $resultado = 0;
               
                //definir autorizaciones y comprobar comentarios por solicitud tardía o urgente
                if(($urgencia == 1)&&($colaborador_id == 1050)){
                    $req_contraloria = 0;
                    $req_lider = 0;
                    $tardia = 0;
                }else{
                    $req_lider = 1;
                    if($solicitud_errordias >= 1){
                        $req_contraloria = 0;//$req_contraloria = 1;
                        $tardia = 1;
                    }else{
                        if($autoriza_contralor >= 1){
                            $req_contraloria = 0;//$req_contraloria = 1;
                            $tardia = 0;
                        }else{
                            $req_contraloria = 0;
                            $tardia = 0;
                        }
                    }
                }
                //encontrar cuál AUO autorizara
                $id_contralor = $this->SR_DATA_contraloria($id_cabecera)[1];
                //revisar contenido de comentario por solicitud tardia
                if($tardia == 1){
                    if(is_null($solicitud_tardia))
                        $regresar_solic = 1;
                    else{
                        $cadena = str_replace(' ', '', $solicitud_tardia);
                        if($cadena == '')
                            $regresar_solic = 1;
                        else
                            $regresar_solic = 0;
                    }
                }else
                    $regresar_solic = 0;
                
                
                //comprueba si se regresa la solicitud o se prosigue con el cierre
                if($regresar_solic == 1)
                    $resultado = 0;//se regresa la solicitud porqe falta llenar el campo de solicitud tardía
                else{
                    //prosigue con la solicitud
                    //guardar errores de la solicitud
                    $sql_errores="  INSERT INTO serviciosyreservaciones_errores (id_cabecera, fecha, prj_presupuesto, viaticos_pend, v_dias_semanasanta, v_dias_verano, v_dias_navidad, v_dias_internacional, v_dias_nacional, h_dias_semanasanta, h_dias_verano, h_dias_navidad, h_dias_internacional, h_dias_nacional, dias_autos) 
                                    VALUES ($id_cabecera, '$hoy', $error_prj_presupuesto, $error_viaticos_pend, $error_v_dias_semanasanta, $error_v_dias_verano, $error_v_dias_navidad, $error_v_dias_internacional, $error_v_dias_nacional, $error_h_dias_semanasanta, $error_h_dias_verano, $error_h_dias_navidad, $error_h_dias_internacional, $error_h_dias_nacional, $error_dias_autos)";
                    $this->db->query($sql_errores);
                   
                    //consultar correo del líder
                    $sql="  SELECT 
                                t.lider_id, 
                                c.email, 
                                j.email AS c_email, 
                                j.nombres, 
                                j.apellido_p, 
                                j.apellido_m 
                            FROM 
                                serviciosyreservaciones_cabeceras t LEFT JOIN 
                                Colaboradores c on t.lider_id = c.id_colaborador LEFT JOIN 
                                Colaboradores j on t.id_colaborador = j.id_colaborador 
                            WHERE t.id = $id_cabecera";
                    $qry = $this->db->query($sql)->getResult();
                    if(sizeof($qry) > 0){
                        $email_lider = $qry[0]->email;
                        $email_colab = $qry[0]->c_email;
                        $nombres = $qry[0]->nombres;
                        $apellido_p = $qry[0]->apellido_p;
                        $apellido_m = $qry[0]->apellido_m;
                        $lider_id = $qry[0]->lider_id;
                    }else{
                        $email_lider = '';
                        $email_colab = '';
                        $nombres = '';
                        $apellido_p = '';
                        $apellido_m = '';
                        $lider_id = NULL;
                    }
        
                    //guardar comentarios en el log-comentarios
                    $sql0 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                VALUES ($id_cabecera, $colaborador_id, '$hoy', 6, 2, '$comentario_reenviar')";
                    if($this->db->query($sql0)){
                        if($tardia == 1){
                            $sql0c="INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                    VALUES ($id_cabecera, $colaborador_id, '$hoy', 2, 2, '$solicitud_tardia')";
                            $this->db->query($sql0c);
                        }
                        
                        // aqui va donde se separa si el colab es lider de si mismo
                        if($lider_id == $colaborador_id){
                            //encontrar cuál AUO autorizara
                            $id_contralor = $this->SR_DATA_contraloria($id_cabecera)[1];
                            if(($req_contraloria == 0) && ($internacional_inge == 0)){ // no necesita contralor ni al inge
                                $tipo_modif = 3; // aprueba cometario
                                $nv_estado = 4; // aprobada por el lider
                                //$sql1_n="update serviciosyreservaciones_cabeceras set lider_celular=$colab_celular, lider_autoriza=1, estado=$nv_estado where id=$id_cabecera";
                                $to = "mtrejo@ecn.com.mx";//maura          
                                $subject = "Nueva solicitud de vuelo, auto u hospedaje en Intranet";
                                $mssge = "Buen día, se ha solicitado y aprobado por Intranet un servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.";
                                //actualizar campo req_contraloria en tabla de cabecera y actualizar el estado a 2 qe significa qe se envio al líder
                                $sql1=" UPDATE serviciosyreservaciones_cabeceras 
                                        SET 
                                            req_contraloria = $req_contraloria, 
                                            estado = 4 
                                        WHERE id = $id_cabecera";
                            }else{ // necesita contralor
                                $tipo_modif = 3; // aprueba cometario
                                if($internacional_inge == 1){
                                    $nv_estado = 18; // falta agregar a funciones el estado
                                    $req_contraloria = 1;
                                    $id_contralor = 39;
                                }else
                                    $nv_estado = 5; // aprobada por el lider, necesita contralor
                                
                                //$sql1_n="update serviciosyreservaciones_cabeceras set lider_celular=$colab_celular, lider_autoriza=1, estado=$nv_estado where id=$id_cabecera";
                                //encontrar cuál AUO autorizara
                                if($internacional_inge == 1)
                                    $to="arturo.freydig@ecnautomation.com";
                                else
                                    $to = $this->SR_DATA_contraloria($id_cabecera)[0];
                                
                                $subject = "Atender solicitud de vuelo, auto u hospedaje";
                                $mssge = "Buen día, se necesita tu aprobación por Intranet para un servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.";
                                //actualizar campo req_contraloria en tabla de cabecera y actualizar el estado a 2 qe significa qe se envio al líder
                                $sql1=" UPDATE serviciosyreservaciones_cabeceras 
                                        SET 
                                            req_contraloria = $req_contraloria, 
                                            contralor_id = $id_contralor, 
                                            estado = 5 
                                        WHERE id = $id_cabecera";
                            }
                            $sql0_n="   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                        VALUES ($id_cabecera, $colaborador_id, '$hoy', $tipo_modif, $nv_estado, 'No se requiere autorización de líder')";
                            if($this->db->query($sql0_n)){
                                if($this->db->query($sql1)){
                                    $mensaje = "<html lang='es'>
                                                            <head>
                                                                <meta charset='UTF-8'>
                                                                <title>Titutlo</title>
                                                            </head>
                                                            <body>
                                                                <table>
                                                                    <tr>
                                                                        <td style='width:50px;'></td>
                                                                        <td style='width:600px;'>
                                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                            <hr>
                                                                            <br>
                                                                            <p style='font-family:Helvetica;'><br>
                                                                                ".$mssge."<br>
                                                                                Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones'.<br>
                                                                            </p>
                                                                            <p style=' font-family:Helvetica;'>
                                                                                <br><br>
                                                                                Saludos.
                                                                            </p>
                                                                            <br><br><br>
                                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                                        </td>
                                                                        <td style='width:50px;'></td>
                                                                    </tr>
                                                                </table>
                                                            </body>
                                                            </html>";        
                                    $message = $mensaje;
                                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                    $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                    //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                    //$headers .= "CC:" . $concopia;
                                    //echo $to.$subject.$message.$headers;

                                    try{
                                        mail($to,$subject,$message,$headers);
                                        $resultado = 4;
                                    }catch(\Exception $e){
                                        $resultado = 3;//$email_colab
                                    }

                                }else
                                    $resultado = 2;
                            }else
                                $resultado = 2;
                        }else{
                            if(($internacional_inge == 1) && ($lider_id != 39)){
                                $req_contraloria = 1;
                                $id_contralor = 39;
                            }
                            // actualizar campo req_contraloria en tabla de cabecera y actualizar el estado a 2 qe significa qe se envio al líder
                            $sql1=" UPDATE serviciosyreservaciones_cabeceras 
                                    SET 
                                        req_contraloria = $req_contraloria, 
                                        contralor_id = $id_contralor, 
                                        estado = 2 
                                    WHERE id = $id_cabecera";
                            if($this->db->query($sql1)){
                                $mensaje = "<html lang='es'>
                                        <head>
                                            <meta charset='UTF-8'>
                                            <title>Titutlo</title>
                                        </head>
                                        <body>
                                            <table>
                                                <tr>
                                                    <td style='width:50px;'></td>
                                                    <td style='width:600px;'>
                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                        <hr>
                                                        <br>
                                                        <p style='font-family:Helvetica;'><br>
                                                            Buen día, ".$apellido_p." ".$apellido_m." ".$nombres." solicitó por Intranet algún servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.<br>
                                                            Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones'.<br>
                                                        </p>
                                                        <p style=' font-family:Helvetica;'>
                                                            <br><br>
                                                            Saludos.
                                                        </p>
                                                        <br><br><br>
                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                    </td>
                                                    <td style='width:50px;'></td>
                                                </tr>
                                            </table>
                                        </body>
                                        </html>";
                                $to = $email_lider;          
                                $subject = "Atender solicitud de vuelo, auto u hospedaje";
                                $message = $mensaje;
                                $headers  = 'MIME-Version: 1.0' . "\r\n";
                                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                //$headers .= "CC:" . $concopia;
                                //echo $to.$subject.$message.$headers;

                                try{
                                    mail($to,$subject,$message,$headers);
                                    $resultado = 4;
                                }catch(\Exception $e){
                                    $resultado = 3;
                                }

                                if($resultado != 4){
                                    $mensaje2 = "<html lang='es'>
                                        <head>
                                            <meta charset='UTF-8'>
                                            <title>Titutlo</title>
                                        </head>
                                        <body>
                                            <table>
                                                <tr>
                                                    <td style='width:50px;'></td>
                                                    <td style='width:600px;'>
                                                        <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                        <hr>
                                                        <br>
                                                        <p style='font-family:Helvetica;'><br>
                                                            Buen día, Intranet no pudo notificarle a tu líder, por correo electrónico, que debe atender tu <b>solicitud #".$id_cabecera."</b>.<br>
                                                            Por favor contáctalo para hacérselo saber.<br>
                                                        </p>
                                                        <p style=' font-family:Helvetica;'>
                                                            <br><br>
                                                            Saludos.
                                                        </p>
                                                        <br><br><br>
                                                        <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                            Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                    </td>
                                                    <td style='width:50px;'></td>
                                                </tr>
                                            </table>
                                        </body>
                                        </html>";
                                    $to2 = $email_colab;          
                                    $subject2 = "Falló envío a correo del líder en solicitud de vuelo, auto u hospedaje";
                                    $message2 = $mensaje2;
                                    $headers2  = 'MIME-Version: 1.0' . "\r\n";
                                    $headers2 .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                    $headers2 .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                    //$headers2 .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";

                                    try{
                                        mail($to2,$subject2,$message2,$headers2);
                                    }catch(\Exception $e){

                                    }
                                }
                            }else
                                $resultado = 2;
                            
                        }
                    }else
                        $resultado = 1;
                }
                return $this->response->setStatusCode(200)->setJSON($resultado);
            // contralor atiende la solicitud, puede aprobarla o rechazarla
            case 205:
                $id_cabecera = $json->id_cabecera;
                $colaborador_id = $json->colaborador_id;//lider        
                $comentario_contralor = $json->comentario_contralor;
                $aprueba = $json->aprueba;
                $internacional_inge = $json->internacional_inge;

                $resultado = 0;
                
                $sql = "SELECT c.email 
                        FROM 
                            serviciosyreservaciones_cabeceras t LEFT JOIN 
                            Colaboradores c ON t.id_colaborador=c.id_colaborador 
                        WHERE t.id=$id_cabecera";
                $qry = $this->db->query($sql)->getResult();
                if(sizeof($qry) > 0)
                    $mailcol = $qry[0]->email;
                else
                    $mailcol = '';
                
                if($aprueba == 1){
                    if($internacional_inge == 1){
                        $tipo_modif = 19; // aprueba inge
                        $nv_estado = 19; // aprobada por el inge
                    }else{
                        $tipo_modif = 8; // aprueba contralor
                        $nv_estado = 6; // aprobada por el contralor
                    }
                    $sql1 = "   UPDATE serviciosyreservaciones_cabeceras 
                                SET 
                                    contralor_autoriza = 1, 
                                    estado = $nv_estado 
                                WHERE id = $id_cabecera";
                    $to = "mtrejo@ecn.com.mx"; // contralor          
                    $subject = "Atender solicitud de vuelo, auto u hospedaje";
                    $mssge = "Buen día, en Intranet hay una nueva solicitud para un servicio de vuelo, hospedaje o renta de auto, con el folio #<b>".$id_cabecera."</b>.";
                }else{
                    if($internacional_inge == 1){
                        $tipo_modif = 20; // rechaza comentario
                        $nv_estado = 20; // regresada al colaborador
                        $subject  =  "Arturo Freydig solicita cambios a solicitud de vuelo, auto u hospedaje";
                        $mssge = "Buen día, Arturo Freydig solicitó cambios a la solicitud de servicio de vuelo, hospedaje o renta de auto con el folio #<b>".$id_cabecera."</b>, haciendo el siguiente comentario: ".$comentario_contralor;
                    }else{
                        $tipo_modif = 7; // rechaza comentario
                        $nv_estado = 7; // regresada al colaborador
                        $subject = "Contraloría solicita cambios a solicitud de vuelo, auto u hospedaje";
                        $mssge = "Buen día, Contraloría solicitó cambios a la solicitud de servicio de vuelo, hospedaje o renta de auto con el folio #<b>".$id_cabecera."</b>, haciendo el siguiente comentario: ".$comentario_contralor;
                    }
                    $sql1 = "   UPDATE serviciosyreservaciones_cabeceras 
                                SET 
                                    estado = $nv_estado, 
                                    req_contraloria = NULL, 
                                    contralor_id = NULL 
                                WHERE id = $id_cabecera";
                    $to = $mailcol; // colaborador          
                }
                
                $sql0 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                                                     VALUES ($id_cabecera, $colaborador_id, '$hoy', $tipo_modif, $nv_estado, '$comentario_contralor')";
                if($this->db->query($sql0)){
                    if($this->db->query($sql1)){
                        $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                ".$mssge."<br>
                                                                Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones'.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                            </html>";
        
                        $message = $mensaje;
                        $headers = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                        //$headers .= "CC:" . $concopia;
                        //echo $to.$subject.$message.$headers;

                        try{
                            mail($to,$subject,$message,$headers);
                            $resultado = 4;
                        }catch(\Exception $e){
                            $resultado = 3;
                        }
                    }else
                        $resultado = 2;
                }else
                    $resultado = 1;

                return $this->response->setStatusCode(200)->setJSON($resultado);
            // Cambiar de nombre aprueba rechaza lider o contraloria
            // "Existia un codigo comentado de contraloria, por esta razón no se agrego."
            case 206:
            case 207:

                $id_cabecera = isset($json->id_cabecera) ? $json->id_cabecera : 'NULL';
                $solicitante_id = isset($json->solicitante_id) ? $json->solicitante_id : 'NULL';
                $edo = isset($json->edo) ? $json->edo : 'NULL';
                $lider_id = isset($json->lider_id) ? $json->lider_id : 'NULL';
                $respuesta = isset($json->respuesta) ? $json->respuesta : 'NULL';
                $solic_nvo = isset($json->solic_nvo) ? $json->solic_nvo : 'NULL';
                $tipofx = isset($json->tipofx) ? $json->tipofx : 'NULL';
                $colaborador_id = isset($json->colaborador_id) ? $json->colaborador_id : 'NULL';
                
                $sql = '';
                $resultado = 0;

                if($tipofx == "L"){ //lider
                    if($respuesta == 1){ //aprueba
                        $sql = "UPDATE serviciosyreservaciones_cabeceras 
                                SET 
                                    id_colaborador = $solic_nvo, 
                                    solicitante_nuevo = NULL, 
                                    estado_solicnv = 0 
                                WHERE id = $id_cabecera";
                        $sql1= "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                VALUES ($id_cabecera, $colaborador_id, '$hoy', 16, $edo, 'Solicitud de cambio de nombre aprobada por el líder')";
                        
                        // correo a colaborador
                        $to_sql = " SELECT email 
                                    FROM Colaboradores 
                                    WHERE id_colaborador = $solicitante_id";
                        $to_qr = $this->db->query($to_sql)->getResult();
                        if(sizeof($to_qr) > 0)
                            $to = $to_qr[0]->email; // colaborador solicitante original
                        else
                            $to = ''; // colaborador solicitante original
                            
                        $subject = "Cambio de nombre aprobado para solicitud de servicios y reservaciones";
                        $mensaje = "<html lang='es'>
                            <head>
                                <meta charset='UTF-8'>
                                <title>Titutlo</title>
                            </head>
                            <body>
                                <table>
                                    <tr>
                                        <td style='width:50px;'></td>
                                        <td style='width:600px;'>
                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                            <hr>
                                            <br>
                                            <p style='font-family:Helvetica;'><br>
                                                Buen día, tu líder aprobaró tu solicitud para cambio de nombre a la <b>solicitud #".$id_cabecera."</b>.<br>
                                            </p>
                                            <p style=' font-family:Helvetica;'>
                                                <br><br>
                                                Saludos.
                                            </p>
                                            <br><br><br>
                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                        </td>
                                        <td style='width:50px;'></td>
                                    </tr>
                                </table>
                            </body>
                        </html>";
                    }else{ // rechaza
                        $sql = "UPDATE serviciosyreservaciones_cabeceras 
                                SET 
                                    solicitante_nuevo = NULL, 
                                    estado_solicnv = NULL 
                                WHERE id = $id_cabecera";
                        $sql1 ="INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                VALUES ($id_cabecera, $colaborador_id, '$hoy', 16, $edo, 'Solicitud de cambio de nombre rechazada por el líder')"; // estado actual en edo
                        // correo a colaborador
                        $to_sql = " SELECT email 
                                    FROM Colaboradores 
                                    WHERE id_colaborador = $solicitante_id";
                        $to_qr = $this->db->query($to_sql)->getResult();
                        if(sizeof($to_qr) > 0)
                            $to = $to_qr[0]->email; // colaborador solicitante original
                        else
                            $to = ''; // colaborador solicitante original 
                        $subject = "Cambio de nombre rechazado por líder";
                        $mensaje = "<html lang='es'>
                            <head>
                                <meta charset='UTF-8'>
                                <title>Titutlo</title>
                            </head>
                            <body>
                                <table>
                                    <tr>
                                        <td style='width:50px;'></td>
                                        <td style='width:600px;'>
                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                            <hr>
                                            <br>
                                            <p style='font-family:Helvetica;'><br>
                                                Buen día, tu líder rechazó tu solicitud para cambio de nombre a la <b>solicitud #".$id_cabecera."</b>.<br>
                                            </p>
                                            <p style=' font-family:Helvetica;'>
                                                <br><br>
                                                Saludos.
                                            </p>
                                            <br><br><br>
                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                        </td>
                                        <td style='width:50px;'></td>
                                    </tr>
                                </table>
                            </body>
                        </html>";
                    }
                }
                
                if(!empty($sql)){
                    if($this->db->query($sql)){
                        if($this->db->query($sql1)){
                            $message = $mensaje;
                            $headers  = 'MIME-Version: 1.0' . "\r\n";
                            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                            $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                            //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                            //$headers .= "CC:" . $concopia;
                            //echo $to.$subject.$message.$headers;

                            try{
                                mail($to,$subject,$message,$headers);
                                $resultado = 1;
                            }catch(\Exception $e){
                                $resultado = 3;
                            }
                        }else
                            $resultado = 2;
                    }
                }

                return $this->response->setStatusCode(200)->setJSON($resultado);
            // Reagendar vuelo
            case 208: 
                
                $tipo_solicitud = $json->tipo_solicitud;
                $folio_cabecera = $json->folio_cabecera;
                $filio_detalle = $json->filio_detalle;
                $respuesta = $json->respuesta;
                $lider_id = $json->lider_id;
                $colaborador_id = $json->colaborador_id;
                $id17 = $json->id17;
                $etapa = $json->etapa;

                $qry_res = 0;
        
                switch($etapa){
                    case 2 :
                        //líder
                        //puede rechazar o aceptar
                        //si rechaza la de estado 17 se convierte en 0 con status 0 y se avisa al colaborador
                        //si acepta se avisa a contralor y cambia a etapa2
                        //se inserta comentario
                        if($respuesta == 1){
                            $sql = "UPDATE serviciosyreservaciones_detalle SET estado = 1 WHERE id = $id17";
                            $sql2 = "   UPDATE serviciosyreservaciones_cabeceras 
                                        SET 
                                            reagenda_vuelo = 0, 
                                            estado = 16 
                                        WHERE id = $folio_cabecera";
                            $sql3 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                        VALUES ($folio_cabecera, 1, $colaborador_id, '$hoy', 21, 'El líder aprobó reagendar el Vuelo')";
                            if($this->db->query($sql)){
                                if($this->db->query($sql2)){
                                    if($this->db->query($sql3)){
                                        $to_sql = " SELECT c.email 
                                                    FROM 
                                                        Colaboradores c LEFT JOIN 
                                                        serviciosyreservaciones_cabeceras t ON c.id_colaborador = t.id_colaborador 
                                                    WHERE t.id = $folio_cabecera";
                                        $to_qr = $this->db->query($to_sql)->getResult();
                                        if(sizeof($to_qr) > 0)
                                            $to = $to_qr[0]->email; // lider 
                                        else
                                            $to = '';
                                        $subject = "Tu líder autorizó reagendar tu solicitud de servicios y reservaciones";
                                        $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                Buen día, tu líder autorizó reagendar el vuelo de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                        </html>";
                                        $message = $mensaje;
                                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                        //$headers .= "CC:" . $concopia;
                                        //echo $to.$subject.$message.$headers;

                                        try{
                                            mail($to,$subject,$message,$headers);
                                            $qry_res = 1;
                                        }catch(\Exception $e){
                                            $qry_res = 4;
                                        }
                                        
                                        $sql_0 = "  UPDATE serviciosyreservaciones_cotizaciones 
                                                    SET estado = 1 
                                                    WHERE 
                                                        id_cabecera = $folio_cabecera AND 
                                                        tipo_solicitud = 1 AND 
                                                        estado IN (1,2);";
                                        $this->db->query($sql_0);
                                    }else
                                        $qry_res = 3;
                                }else
                                    $qry_res = 2;
                            }else
                                $qry_res = 0;
                        }else{
                            $sql = "UPDATE serviciosyreservaciones_detalle 
                                    SET 
                                        estado = 0, 
                                        status = 0 
                                    WHERE id = $id17";
                            $sql2 = "   UPDATE serviciosyreservaciones_cabeceras 
                                        SET reagenda_vuelo = 0 
                                        WHERE id = $folio_cabecera";
                            $sql3 = "   UPDATE serviciosyreservaciones_detalle 
                                        SET estado = 12 
                                        WHERE id = $filio_detalle";
                            $sql4 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                        VALUES ($folio_cabecera, 1, $colaborador_id, '$hoy', 22, 'El líder rechazó reagendar el Vuelo')";
                            
                            if($this->db->query($sql)){
                                if($this->db->query($sql2)){
                                    if($this->db->query($sql3)){
                                        $to_sql = " SELECT c.email 
                                                    FROM 
                                                        Colaboradores c LEFT JOIN 
                                                        serviciosyreservaciones_cabeceras t ON c.id_colaborador = t.id_colaborador 
                                                    WHERE t.id = $folio_cabecera";
                                        $to_qr = $this->db->query($to_sql)->getResult();
                                        if(sizeof($to_qr) > 0)
                                            $to = $to_qr[0]->email; // lider 
                                        else
                                            $to = '';

                                        $subject = "El líder rechazó reagendar tu solicitud de servicios y reservaciones";
                                        $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                Buen día, tu líder rechazó reagendar el vuelo de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                        </html>";
                                        $message = $mensaje;
                                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                        //$headers .= "CC:" . $concopia;
                                        //echo $to.$subject.$message.$headers;

                                        try{
                                            mail($to,$subject,$message,$headers);
                                            $qry_res = 1;
                                        }catch(\Exception $e){
                                            $qry_res = 4;
                                        }

                                    }else
                                        $qry_res = 3;
                                }else
                                    $qry_res = 2;
                            }else
                                $qry_res = 0;
                        }


                        break;
                   /* case 3 :
                        //contraloria
                        //puede rechazar o aceptar
                        //si rechaza la de estado 17 se convierte en 0 con status 0 y se avisa al colaborador
                        //si acepta se avisa al colaborador, cambia a etapa 0, se le pone estado 14 al detalle anterior y al detalle en estado 17 se le pone 1, cambiar estado de la cabecera a cotizando
                        //se inserta comentario
                        if($respuesta==1){
                            $sql="update serviciosyreservaciones_detalle set estado=1 where id=$id17";
                            $sql2="update serviciosyreservaciones_cabeceras set reagenda_vuelo=0, estado=16 where id=$folio_cabecera";
                            $sql3="update serviciosyreservaciones_detalle set estado=12 where id=$filio_detalle";
                            $sql4="insert into serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) values ($folio_cabecera, 1, $colaborador_id, '$hoy', 18, 'Contraloría aceptó reagendar el Vuelo')";
                            
                            if(sqlsrv_query($conn,$sql)){
                                if(sqlsrv_query($conn,$sql2)){
                                    if(sqlsrv_query($conn,$sql3)){
                                        if(sqlsrv_query($conn,$sql4)){
                                            $to_sql="select c.email from Colaboradores c left join serviciosyreservaciones_cabeceras t on c.id_colaborador=t.id_colaborador where t.id=$folio_cabecera";
                                            $to_qr=sqlsrv_query($conn,$to_sql);
                                            $to_row=sqlsrv_fetch_array($to_qr);
                                            $to = $to_row['email'];//lider 
                                            $subject = "Contraloría autorizó reagendar tu solicitud de servicios y reservaciones";
                                            $mensaje = "<html lang='es'>
                                                <head>
                                                    <meta charset='UTF-8'>
                                                    <title>Titutlo</title>
                                                </head>
                                                <body>
                                                    <table>
                                                        <tr>
                                                            <td style='width:50px;'></td>
                                                            <td style='width:600px;'>
                                                                <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                <hr>
                                                                <br>
                                                                <p style='font-family:Helvetica;'><br>
                                                                    Buen día, contraloría autorizó reagendar el vuelo de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                                </p>
                                                                <p style=' font-family:Helvetica;'>
                                                                    <br><br>
                                                                    Saludos.
                                                                </p>
                                                                <br><br><br>
                                                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                    Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                            </td>
                                                            <td style='width:50px;'></td>
                                                        </tr>
                                                    </table>
                                                </body>
                                            </html>";
                                            $message = $mensaje;
                                            $headers  = 'MIME-Version: 1.0' . "\r\n";
                                            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                            $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                            //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                            //$headers .= "CC:" . $concopia;
                                            //echo $to.$subject.$message.$headers;
                                            if(mail($to,$subject,$message,$headers)){
                                                $qry_res=1;
                                            }else{
                                                $qry_res=4;
                                            }
                                        }else{
                                            $qry_res=4;
                                        }
                                        $sql_0="update serviciosyreservaciones_cotizaciones set estado=1 where id_cabecera=$folio_cabecera and tipo_solicitud=1 and estado in (1,2)";
                                        $qry_0=sqlsrv_query($conn,$sql_0);
                                    }else{
                                        $qry_res=3;
                                    }
                                }else{
                                    $qry_res=2;
                                }
                            }else{
                                $qry_res=0;
                            }
                        }else{
                            $sql="update serviciosyreservaciones_detalle set estado=0, status=0 where id=$id17";
                            $sql2="update serviciosyreservaciones_cabeceras set reagenda_vuelo=0 where id=$folio_cabecera";
                            $sql3="insert into serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) values ($folio_cabecera, 1, $colaborador_id, '$hoy', 18, 'Contraloría rechazó reagendar el Vuelo')";
                            if(sqlsrv_query($conn,$sql)){
                                if(sqlsrv_query($conn,$sql2)){
                                    if(sqlsrv_query($conn,$sql3)){
                                        $to_sql="select c.email from Colaboradores c left join serviciosyreservaciones_cabeceras t on c.id_colaborador=t.id_colaborador where t.id=$folio_cabecera";
                                        $to_qr=sqlsrv_query($conn,$to_sql);
                                        $to_row=sqlsrv_fetch_array($to_qr);
                                        $to = $to_row['email']; 
                                        $subject = "Contraloría rechazó reagendar tu solicitud de servicios y reservaciones";
                                        $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                Buen día, contraloría rechazó reagendar el vuelo de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                        </html>";
                                        $message = $mensaje;
                                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                        //$headers .= "CC:" . $concopia;
                                        //echo $to.$subject.$message.$headers;
                                        if(mail($to,$subject,$message,$headers)){
                                            $qry_res=1;
                                        }else{
                                            $qry_res=4;
                                        }
                                    }else{
                                        $qry_res=3;
                                    }
                                }else{
                                    $qry_res=2;
                                }
                            }else{
                                $qry_res=0;
                            }
                        }
                        break;*/
                }
                
                return $this->response->setStatusCode(200)->setJSON($qry_res);
            // Reagendar hospedaje
            case 209:
                
                $tipo_solicitud = $json->tipo_solicitud;
                $folio_cabecera = $json->folio_cabecera;
                $filio_detalle = $json->filio_detalle;
                $etapa = $json->etapa;
                $respuesta = $json->respuesta;
                $lider_id = $json->lider_id;
                $colaborador_id = $json->colaborador_id;
                $id17 = $json->id17;

                $qry_res = 0;
                
                switch($etapa){
                    case 2 :
                        //líder
                        //puede rechazar o aceptar
                        //si rechaza la de estado 17 se convierte en 0 con status 0 y se avisa al colaborador
                        //si acepta se avisa a contralor y cambia a etapa2
                        //se inserta comentario
                        if($respuesta == 1){
                            $sql="UPDATE serviciosyreservaciones_detalle SET estado = 1 WHERE id = $id17";
                            $sql2 = "   UPDATE serviciosyreservaciones_cabeceras 
                                        SET 
                                            reagenda_hosp = 0, 
                                            estado = 16 
                                        WHERE id = $folio_cabecera";
                            $sql3 = "UPDATE serviciosyreservaciones_detalle SET estado = 12 WHERE id = $filio_detalle";
                            $sql4 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                        VALUES ($folio_cabecera, 2, $colaborador_id, '$hoy', 21, 'El líder aprobó reagendar el Hospedaje')";
                            
                            if($this->db->query($sql)){
                                if($this->db->query($sql2)){
                                    if($this->db->query($sql3)){
                                        if($this->db->query($sql4)){
                                            //encontrar cuál AUO autorizara
                                            $to = $this->SR_DATA_contraloria($folio_cabecera)[0];
                                            //$to = "acastro@ecn.com.mx";
                                            $subject = "Tu líder autorizó reagendar tu solicitud de servicios y reservaciones";
                                            $mensaje = "<html lang='es'>
                                                <head>
                                                    <meta charset='UTF-8'>
                                                    <title>Titutlo</title>
                                                </head>
                                                <body>
                                                    <table>
                                                        <tr>
                                                            <td style='width:50px;'></td>
                                                            <td style='width:600px;'>
                                                                <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                <hr>
                                                                <br>
                                                                <p style='font-family:Helvetica;'><br>
                                                                    Buen día, tu líder autorizó reagendar el hospedaje de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                                </p>
                                                                <p style=' font-family:Helvetica;'>
                                                                    <br><br>
                                                                    Saludos.
                                                                </p>
                                                                <br><br><br>
                                                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                    Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                            </td>
                                                            <td style='width:50px;'></td>
                                                        </tr>
                                                    </table>
                                                </body>
                                            </html>";
                                            $message = $mensaje;
                                            $headers  = 'MIME-Version: 1.0' . "\r\n";
                                            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                            $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                            //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                            //$headers .= "CC:" . $concopia;
                                            //echo $to.$subject.$message.$headers;

                                            try{
                                                mail($to,$subject,$message,$headers);
                                                $qry_res = 1;
                                            }catch(\Exception $e){
                                                $qry_res = 4;
                                            }
                                        }else
                                            $qry_res = 4;
                                        $sql_0 = "  UPDATE serviciosyreservaciones_cotizaciones 
                                                    SET estado = 1 
                                                    WHERE 
                                                        id_cabecera = $folio_cabecera AND 
                                                        tipo_solicitud = 2 AND 
                                                        estado IN (1,2)";
                                        $this->db->query($sql_0);
                                    }else
                                        $qry_res = 3;
                                }else
                                    $qry_res = 2;
                            }else
                                $qry_res = 0;
                        }else{
                            $sql = "UPDATE serviciosyreservaciones_detalle 
                                    SET 
                                        estado = 0, 
                                        status = 0 
                                    WHERE id = $id17";
                            $sql2 = "UPDATE serviciosyreservaciones_cabeceras SET reagenda_hosp = 0 WHERE id = $folio_cabecera";
                            $sql3 = "   INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) 
                                        VALUES ($folio_cabecera, 2, $colaborador_id, '$hoy', 22, 'El líder rechazó reagendar el Hospedaje')";
                            if($this->db->query($sql)){
                                if($this->db->query($sql2)){
                                    if($this->db->query($sql3)){
                                        $to_sql = " SELECT c.email 
                                                    FROM 
                                                        Colaboradores c LEFT JOIN 
                                                        serviciosyreservaciones_cabeceras t ON c.id_colaborador = t.id_colaborador 
                                                    WHERE t.id = $folio_cabecera";
                                        $to_qr = $this->db->query($to_sql)->getResult();
                                        if(sizeof($to_qr) > 0)
                                            $to = $to_qr[0]->email;
                                        else
                                            $to = '';
                                        $subject = "El líder rechazó reagendar tu solicitud de servicios y reservaciones";
                                        $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                Buen día, tu líder rechazó reagendar el hospedaje de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                        </html>";
                                        $message = $mensaje;
                                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                        //$headers .= "CC:" . $concopia;
                                        //echo $to.$subject.$message.$headers;

                                        try{
                                            mail($to,$subject,$message,$headers);
                                            $qry_res = 1;
                                        }catch(\Exception $e){
                                            $qry_res = 4;
                                        }
                                    }else
                                        $qry_res = 3;
                                }else
                                    $qry_res = 2;
                            }else
                                $qry_res = 0;
                        }
                        break;
                        
                    /*case 3 :
                        //contraloria
                        //puede rechazar o aceptar
                        //si rechaza la de estado 17 se convierte en 0 con status 0 y se avisa al colaborador
                        //si acepta se avisa al colaborador, cambia a etapa 0, se le pone estado 14 al detalle anterior y al detalle en estado 17 se le pone 1, cambiar estado de la cabecera a cotizando
                        //se inserta comentario
                        if($respuesta==1){
                            $sql="update serviciosyreservaciones_detalle set estado=1 where id=$id17";
                            $sql2="update serviciosyreservaciones_cabeceras set reagenda_hosp=0, estado=16 where id=$folio_cabecera";
                            $sql3="update serviciosyreservaciones_detalle set estado=12 where id=$filio_detalle";
                            $sql4="insert into serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) values ($folio_cabecera, 2, $colaborador_id, '$hoy', 18, 'Contraloría aceptó reagendar el Hospedaje')";
                            
                            if(sqlsrv_query($conn,$sql)){
                                if(sqlsrv_query($conn,$sql2)){
                                    if(sqlsrv_query($conn,$sql3)){
                                        if(sqlsrv_query($conn,$sql4)){
                                            $to_sql="select c.email from Colaboradores c left join serviciosyreservaciones_cabeceras t on c.id_colaborador=t.id_colaborador where t.id=$folio_cabecera";
                                            $to_qr=sqlsrv_query($conn,$to_sql);
                                            $to_row=sqlsrv_fetch_array($to_qr);
                                            $to = $to_row['email'];//lider 
                                            $subject = "Contraloría autorizó reagendar tu solicitud de servicios y reservaciones";
                                            $mensaje = "<html lang='es'>
                                                <head>
                                                    <meta charset='UTF-8'>
                                                    <title>Titutlo</title>
                                                </head>
                                                <body>
                                                    <table>
                                                        <tr>
                                                            <td style='width:50px;'></td>
                                                            <td style='width:600px;'>
                                                                <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                                <hr>
                                                                <br>
                                                                <p style='font-family:Helvetica;'><br>
                                                                    Buen día, contraloría autorizó reagendar el hospedaje de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                                </p>
                                                                <p style=' font-family:Helvetica;'>
                                                                    <br><br>
                                                                    Saludos.
                                                                </p>
                                                                <br><br><br>
                                                                <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                    Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                            </td>
                                                            <td style='width:50px;'></td>
                                                        </tr>
                                                    </table>
                                                </body>
                                            </html>";
                                            $message = $mensaje;
                                            $headers  = 'MIME-Version: 1.0' . "\r\n";
                                            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                            $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                            //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                            //$headers .= "CC:" . $concopia;
                                            //echo $to.$subject.$message.$headers;
                                            if(mail($to,$subject,$message,$headers)){
                                                $qry_res=1;
                                            }else{
                                                $qry_res=4;
                                            }
                                        }else{
                                            $qry_res=4;
                                        }
                                        $sql_0="update serviciosyreservaciones_cotizaciones set estado=1 where id_cabecera=$folio_cabecera and tipo_solicitud=2 and estado in (1,2)";
                                        $qry_0=sqlsrv_query($conn,$sql_0);
                                    }else{
                                        $qry_res=3;
                                    }
                                }else{
                                    $qry_res=2;
                                }
                            }else{
                                $qry_res=0;
                            }
                        }else{
                            $sql="update serviciosyreservaciones_detalle set estado=0, status=0 where id=$id17";
                            $sql2="update serviciosyreservaciones_cabeceras set reagenda_hosp=0 where id=$folio_cabecera";
                            $sql3="insert into serviciosyreservaciones_comentarios (id_cabecera, tipo_solicitud, id_creador, fecha, tipo_modificacion, comentario) values ($folio_cabecera, 2, $colaborador_id, '$hoy', 18, 'Contraloría rechazó reagendar el Hospedaje')";
                            if(sqlsrv_query($conn,$sql)){
                                if(sqlsrv_query($conn,$sql2)){
                                    if(sqlsrv_query($conn,$sql3)){
                                        $to_sql="select c.email from Colaboradores c left join serviciosyreservaciones_cabeceras t on c.id_colaborador=t.id_colaborador where t.id=$folio_cabecera";
                                        $to_qr=sqlsrv_query($conn,$to_sql);
                                        $to_row=sqlsrv_fetch_array($to_qr);
                                        $to = $to_row['email']; 
                                        $subject = "Contraloría rechazó reagendar tu solicitud de servicios y reservaciones";
                                        $mensaje = "<html lang='es'>
                                            <head>
                                                <meta charset='UTF-8'>
                                                <title>Titutlo</title>
                                            </head>
                                            <body>
                                                <table>
                                                    <tr>
                                                        <td style='width:50px;'></td>
                                                        <td style='width:600px;'>
                                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                                            <hr>
                                                            <br>
                                                            <p style='font-family:Helvetica;'><br>
                                                                Buen día, contraloría rechazó reagendar el hospedaje de la <b>solicitud #".$folio_cabecera."</b>.<br>
                                                            </p>
                                                            <p style=' font-family:Helvetica;'>
                                                                <br><br>
                                                                Saludos.
                                                            </p>
                                                            <br><br><br>
                                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                                        </td>
                                                        <td style='width:50px;'></td>
                                                    </tr>
                                                </table>
                                            </body>
                                        </html>";
                                        $message = $mensaje;
                                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                                        //$headers .= "CC:" . $concopia;
                                        //echo $to.$subject.$message.$headers;
                                        if(mail($to,$subject,$message,$headers)){
                                            $qry_res=1;
                                        }else{
                                            $qry_res=4;
                                        }
                                    }else{
                                        $qry_res=3;
                                    }
                                }else{
                                    $qry_res=2;
                                }
                            }else{
                                $qry_res=0;
                            }
                        }
                        break;*/
                }

                
                return $this->response->setStatusCode(200)->setJSON($qry_res);
            
            case 1001: // Cancelar solicitud

                $comentario = $json->comentario;
                $id_cabecera = $json->id_cabecera;
                $colaborador_id = $json->colaborador_id;
                
                $sql = "UPDATE serviciosyreservaciones_cabeceras SET estado = 0 WHERE id = $id_cabecera ";
                if($this->db->query($sql)){
                    $sql1 = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                                VALUES ($id_cabecera, $colaborador_id, '$hoy', 0, 0, '$comentario')";
                    if($this->db->query($sql1)) $qry_res = 1;
                    else $qry_res = 2;
                }else $qry_res = 0;

                return $this->response->setStatusCode(200)->setJSON($qry_res);
            case 1002: // Solicitar cambio de nombre

                $id_cabecera = $json->id_cabecera;
                $solicitante_id = $json->solicitante_id;
                $edo = $json->edo;
                $nuevo_solic = $json->nuevo_solic;
                $comentario = $json->comentario;
                $colaborador_id = $json->colaborador_id;
                $lider_id = $json->lider_id;
                
                $sql = "INSERT INTO serviciosyreservaciones_comentarios (id_cabecera, id_creador, fecha, tipo_modificacion, nuevo_estado, comentario) 
                        VALUES ($id_cabecera, $colaborador_id, '$hoy', 15, $edo, '$comentario')";//estado actual en edo

                $estado_solicnv = $lider_id == $colaborador_id ? 2 : 1;
                $sql2 = "UPDATE serviciosyreservaciones_cabeceras 
                            SET 
                                solicitante_nuevo = $nuevo_solic, 
                                estado_solicnv = $estado_solicnv 
                            WHERE 
                                id = $id_cabecera AND 
                                id_colaborador = $solicitante_id";
                
                $to = '';

                if($this->db->query($sql)){
                    if($this->db->query($sql2)){
                        //$resultado=1;
                        if($lider_id == $colaborador_id){//el lider lo solicita
                            //encontrar cuál AUO autorizara
                            $to = $this->SR_DATA_contraloria($id_cabecera)[0];
                            //$to = "acastro@ecn.com.mx";//contralor    
                        }else{
                            $to_sql = "SELECT 
                                        (SELECT email FROM Colaboradores WHERE id_colaborador = $lider_id) AS email_lider, 
                                        (SELECT apellido_p+' '+apellido_m+' '+nombres AS nombre FROM Colaboradores WHERE id_colaborador = $solicitante_id) AS solic_original, 
                                        (SELECT apellido_p+' '+apellido_m+' '+nombres AS nombre FROM Colaboradores WHERE id_colaborador = $nuevo_solic) AS solic_nvo";
                            $to_qr = $this->db->query($to_sql);
                            if($to_qr){
                                $to_row = $to_qr->getResult()[0];
                                $to = $to_row->email_lider;//lider
                            }
                        }
                        $subject = "Se requiere su autorización para cambio de nombre en solicitud de servicios y reservaciones";
                        $mensaje = "<html lang='es'>
                            <head>
                                <meta charset='UTF-8'>
                                <title>Titutlo</title>
                            </head>
                            <body>
                                <table>
                                    <tr>
                                        <td style='width:50px;'></td>
                                        <td style='width:600px;'>
                                            <img border=0 src='http://".$_SERVER['HTTP_HOST']."/images/logos/ecn3.png' alt='ecn' style='width:165px; height:auto;'/>
                                            <hr>
                                            <br>
                                            <p style='font-family:Helvetica;'><br>
                                                Buen día, se pide su autorización para cambiar de nombre la <b>solicitud #".$id_cabecera."</b> de ".$to_row->solic_original." a ".$to_row->solic_nvo."<br>
                                                Por favor, atiéndelo lo antes posible desde el módulo 'Servicios y reservaciones' de Intranet.<br>
                                            </p>
                                            <p style=' font-family:Helvetica;'>
                                                <br><br>
                                                Saludos.
                                            </p>
                                            <br><br><br>
                                            <p style=' font-family:Helvetica; color:#848484; font-size:0.7em;'>©".date('Y')." ecn.com.mx. Todos los derechos reservados ECN.<br>
                                                Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</p>
                                        </td>
                                        <td style='width:50px;'></td>
                                    </tr>
                                </table>
                            </body>
                        </html>";
                        
                        $message = $mensaje;
                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                        $headers .= "From:" . "intranet@ecnautomation.com" . "\r\n";
                        //$headers .= "Bcc:" . "nsantacruz@ecn.com.mx" . "\r\n";
                        //$headers .= "CC:" . $concopia;
                        //echo $to.$subject.$message.$headers;

                        try{
                            mail($to,$subject,$message,$headers);
                            $resultado = 1;
                        }catch(\Exception $e){
                            $resultado = 3;
                        }
                        
                    }else $resultado = 2;    
                    
                }else
                    $resultado = 0;
                

                return $this->response->setStatusCode(200)->setJSON($resultado);
            default:
                return $this->response->setStatusCode(200)->setJSON(0);
        }
    }
}
?>