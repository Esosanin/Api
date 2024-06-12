<?php

namespace App\Controllers;

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SoapClient;
use SoapFault;

class Servicios extends BaseController{

    private $db;
    //private $sapServer;

    public function __construct(){
        $this->db = db_connect();
        //$this->sapServer = db_connect('sapServer');
    }

    public function mes($mes){
        switch($mes){
            case '01' : $mes="Enero"; break;
            case '02' : $mes="Febrero"; break;
            case '03' : $mes="Marzo"; break;
            case '04' : $mes="Abril"; break;
            case '05' : $mes="Mayo"; break;
            case '06' : $mes="Junio"; break;
            case '07' : $mes="Julio"; break;
            case '08' : $mes="Agosto"; break;
            case '09' : $mes="Septiembre"; break;
            case '10' : $mes="Octubre"; break;
            case '11' : $mes="Noviembre"; break;
            case '12' : $mes="Diciembre"; break;
        }
        return $mes;
    }

    // CONEXIÓN A SAP SERVER
    public function Reporteservicio_consulta(){
        $json = $this->request->getJSON();

        $FI = $json->FI ? date('Ymd',strtotime($json->FI)) : 0;
        $FF = $json->FF ? date('Ymd',strtotime($json->FF)) : 0;
        
        if(empty($this->db->connID))
            $this->db->initialize();

        // { CALL sp_get_knocker_indicadores_20180727 ('$FI', '$FF') } Pt. 1
        $queryDiff = "SELECT 
                        (
                            (DATEDIFF(dd, '$FI', '$FF') + 1) - 
                            (DATEDIFF(wk, '$FI', '$FF') * 2) - 
                            (CASE WHEN DATENAME(dw, '$FI') = 'Sunday' THEN 1 ELSE 0 END) - 
                            (CASE WHEN DATENAME(dw, '$FF') = 'Saturday' THEN 1 ELSE 0 END)
                        ) AS DIFF";
        $resultadoDiff = $this->db->query($queryDiff)->getResult();
        $diff = $resultadoDiff[0]->DIFF;

        // { CALL sp_get_knocker_indicadores_20180727 ('$FI', '$FF') } Pt. 2
        $queryColab = " SELECT 
                            T0.id_colaborador,
                            T0.nombres+' '+T0.apellido_p AS nombre,
                            T5.codigo_region AS zona,
                            CONVERT(numeric(10,3),T1.avg) AS avg,
							CONVERT(numeric(10,3),T2.metrica) AS metrica,
                            CONVERT(numeric(10,3),T1.te) AS te
                        FROM 
                            Colaboradores T0 LEFT JOIN 
                            (	SELECT
                                    1 tipo,
                                    T0.id_colaborador,
                                    T0.nombre,
                                    AVG(T0.avg) AS avg,
                                    AVG(T0.te) AS te
                                FROM
                                    (
                                        SELECT 
                                            T0.id_colaborador,
                                            t0.nombre,
                                            t0.PrjId,
                                            T1.avg,
                                            CAST(T1.te AS FLOAT) te,
                                            T1.PrjCode
                                        FROM 
                                            (
                                                SELECT 
                                                    T0.*,
                                                    T1.*
                                                FROM
                                                (
                                                    SELECT 
                                                        T0.id_colaborador,
                                                        T0.nombres+' '+T0.apellido_p AS nombre
                                                    FROM Colaboradores T0
                                                    WHERE
                                                        (
                                                            T0.id_departamentos = 8 OR 
                                                            T0.id_departamentos = 18) AND
                                                        T0.estado = 1
                                                ) T0 LEFT JOIN
                                                (
                                                    SELECT 
                                                        T1.PrjId,
                                                        T0.IdResource
                                                    FROM
                                                        tbl_knocker_assignments T0 LEFT JOIN
                                                        tbl_knocker_tasks t1 ON t0.IdTask=t1.IdTask LEFT JOIN
                                                        tbl_knocker_projects t2 ON t1.PrjId=t2.id_project
                                                    WHERE 
                                                        T0.Status=1 AND 
                                                        T1.Status=1 AND
                                                        t2.status=1 AND
                                                        t2.PrjCode LIKE 'S-%'
                                                    GROUP BY T1.PrjId,T0.IdResource
                                                ) T1 ON T0.id_colaborador=T1.IdResource
                                            ) T0 INNER JOIN
                                            (
                                                SELECT 
                                                    T1.id_ot,
                                                    T1.id_project,
                                                    COALESCE(T0.avg,10) AS avg,
                                                    t2.PrjCode,
                                                CASE    
                                                    WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                                    WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                                    WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                                    WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                                    WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                                    WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                                    ELSE 1
                                                END AS te
                                                FROM  
                                                    tbl_knocker_work_orders T1 LEFT JOIN
                                                    ( 
                                                        SELECT DISTINCT 
                                                            id_ot,
                                                            AVG(CAST(ID_RESPONSE AS DECIMAL)) AS avg 
                                                        FROM [tbl_knocker_wo_survey] 
                                                        WHERE status = 1 
                                                        GROUP BY id_ot
                                                    ) T0 ON T1.id_ot = T0.id_ot LEFT JOIN
                                                    tbl_knocker_projects T2 ON T2.id_project = t1.id_project INNER JOIN
                                                    tbl_knocker_wo_facturacion T3 ON t1.id_ot = t3.id_ot
                                                WHERE
                                                    T1.status = 1 AND
                                                    t2.status = 1 AND
                                                    t2.PrjCode LIKE 'S-%' AND
                                                    t3.create_date >= '$FI' AND
                                                    t3.create_date <= '$FF' AND
                                                    t3.status = 1
                                            ) T1 ON T0.PrjId = T1.id_project
                                    ) T0
                                WHERE t0.avg <> 10
                                GROUP BY T0.id_colaborador,T0.nombre
                            ) T1 ON T0.id_colaborador=T1.id_colaborador LEFT JOIN
                            (
                                SELECT 
                                    2 AS tipo,
                                    T0.idResource, 
                                    t1.nombres+' '+t1.apellido_p AS colaborador,
                                CASE 
                                    WHEN SUM(t0.effort_horas) >= ((($diff)*9)*0.7) THEN 5
                                    ELSE   ( cast( SUM(T0.effort_horas) AS DECIMAL)/ ((($diff)*9)*0.7)  )*5
                                END AS metrica
                                FROM
                                    tbl_knocker_tasks_effort T0 LEFT JOIN
                                    Colaboradores t1 ON t0.idResource=t1.id_colaborador LEFT JOIN
                                    tbl_knocker_tasks t2 ON t0.idTask=t2.IdTask LEFT JOIN
                                    tbl_knocker_projects t3 ON t2.PrjId=t3.id_project
                                WHERE
                                    T0.status=1 AND
                                    (
                                        T1.id_departamentos = 8 OR 
                                        T1.id_departamentos = 18
                                    ) AND
                                    T1.estado=1 AND
                                    T0.effortDate >= '$FI' AND
                                    T0.effortDate <= '$FF'  AND
                                    t3.PrjCode NOT IN ('s-01','s-02')
                                GROUP BY T0.idResource, t1.nombres+' '+t1.apellido_p
                            ) T2 ON T0.id_colaborador = T2.idResource LEFT JOIN
                            Regiones T5 ON T0.id_region = T5.id_region
                        WHERE
                            (
                                T0.id_departamentos = 8 OR 
                                T0.id_departamentos = 18
                            ) AND
                            T0.estado = 1
                        ORDER BY id_colaborador";
        $resultColab = sqlsrv_query($this->db->connID, $queryColab);

        $queryServicios1 = "SELECT
                                t0.id_ot,
                                T1.PrjCode,
                                T2.PRJNAME,
                                T4.GRPCODE AS zona,
                                CASE t0.ot_estado
                                    WHEN 1 THEN 'En espera'
                                    WHEN 0 THEN 'Cancelado'
                                    WHEN 2 THEN 'Ejecutado'
                                    WHEN 3 THEN 'Terminado'
                                    WHEN 4 THEN 'Finalizado'
                                    WHEN 5 THEN 'Facturado parcial'
                                END AS estado,
                                FORMAT(T3.fecha_compromiso, 'dd/MM/yyyy') AS FC,
                                FORMAT(T3.fecha_terminacion, 'dd/MM/yyyy') AS FT,
                                FORMAT(t3.create_date, 'dd/MM/yyyy') AS CD,
                                t3.id_facturacion,
                                IIF(COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000, 'Sin referencia', COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)) AS DIF,
                                CASE
                                    WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                    WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                    WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                    WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                    WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                    WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                    ELSE 1
                                END AS metrica
                            FROM 
                                tbl_knocker_work_orders T0 
                                LEFT JOIN tbl_knocker_projects T1 ON T0.id_project=T1.id_project 
                                --LEFT JOIN SYN_OPRJ T2 ON T1.PrjCode=T2.PRJCODE COLLATE DATABASE_DEFAULT 
                                LEFT JOIN [SBO_ECN].[dbo].[OPRJ] T2 ON T1.PrjCode=T2.PRJCODE COLLATE DATABASE_DEFAULT 
                                INNER JOIN tbl_knocker_wo_facturacion T3 ON t0.id_ot=t3.id_ot 
                                --LEFT JOIN SYN_OPRC T4 ON T2.U_ZONA=T4.PRCCODE
                                LEFT JOIN [SBO_ECN].[dbo].[OPRC] T4 ON T2.U_ZONA=T4.PRCCODE
                            WHERE
                                T0.status=1 AND
                            --	(T0.ot_estado=3 OR T0.ot_estado=4 OR T0.ot_estado=5) AND
                                T3.status=1 AND
                                T1.status=1 AND
                                T3.create_date >= '$FI' AND
                                T3.create_date <= '$FF'
                            ORDER BY t3.id_facturacion;";
        $resultServicios1 = sqlsrv_query($this->db->connID, $queryServicios1);
        
        $queryServicios2 = "SELECT 
                                T0.PrjCode,
                                T0.PRJNAME,
                                T0.zona,
                                CONVERT(numeric(10,3),AVG(T0.avg)) AS AVG
                            FROM
                                (
                                    SELECT 
                                        t2.PrjCode,
                                        T3.PrjName,
                                        T4.GRPCODE as zona,
                                        COALESCE(T0.avg,10) AS avg
                                    FROM  
                                        tbl_knocker_work_orders T1 
                                    LEFT JOIN ( SELECT distinct id_ot,AVG(cast(ID_RESPONSE AS DECIMAL)) as avg FROM [tbl_knocker_wo_survey] WHERE status=1 GROUP BY id_ot) T0 ON T1.id_ot=T0.id_ot 
                                    LEFT JOIN tbl_knocker_projects T2 ON T2.id_project=t1.id_project 
                                    --LEFT JOIN SYN_OPRJ T3 ON T2.PrjCode=T3.PrjCode COLLATE DATABASE_DEFAULT 
                                    LEFT JOIN [SBO_ECN].[dbo].[OPRJ] T3 ON T2.PrjCode=T3.PrjCode COLLATE DATABASE_DEFAULT 
                                    --LEFT JOIN SYN_OPRC T4 ON T3.U_ZONA=T4.PRCCODE 
                                    LEFT JOIN [SBO_ECN].[dbo].[OPRC] T4 ON T3.U_ZONA=T4.PRCCODE 
                                    INNER JOIN tbl_knocker_wo_facturacion t5 ON t1.id_ot=t5.id_ot
                                    WHERE
                                        T1.status=1 AND
                                        t2.PrjCode LIKE 'S-%' AND
                                        t2.status=1 AND 
                                        T5.status=1 AND
                                        T5.fecha_terminacion >= '$FI' AND
                                        T5.fecha_terminacion <= '$FF'
                                ) T0
                            GROUP BY T0.PrjCode,T0.PRJNAME,T0.zona
                            ORDER BY PrjCode;";
        $resultServicios2 = sqlsrv_query($this->db->connID, $queryServicios2);

        $colab = array();
        while($row = sqlsrv_fetch_array($resultColab)){
            $nombre = $row['nombre'];
            $zona = $row['zona'];
            $avg = $row['avg'];
            $metrica = $row['metrica'];
            $te = $row['te'];

            array_push($colab, array(
                'nombre' => $nombre,
                'zona' => $zona,
                'avg' => $zona,
                'avg' => $avg,
                'metrica' => $metrica,
                'te' => $te
            ));
        }
        $serv1 = array();
        while($row = sqlsrv_fetch_array($resultServicios1)){
            $proyecto = $row['PrjCode'];
            $nombre = $row['PRJNAME'];
            $zona = $row['zona'];
            $FC = $row['FC'];
            $FT = $row['FT'];
            $dif = $row['DIF'];
            $metrica = $row['metrica'];

            array_push($serv1, array(
                'PrjCode' => $proyecto,
                'PRJNAME' => $nombre,
                'zona' => $zona,
                'fecha_compromiso' => $FC,
                'fecha_terminacion' => $FT,
                'DIF' => $dif,
                'metrica' => $metrica
            ));
        }
        $serv2 = array();
        while($row = sqlsrv_fetch_array($resultServicios2)){
            $proyecto = $row['PrjCode'];
            $nombre = $row['PRJNAME'];
            $zona = $row['zona'];
            $AVG = $row['AVG'];

            array_push($serv2, array(
                'PrjCode' => $proyecto,
                'PRJNAME' => $nombre,
                'zona' => $zona,
                'AVG' => $AVG
            ));
        }

        return $this->response->setStatusCode(200)->setJSON(array($colab, $serv1, $serv2));
    }

    public function Embudo_getEmbudo(){
        if(empty($this->db->connID))
            $this->db->initialize();

        $json = $this->request->getJSON();
        $user = $json->user;
        $id_sap = $json->id_sap ? $json->id_sap : '';
        $search = $json->search ? $json->search : '';
        
        $queryZona = "  SELECT
                            DISTINCT(t3.codigo_region) AS region,
                            gerente_id
                        FROM [plataformaecn1].[dbo].[tbl_pt_gerentes_sucursales] 
                        LEFT JOIN Colaboradores t1 ON gerente_id=t1.id_colaborador
                        LEFT JOIN Sucursales t2 ON sucursal_id=t2.id_sucursal
                        LEFT JOIN Regiones t3 ON t2.region=t3.id_region
                        WHERE gerente_id=$user";
        $resultZona = $this->db->query($queryZona)->getResult();
        $zona = array();

        if($resultZona){
            for ($i=0; $i < sizeof($resultZona); $i++) { 
                $region = "'".$resultZona[$i]->region."'";
                array_push($zona, $region);
            }
            $zona = implode(',', $zona);
            $permiso = ", IIF(zona IN ($zona), 1, 0) AS permiso";
        }else
            $permiso = ", 0 AS permiso";
        
        
        $datas = [];
        $queryCotizaciones = "  SELECT
                                    C.FechaCont
                                    ,FORMAT(ISNULL(C.FechaCont, ''), 'dd/MM/yyyy') AS FechaCont_n
			                        ,C.DocEntry
                                    ,C.DocNum
                                    ,C.Cliente
                                    ,C.nvendedor
                                    ,C.vendedor
                                    ,ISNULL(C.zona, '') AS zona
                                    ,c.oficina
                                    ,SUM(C.TOTPARTITEM) AS TOTAL
                                    ,FORMAT(SUM(C.TOTPARTITEM), 'C') AS TOTAL_n
                                    ,ISNULL(C.Referencia, '') AS Referencia 
                                    ,[C].[% Cierre] AS porc_cierre
                                    ,CASE WHEN ISNULL(C.U_FECHACIERRE, '') = '' THEN 'Sin capturar' ELSE FORMAT(C.U_FECHACIERRE, 'yyyy-MM-dd') END AS U_FECHACIERRE
                                    ,CASE WHEN ISNULL(C.U_FECHACIERRE, '') = '' THEN 'Sin capturar' ELSE FORMAT(C.U_FECHACIERRE, 'dd/MM/yyyy') END AS U_FECHACIERRE_n
                                    ,ISNULL(C.Comments, '') AS Comments
                                    ,C.Etapa
                                    ,C.etapacodigo
                                    ,ISNULL(C.industria, '') AS industria
			                        ,ISNULL(U_BitrixID,0) AS U_BitrixID
                                    ,ISNULL(DATEDIFF(day,U_FECHACIERRE,getdate()), '') AS diff
                                    $permiso
                                FROM CotizacionesTest C 
                                WHERE 
                                    (
                                        C.DocNum LIKE ('%$search%') OR
                                        C.Cliente LIKE ('%$search%') OR
                                        C.FechaCont LIKE ('%$search%') OR
                                        FORMAT(ISNULL(C.FechaCont, ''), 'dd/MM/yyyy') LIKE ('%$search%')
                                    )
                                GROUP BY DOCENTRY,C.Etapa,C.industria,C.etapacodigo, C.FechaCont,C.DocNum,C.Cliente,C.Referencia, [C].[% Cierre],C.U_FECHACIERRE,C.Comments,C.nvendedor,C.vendedor,C.zona,C.U_BitrixID,C.oficina
                                ORDER BY C.U_FECHACIERRE;";
        $result = sqlsrv_query($this->db->connID, $queryCotizaciones);

        while($row = sqlsrv_fetch_array($result)){
            $FechaCont = $row['FechaCont'];
            $FechaCont_n = $row['FechaCont_n'];
            $DocEntry = $row['DocEntry'];
            $DocNum = $row['DocNum'];
            $Cliente = $row['Cliente'];
            $nvendedor = $row['nvendedor'];

            $vendedor = $row['vendedor'];
            $zona = $row['zona'];
            $oficina = $row['oficina'];
            $TOTAL = $row['TOTAL'];
            $TOTAL_n = $row['TOTAL_n'];
            $Referencia = $row['Referencia'];

            $porc_cierre = $row['porc_cierre'];
            $U_FECHACIERRE = $row['U_FECHACIERRE'];
            $U_FECHACIERRE_n = $row['U_FECHACIERRE_n'];
            $Comments = $row['Comments'];
            $Etapa = $row['Etapa'];
            $etapacodigo = $row['etapacodigo'];

            $industria = $row['industria'];
            $U_BitrixID = $row['U_BitrixID'];
            $diff = $row['diff'];
            $permiso = $row['permiso'];

            // SAP
            if ($nvendedor==$id_sap 
                || $user==1069 //nidia santacruz (todo)
                || $user==1546
                || $user==63 //Heriberto	Ayala Ruiz (todo)
                || $user==305 //Julio Cesar Alvarez Mendoza (todo)
                || $user==65 //Jesús Octavio	Delgado	Ochoa (todo)
                || $user==60 //Héctor Gerardo Huerta	Orozco (todo)
                || $user==1224 //Javier Miroz Lozano  (todo)
                || $user==54 //Martin Rodolfo Padilla Maldonado (todo)
                || $user==49 //Álvaro Rendón Montoya (todo)
                || $user==82 //Jesús Omar Arriquidez	Barrios (todo)
                || $user==43 //Jesús Said Rodríguez Padilla (ind)
                || $user==53 //Albert Josef Meyer Jorg (ind)
                || $user==135 //Luis Alberto Sánchez	Castro (todo)
                || $user==120 //Omar Guillermo Fimbres Munguía (ind)
                || $user==51 //José Ernesto Samaniego Ruiz (todo)
                || $user==182 //Víctor Alonso Álvarez Pérez (MTY-NE)
                || $user==132 //Nelson Everardo Cantú Alvarado (MTY-NE)
                || $user==145 //Humberto Alonso  Castañón Rodríguez (MTY-NE)
                || $user==36 //Dorian Uriel Cardiel Álvarez (LAG)
                || $user==58 //Daniel Alberto Félix Morales (LAG)
                || $user==150 //Eduardo Edilberto Mendoza Chávez (LAG)
                || $user==35 //Pablo Ortiz López (BAJ)
                || $user==80 //Daniel Peñuelas Rodríguez (BAJ)
                || $user==1429 //Rodrigo Montoya Flores (QRO)
                || $user==1039 //Javier  Salinas Valdes (CDMX)
                || $user==301 //Jorge Armando  Preciado  Lomeli (CDMX/OCC)
                || $user==64 //José Vicente Hernández Mena (OCC)
                || $user==137 //Ramsés Tadeo Zazueta Ezrré (NOE)
                || $user==267 //Carlos Armando Beltran  Moroyoqui (NOE)
                || $user==1434 //Oscar Alfredo Duran  Sanchez  (NOE)
                || $user==223 //Sergio Caballero Balderrama (todo)
                || $user==1416 //Hector Alcantara Roldan  (todo)
                || $user==52 //Eduardo Albino Barrón Mesta  (ind)
                || $user==945 //Martín Ivan Rodriguez Rosas  (DCU)
                || $user==309 //Cisneros 	Simental 	Eleazar  (NOE)
                || $user==79 //Peña	Del Castillo	Raymundo (CORP)
                || $user==1427 //Aurora Morales
                || $user==625 //Martin Aguirre (CHIH)
                //|| $user_id==1459 //Martinez Garcia Luis Raul  (QRO)
                || $permiso==1 ) {
                //$table.="<td><div class='btn-group'><button class='btn btn-outline btn-success' onclick='VerInfo({$DocNum});'><i class='fa fa-search'></i></button> <button class='btn btn-outline btn-info' onclick='Editar({$DocEntry});'><i class='fa fa-pencil-alt'></i></button></div></td>";
                $btn_verinfo = 1;
            }else{
                //$table.="<td><button class='btn btn-outline btn-success' onclick='VerInfo({$DocNum});'><i class='fa fa-search'></i></button></td>";
                $btn_verinfo = 0;
            }

            $datas[] = ([
                "DocNum" => $DocNum,
                "DocEntry" => $DocEntry,
                "FechaCont" => $FechaCont,
                "FechaCont_n" => $FechaCont_n,
                "Cliente" => $Cliente,
                "TOTAL" => $TOTAL,
                "TOTAL_n" => $TOTAL_n,
                "Referencia" => $Referencia,
                "porc_cierre"=> $porc_cierre,
                "vendedor" => $vendedor,
                "zona" => $zona,
                "oficina" => $oficina,
                "industria" => $industria,
                "U_FECHACIERRE" => $U_FECHACIERRE,
                "U_FECHACIERRE_n" => $U_FECHACIERRE_n,
                "comments" => $Comments,
                "Etapa" => $Etapa,
                "etapacodigo" => $etapacodigo,
                "verinfo" => $btn_verinfo,
                "U_BitrixID" => $U_BitrixID,
                "diff" => $diff
            ]);
        }

        return $this->response->setStatusCode(200)->setJSON($datas);
    }

    public function Embudo_getCotizacion(){
        $json = $this->request->getJSON();
        $query = "  SELECT  
                        Etapa,
                        etapacodigo,
                        ISNULL(FechaCont, '') AS FechaCont,
                        FORMAT(ISNULL(FechaCont, ''), 'dd/MM/yyyy') AS FechaCont_n,
                        Cliente,
                        [Subtotal USD] AS subtotal,
                        FORMAT([Subtotal USD], 'C') AS subtotal_n,
                        [% Cierre] as porc_cierre,
                        CASE WHEN ISNULL(U_FECHACIERRE, '') = '' THEN '' ELSE FORMAT(U_FECHACIERRE, 'yyyy-MM-dd') END AS U_FECHACIERRE,
                        CASE WHEN ISNULL(U_FECHACIERRE, '') = '' THEN 'Sin capturar' ELSE '' END AS U_FECHACIERRE_n,
                        ISNULL(Comments, '') AS Comments,
                        ISNULL(zona, '') AS zona,
                        oficina,
                        ISNULL(industria, '') AS industria,
                        Descripcion,
                        Cantidad,
                        TotPartida,
                        FORMAT(TotPartida, 'C') AS TotPartida_n,
                        ISNULL(Referencia, '') AS Referencia 
                    FROM CotizacionesTest 
                    WHERE DocNum=$json->docnum;";
        $result = $this->db->query($query)->getResult();

        $encabezado = array();

        $partida = array();
        for ($i=0; $i < sizeof($result); $i++) { 
            if($i == 0){
                $encabezado = array(
                    "Cliente" => $result[$i]->Cliente,
                    "Etapa" => $result[$i]->Etapa,
                    "etapacodigo" => $result[$i]->etapacodigo,
                    "FechaCont" => $result[$i]->FechaCont,
                    "FechaCont_n" => $result[$i]->FechaCont_n,
                    "subtotal" => $result[$i]->subtotal,
                    "subtotal_n" => $result[$i]->subtotal_n,
                    "porc_cierre" => $result[$i]->porc_cierre,
                    "U_FECHACIERRE" => $result[$i]->U_FECHACIERRE,
                    "U_FECHACIERRE_n" => $result[$i]->U_FECHACIERRE_n,
                    "zona" => $result[$i]->zona,
                    "oficina" => $result[$i]->oficina,
                    "industria" => $result[$i]->industria,
                    "comments" => $result[$i]->Comments
                );
            }

            $partida[] = array(
                "Descripcion" => $result[$i]->Descripcion,
                "Cantidad" => $result[$i]->Cantidad,
                "TotPartida" => $result[$i]->TotPartida,
                "TotPartida_n" => $result[$i]->TotPartida_n,
                "Referencia" => $result[$i]->Referencia
            );


        }
        

        return $this->response->setStatusCode(200)->setJSON(array($encabezado, $partida));
    }

    // CONEXIÓN A SAP SERVER 
    public function Embudo_getSelectBitrix(){
        $query="SELECT T0.CODE,
                    CASE T0.NAME 
                        WHEN  'Cerrada Ganada' THEN 'GANADA'
                        WHEN  'Cerrada Perdida' THEN 'PERDIDA'
                        ELSE T0.NAME
                    END AS NAME,
                    U_BitrixID
                --FROM SYN_ETAPACOT T0 
                FROM [SBO_ECN].[dbo].[@ETAPACOT] T0
                WHERE 
                    T0.NAME NOT LIKE '*%' AND 
                    code NOT LIKE 'G';";

        $result = $this->db->query($query)->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function Embudo_getCotizacion_docentry(){
        $json = $this->request->getJSON();
        $docentry = $json->docentry;
        $query = "  SELECT 
                        [% Cierre] AS porc_cierre,
                        U_FECHACIERRE, 
                        Comments,
                        etapacodigo,
                        GETDATE() AS U_FECHACIERRE
                    FROM CotizacionesTest 
                    WHERE DocEntry=$docentry";
        $result = $this->db->query($query)->getResult()[0];

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function Embudo_updateQuotation(){
        ini_set("soap.wsdl_cache_enabled", "0");

        $url = "http://sapserver:8080/B1iXcellerator/exec/webdav/com.sap.b1i.vplatform.scenarios.setup/vPac.Z.ECN/0010000101_Z.ECN.wsdl";
        $login = "B1iadmin";
        $password = "Qwe890Asd123";

        $options = array('login' =>$login ,'password' =>$password,'exceptions' => true);
        $client = null;
        
        $SOAP_ = false; // True: Con SOAP ; False: Sin SOAP
        $ws = 1;

        if($SOAP_){
            try{
                $client = new SoapClient($url, $options);

                $ws = 1;
            }catch( SoapFault $e) {
                $ws = 0;
            }
        }

        if($ws == 1){
            
            $json = $this->request->getJSON();
            $doc = isset($json->doc) ? $json->doc : 0;
            $porc_cierre = isset($json->porc_cierre) ? $json->porc_cierre : 0;
            $fecha_cierre = isset($json->fecha_cierre) ? $json->fecha_cierre : 0;
            $comments = isset($json->comments) ? $json->comments : NULL;
            $edo = isset($json->edo) ? $json->edo : NULL;

            $user = isset($json->user) ? $json->user : NULL;
    
            if(is_null($user)) 
                return $this->response->setStatusCode(200)->setJSON(array(0, -1));
    
    
    
            $porcentaje_cierre = isset($json->porc_cierre) ? $json->porc_cierre : 0;
    
            $fecha_cierre2 = str_replace("-", "", $fecha_cierre);
    
            // Actualizar datos
            $params = array('DocEntry'=>$doc,'U_PORCCIERRE'=>$porc_cierre,'U_FECHACIERRE'=>$fecha_cierre2,'Comments'=>$comments, 'U_ETAPACOT'=>$edo);
    
            $params2 = array('SysId' =>'0010000100' ,'Document'=>$params);
            $QuotationUpdateType = array('QuotationUpdateType'=>$params2);
    
            // Cierre de cotización
            $params3 = array('DocEntry'=>$doc);
            $params4 = array('SysId' =>'0010000100' ,'Document'=>$params3);
            $CloseQuotationType = array('CloseQuoteType'=>$params4);
    
    
    
            $query1 = "SELECT TOP 1 * FROM cotizacionesTest WHERE DocEntry=$doc";
            $result1 = $this->db->query($query1);
            if($result1){
                $row = $result1->getResult()[0];
    
                $fecha_cont = date_format(date_create($row->FechaCont), 'Y-m-d');
    
                $fecha_cont2 = strtotime($fecha_cont); // or your date as well
                $fecha_cierre3 = strtotime($fecha_cierre);
                $datediff = $fecha_cierre3 - $fecha_cont2;
                $dias = ($datediff/(60*60*24));
    
                if($dias <= 0)
                    return $this->response->setStatusCode(200)->setJSON(array(2, 0));
    
                else{
                    
                    // { CALL sp_UpdateCotizacionEncabezado_17052016(?,?,?,?,?) }
                    $query2 = " UPDATE CotizacionesTest 
                                SET  
                                    [% Cierre]     = $porc_cierre, 
                                    U_FECHACIERRE  = '$fecha_cierre', 
                                    Comments       = '$comments',
                                    etapacodigo    = '$edo',
                                    CotUpdate      = 1
                                WHERE DocEntry = '$doc'";
    
                    if($this->db->query($query2)){
                        $DocNum = $row->DocNum;
                        $DocEntry = $row->DocEntry;
    
                        // if($edo == 'G' || $edo == 'Z' || $edo == 'R' || $edo == 'A' || $edo == 'O' ){
                        if( $edo == 'Z' || $edo == 'R' || $edo == 'A' || $edo == 'O' ){
                            $query3 = "SELECT DISTINCT DocNum FROM CotizacionesCerradasTest";
                            $return3 = $this->db->query($query3);
    
                            if($return3){
                                $result3 = $return3->getResult();
                                for ($i=0; $i < sizeof($result3); $i++) { 
                                    $row3 = $result3[$i];
                                    if($row3->DocNum == $DocNum)
                                        $band = 1;
                                    else    
                                        $band = 0;
                                }
                            }
    
                            if($band == 0){
                                $query4 = "SELECT TOP 1 * FROM CotizacionesTest WHERE DocNum=$DocNum";
                                $result4 = $this->db->query($query4)->getResult()[0];
    
                                $DocNum = $result4->DocNum;
                                $DocEntry = $result4->DocEntry;
    
                                $soap = false;
                                
                                if($SOAP_){ // Con SOAP
                                    $res = $client->__SoapCall("Z.QuotationUpdate", $QuotationUpdateType);
                                    $soap = is_soap_fault($res);
                                }
                                if($soap)
                                    return $this->response->setStatusCode(200)->setJSON(array(0, 1));

                                else{
                                    /// AQUI TENGO QUE LLAMAR EL METODO PARA CERRAR LA COTIZACION EN SAP ///

                                    if($SOAP_){ // Con SOAP
                                        $res = $client->__SoapCall("Z.CloseQuotation", $CloseQuotationType);
                                        $soap = is_soap_fault($res);
                                    }
                                    if($soap)
                                        return $this->response->setStatusCode(200)->setJSON(array(0, 2));
                                    
                                    else{
                                        unset($client);
                                        unset($res);

                                        $flag5 = false; $flag6 = false;

                                        $query5 = "DELETE FROM CotizacionesTest where DocNum=$DocNum";
                                        if($this->db->query($query5)){
                                            if($edo == "G")
                                                $query6 = " INSERT INTO CotizacionesCerradasTest (DocNum,DocEntry,Estado,Etapa,Comments,Cancelada,porc_cierre) 
                                                            VALUES('$DocNum','$DocEntry','C','$edo','$comments','N',$porcentaje_cierre)";
                                        
                                            else
                                                $query6 = " INSERT INTO CotizacionesCerradasTest (DocNum,DocEntry,Estado,Etapa,Comments,Cancelada,porc_cierre) 
                                                            VALUES('$DocNum','$DocEntry','C','$edo','$comments','Y',$porcentaje_cierre)";

                                            if($this->db->query($query6))
                                                $flag6 = true;

                                        }else
                                            $flag5 = true;
                                        
                                        // insert en la tabla de seguimiento
                                        $query7 = " INSERT INTO tbl_dash_seguimiento (user_id, id_registro, tipo_modificacion) 
                                                    VALUES ($user, $DocNum, 1)";
                                        $this->db->query($query7);

                                        if($flag5)
                                            return $this->response->setStatusCode(200)->setJSON(array(0, 5));

                                        if($flag6)
                                            return $this->response->setStatusCode(200)->setJSON(array(1, 3));
                                        else
                                            return $this->response->setStatusCode(200)->setJSON(array(0, 4));

                                    }
                                }
                            }
                        }else{
								
                            ////// CODIGO PARA CAMBIAR EL ESTADO DE LA COTIZACION PERO DEJARLA ABIERTA

                            $query3 = "SELECT DocNum,Estado FROM CotizacionesAbiertasTest";
                            $return3 = $this->db->query($query3);
    
                            if($return3){
                                $result3 = $return3->getResult();
                                for ($i=0; $i < sizeof($result3); $i++) { 
                                    $row3 = $result3[$i];
                                    if($row3->DocNum == $DocNum && $row3->Estado == $edo)
                                        $band = 1;
                                    else    
                                        $band = 0;
                                }
                            }

                            if($band == 0){
                                $query4 = " INSERT INTO CotizacionesAbiertasTest (DocNum,DocEntry,Estado,Etapa,Comments,Cancelada,porc_cierre) 
                                            VALUES($DocNum,'$DocEntry','O','$edo','$comments','N',$porcentaje_cierre)";

                                $this->db->query($query4);
                            }
    
                            $soap = false;

                            
                            if($SOAP_){ // Con SOAP
                                $res = $client->__SoapCall("Z.QuotationUpdate", $QuotationUpdateType);
                                $soap = is_soap_fault($res);
                            }
                            if($soap)
                                return $this->response->setStatusCode(200)->setJSON(array(0, 6));

                            else{
                                unset($client);
                                unset($res);

                                $query5 = "SELECT TotPartItem,[% Cierre] AS pcierre,LineNum FROM CotizacionesTest WHERE DocNum=$DocNum";
                                $result5 = $this->db->query($query5)->getResult();

                                $query6 = "";

                                for ($i=0; $i < sizeof($result5); $i++) { 
                                    $LineNum = $result5[$i]->LineNum;
                                    $TotPartItem = $result5[$i]->TotPartItem;
                                    $pcierre = ($result5[$i]->pcierre/100);
                                    $MontoPond = ($TotPartItem*$pcierre);
                                    

                                    $query6.="UPDATE CotizacionesTest SET MontoPond=$MontoPond WHERE DocNum=$DocNum and LineNum=$LineNum;";
                                }

                                if($this->db->query($query6)){

                                    // insert en la tabla de seguimiento
                                    $query7 = "INSERT INTO tbl_dash_seguimiento (user_id, id_registro, tipo_modificacion) VALUES ($user,$DocNum,1)";
                                    $this->db->query($query7);

                                    return $this->response->setStatusCode(200)->setJSON(array(1, 7));
                                }else
                                    return $this->response->setStatusCode(200)->setJSON(array(0, 8));

                            }
                        }
                    }else
                        return $this->response->setStatusCode(200)->setJSON(array(0, 9));

                }

            }else
                return $this->response->setStatusCode(200)->setJSON(array(0, 10));
            
        }else
            return $this->response->setStatusCode(200)->setJSON(array(0, 11));
    }

    public function createPDF(){
        if(empty($this->db->connID))
            $this->db->initialize();

        $json = $this->request->getJSON();

        $tipo = $json->tipo;
        $FI = $json->Fi;
        $FF = $json->Ff;
        $search = $json->search;
        $url = $json->url;

        // { CALL sp_get_knocker_indicadores_20180727 ('$FI', '$FF') } Pt. 1
        $queryDiff = "SELECT 
                        (
                            (DATEDIFF(dd, '$FI', '$FF') + 1) - 
                            (DATEDIFF(wk, '$FI', '$FF') * 2) - 
                            (CASE WHEN DATENAME(dw, '$FI') = 'Sunday' THEN 1 ELSE 0 END) - 
                            (CASE WHEN DATENAME(dw, '$FF') = 'Saturday' THEN 1 ELSE 0 END)
                        ) AS DIFF";
        $resultadoDiff = $this->db->query($queryDiff)->getResult();
        $diff = $resultadoDiff[0]->DIFF;

        $html = "";

        switch($tipo){
            case 0: // Colaboradores

                // { CALL sp_get_knocker_indicadores_20180727 ('$FI', '$FF') } Pt. 2
                $queryColab = " SELECT 
                                    T0.id_colaborador,
                                    T0.nombres+' '+T0.apellido_p AS nombre,
                                    T5.codigo_region AS zona,
                                    CONVERT(numeric(10,3),T1.avg) AS avg,
                                    CONVERT(numeric(10,3),T2.metrica) AS metrica,
                                    CONVERT(numeric(10,3),T1.te) AS te
                                FROM 
                                    Colaboradores T0 LEFT JOIN 
                                    (	SELECT
                                            1 tipo,
                                            T0.id_colaborador,
                                            T0.nombre,
                                            AVG(T0.avg) AS avg,
                                            AVG(T0.te) AS te
                                        FROM
                                            (
                                                SELECT 
                                                    T0.id_colaborador,
                                                    t0.nombre,
                                                    t0.PrjId,
                                                    T1.avg,
                                                    CAST(T1.te AS FLOAT) te,
                                                    T1.PrjCode
                                                FROM 
                                                    (
                                                        SELECT 
                                                            T0.*,
                                                            T1.*
                                                        FROM
                                                        (
                                                            SELECT 
                                                                T0.id_colaborador,
                                                                T0.nombres+' '+T0.apellido_p AS nombre
                                                            FROM Colaboradores T0
                                                            WHERE
                                                                (
                                                                    T0.id_departamentos = 8 OR 
                                                                    T0.id_departamentos = 18) AND
                                                                T0.estado = 1
                                                        ) T0 LEFT JOIN
                                                        (
                                                            SELECT 
                                                                T1.PrjId,
                                                                T0.IdResource
                                                            FROM
                                                                tbl_knocker_assignments T0 LEFT JOIN
                                                                tbl_knocker_tasks t1 ON t0.IdTask=t1.IdTask LEFT JOIN
                                                                tbl_knocker_projects t2 ON t1.PrjId=t2.id_project
                                                            WHERE 
                                                                T0.Status=1 AND 
                                                                T1.Status=1 AND
                                                                t2.status=1 AND
                                                                t2.PrjCode LIKE 'S-%'
                                                            GROUP BY T1.PrjId,T0.IdResource
                                                        ) T1 ON T0.id_colaborador=T1.IdResource
                                                    ) T0 INNER JOIN
                                                    (
                                                        SELECT 
                                                            T1.id_ot,
                                                            T1.id_project,
                                                            COALESCE(T0.avg,10) AS avg,
                                                            t2.PrjCode,
                                                        CASE    
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                                            ELSE 1
                                                        END AS te
                                                        FROM  
                                                            tbl_knocker_work_orders T1 LEFT JOIN
                                                            ( 
                                                                SELECT DISTINCT 
                                                                    id_ot,
                                                                    AVG(CAST(ID_RESPONSE AS DECIMAL)) AS avg 
                                                                FROM [tbl_knocker_wo_survey] 
                                                                WHERE status = 1 
                                                                GROUP BY id_ot
                                                            ) T0 ON T1.id_ot = T0.id_ot LEFT JOIN
                                                            tbl_knocker_projects T2 ON T2.id_project = t1.id_project INNER JOIN
                                                            tbl_knocker_wo_facturacion T3 ON t1.id_ot = t3.id_ot
                                                        WHERE
                                                            T1.status = 1 AND
                                                            t2.status = 1 AND
                                                            t2.PrjCode LIKE 'S-%' AND
                                                            t3.create_date >= '$FI' AND
                                                            t3.create_date <= '$FF' AND
                                                            t3.status = 1
                                                    ) T1 ON T0.PrjId = T1.id_project
                                            ) T0
                                        WHERE t0.avg <> 10
                                        GROUP BY T0.id_colaborador,T0.nombre
                                    ) T1 ON T0.id_colaborador=T1.id_colaborador LEFT JOIN
                                    (
                                        SELECT 
                                            2 AS tipo,
                                            T0.idResource, 
                                            t1.nombres+' '+t1.apellido_p AS colaborador,
                                        CASE 
                                            WHEN SUM(t0.effort_horas) >= ((($diff)*9)*0.7) THEN 5
                                            ELSE   ( cast( SUM(T0.effort_horas) AS DECIMAL)/ ((($diff)*9)*0.7)  )*5
                                        END AS metrica
                                        FROM
                                            tbl_knocker_tasks_effort T0 LEFT JOIN
                                            Colaboradores t1 ON t0.idResource=t1.id_colaborador LEFT JOIN
                                            tbl_knocker_tasks t2 ON t0.idTask=t2.IdTask LEFT JOIN
                                            tbl_knocker_projects t3 ON t2.PrjId=t3.id_project
                                        WHERE
                                            T0.status=1 AND
                                            (
                                                T1.id_departamentos = 8 OR 
                                                T1.id_departamentos = 18
                                            ) AND
                                            T1.estado=1 AND
                                            T0.effortDate >= '$FI' AND
                                            T0.effortDate <= '$FF'  AND
                                            t3.PrjCode NOT IN ('s-01','s-02')
                                        GROUP BY T0.idResource, t1.nombres+' '+t1.apellido_p
                                    ) T2 ON T0.id_colaborador = T2.idResource LEFT JOIN
                                    Regiones T5 ON T0.id_region = T5.id_region
                                WHERE
                                    (
                                        T0.id_departamentos = 8 OR 
                                        T0.id_departamentos = 18
                                    ) AND
                                    T0.estado = 1 AND
                                    (
                                        T0.nombres+' '+T0.apellido_p LIKE ('%$search%') OR
                                        T5.codigo_region LIKE ('%$search%') OR
                                        CONVERT(numeric(10,3),T1.avg) LIKE ('%$search%') OR
                                        CONVERT(numeric(10,3),T2.metrica) LIKE ('%$search%') OR
                                        CONVERT(numeric(10,3),T1.te) LIKE ('%$search%')
                                    )
                                ORDER BY id_colaborador";
                $resultColab = sqlsrv_query($this->db->connID, $queryColab);

                $html=" <html>
                            <head>
                                <style>
                                    body{
                                        font-family: 'sofiapro', sans-serif !important;
                                        font-size: 10pt;
                                        color: #58595b;
                                    }
                                    table tr td{
                                        font-family: 'sofiapro', sans-serif !important;
                                        font-size: 10pt;
                                        color: #58595b;
                                    }
                                </style>
                            </head>
                            <body>
                                <table style='width:100%;'>
                                    <tr>
                                        <th style='width:60%; '><img src='http://{$url}/assets/images/logo-2020.png' style='width:30%;'></th>
                                        <th style='width:40%; text-align:right; border-right:2px solid #EE8624; padding-right:15px;'><h3>Indicadores de colaboradores</h3></th>
                                    </tr>
                                    <tr>
                                        <td><b>Fecha Inicio:</b></td>
                                        <td><b>Fecha Final:</b></td>
                                    </tr>
                                    <tr>
                                        <td>".date_format(date_create($FI), 'd/m/Y')."</td>
                                        <td>".date_format(date_create($FF), 'd/m/Y')."</td>
                                    </tr>
                                </table>
                                <br>
                                <table style='width:100%; margin-top:-5px;'>
                                    <tr>
                                        <td style='width:40%; border-bottom: 1px solid !important;'><b>Colaborador</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Zona</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Calidad del servicio</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Esfuerzo</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Tiempo de Entrega</b></td>
                                    </tr>";

                while($row = sqlsrv_fetch_array($resultColab)){
                    $nombre = $row['nombre'];
                    $zona = $row['zona'];
                    $avg = $row['avg'] ? $row['avg'] : '0.000';
                    $metrica = $row['metrica'] ? $row['metrica'] : '0.000';
                    $te = $row['te'] ? $row['te'] : '0.000';
                    $html.= "
                                    <tr>
                                        <td style='width:40%; border-bottom: 1px solid !important; border-color: gray;'>{$nombre}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$zona}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$avg}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$metrica}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$te}</td>
                                    </tr>";
                }
                $html.= "       </table>
                            </body>
                        </html>";

                break;
            case 1: // Servicios 1
                
                $queryServicios1 = "SELECT
                                        T1.PrjCode,
                                        T2.PRJNAME,
                                        T4.GRPCODE AS zona,
                                        FORMAT(T3.fecha_compromiso, 'dd/MM/yyyy') AS FC,
                                        FORMAT(T3.fecha_terminacion, 'dd/MM/yyyy') AS FT,
                                        IIF(COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000, 'Sin referencia', COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)) AS DIF,
                                        CASE
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                            ELSE 1
                                        END AS metrica
                                    FROM 
                                        tbl_knocker_work_orders T0 
                                        LEFT JOIN tbl_knocker_projects T1 ON T0.id_project=T1.id_project 
                                        --LEFT JOIN SYN_OPRJ T2 ON T1.PrjCode=T2.PRJCODE COLLATE DATABASE_DEFAULT 
                                        LEFT JOIN [SBO_ECN].[dbo].[OPRJ] T2 ON T1.PrjCode=T2.PRJCODE COLLATE DATABASE_DEFAULT 
                                        INNER JOIN tbl_knocker_wo_facturacion T3 ON t0.id_ot=t3.id_ot 
                                        --LEFT JOIN SYN_OPRC T4 ON T2.U_ZONA=T4.PRCCODE
                                        LEFT JOIN [SBO_ECN].[dbo].[OPRC] T4 ON T2.U_ZONA=T4.PRCCODE
                                    WHERE
                                        T0.status=1 AND
                                    --	(T0.ot_estado=3 OR T0.ot_estado=4 OR T0.ot_estado=5) AND
                                        T3.status=1 AND
                                        T1.status=1 AND
                                        T3.create_date >= '$FI' AND
                                        T3.create_date <= '$FF' AND
                                        (
                                            T1.PrjCode LIKE ('%$search%') OR
                                            T2.PRJNAME LIKE ('%$search%') OR
                                            T4.GRPCODE LIKE ('%$search%') OR
                                            FORMAT(T3.fecha_compromiso, 'dd/MM/yyyy') LIKE ('%$search%') OR
                                            FORMAT(T3.fecha_terminacion, 'dd/MM/yyyy') LIKE ('%$search%') OR
                                            IIF(COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000, 'Sin referencia', COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)) LIKE ('%$search%') OR
                                            CASE
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                                ELSE 1
                                            END LIKE ('%$search%')
                                        )
                                    ORDER BY t3.id_facturacion;";
                $resultServicios1 = sqlsrv_query($this->db->connID, $queryServicios1);

                $html=" <html>
                            <head>
                                <style>
                                    body{
                                        font-family: 'sofiapro', sans-serif !important;
                                        font-size: 10pt;
                                        color: #58595b;
                                    }
                                    table tr td{
                                        font-family: 'sofiapro', sans-serif !important;
                                        font-size: 9pt;
                                        color: #58595b;
                                    }
                                </style>
                            </head>
                            <body>
                                <table style='width:100%;'>
                                    <tr>
                                        <th style='width:60%;'><img src='http://{$url}/assets/images/logo-2020.png' style='width:30%;'></th>
                                        <th style='width:40%; text-align:right; border-right:2px solid #EE8624; padding-right:15px;'><h3><b>Indicadores de servicios 1</b></h3></th>
                                    </tr>
                                    <tr>
                                        <td><b>Fecha Inicio:</b></td>
                                        <td><b>Fecha Final:</b></td>
                                    </tr>
                                    <tr>
                                        <td>".date_format(date_create($FI), 'd/m/Y')."</td>
                                        <td>".date_format(date_create($FF), 'd/m/Y')."</td>
                                    </tr>
                                </table>
                                <br>
                                <table style='width:100%; margin-top:-5px;'>
                                    <tr>
                                        <td style='width:10%; border-bottom: 1px solid !important; text-align: center;'><b>Proyecto</b></td>
                                        <td style='width:40%; border-bottom: 1px solid !important;'><b>Nombre</b></td>
                                        <td style='width:10%; border-bottom: 1px solid !important; text-align: center;'><b>Zona</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Fecha compromiso</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Fecha terminación</b></td>
                                        <td style='width:10%; border-bottom: 1px solid !important; text-align: center;'><b>Diferencia</b></td>
                                        <td style='width:10%; border-bottom: 1px solid !important; text-align: center;'><b>Resultado</b></td>
                                    </tr>";

                while($row = sqlsrv_fetch_array($resultServicios1)) { 
                    $proyecto = $row['PrjCode'];
                    $nombre = $row['PRJNAME'];
                    $zona = $row['zona'];
                    $FC = $row['FC'] ? $row['FC'] : '';
                    $FT = $row['FT'] ? $row['FT'] : '';
                    $dif = $row['DIF'] ? $row['DIF'] : '';
                    $metrica = $row['metrica'] ? $row['metrica'] : '';
                    $html.= "
                                    <tr>
                                        <td style='width:10%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$proyecto}</td>
                                        <td style='width:40%; border-bottom: 1px solid !important; border-color: gray;'>{$nombre}</td>
                                        <td style='width:10%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$zona}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$FC}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$FT}</td>
                                        <td style='width:10%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$dif}</td>
                                        <td style='width:10%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$metrica}</td>
                                    </tr>";
                }
                $html.= "       </table>
                            </body>
                        </html>";


                break;
            case 2: // Servicios 2
        
                $queryServicios2 = "SELECT 
                                        T0.PrjCode,
                                        T0.PRJNAME,
                                        T0.zona,
                                        CONVERT(numeric(10,3),AVG(T0.avg)) AS AVG
                                    FROM
                                        (
                                            SELECT 
                                                t2.PrjCode,
                                                T3.PrjName,
                                                T4.GRPCODE as zona,
                                                COALESCE(T0.avg,10) AS avg
                                            FROM  
                                                tbl_knocker_work_orders T1 
                                            LEFT JOIN ( SELECT distinct id_ot,AVG(cast(ID_RESPONSE AS DECIMAL)) as avg FROM [tbl_knocker_wo_survey] WHERE status=1 GROUP BY id_ot) T0 ON T1.id_ot=T0.id_ot 
                                            LEFT JOIN tbl_knocker_projects T2 ON T2.id_project=t1.id_project 
                                            --LEFT JOIN SYN_OPRJ T3 ON T2.PrjCode=T3.PrjCode COLLATE DATABASE_DEFAULT 
                                            LEFT JOIN [SBO_ECN].[dbo].[OPRJ] T3 ON T2.PrjCode=T3.PrjCode COLLATE DATABASE_DEFAULT 
                                            --LEFT JOIN SYN_OPRC T4 ON T3.U_ZONA=T4.PRCCODE 
                                            LEFT JOIN [SBO_ECN].[dbo].[OPRC] T4 ON T3.U_ZONA=T4.PRCCODE 
                                            INNER JOIN tbl_knocker_wo_facturacion t5 ON t1.id_ot=t5.id_ot
                                            WHERE
                                                T1.status=1 AND
                                                t2.PrjCode LIKE 'S-%' AND
                                                t2.status=1 AND 
                                                T5.status=1 AND
                                                T5.fecha_terminacion >= '$FI' AND
                                                T5.fecha_terminacion <= '$FF' AND
                                                (
                                                    t2.PrjCode LIKE ('%$search%') OR
                                                    T3.PrjName LIKE ('%$search%') OR
                                                    T4.GRPCODE LIKE ('%$search%') OR
                                                    COALESCE(T0.avg,10) LIKE ('%$search%')
                                                )
                                        ) T0
                                    GROUP BY T0.PrjCode,T0.PRJNAME,T0.zona
                                    ORDER BY PrjCode;";
                $resultServicios2 = sqlsrv_query($this->db->connID, $queryServicios2);

                $html=" <html>
                            <head>
                                <style>
                                    body{
                                        font-family: 'sofiapro', sans-serif !important;
                                        font-size: 10pt;
                                        color: #58595b;
                                    }
                                    table tr td{
                                        font-family: 'sofiapro', sans-serif !important;
                                        font-size: 10pt;
                                        color: #58595b;
                                    }
                                </style>
                            </head>
                            <body>
                                <table style='width:100%;'>
                                    <tr>
                                        <th style='width:60%;'><img src='http://{$url}/assets/images/logo-2020.png' style='width:30%;'></th>
                                        <th style='width:40%; text-align:right; border-right:2px solid #EE8624; padding-right:15px;'><h3><b>Indicadores de servicios 2</b></h3></th>
                                    </tr>
                                    <tr>
                                        <td><b>Fecha Inicio:</b></td>
                                        <td><b>Fecha Final:</b></td>
                                    </tr>
                                    <tr>
                                        <td>".date_format(date_create($FI), 'd/m/Y')."</td>
                                        <td>".date_format(date_create($FF), 'd/m/Y')."</td>
                                    </tr>
                                </table>
                                <br>
                                <table style='width:100%; margin-top:-5px;'>
                                    <tr>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Proyecto</b></td>
                                        <td style='width:55%; border-bottom: 1px solid !important;'><b>Nombre</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Zona</b></td>
                                        <td style='width:15%; border-bottom: 1px solid !important; text-align: center;'><b>Resultado</b></td>
                                    </tr>";

                while($row = sqlsrv_fetch_array($resultServicios2)) { 
                    $proyecto = $row['PrjCode'];
                    $nombre = $row['PRJNAME'];
                    $zona = $row['zona'];
                    $avg = $row['AVG'] ? $row['AVG'] : '';
                    $html.= "
                                    <tr>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$proyecto}</td>
                                        <td style='width:55%; border-bottom: 1px solid !important; border-color: gray;'>{$nombre}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$zona}</td>
                                        <td style='width:15%; border-bottom: 1px solid !important; border-color: gray; text-align: center;'>{$avg}</td>
                                    </tr>";
                }
                $html.= "       </table>
                            </body>
                        </html>";
                break;
        }
        
        $pdf = new Mpdf([
            'debug' => true,
            'mode' => 'utf-8'
        ]);
        
        $pdf->debug = true;
        $pdf->WriteHTML($html);
        return $this->response->setStatusCode(200)->setContentType('application/pdf')->sendBody($pdf->Output());

    }

    public function createXLS(){
        if(empty($this->db->connID))
            $this->db->initialize();

        $json = $this->request->getJSON();

        $tipo = $json->tipo;
        $FI = $json->Fi;
        $FF = $json->Ff;
        $search = $json->search;

        $spreadsheet = new Spreadsheet();
        $FontSize_Text = 12;
        $FontSize_Title = 16;
        $cont = 4;

        // { CALL sp_get_knocker_indicadores_20180727 ('$FI', '$FF') } Pt. 1
        $queryDiff = "SELECT 
                        (
                            (DATEDIFF(dd, '$FI', '$FF') + 1) - 
                            (DATEDIFF(wk, '$FI', '$FF') * 2) - 
                            (CASE WHEN DATENAME(dw, '$FI') = 'Sunday' THEN 1 ELSE 0 END) - 
                            (CASE WHEN DATENAME(dw, '$FF') = 'Saturday' THEN 1 ELSE 0 END)
                        ) AS DIFF";
        $resultadoDiff = $this->db->query($queryDiff)->getResult();
        $diff = $resultadoDiff[0]->DIFF;
        
        switch($tipo){
            case 0: // Indicadores de colaboradores
                
                $spreadsheet->getProperties()->setCreator("Intranet ECN")
                            ->setLastModifiedBy("Intranet ECN")
                            ->setTitle("Indicadores de colaboradores")
                            ->setSubject("Indicadores de colaboradores")
                            ->setDescription("Indicadores de colaboradores")
                            ->setKeywords("office 2007 openxml php")
                            ->setCategory("Test result file");

                // Pagina 1 | Encabezados 1
                $spreadsheet->setActiveSheetIndex(0)
                            ->setTitle("ECN");

                // TAMAÑO DE FILA
                $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(100);    //Altura de la primera fila

                // TAMAÑO DE LETRA
                $spreadsheet->getActiveSheet()->getStyle('A:E')->getFont()->setSize($FontSize_Text);
                $spreadsheet->getActiveSheet()->getStyle('A1:A2')->getFont()->setSize($FontSize_Title);
                
                // Unir
                $spreadsheet->getActiveSheet()->mergeCells('A1:E1');
                $spreadsheet->getActiveSheet()->mergeCells('A2:E2');

                // establecer centro vertical
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical('PHPExcel_Style_Alignment::VERTICAL_CENTER');
    
                //  Establecer centro horizontal
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');
                $spreadsheet->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');

                //Establecer alineación a la izquierda
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_RIGHT');



                // Establecer negritas
                $spreadsheet->getActiveSheet()->getStyle('A1:A3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('D3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('A'.$cont.':E'.$cont)->getFont()->setBold(true);

                $spreadsheet->getActiveSheet()
                            ->setCellValue('A1', "ELECTRO CONTROLES DEL NOROESTE S.A. DE C.V.")
                            ->setCellValue('A2', "Indicadores de colaboradores")
                            ->setCellValue('A3', "Fecha inicio: ")
                            ->setCellValue('B3', date_format(date_create($FI), 'd/m/Y'))
                            ->setCellValue('D3', "Fecha fin: ")
                            ->setCellValue('E3', date_format(date_create($FF), 'd/m/Y'));

                
                $spreadsheet->getActiveSheet()
                            ->setTitle("RH")
                            ->setCellValue('A'.$cont, 'Colaborador')
                            ->setCellValue('B'.$cont, 'Zona')
                            ->setCellValue('C'.$cont, 'Calidad del servicio')
                            ->setCellValue('D'.$cont, 'Esfuerzo')
                            ->setCellValue('E'.$cont, 'Tiempo de entrega');

                foreach(range('A','E') as $columnID) { 
                    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
                }

                
                // { CALL sp_get_knocker_indicadores_20180727 ('$FI', '$FF') } Pt. 2
                $queryColab = " SELECT 
                                    T0.id_colaborador,
                                    T0.nombres+' '+T0.apellido_p AS nombre,
                                    T5.codigo_region AS zona,
                                    CONVERT(numeric(10,3),T1.avg) AS avg,
                                    CONVERT(numeric(10,3),T2.metrica) AS metrica,
                                    CONVERT(numeric(10,3),T1.te) AS te
                                FROM 
                                    Colaboradores T0 LEFT JOIN 
                                    (	SELECT
                                            1 tipo,
                                            T0.id_colaborador,
                                            T0.nombre,
                                            AVG(T0.avg) AS avg,
                                            AVG(T0.te) AS te
                                        FROM
                                            (
                                                SELECT 
                                                    T0.id_colaborador,
                                                    t0.nombre,
                                                    t0.PrjId,
                                                    T1.avg,
                                                    CAST(T1.te AS FLOAT) te,
                                                    T1.PrjCode
                                                FROM 
                                                    (
                                                        SELECT 
                                                            T0.*,
                                                            T1.*
                                                        FROM
                                                        (
                                                            SELECT 
                                                                T0.id_colaborador,
                                                                T0.nombres+' '+T0.apellido_p AS nombre
                                                            FROM Colaboradores T0
                                                            WHERE
                                                                (
                                                                    T0.id_departamentos = 8 OR 
                                                                    T0.id_departamentos = 18) AND
                                                                T0.estado = 1
                                                        ) T0 LEFT JOIN
                                                        (
                                                            SELECT 
                                                                T1.PrjId,
                                                                T0.IdResource
                                                            FROM
                                                                tbl_knocker_assignments T0 LEFT JOIN
                                                                tbl_knocker_tasks t1 ON t0.IdTask=t1.IdTask LEFT JOIN
                                                                tbl_knocker_projects t2 ON t1.PrjId=t2.id_project
                                                            WHERE 
                                                                T0.Status=1 AND 
                                                                T1.Status=1 AND
                                                                t2.status=1 AND
                                                                t2.PrjCode LIKE 'S-%'
                                                            GROUP BY T1.PrjId,T0.IdResource
                                                        ) T1 ON T0.id_colaborador=T1.IdResource
                                                    ) T0 INNER JOIN
                                                    (
                                                        SELECT 
                                                            T1.id_ot,
                                                            T1.id_project,
                                                            COALESCE(T0.avg,10) AS avg,
                                                            t2.PrjCode,
                                                        CASE    
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                                            WHEN  coalesce((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                                            ELSE 1
                                                        END AS te
                                                        FROM  
                                                            tbl_knocker_work_orders T1 LEFT JOIN
                                                            ( 
                                                                SELECT DISTINCT 
                                                                    id_ot,
                                                                    AVG(CAST(ID_RESPONSE AS DECIMAL)) AS avg 
                                                                FROM [tbl_knocker_wo_survey] 
                                                                WHERE status = 1 
                                                                GROUP BY id_ot
                                                            ) T0 ON T1.id_ot = T0.id_ot LEFT JOIN
                                                            tbl_knocker_projects T2 ON T2.id_project = t1.id_project INNER JOIN
                                                            tbl_knocker_wo_facturacion T3 ON t1.id_ot = t3.id_ot
                                                        WHERE
                                                            T1.status = 1 AND
                                                            t2.status = 1 AND
                                                            t2.PrjCode LIKE 'S-%' AND
                                                            t3.create_date >= '$FI' AND
                                                            t3.create_date <= '$FF' AND
                                                            t3.status = 1
                                                    ) T1 ON T0.PrjId = T1.id_project
                                            ) T0
                                        WHERE t0.avg <> 10
                                        GROUP BY T0.id_colaborador,T0.nombre
                                    ) T1 ON T0.id_colaborador=T1.id_colaborador LEFT JOIN
                                    (
                                        SELECT 
                                            2 AS tipo,
                                            T0.idResource, 
                                            t1.nombres+' '+t1.apellido_p AS colaborador,
                                        CASE 
                                            WHEN SUM(t0.effort_horas) >= ((($diff)*9)*0.7) THEN 5
                                            ELSE   ( cast( SUM(T0.effort_horas) AS DECIMAL)/ ((($diff)*9)*0.7)  )*5
                                        END AS metrica
                                        FROM
                                            tbl_knocker_tasks_effort T0 LEFT JOIN
                                            Colaboradores t1 ON t0.idResource=t1.id_colaborador LEFT JOIN
                                            tbl_knocker_tasks t2 ON t0.idTask=t2.IdTask LEFT JOIN
                                            tbl_knocker_projects t3 ON t2.PrjId=t3.id_project
                                        WHERE
                                            T0.status=1 AND
                                            (
                                                T1.id_departamentos = 8 OR 
                                                T1.id_departamentos = 18
                                            ) AND
                                            T1.estado=1 AND
                                            T0.effortDate >= '$FI' AND
                                            T0.effortDate <= '$FF'  AND
                                            t3.PrjCode NOT IN ('s-01','s-02')
                                        GROUP BY T0.idResource, t1.nombres+' '+t1.apellido_p
                                    ) T2 ON T0.id_colaborador = T2.idResource LEFT JOIN
                                    Regiones T5 ON T0.id_region = T5.id_region
                                WHERE
                                    (
                                        T0.id_departamentos = 8 OR 
                                        T0.id_departamentos = 18
                                    ) AND
                                    T0.estado = 1 AND
                                    (
                                        T0.nombres+' '+T0.apellido_p LIKE ('%$search%') OR
                                        T5.codigo_region LIKE ('%$search%') OR
                                        CONVERT(numeric(10,3),T1.avg) LIKE ('%$search%') OR
                                        CONVERT(numeric(10,3),T2.metrica) LIKE ('%$search%') OR
                                        CONVERT(numeric(10,3),T1.te) LIKE ('%$search%')
                                    )
                                ORDER BY id_colaborador";
                $resultColab = sqlsrv_query($this->db->connID, $queryColab);

                while($row = sqlsrv_fetch_array($resultColab)){
                    $cont+= 1;
                    $nombre = $row['nombre'];
                    $zona = $row['zona'];
                    $avg = $row['avg'] ? $row['avg'] : '0.000';
                    $metrica = $row['metrica'] ? $row['metrica'] : '0.000';
                    $te = $row['te'] ? $row['te'] : '0.000';
                    $spreadsheet->getActiveSheet()
                                ->setCellValue('A'.($cont), $nombre)
                                ->setCellValue('B'.($cont), $zona)
                                ->setCellValue('C'.($cont), $avg)
                                ->setCellValue('D'.($cont), $metrica)
                                ->setCellValue('E'.($cont), $te);
                }
                
                break;
            case 1: // Indicadores de servicios 1
                
                $spreadsheet->getProperties()->setCreator("Intranet ECN")
                            ->setLastModifiedBy("Intranet ECN")
                            ->setTitle("Indicadores de servicios 1")
                            ->setSubject("Indicadores de servicios 1")
                            ->setDescription("Indicadores de servicios 1")
                            ->setKeywords("office 2007 openxml php")
                            ->setCategory("Test result file");

                // Pagina 1 | Encabezados 1
                $spreadsheet->setActiveSheetIndex(0)
                            ->setTitle("ECN");

                // TAMAÑO DE FILA
                $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(100);    //Altura de la primera fila

                // TAMAÑO DE LETRA
                $spreadsheet->getActiveSheet()->getStyle('A:G')->getFont()->setSize($FontSize_Text);
                $spreadsheet->getActiveSheet()->getStyle('A1:A2')->getFont()->setSize($FontSize_Title);
                
                // Unir
                $spreadsheet->getActiveSheet()->mergeCells('A1:G1');
                $spreadsheet->getActiveSheet()->mergeCells('A2:G2');

                // establecer centro vertical
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical('PHPExcel_Style_Alignment::VERTICAL_CENTER');
    
                //  Establecer centro horizontal
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');
                $spreadsheet->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');

                //Establecer alineación a la izquierda
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_RIGHT');



                // Establecer negritas
                $spreadsheet->getActiveSheet()->getStyle('A1:A3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('D3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('A'.$cont.':G'.$cont)->getFont()->setBold(true);

                $spreadsheet->getActiveSheet()
                            ->setCellValue('A1', "ELECTRO CONTROLES DEL NOROESTE S.A. DE C.V.")
                            ->setCellValue('A2', "Indicadores de servicios 1")
                            ->setCellValue('A3', "Fecha inicio: ")
                            ->setCellValue('B3', date_format(date_create($FI), 'd/m/Y'))
                            ->setCellValue('D3', "Fecha fin: ")
                            ->setCellValue('E3', date_format(date_create($FF), 'd/m/Y'));

                
                $spreadsheet->getActiveSheet()
                            ->setTitle("RH")
                            ->setCellValue('A'.$cont, 'Proyecto')
                            ->setCellValue('B'.$cont, 'Nombre')
                            ->setCellValue('C'.$cont, 'Zona')
                            ->setCellValue('D'.$cont, 'Fecha compromiso')
                            ->setCellValue('E'.$cont, 'Fecha terminación')
                            ->setCellValue('F'.$cont, 'Diferencia')
                            ->setCellValue('G'.$cont, 'Resultado');

                foreach(range('A','G') as $columnID) { 
                    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
                }
                
                $queryServicios1 = "SELECT
                                        T1.PrjCode,
                                        T2.PRJNAME,
                                        T4.GRPCODE AS zona,
                                        FORMAT(T3.fecha_compromiso, 'dd/MM/yyyy') AS FC,
                                        FORMAT(T3.fecha_terminacion, 'dd/MM/yyyy') AS FT,
                                        IIF(COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000, 'Sin referencia', COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)) AS DIF,
                                        CASE
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                            WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                            ELSE 1
                                        END AS metrica
                                    FROM 
                                        tbl_knocker_work_orders T0 
                                        LEFT JOIN tbl_knocker_projects T1 ON T0.id_project=T1.id_project 
                                        --LEFT JOIN SYN_OPRJ T2 ON T1.PrjCode=T2.PRJCODE COLLATE DATABASE_DEFAULT 
                                        LEFT JOIN [SBO_ECN].[dbo].[OPRJ] T2 ON T1.PrjCode=T2.PRJCODE COLLATE DATABASE_DEFAULT 
                                        INNER JOIN tbl_knocker_wo_facturacion T3 ON t0.id_ot=t3.id_ot 
                                        --LEFT JOIN SYN_OPRC T4 ON T2.U_ZONA=T4.PRCCODE
                                        LEFT JOIN [SBO_ECN].[dbo].[OPRC] T4 ON T2.U_ZONA=T4.PRCCODE
                                    WHERE
                                        T0.status=1 AND
                                    --	(T0.ot_estado=3 OR T0.ot_estado=4 OR T0.ot_estado=5) AND
                                        T3.status=1 AND
                                        T1.status=1 AND
                                        T3.create_date >= '$FI' AND
                                        T3.create_date <= '$FF' AND
                                        (
                                            T1.PrjCode LIKE ('%$search%') OR
                                            T2.PRJNAME LIKE ('%$search%') OR
                                            T4.GRPCODE LIKE ('%$search%') OR
                                            FORMAT(T3.fecha_compromiso, 'dd/MM/yyyy') LIKE ('%$search%') OR
                                            FORMAT(T3.fecha_terminacion, 'dd/MM/yyyy') LIKE ('%$search%') OR
                                            IIF(COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000, 'Sin referencia', COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)) LIKE ('%$search%') OR
                                            CASE
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) = -15000 THEN 0
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 0 THEN 5
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 2 THEN 4
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000) <= 4 THEN 3
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  = 5 THEN 2
                                                WHEN  COALESCE((DATEDIFF(DAY,T3.fecha_compromiso,T3.fecha_terminacion)),-15000)  > 5 THEN 1
                                                ELSE 1
                                            END LIKE ('%$search%')
                                        )
                                    ORDER BY t3.id_facturacion;";
                $resultServicios1 = sqlsrv_query($this->db->connID, $queryServicios1);

                while($row = sqlsrv_fetch_array($resultServicios1)){
                    $cont+= 1;
                    $proyecto = $row['PrjCode'];
                    $nombre = $row['PRJNAME'];
                    $zona = $row['zona'];
                    $FC = $row['FC'];
                    $FT = $row['FT'];
                    $dif = $row['DIF'] ? $row['DIF'] : '';
                    $metrica = $row['metrica'] ? $row['metrica'] : '';
                    $spreadsheet->getActiveSheet()
                                ->setCellValue('A'.($cont), $proyecto)
                                ->setCellValue('B'.($cont), $nombre)
                                ->setCellValue('C'.($cont), $zona)
                                ->setCellValue('D'.($cont), $FC)
                                ->setCellValue('E'.($cont), $FT)
                                ->setCellValue('F'.($cont), $dif)
                                ->setCellValue('G'.($cont), $metrica);
                }

                break;
            case 2: // Indicadores de servicios 2
                
                $spreadsheet->getProperties()->setCreator("Intranet ECN")
                            ->setLastModifiedBy("Intranet ECN")
                            ->setTitle("Indicadores de servicios 2")
                            ->setSubject("Indicadores de servicios 2")
                            ->setDescription("Indicadores de servicios 2")
                            ->setKeywords("office 2007 openxml php")
                            ->setCategory("Test result file");

                // Pagina 1 | Encabezados 1
                $spreadsheet->setActiveSheetIndex(0)
                            ->setTitle("ECN");

                // TAMAÑO DE FILA
                $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(100);    //Altura de la primera fila

                // TAMAÑO DE LETRA
                $spreadsheet->getActiveSheet()->getStyle('A:D')->getFont()->setSize($FontSize_Text);
                $spreadsheet->getActiveSheet()->getStyle('A1:A2')->getFont()->setSize($FontSize_Title);
                
                // Unir
                $spreadsheet->getActiveSheet()->mergeCells('A1:D1');
                $spreadsheet->getActiveSheet()->mergeCells('A2:D2');

                // establecer centro vertical
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical('PHPExcel_Style_Alignment::VERTICAL_CENTER');
    
                //  Establecer centro horizontal
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');
                $spreadsheet->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');

                //Establecer alineación a la izquierda
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_RIGHT');



                // Establecer negritas
                $spreadsheet->getActiveSheet()->getStyle('A1:A3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('C3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('A'.$cont.':D'.$cont)->getFont()->setBold(true);

                $spreadsheet->getActiveSheet()
                            ->setCellValue('A1', "ELECTRO CONTROLES DEL NOROESTE S.A. DE C.V.")
                            ->setCellValue('A2', "Indicadores de servicios 2")
                            ->setCellValue('A3', "Fecha inicio: ")
                            ->setCellValue('B3', date_format(date_create($FI), 'd/m/Y'))
                            ->setCellValue('C3', "Fecha fin: ")
                            ->setCellValue('D3', date_format(date_create($FF), 'd/m/Y'));

                
                $spreadsheet->getActiveSheet()
                            ->setTitle("RH")
                            ->setCellValue('A'.$cont, 'Proyecto')
                            ->setCellValue('B'.$cont, 'Nombre')
                            ->setCellValue('C'.$cont, 'Zona')
                            ->setCellValue('D'.$cont, 'Resultado');

                foreach(range('A','D') as $columnID) { 
                    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
                }
                
                $queryServicios2 = "SELECT 
                                        T0.PrjCode,
                                        T0.PRJNAME,
                                        T0.zona,
                                        CONVERT(numeric(10,3),AVG(T0.avg)) AS AVG
                                    FROM
                                        (
                                            SELECT 
                                                t2.PrjCode,
                                                T3.PrjName,
                                                T4.GRPCODE as zona,
                                                COALESCE(T0.avg,10) AS avg
                                            FROM  
                                                tbl_knocker_work_orders T1 
                                            LEFT JOIN ( SELECT distinct id_ot,AVG(cast(ID_RESPONSE AS DECIMAL)) as avg FROM [tbl_knocker_wo_survey] WHERE status=1 GROUP BY id_ot) T0 ON T1.id_ot=T0.id_ot 
                                            LEFT JOIN tbl_knocker_projects T2 ON T2.id_project=t1.id_project 
                                            --LEFT JOIN SYN_OPRJ T3 ON T2.PrjCode=T3.PrjCode COLLATE DATABASE_DEFAULT 
                                            LEFT JOIN [SBO_ECN].[dbo].[OPRJ] T3 ON T2.PrjCode=T3.PrjCode COLLATE DATABASE_DEFAULT 
                                            --LEFT JOIN SYN_OPRC T4 ON T3.U_ZONA=T4.PRCCODE 
                                            LEFT JOIN [SBO_ECN].[dbo].[OPRC] T4 ON T3.U_ZONA=T4.PRCCODE 
                                            INNER JOIN tbl_knocker_wo_facturacion t5 ON t1.id_ot=t5.id_ot
                                            WHERE
                                                T1.status=1 AND
                                                t2.PrjCode LIKE 'S-%' AND
                                                t2.status=1 AND 
                                                T5.status=1 AND
                                                T5.fecha_terminacion >= '$FI' AND
                                                T5.fecha_terminacion <= '$FF' AND
                                                (
                                                    t2.PrjCode LIKE ('%$search%') OR
                                                    T3.PrjName LIKE ('%$search%') OR
                                                    T4.GRPCODE LIKE ('%$search%') OR
                                                    COALESCE(T0.avg,10) LIKE ('%$search%')
                                                )
                                        ) T0
                                    GROUP BY T0.PrjCode,T0.PRJNAME,T0.zona
                                    ORDER BY PrjCode;";
                $resultServicios2 = sqlsrv_query($this->db->connID, $queryServicios2);

                while($row = sqlsrv_fetch_array($resultServicios2)){
                    $cont+= 1;
                    $proyecto = $row['PrjCode'];
                    $nombre = $row['PRJNAME'];
                    $zona = $row['zona'];
                    $avg = $row['AVG'] ? $row['AVG'] : '';
                    $spreadsheet->getActiveSheet()
                                ->setCellValue('A'.($cont), $proyecto)
                                ->setCellValue('B'.($cont), $nombre)
                                ->setCellValue('C'.($cont), $zona)
                                ->setCellValue('D'.($cont), $avg);
                }

                break;
            case 3: // Embudo de Ventas
                
                $spreadsheet->getProperties()->setCreator("Intranet ECN")
                            ->setLastModifiedBy("Intranet ECN")
                            ->setTitle("Reporte de ventas")
                            ->setSubject("Reporte de ventas")
                            ->setDescription("Reporte de ventas")
                            ->setKeywords("office 2007 openxml php")
                            ->setCategory("Test result file");

                // Pagina 1 | Encabezados 1
                $spreadsheet->setActiveSheetIndex(0)
                            ->setTitle("ECN");

                // TAMAÑO DE FILA
                $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(100);    //Altura de la primera fila

                // TAMAÑO DE LETRA
                $spreadsheet->getActiveSheet()->getStyle('A:L')->getFont()->setSize($FontSize_Text);
                $spreadsheet->getActiveSheet()->getStyle('A1:A2')->getFont()->setSize($FontSize_Title);
                
                // Unir
                $spreadsheet->getActiveSheet()->mergeCells('A1:L1');
                $spreadsheet->getActiveSheet()->mergeCells('A2:L2');

                // establecer centro vertical
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical('PHPExcel_Style_Alignment::VERTICAL_CENTER');
    
                //  Establecer centro horizontal
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');
                $spreadsheet->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_CENTER');

                //Establecer alineación a la izquierda
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('PHPExcel_Style_Alignment::HORIZONTAL_RIGHT');



                // Establecer negritas
                $spreadsheet->getActiveSheet()->getStyle('A1:A3')->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle('A'.$cont.':L'.$cont)->getFont()->setBold(true);

                $spreadsheet->getActiveSheet()
                            ->setCellValue('A1', "ELECTRO CONTROLES DEL NOROESTE S.A. DE C.V.")
                            ->setCellValue('A2', "Reporte de ventas");

                
                $spreadsheet->getActiveSheet()
                            ->setTitle("RH")
                            ->setCellValue('A'.$cont, '# Cotización')
                            ->setCellValue('B'.$cont, 'Fecha de contabilización')
                            ->setCellValue('C'.$cont, 'Cliente')
                            ->setCellValue('D'.$cont, 'Importe USD')
                            ->setCellValue('E'.$cont, 'Referencia')
                            ->setCellValue('F'.$cont, '% Cierre')
                            ->setCellValue('G'.$cont, 'Vendedor')
                            ->setCellValue('H'.$cont, 'Región')
                            ->setCellValue('I'.$cont, 'Oficina')
                            ->setCellValue('J'.$cont, 'Industria')
                            ->setCellValue('K'.$cont, 'Fecha cierre')
                            ->setCellValue('L'.$cont, 'Comentarios');

                foreach(range('A','L') as $columnID) { 
                    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
                }
                
                $queryCotizaciones = "  SELECT
                                            FORMAT(ISNULL(C.FechaCont, ''), 'dd/MM/yyyy') AS FechaCont_n
                                            ,C.DocNum
                                            ,C.Cliente
                                            ,C.vendedor
                                            ,ISNULL(C.zona, '') AS zona
                                            ,c.oficina
                                            ,FORMAT(SUM(C.TOTPARTITEM), 'C') AS TOTAL_n
                                            ,ISNULL(C.Referencia, '') AS Referencia 
                                            ,[C].[% Cierre] AS porc_cierre
                                            ,CASE WHEN ISNULL(C.U_FECHACIERRE, '') = '' THEN 'Sin capturar' ELSE FORMAT(C.U_FECHACIERRE, 'dd/MM/yyyy') END AS U_FECHACIERRE_n
                                            ,ISNULL(C.Comments, '') AS Comments
                                            ,ISNULL(C.industria, '') AS industria
                                        FROM CotizacionesTest C 
                                        WHERE 
                                            (
                                                C.DocNum LIKE ('%$search%') OR
                                                C.Cliente LIKE ('%$search%') OR
                                                C.FechaCont LIKE ('%$search%') OR
                                                FORMAT(ISNULL(C.FechaCont, ''), 'dd/MM/yyyy') LIKE ('%$search%')
                                            )
                                        GROUP BY C.industria, C.FechaCont,C.DocNum,C.Cliente,C.Referencia, [C].[% Cierre],C.U_FECHACIERRE,C.Comments,C.vendedor,C.zona,C.oficina
                                        ORDER BY C.U_FECHACIERRE;";
                $result = sqlsrv_query($this->db->connID, $queryCotizaciones);

                while($row = sqlsrv_fetch_array($result)){
                    $cont+= 1;

                    $docnum = $row['DocNum'];
                    $fechaCont = $row['FechaCont_n'];
                    $cliente = $row['Cliente'];
                    $total = $row['TOTAL_n'];
                    $referencia = $row['Referencia'];
                    $porcCierre = $row['porc_cierre'];
                    $vendedor = $row['vendedor'];
                    $zona = $row['zona'];
                    $oficina = $row['oficina'];
                    $industria = $row['industria'];
                    $fechaCierre = $row['U_FECHACIERRE_n'];
                    $comments = $row['Comments'];
                    
                    $spreadsheet->getActiveSheet()
                                ->setTitle("RH")
                                ->setCellValue('A'.$cont, $docnum)
                                ->setCellValue('B'.$cont, $fechaCont)
                                ->setCellValue('C'.$cont, $cliente)
                                ->setCellValue('D'.$cont, $total)
                                ->setCellValue('E'.$cont, $referencia)
                                ->setCellValue('F'.$cont, $porcCierre)
                                ->setCellValue('G'.$cont, $vendedor)
                                ->setCellValue('H'.$cont, $zona)
                                ->setCellValue('I'.$cont, $oficina)
                                ->setCellValue('J'.$cont, $industria)
                                ->setCellValue('K'.$cont, $fechaCierre)
                                ->setCellValue('L'.$cont, $comments);
                }

                break;
        }

        $spreadsheet->setActiveSheetIndex(0);
        
        $objWriter = new Xlsx($spreadsheet);
        $objWriter->save('php://output');
        return  $this->response->setStatusCode(200)->setContentType('application/vnd.ms-excel')->sendBody($objWriter);

    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
?>