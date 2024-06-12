<?php

namespace App\Controllers\salesup;

use App\Controllers\BaseController;
use App\Models\salesup\Acompanamiento;
use App\Models\salesup\Actividad;
use App\Models\salesup\Compromiso;
use App\Models\salesup\Contacto;
use App\Models\salesup\Cuenta;
use App\Models\salesup\Industria;
use App\Models\salesup\Levantamiento;
use App\Models\salesup\Oportunidad;
use App\Models\salesup\Plan;
use App\Models\salesup\Spk1;
use App\Models\salesup\Visita;
use DateTime;

class Planes extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function checkPlan()
    {
        $json = $this->request->getJSON();
        $semana = date('W');
        $model = new Plan();
        $result = $model
            ->select('id')
            ->where('id_sap', $json->id_sap, true)
            ->where('DATEPART(isowk,fecha_create)', $semana, true)
            ->findAll(1);

        if (count($result) > 0) {
            return $this->response->setStatusCode(200)->setJSON(1);
        } else {
            return $this->response->setStatusCode(200)->setJSON(0);
        }
    }

    public function getPonderado2($año, $mes, $semana, $id_sap)
    {

        $query = $this->db->query(
            "SELECT id FROM planes WHERE fecha_create=(SELECT MIN(fecha_create) AS fecha FROM
        planes WHERE MONTH(fecha_create)=?
        AND YEAR(fecha_create)=? AND confirmacion=1) AND id_sap=?",
            [$mes, $año, $id_sap]
        );
        $result = $query->getResult();

        if (count($result) > 0) {
            $query = $this->db->query("SELECT proy_men FROM planes where id=?", [$result[0]->id]);
            $proy_men = $query->getResult()[0]->proy_men;
        } else {
            $query = $this->db->query(
                "SELECT isnull((select sum(t.[Monto Ponderado]) FROM 
            [SAPSERVER].[SBO_ECN].[dbo].[VW_Cotizaciones_Partidas_28092018] t LEFT JOIN SYN_OSLP j ON t.Agente=j.SlpName WHERE
            year(t.U_FECHACIERRE)=? AND month(t.U_FECHACIERRE)=? AND j.SlpCode=?),0) proyeccion",
                [
                    $año,
                    $mes,
                    $id_sap
                ]
            );

            $proy_men = $query->getResult()[0]->proyeccion;
        }

        $query = $this->db->query(
            "SELECT isnull((select sum(t.[Monto Ponderado]) FROM 
        [SAPSERVER].[SBO_ECN].[dbo].[VW_Cotizaciones_Partidas_28092018] t LEFT JOIN SYN_OSLP j ON t.Agente=j.SlpName WHERE
        year(t.U_FECHACIERRE)=? AND DATEPART(isowk,t.U_FECHACIERRE)=? AND j.SlpCode=?),0) proyeccion",
            [
                $año,
                $semana,
                $id_sap
            ]
        );

        $proy_sem = $query->getResult()[0]->proyeccion;

        return json_encode([
            'proy_men' => $proy_men,
            'proy_sem' => $proy_sem
        ]);
    }

    // public function getProyecciones()
    // {
    //     $id_sap = $this->request->getJSON()->id_sap;
    //     $tipo = $this->request->getJSON()->tipo;
    //     $id_plan = $this->request->getJSON()->id_plan;

    //     if ($tipo == 'semana') {
    //         $query = $this->db->query("SELECT fecha_create FROM planes WHERE id=?", [$id_plan]);
    //         $result = $query->getResult();
    //         $fecha = $result[0]->fecha_create ? $result[0]->fecha_create : NULL;
    //         if ($fecha != NULL) {
    //             $date = new DateTime($fecha);
    //             $semana = $date->format('W');
    //             $año = $date->format('Y');
    //             $query = $this->db->query("SELECT [ID SAP] AS folio,[Monto Ponderado] AS monto,[U_FECHACIERRE] FROM 
    //         [SAPSERVER].[SBO_ECN].[dbo].[VW_Cotizaciones_Partidas_28092018] t LEFT JOIN
    //         SYN_OSLP j ON t.Agente=j.SlpName WHERE
    //         year(t.U_FECHACIERRE)=? AND DATEPART(isowk,t.U_FECHACIERRE)=? AND j.SlpCode=? ORDER BY [U_FECHACIERRE]", [
    //                 $año,
    //                 $semana,
    //                 $id_sap
    //             ]);
    //             $proyecciones = $query->getResult();

    //             return $this->getResponse([
    //                 'message' => 'Data successfully retrieved',
    //                 'proyecciones' => $proyecciones
    //             ]);
    //         } else {
    //             return $this->getResponse([
    //                 'message' => 'Error en la fecha.',
    //             ], 500);
    //         }
    //     } else {
    //         $query = $this->db->query("SELECT fecha_create FROM planes WHERE id=?", [$id_plan]);
    //         $result = $query->getResult();
    //         $fecha = $result[0]->fecha_create ? $result[0]->fecha_create : NULL;
    //         if ($fecha != NULL) {
    //             $date = new DateTime($fecha);
    //             $mes = $date->format('n');
    //             $año = $date->format('Y');
    //             $query = $this->db->query(
    //                 "SELECT id FROM planes WHERE fecha_create=(SELECT MIN(fecha_create) AS fecha FROM
    //             planes WHERE MONTH(fecha_create)=?
    //             AND YEAR(fecha_create)=? AND confirmacion=1)",
    //                 [$mes, $año]
    //             );
    //             $result = $query->getResult();
    //             if (count($result) > 0) {
    //                 $id_plan = $result[0]->id;
    //                 $query = $this->db->query("SELECT [ID SAP] AS folio,[Monto Ponderado] AS monto,[U_FECHACIERRE] FROM 
    //                 [SAPSERVER].[SBO_ECN].[dbo].[VW_Cotizaciones_Partidas_28092018] WHERE
    //                 [ID SAP] IN (SELECT folio FROM proyecciones WHERE id_plan=?)", [$id_plan]);
    //                 $proyecciones = $query->getResult();

    //                 return $this->getResponse([
    //                     'message' => 'Data successfully retrieved',
    //                     'proyecciones' => $proyecciones
    //                 ]);
    //             } else {
    //                 $query = $this->db->query("SELECT [ID SAP] AS folio,[Monto Ponderado] AS monto,[U_FECHACIERRE] FROM 
    //                 [SAPSERVER].[SBO_ECN].[dbo].[VW_Cotizaciones_Partidas_28092018] t LEFT JOIN
    //                 SYN_OSLP j ON t.Agente=j.SlpName WHERE
    //                 year(t.U_FECHACIERRE)=? AND month(t.U_FECHACIERRE)=? AND j.SlpCode=? ORDER BY [U_FECHACIERRE]", [
    //                     $año,
    //                     $mes,
    //                     $id_sap
    //                 ]);
    //                 $proyecciones = $query->getResult();

    //                 return $this->getResponse([
    //                     'message' => 'Data successfully retrieved',
    //                     'proyecciones' => $proyecciones
    //                 ]);
    //             }
    //         } else {
    //             return $this->getResponse([
    //                 'message' => 'Error en la fecha.',
    //             ], 500);
    //         }
    //     }
    // }

    public function getEtapas()
    {
        $query = $this->db->query("SELECT T0.CODE,
        CASE T0.NAME 
         WHEN  'Cerrada Ganada' THEN 'GANADA'
         WHEN  'Cerrada Perdida' THEN 'PERDIDA'
         ELSE T0.NAME
        end as NAME,
        U_BitrixID
        FROM SYN_ETAPACOT T0 WHERE T0.NAME NOT LIKE '*%' and code not like 'G'");
        $result = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'etapas' => $result
        ]);
    }

    public function getPonderado()
    {
        $id_sap = $this->request->getJSON()->id_sap;
        $date = new DateTime();
        $mes = $date->format('n');
        $año = $date->format('Y');
        $semana = $date->format('W');

        $query = $this->db->query(
            "SELECT id FROM planes WHERE fecha_create=(SELECT MIN(fecha_create) AS fecha FROM
        planes WHERE MONTH(fecha_create)=?
        AND YEAR(fecha_create)=? AND confirmacion=1) AND id_sap=?",
            [$mes, $año, $id_sap]
        );
        $result = $query->getResult();

        if (count($result) > 0) {
            $id_plan = $result[0]->id;
            $query = $this->db->query("SELECT proy_men FROM planes where id=?", [$id_plan]);
            $proy_men = $query->getResult()[0]->proy_men;
        } else {
            $query = $this->db->query(
                "SELECT isnull((select sum(t.[Monto Ponderado]) FROM 
            [SAPSERVER].[SBO_ECN].[dbo].[VW_Cotizaciones_Partidas_28092018] t LEFT JOIN SYN_OSLP j ON t.Agente=j.SlpName WHERE
            year(t.U_FECHACIERRE)=? AND month(t.U_FECHACIERRE)=? AND j.SlpCode=?),0) proyeccion",
                [
                    $año,
                    $mes,
                    $id_sap
                ]
            );

            $proy_men = $query->getResult()[0]->proyeccion;
        }

        $query = $this->db->query(
            "SELECT isnull((select sum(t.[Monto Ponderado]) FROM 
        [SAPSERVER].[SBO_ECN].[dbo].[VW_Cotizaciones_Partidas_28092018] t LEFT JOIN SYN_OSLP j ON t.Agente=j.SlpName WHERE
        year(t.U_FECHACIERRE)=? AND DATEPART(isowk,t.U_FECHACIERRE)=? AND j.SlpCode=?),0) proyeccion",
            [
                $año,
                $semana,
                $id_sap
            ]
        );

        $proy_sem = $query->getResult()[0]->proyeccion;

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'proy_men' => $proy_men,
            'proy_sem' => $proy_sem
        ]);
    }

    public function addPlan()
    {
        $json = $this->request->getJSON();
        $model = new Plan();
        $model->insert($json, false);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function getPlanes()
    {
        helper('date');
        $json = $this->request->getJSON();

        $tipo = isset($json->tipo) ? $json->tipo : NULL;
        $id_zona = isset($json->id_zona) ? $json->id_zona : NULL;

        if ($tipo == 'Gerente' && $id_zona) {
            $query = $this->db->query("SELECT
            P.id,
            P.id_sap,
            C.id_colaborador,
            P.proy_men,
            P.proy_sem,
            P.fecha_create,
            DATEPART(isowk,P.fecha_create) AS 'semana',
            DATEPART(YEAR,P.fecha_create) AS 'anio',
            P.confirmacion,
            CONCAT(C.nombres,' ',C.apellido_p, ' ',C.apellido_m) AS 'vendedor'
            FROM
            planes AS P,
            (
            SELECT
            id_colaborador,
            nombres,
            apellido_p,
            apellido_m,
            id_vendedorSap
            FROM
            Colaboradores
            WHERE
            estado=1
            ) AS C,
            (
            SELECT
            id_sap,
            id_zona
            FROM det_vendedores
            ) AS DT
            WHERE P.id_sap = C.id_vendedorSap AND
            P.id_sap = DT.id_sap AND
            DT.id_zona = ? AND
            DATEPART(YEAR,P.fecha_create) = ?
            ORDER BY semana DESC", [
                $id_zona,
                date('Y', now())
            ]);
        } else if ($tipo == 'Corporativo') {
            $query = $this->db->query("SELECT
            P.id,
            P.id_sap,
            C.id_colaborador,
            P.proy_men,
            P.proy_sem,
            P.fecha_create,
            DATEPART(isowk,P.fecha_create) AS 'semana',
            DATEPART(YEAR,P.fecha_create) AS 'anio',
            P.confirmacion,
            CONCAT(C.nombres,' ',C.apellido_p, ' ',C.apellido_m) AS 'vendedor'
            FROM planes P,
            (
            SELECT
            id_colaborador,
            nombres,
            apellido_p,
            apellido_m,
            id_vendedorSap
            FROM
            Colaboradores
            WHERE
            estado=1
            ) AS C
            WHERE
            DATEPART(YEAR,P.fecha_create) = ? AND
            P.id_sap=C.id_vendedorSap
            ORDER BY semana DESC", [
                date('Y', now())
            ]);
        } else if ($tipo == 'Vendedor') {
            $query = $this->db->query("SELECT
            P.id,
            P.id_sap,
            C.id_colaborador,
            P.proy_men,
            P.proy_sem,
            P.fecha_create,
            DATEPART(isowk,P.fecha_create) AS 'semana',
            DATEPART(YEAR,P.fecha_create) AS 'anio',
            P.confirmacion,
            CONCAT(C.nombres,' ',C.apellido_p, ' ',C.apellido_m) AS 'vendedor'
            FROM planes AS P,
            (
            SELECT
            id_colaborador,
            nombres,
            apellido_p,
            apellido_m,
            id_vendedorSap
            FROM
            Colaboradores
            WHERE estado=1
            ) AS C
            WHERE P.id_sap=C.id_vendedorSap AND
            P.id_sap = ? AND
            DATEPART(YEAR,P.fecha_create) = ?
            ORDER BY semana DESC", [
                $json->id_sap,
                date('Y', now())
            ]);
        }

        $planes = $query->getResult();


        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'planes' => $planes
        ]);
    }

    public function getVisitas()
    {
        $json = $this->request->getJSON();
        if (isset($json->tipo)) {
            $tipo = $json->tipo;
            $mes = $json->mes ? $json->mes : 0;
            $id_zona = isset($json->id_zona) ? $json->id_zona : 0;
            $id_sap = isset($json->id_sap) ? $json->id_sap : 0;
            switch ($tipo) {
                case 'Vendedor':
                    $query = $this->db->query("SELECT
                    C.id,
                    CU2.CardName,
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'Vendedor',
                    C.fecha_comp,CONVERT(varchar,C.hora_inicio,100) AS 'hora_inicioTitulo',
                    CONVERT(varchar,C.hora_inicio,8) AS 'hora_inicio',
                    CONVERT(varchar,C.hora_fin,100) AS 'hora_finTitulo',
                    CONVERT(varchar,C.hora_fin,8) AS 'hora_fin',
                    C.estatus,
                    C.objetivo_gen,
                    C.CardCode,
                    CU2.p_geografica,
                    C.id_sap,
                    C.LineNum,
                    CU2.descripcion
                    FROM
                    citas AS C,
                    (SELECT
                    CRD1.Address AS 'descripcion',
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS CardName,
                    CONCAT(
                    OCRD.Address COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                    OCRD.ZipCode COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                    OCRD.city COLLATE SQL_Latin1_General_CP850_CI_AS) AS p_geografica,
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.SlpCode!=-1 AND
                OCRD.SlpCode!=0 AND
                CRD1.AdresType = 'S' AND
                OCRD.CardType = 'C' AND
                OCRD.CardName IS NOT NULL
                    UNION ALL
                    SELECT 'Sin descripción' AS 'descripcion',
                    CAST(id as varchar) AS 'CardCode',
                    raz_social AS 'CardName',
                    CONCAT(direccion,' ',municipio, ' ',estado) AS 'p_geografica',
                    '-1' AS 'LineNum'
                    FROM cuentas
                    ) AS CU2,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM Colaboradores
                    WHERE
                    estado=1
                    ) AS CO
                    WHERE
                    C.CardCode=CU2.CardCode AND
                    C.LineNum=CU2.LineNum AND
                    C.id_sap=CO.id_vendedorSap AND
                    C.id_sap=? AND
                    MONTH(C.fecha_comp)=? AND
                    C.deleted IS NULL", [
                        $id_sap,
                        $mes
                    ]);
                    break;

                case 'Gerente':
                    $query = $this->db->query("SELECT
                    C.id,
                    CU2.CardName,
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'Vendedor',
                    C.fecha_comp,CONVERT(varchar,C.hora_inicio,100) AS 'hora_inicioTitulo',
                    CONVERT(varchar,C.hora_inicio,8) AS 'hora_inicio',
                    CONVERT(varchar,C.hora_fin,100) AS 'hora_finTitulo',
                    CONVERT(varchar,C.hora_fin,8)hora_fin,
                    C.estatus,
                    C.objetivo_gen,
                    C.CardCode,
                    CU2.p_geografica,
                    C.id_sap,
                    C.LineNum,
                    CU2.descripcion
                    FROM
                    citas AS C,
                    (SELECT
                    CRD1.Address AS 'descripcion',
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS CardName,
                    CONCAT(
                    OCRD.Address COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                    OCRD.ZipCode COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                    OCRD.city COLLATE SQL_Latin1_General_CP850_CI_AS)AS p_geografica,
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.SlpCode!=-1 AND
                OCRD.SlpCode!=0 AND
                CRD1.AdresType = 'S' AND
                OCRD.CardType = 'C' AND
                OCRD.CardName IS NOT NULL
                    UNION ALL
                    SELECT 'Sin descripción' AS 'descripcion',
                    CAST(id as varchar) AS 'CardCode',
                    raz_social AS 'CardName',
                    CONCAT(direccion,' ',municipio, ' ',estado) AS 'p_geografica',
                    '-1' AS 'LineNum'
                    FROM cuentas
                    ) AS CU2,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM Colaboradores
                    WHERE
                    estado=1
                    ) AS CO,
                    (SELECT
                    id_sap,
                    id_zona
                    FROM det_vendedores
                    ) AS DT
                    WHERE
                    C.CardCode=CU2.CardCode AND
                    C.LineNum=CU2.LineNum AND
                    C.id_sap=CO.id_vendedorSap AND
                    C.id_sap=DT.id_sap AND
                    DT.id_zona=? AND
                    MONTH(C.fecha_comp)=? AND
                    C.deleted IS NULL", [
                        $id_zona,
                        $mes
                    ]);
                    break;

                case 'Corporativo':
                    $query = $this->db->query("SELECT
                    C.id,
                    CU2.CardName,
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'Vendedor',
                    C.fecha_comp,
                    CONVERT(varchar,C.hora_inicio,100) AS 'hora_inicioTitulo',
                    CONVERT(varchar,C.hora_inicio,8) AS 'hora_inicio',
                    CONVERT(varchar,C.hora_fin,100) AS 'hora_finTitulo',
                    CONVERT(varchar,C.hora_fin,8) AS 'hora_fin',
                    C.estatus,
                    C.objetivo_gen,
                    C.CardCode,
                    CU2.p_geografica,
                    C.id_sap,
                    C.LineNum,
                    CU2.descripcion
                    FROM citas AS C,
                    (SELECT
                    CRD1.Address AS 'descripcion',
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    CONCAT(
                    OCRD.Address COLLATE SQL_Latin1_General_CP850_CI_AS,
                    ' ',
                    OCRD.ZipCode COLLATE SQL_Latin1_General_CP850_CI_AS,
                    ' ',
                    OCRD.city COLLATE SQL_Latin1_General_CP850_CI_AS
                    ) AS 'p_geografica',
                    CRD1.LineNum
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.SlpCode!=-1 AND
                OCRD.SlpCode!=0 AND
                CRD1.AdresType = 'S' AND
                OCRD.CardType = 'C' AND
                OCRD.CardName IS NOT NULL
                    UNION ALL
                    SELECT
                    'Sin descripción' AS 'descripcion',
                    CAST(id as varchar) AS 'CardCode',
                    raz_social AS 'CardName',
                    CONCAT(direccion,' ',municipio, ' ',estado) AS 'p_geografica',
                    '-1' AS 'LineNum'
                    FROM cuentas
                    ) AS CU2,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM Colaboradores
                    WHERE
                    estado=1
                    ) AS CO
                    WHERE
                    C.CardCode=CU2.CardCode AND
                    C.LineNum=CU2.LineNum AND
                    C.id_sap=CO.id_vendedorSap AND
                    MONTH(C.fecha_comp)=? AND
                    C.deleted IS NULL", [
                        $mes
                    ]);
                    break;
            }
        } else {
            if (isset($json->id_sap)) {
                $fecha_plan = $json->fecha_create;
                $date = new DateTime($fecha_plan);
                $semana = $date->format('W');
                $query = $this->db->query("SELECT
                C.id,
                CU2.CardName,
                CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'Vendedor',
                C.fecha_comp,CONVERT(varchar,C.hora_inicio,100) AS 'hora_inicioTitulo',
                CONVERT(varchar,C.hora_inicio,8) AS 'hora_inicio',
                CONVERT(varchar,C.hora_fin,100) AS 'hora_finTitulo',
                CONVERT(varchar,C.hora_fin,8)hora_fin,
                C.estatus,
                C.objetivo_gen,
                C.CardCode,
                CU2.p_geografica,
                C.id_sap,
                C.LineNum,
                CU2.descripcion
                FROM
                citas AS C,
                (SELECT
                CRD1.Address AS 'descripcion',
                OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS CardName,
                CONCAT(
                OCRD.Address COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                OCRD.ZipCode COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                OCRD.city COLLATE SQL_Latin1_General_CP850_CI_AS)AS p_geografica,
                CRD1.LineNum AS 'LineNum'
                FROM
                [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                WHERE
                OCRD.CardCode=CRD1.CardCode AND
                OCRD.SlpCode!=-1 AND
                OCRD.SlpCode!=0 AND
                CRD1.AdresType = 'S' AND
                OCRD.CardType = 'C' AND
                OCRD.CardName IS NOT NULL
                UNION ALL
                SELECT 'Sin descripción' AS 'descripcion',
                CAST(id as varchar) AS 'CardCode',
                raz_social AS 'CardName',
                CONCAT(direccion,' ',municipio, ' ',estado) AS 'p_geografica',
                '-1' AS 'LineNum'
                FROM cuentas
                ) AS CU2,
                (SELECT
                nombres,
                apellido_p,
                apellido_m,
                id_vendedorSap
                FROM Colaboradores
                WHERE
                estado=1
                ) AS CO
                WHERE
                C.CardCode=CU2.CardCode AND
                C.LineNum=CU2.LineNum AND
                C.id_sap=CO.id_vendedorSap AND
                C.id_sap=? AND
                DATEPART(isowk,C.fecha_comp)=? AND
                C.deleted IS NULL", [
                    $json->id_sap,
                    $semana
                ]);
            } else {
                helper('date');
                $semana = date('W', now());
                $query = $this->db->query("SELECT
                C.id,
                CU2.CardName,
                CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'Vendedor',
                C.fecha_comp,CONVERT(varchar,C.hora_inicio,100) AS 'hora_inicioTitulo',
                CONVERT(varchar,C.hora_inicio,8) AS 'hora_inicio',
                CONVERT(varchar,C.hora_fin,100) AS 'hora_finTitulo',
                CONVERT(varchar,C.hora_fin,8) AS 'hora_fin',
                C.estatus,
                C.objetivo_gen,
                C.CardCode,
                CU2.p_geografica,
                C.id_sap,
                C.LineNum,
                CU2.descripcion
                FROM
                citas AS C,
                (SELECT
                CRD1.Address AS 'descripcion',
                OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS CardName,
                CONCAT(
                OCRD.Address COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                OCRD.ZipCode COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
                OCRD.city COLLATE SQL_Latin1_General_CP850_CI_AS)AS p_geografica,
                CRD1.LineNum AS 'LineNum'
                FROM
                [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                WHERE
                OCRD.CardCode=CRD1.CardCode AND
                OCRD.SlpCode!=-1 AND
                OCRD.SlpCode!=0 AND
                CRD1.AdresType = 'S' AND
                OCRD.CardType = 'C' AND
                OCRD.CardName IS NOT NULL
                UNION ALL
                SELECT 'Sin descripción' AS 'descripcion',
                CAST(id as varchar) AS 'CardCode',
                raz_social AS 'CardName',
                CONCAT(direccion,' ',municipio, ' ',estado) AS 'p_geografica',
                '-1' AS 'LineNum'
                FROM cuentas
                ) AS CU2,
                (SELECT
                nombres,
                apellido_p,
                apellido_m,
                id_vendedorSap
                FROM Colaboradores
                WHERE
                estado=1
                ) AS CO
                WHERE
                C.CardCode=CU2.CardCode AND
                C.LineNum=CU2.LineNum AND
                C.id_sap=CO.id_vendedorSap AND
                C.CardCode=? AND
                DATEPART(isowk,C.fecha_comp)=? AND
                C.deleted IS NULL", [
                    $json->CardCode,
                    $semana
                ]);
            }
        }

        $visitas = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'visitas' => $visitas
        ]);
    }

    public function getActividades()
    {
        $json = $this->request->getJSON();
        $query = $this->db->query(
            "SELECT
            A.id,
            T3.CardName,
            T2.CntctCode,
            T2.contacto,
            A.detalles,
            A.id_cot,
            A.estatus,
            A.objetivo,
            T2.CardCode,
            T3.LineNum,
            T3.descripcion,
            CI.id_sap
            FROM actividades A,
            (SELECT
            CAST(CntctCode AS varchar) AS 'CntctCode',
            CONCAT(FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',
            LastName COLLATE SQL_Latin1_General_CP850_CI_AS) AS 'contacto',
            CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCPR
            UNION ALL
            SELECT
            id_2,
            contacto,
            CardCode
            FROM
            contactos
            ) AS T2,
            (SELECT
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            CRD1.LineNum AS 'LineNum',
            CRD1.Address AS 'descripcion',
            CRD1.U_SalesUPID AS 'SlpCode'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
            WHERE
            OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
            UNION ALL
            SELECT CAST(id as nvarchar) AS 'CardCode',
            raz_social AS 'CardName',
            '-1' AS 'LineNum',
            'Sin descripción' AS 'descripcion',
            id_sap AS 'SlpCode'
            FROM cuentas
            ) AS T3,
            (SELECT
            id,
            CardCode,
            LineNum,
            id_sap
            FROM citas
            ) AS CI
            WHERE
            A.CntctCode=T2.CntctCode AND
            T2.CardCode=T3.CardCode AND
            T3.CardCode=CI.CardCode AND
            T3.LineNum=CI.LineNum AND
            T3.SlpCode=CI.id_sap AND
            A.id_cita=CI.id AND
            A.id_cita = ?",
            [$json->id_cita]
        );

        $actividades = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'actividades' => $actividades
        ]);
    }

    public function getImgLevantamiento(int $id)
    {
        $query = $this->db->query("SELECT * FROM imglevantamientos WHERE id_levantamiento=?", [$id]);
        $imagenes = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'imagenes' => $imagenes
        ]);
    }

    public function deleteImagen()
    {
        $json = $this->request->getJSON();
        $id = isset($json->id) ? $json->id : 0;
        $query = $this->db->query("SELECT imagen FROM imglevantamientos WHERE id=?", [$id]);
        $imagen = $query->getResult()[0]->imagen;
        unlink('E:/xampp/htdocs/lineup/public/files/salesup/levantamientos/' . $imagen);
        $this->db->query("DELETE FROM imglevantamientos WHERE id=?", [$id]);
        return $this->getResponse([
            'message' => 'Data successfully deleted'
        ]);
    }

    public function saveImagenLevantamiento()
    {
        $post = $this->request->getPost();
        $datos = json_decode($post['datos']);
        $sinArchivo = $post['sinarchivo'];
        $editar = $post['editar'];
        if ($sinArchivo == 'true') {
            $this->db->query(
                "UPDATE imglevantamientos SET numparte=?,comentarios=? WHERE id=?",
                [$datos->numParte, $datos->comentarios, $datos->id]
            );

            return $this->getResponse([
                'message' => 'Data successfully saved'
            ]);
        } else {
            $archivo = $this->request->getFile('archivo');
            if ($archivo->isValid() && !$archivo->hasMoved()) {
                if ($archivo->move("E:/xampp/htdocs/lineup/public/files/salesup/levantamientos")) {
                    $nombre_archivo = $archivo->getName();
                    if ($editar == 'true') {
                        $query = $this->db->query("SELECT imagen FROM imglevantamientos WHERE id=?", [$datos->id]);
                        $imagen = $query->getResult()[0]->imagen;
                        unlink('E:/xampp/htdocs/lineup/public/files/salesup/levantamientos/' . $imagen);
                        $this->db->query(
                            "UPDATE imglevantamientos SET imagen=?,numparte=?,comentarios=? WHERE id=?",
                            [$nombre_archivo, $datos->numParte, $datos->comentarios, $datos->id]
                        );
                    } else {
                        $this->db->query(
                            "INSERT INTO imglevantamientos(imagen,numparte,comentarios,id_levantamiento) VALUES(?,?,?,?)",
                            [$nombre_archivo, $datos->numParte, $datos->comentarios, $datos->id_levantamiento]
                        );
                    }

                    return $this->getResponse([
                        'message' => 'Data successfully saved'
                    ]);
                }
            }
        }
    }

    public function getCotizaciones(int $slpCode)
    {
        $query = $this->db->query("SELECT [DocNum],[NumatCard]
        FROM 
        SYN_OQUT
        WHERE 
        [DocStatus] = 'O'
        AND [SlpCode] = ?", [$slpCode]);

        $cotizaciones = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'cotizaciones' => $cotizaciones
        ]);
    }



    public function getCompromisos()
    {
        $json = $this->request->getJSON();

        if (isset($json->tipo)) {
            $tipo = $json->tipo;
            $mes = $json->mes ? $json->mes : 0;
            $id_zona = isset($json->id_zona) ? $json->id_zona : 0;
            $id_sap = isset($json->id_sap) ? $json->id_sap : 0;
            switch ($tipo) {
                case 'Vendedor':
                    $query = $this->db->query(
                        "SELECT
                        C.id,
                        C.detalles,
                        C.fecha,
                        CONVERT(varchar,C.hora,100) AS 'horaTitulo',
                        CONVERT(varchar,C.hora,8) AS 'hora',
                        C.estatus,
                        C.CardCode,
                        C.LineNum,
                        C.id_sap,
                        T2.contacto,
                        C.CntctCode,
                        T3.CardName,
                        T3.descripcion
                        FROM compromisos AS C,
                        (SELECT
                        CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
                        CONCAT(
                        OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS, ' ',
                        OCPR.LastName COLLATE SQL_Latin1_General_CP850_CI_AS
                        ) AS 'contacto',
                        OCPR.CardCode
                        FROM [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
                        WHERE Active='Y'
                        UNION ALL
                        SELECT CON.id_2 AS 'CntctCode',
                        CON.contacto,
                        CON.CardCode
                        FROM contactos AS CON
                        ) AS T2,
                        (SELECT
                        OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                        OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                        CRD1.LineNum AS 'LineNum',
                        CRD1.Address AS 'descripcion'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                        [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                        WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
                        UNION ALL
                        SELECT
                        raz_social AS 'CardName',
                        CAST(id AS varchar) AS 'CardCode',
                        '-1' AS 'LineNum',
                        'Sin descripción' AS descripcion
                        FROM cuentas
                        ) AS T3
                        WHERE
                        C.CntctCode=T2.CntctCode AND
                        C.CardCode=T3.CardCode AND
                        C.LineNum=T3.LineNum AND
                        C.id_sap = ? AND
                        MONTH(C.fecha) = ? AND
                        C.id_actividad=0",
                        [
                            $id_sap,
                            $mes
                        ]
                    );
                    break;
                case 'Gerente':
                    $query = $this->db->query(
                        "SELECT
                        C.id,
                        C.detalles,
                        C.fecha,
                        CONVERT(varchar,C.hora,100) AS 'horaTitulo',
                        CONVERT(varchar,C.hora,8) AS 'hora',
                        C.estatus,
                        C.CardCode,
                        C.LineNum,
                        C.id_sap,
                        T2.contacto,
                        C.CntctCode,
                        T3.descripcion,
                        T3.CardName
                        FROM compromisos AS C,
                        (SELECT
                        CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
                        CONCAT(
                        OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS, ' ',
                        OCPR.LastName COLLATE SQL_Latin1_General_CP850_CI_AS
                        ) AS 'contacto',
                        OCPR.CardCode
                        FROM [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
                        WHERE Active='Y'
                        UNION ALL
                        SELECT CON.id_2 AS 'CntctCode',
                        CON.contacto,
                        CON.CardCode
                        FROM contactos AS CON
                        ) AS T2,
                        (SELECT
                        OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                        OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                        CRD1.LineNum AS 'LineNum',
                        CRD1.Address AS 'descripcion'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                        [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                        WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
                        UNION ALL
                        SELECT
                        raz_social AS 'CardName',
                        CAST(id AS varchar) AS 'CardCode',
                        '-1' AS 'LineNum',
                        'Sin descripción' AS descripcion
                        FROM cuentas
                        ) AS T3,
                        (SELECT
                        id_sap,
                        id_zona
                        FROM det_vendedores
                        ) AS DT
                        WHERE
                        C.CntctCode=T2.CntctCode AND
                        C.CardCode=T3.CardCode AND
                        C.LineNum=T3.LineNum AND
                        C.id_sap=DT.id_sap AND
						DT.id_zona=? AND
                        MONTH(C.fecha) = ? AND
                        C.id_actividad=0",
                        [
                            $id_zona,
                            $mes
                        ]
                    );
                    break;
                case 'Corporativo':
                    $query = $this->db->query(
                        "SELECT
                        C.id,
                        C.detalles,
                        C.fecha,
                        CONVERT(varchar,C.hora,100) AS 'horaTitulo',
                        CONVERT(varchar,C.hora,8) AS 'hora',
                        C.estatus,
                        C.CardCode,
                        C.LineNum,
                        C.id_sap,
                        T2.contacto,
                        C.CntctCode,
                        T3.CardName,
                        T3.descripcion
                        FROM compromisos AS C,
                        (SELECT
                        CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
                        CONCAT(
                        OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS, ' ',
                        OCPR.LastName COLLATE SQL_Latin1_General_CP850_CI_AS
                        ) AS 'contacto',
                        OCPR.CardCode
                        FROM [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
                        WHERE Active='Y'
                        UNION ALL
                        SELECT CON.id_2 AS 'CntctCode',
                        CON.contacto,
                        CON.CardCode
                        FROM contactos AS CON
                        ) AS T2,
                        (SELECT
                        OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                        OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                        CRD1.LineNum AS 'LineNum',
                        CRD1.Address AS 'descripcion'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                        [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                        WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
                        UNION ALL
                        SELECT
                        raz_social AS 'CardName',
                        CAST(id AS varchar) AS 'CardCode',
                        '-1' AS 'LineNum',
                        'Sin descripción' AS descripcion
                        FROM cuentas
                        ) AS T3
                        WHERE
                        C.CntctCode=T2.CntctCode AND
                        C.CardCode=T3.CardCode AND
                        C.LineNum=T3.LineNum AND
                        MONTH(C.fecha) = ? AND
                        C.id_actividad=0",
                        [
                            $mes
                        ]
                    );
                    break;
            }
        } else if (isset($json->id_actividad) && $json->id_actividad != 0) {
            $query = $this->db->query("SELECT
            C.id,
            C.detalles,
            C.fecha,
            CONVERT(varchar,C.hora,100) AS 'horaTitulo',
            CONVERT(varchar,C.hora,8) AS 'hora',
            C.estatus,
            C.CardCode,
            C.LineNum,
            C.id_sap,
            T2.contacto,
            C.CntctCode,
            T3.descripcion,
            T3.CardName
            FROM compromisos AS C,
            (SELECT
            CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
            CONCAT(
            OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS, ' ',
            OCPR.LastName COLLATE SQL_Latin1_General_CP850_CI_AS
            ) AS 'contacto',
            OCPR.CardCode
            FROM [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
            WHERE Active='Y'
            UNION ALL
            SELECT CON.id_2 AS 'CntctCode',
            CON.contacto,
            CON.CardCode
            FROM contactos AS CON
            ) AS T2,
            (SELECT
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            CRD1.LineNum AS 'LineNum',
            CRD1.Address AS 'descripcion'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
            WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
            UNION ALL
            SELECT
            raz_social AS 'CardName',
            CAST(id AS varchar) AS 'CardCode',
            '-1' AS 'LineNum',
            'Sin descripción' AS descripcion
            FROM cuentas
            ) AS T3
            WHERE
            C.CntctCode=T2.CntctCode AND
            C.CardCode=T3.CardCode AND
            C.LineNum=T3.LineNum AND
            C.id_actividad=?", [
                $json->id_actividad
            ]);
        } else {
            $fecha_plan = $json->fecha_create;
            $date = new DateTime($fecha_plan);
            $semana = $date->format('W');
            $query = $this->db->query("SELECT
            C.id,
            C.detalles,
            C.fecha,
            CONVERT(varchar,C.hora,100) AS 'horaTitulo',
            CONVERT(varchar,C.hora,8) AS 'hora',
            C.estatus,
            C.CardCode,
            C.LineNum,
            C.id_sap,
            T2.contacto,
            C.CntctCode,
            T3.descripcion,
            T3.CardName
            FROM compromisos AS C,
            (SELECT
            CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
            CONCAT(
            OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS, ' ',
            OCPR.LastName COLLATE SQL_Latin1_General_CP850_CI_AS
            ) AS 'contacto',
            OCPR.CardCode
            FROM [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
            WHERE Active='Y'
            UNION ALL
            SELECT CON.id_2 AS 'CntctCode',
            CON.contacto,
            CON.CardCode
            FROM contactos AS CON
            ) AS T2,
            (SELECT
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            CRD1.LineNum AS 'LineNum',
            CRD1.Address AS 'descripcion'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
            WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
            UNION ALL
            SELECT
            raz_social AS 'CardName',
            CAST(id AS varchar) AS 'CardCode',
            '-1' AS 'LineNum',
            'Sin descripción' AS descripcion
            FROM cuentas
            ) AS T3
            WHERE
            C.CntctCode=T2.CntctCode AND
            C.CardCode=T3.CardCode AND
            C.LineNum=T3.LineNum AND
            C.id_sap=? AND
            DATEPART(isowk,C.fecha) = ? AND
            C.id_actividad=0", [
                $json->id_sap,
                $semana
            ]);
        }

        $compromisos = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'compromisos' => $compromisos
        ]);
    }

    public function actualizarProy()
    {
        $id_plan = $this->request->getJSON()->id_plan;
        $query = $this->db->query("SELECT fecha_create,id_sap FROM planes WHERE id = ?", [$id_plan]);
        $result = $query->getResult();
        $fecha_create = $result[0]->fecha_create;
        $id_sap = $result[0]->id_sap;
        $date = new DateTime($fecha_create);
        $año = $date->format('Y');
        $mes = $date->format('n');
        $semana = $date->format('W');
        $ponderado = json_decode($this->getPonderado2($año, $mes, $semana, $id_sap));
        $query = $this->db->query("UPDATE planes SET proy_men = ?, proy_sem = ? WHERE id = ?", [
            $ponderado->proy_men,
            $ponderado->proy_sem,
            $id_plan
        ]);

        return $this->getResponse([
            'message' => 'Data successfully updated',
            'proy_men' => $ponderado->proy_men,
            'proy_sem' => $ponderado->proy_sem
        ]);
    }

    public function getLevantamientos()
    {
        $json = $this->request->getJSON();
        if (isset($json->tipo)) {
            $tipo = $json->tipo;
            $mes = $json->mes ? $json->mes : 0;
            $id_zona = isset($json->id_zona) ? $json->id_zona : 0;
            $id_sap = isset($json->id_sap) ? $json->id_sap : 0;
            switch ($tipo) {
                case 'Vendedor':
                    $query = $this->db->query(
                        "SELECT
                        L.id,
                        L.nombre_apli,
                        L.fecha_entrega,
                        L.comentarios,
                        L.id_sap,
                        L.estatus,
                        T2.contacto,
                        L.CntctCode,
                        L.CardCode,
                        L.LineNum,
                        T3.descripcion,
                        T3.CardName
                        FROM
                        levantamientos AS L,
                        (SELECT 
                        CAST(CntctCode AS varchar) AS 'CntctCode',
                        CardCode AS 'CardCode',
                        CONCAT(FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',LastName COLLATE SQL_Latin1_General_CP850_CI_AS) AS 'contacto'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCPR
                        UNION ALL
                        SELECT
                        id_2 AS 'CntctCode',
                        CardCode,
                        contacto
                        FROM contactos
                        )AS T2,
                        (SELECT
                        OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                        OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                        CRD1.Address AS 'descripcion',
						CRD1.LineNum AS 'LineNum'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCRD,
                        [SAPSERVER].[SBO_ECN].[dbo].CRD1
                        WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
                        UNION ALL
                        SELECT
                        raz_social AS 'CardName',
                        CAST(id AS nvarchar) AS 'CardCode',
                        'Sin descripción' AS descripcion,
						'-1' AS 'LineNum'
                        FROM cuentas
                        ) AS T3
                        WHERE
                        L.CntctCode=T2.CntctCode AND
                        L.CardCode=T3.CardCode AND
                        L.LineNum=T3.LineNum AND
                        L.id_sap = ? AND
                        MONTH(L.fecha_entrega) = ?
                        AND L.id_actividad=0",
                        [
                            $id_sap,
                            $mes
                        ]
                    );
                    break;
                case 'Gerente':
                    $query = $this->db->query(
                        "SELECT
                        L.id,
                        L.nombre_apli,
                        L.fecha_entrega,
                        L.comentarios,
                        L.id_sap,
                        L.estatus,
                        T2.contacto,
                        L.CntctCode,
                        L.CardCode,
                        L.LineNum,
                        T3.descripcion,
                        T3.CardName
                        FROM
                        levantamientos AS L,
                        (SELECT 
                        CAST(CntctCode AS varchar) AS 'CntctCode',
                        CardCode AS 'CardCode',
                        CONCAT(FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',LastName COLLATE SQL_Latin1_General_CP850_CI_AS) AS 'contacto'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCPR
                        UNION ALL
                        SELECT
                        id_2 AS 'CntctCode',
                        CardCode,
                        contacto
                        FROM contactos
                        )AS T2,
                        (SELECT
                        OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                        OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                        CRD1.Address AS 'descripcion',
						CRD1.LineNum AS 'LineNum'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCRD,
                        [SAPSERVER].[SBO_ECN].[dbo].CRD1
                        WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
                        UNION ALL
                        SELECT
                        raz_social AS 'CardName',
                        CAST(id AS nvarchar) AS 'CardCode',
                        'Sin descripción' AS descripcion,
						'-1' AS 'LineNum'
                        FROM cuentas
                        ) AS T3,
                        (SELECT
                        id_zona,
                        id_sap
                        FROM det_vendedores
                        ) AS DT
                        WHERE
                        L.CntctCode=T2.CntctCode AND
                        L.CardCode=T3.CardCode AND
                        L.LineNum=T3.LineNum AND
                        L.id_sap=DT.id_sap AND
                        DT.id_zona = ? AND
                        MONTH(L.fecha_entrega) = ?
                        AND L.id_actividad=0",
                        [
                            $id_zona,
                            $mes
                        ]
                    );
                    break;
                case 'Corporativo':
                    $query = $this->db->query(
                        "SELECT
                        L.id,
                        L.nombre_apli,
                        L.fecha_entrega,
                        L.comentarios,
                        L.id_sap,
                        L.estatus,
                        T2.contacto,
                        L.CntctCode,
                        L.CardCode,
                        L.LineNum,
                        T3.descripcion,
                        T3.CardName
                        FROM
                        levantamientos AS L,
                        (SELECT 
                        CAST(CntctCode AS varchar) AS 'CntctCode',
                        CardCode AS 'CardCode',
                        CONCAT(FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',LastName COLLATE SQL_Latin1_General_CP850_CI_AS) AS 'contacto'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCPR
                        UNION ALL
                        SELECT
                        id_2 AS 'CntctCode',
                        CardCode,
                        contacto
                        FROM contactos
                        )AS T2,
                        (SELECT
                        OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                        OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                        CRD1.Address AS 'descripcion',
						CRD1.LineNum AS 'LineNum'
                        FROM
                        [SAPSERVER].[SBO_ECN].[dbo].OCRD,
                        [SAPSERVER].[SBO_ECN].[dbo].CRD1
                        WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
                        UNION ALL
                        SELECT
                        raz_social AS 'CardCode',
                        CAST(id AS nvarchar) AS 'CardCode',
                        'Sin descripción' AS descripcion,
						'-1' AS 'LineNum'
                        FROM cuentas
                        ) AS T3
                        WHERE
                        L.CntctCode=T2.CntctCode AND
                        L.CardCode=T3.CardCode AND
                        L.LineNum=T3.LineNum AND
                        MONTH(L.fecha_entrega) = ?
                        AND L.id_actividad=0",
                        [
                            $mes
                        ]
                    );
                    break;
            }
        } else if (isset($json->id_actividad) && $json->id_actividad != 0) {
            $query = $this->db->query("SELECT
            L.id,
            L.nombre_apli,
            L.fecha_entrega,
            L.comentarios,
            L.id_sap,
            L.estatus,
            T2.contacto,
            L.CntctCode,
            L.CardCode,
            L.LineNum,
            T3.descripcion,
            T3.CardName
            FROM
            levantamientos AS L,
            (SELECT 
            CAST(CntctCode AS varchar) AS 'CntctCode',
            CardCode AS 'CardCode',
            CONCAT(FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',LastName COLLATE SQL_Latin1_General_CP850_CI_AS) AS 'contacto'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCPR
            UNION ALL
            SELECT
            id_2 AS 'CntctCode',
            CardCode,
            contacto
            FROM contactos
            )AS T2,
            (SELECT
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            CRD1.Address AS 'descripcion',
            CRD1.LineNum AS 'LineNum'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1
            WHERE
            OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
            UNION ALL
            SELECT
            raz_social AS 'CardName',
            CAST(id AS nvarchar) AS 'CardCode',
            'Sin descripción' AS descripcion,
            '-1' AS 'LineNum'
            FROM cuentas
            ) AS T3
            WHERE
            L.CntctCode=T2.CntctCode AND
            L.CardCode=T3.CardCode AND
            L.LineNum=T3.LineNum AND
            L.id_actividad=?", [
                $json->id_actividad
            ]);
        } else {
            $fecha_plan = $json->fecha_create;
            $date = new DateTime($fecha_plan);
            $semana = $date->format('W');
            $query = $this->db->query("SELECT
            L.id,
            L.nombre_apli,
            L.fecha_entrega,
            L.comentarios,
            L.id_sap,
            L.estatus,
            T2.contacto,
            L.CntctCode,
            L.CardCode,
            L.LineNum,
            T3.descripcion,
            T3.CardName
            FROM
            levantamientos AS L,
            (SELECT 
            CAST(CntctCode AS varchar) AS 'CntctCode',
            CardCode AS 'CardCode',
            CONCAT(FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',LastName COLLATE SQL_Latin1_General_CP850_CI_AS) AS 'contacto'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCPR
            UNION ALL
            SELECT
            id_2 AS 'CntctCode',
            CardCode,
            contacto
            FROM contactos
            )AS T2,
            (SELECT
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            CRD1.Address AS 'descripcion',
            CRD1.LineNum AS 'LineNum'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1
            WHERE OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!=0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
            UNION ALL
            SELECT
            raz_social AS 'CardName',
            CAST(id AS nvarchar) AS 'CardCode',
            'Sin descripción' AS descripcion,
            '-1' AS 'LineNum'
            FROM cuentas
            ) AS T3
            WHERE
            L.CntctCode=T2.CntctCode AND
            L.CardCode=T3.CardCode AND
            L.LineNum=T3.LineNum AND
            L.id_sap = ? AND
            DATEPART(isowk,L.fecha_entrega) = ?
            AND L.id_actividad=0", [
                $json->id_sap,
                $semana
            ]);
        }

        $levantamientos = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'levantamientos' => $levantamientos
        ]);
    }

    public function getCuenta($id)
    {
        helper('date');
        $semana = date('W', now());
        $exp = explode('_',$id);
        $id=$exp[0];
        $LineNum=$exp[1];
        if (is_numeric($id)) {
            $query = $this->db->query("SELECT
            CAST(C.id AS nvarchar) AS 'CardCode',
            C.raz_social AS 'CardName',
            C.municipio,
            C.estado,
            Z.zona,
            CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS vendedor,
            C.produce
            FROM cuentas C,
            (SELECT
            nombres,
            apellido_p,
            apellido_m,
            id_vendedorSap FROM Colaboradores
            WHERE estado=1
            ) AS CO,
            (SELECT
            id,
            zona FROM zonas
            ) AS Z
            WHERE C.id_sap=CO.id_vendedorSap AND
            C.id_zona=Z.id AND
            C.id=?", [$id]);

            $cuenta = $query->getResult();

        } else {
            $query = $this->db->query("SELECT
            T1.CardCode,
            T1.CardName,
            T1.municipio,
            T1.estado,
            T4.zona,
            T2.vendedor
            FROM
            (SELECT
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            OCRD.City AS 'municipio',
            OCRD.State1 AS 'estado',
            CRD1.U_SalesUpID AS 'SlpCode',
            CRD1.LineNUM AS 'LineNum'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
            WHERE
            OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
            OCRD.SlpCode!= 0 AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
            ) AS T1,
            (SELECT
            CONCAT(nombres,' ',apellido_p,' ',apellido_m) AS vendedor,
            id_vendedorSap
            FROM Colaboradores
            WHERE estado=1
            ) AS T2,
            (SELECT
            id_zona,
            id_sap
            FROM det_vendedores
            ) AS T3,
            (SELECT
            id,
            zona
            FROM zonas
            ) AS T4
            WHERE
            T1.SlpCode=T2.id_vendedorSap AND
            T2.id_vendedorSap=T3.id_sap AND
            T3.id_zona=T4.id AND
            T1.CardCode=? AND
            T1.LineNum=?
            ", [$id,$LineNum]);
            $cuenta = $query->getResult();
        }

        $query = $this->db->query("SELECT
        C.contacto,
        T2.CardName,
        CO.vendedor
        FROM
        (SELECT
        contacto AS 'contacto',
        id_2 AS 'CntctCode',
        CardCode AS 'CardCode'
        FROM contactos
        UNION ALL
        SELECT
        OCPR.Name COLLATE SQL_Latin1_General_CP850_CI_AS AS 'contacto',
        OCPR.CntctCode AS 'CntctCode',
        OCPR.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode'
        FROM
        [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
        WHERE
        OCPR.Active='Y'
        ) AS C,
        (SELECT
        OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'Cardcode',
        OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
        CRD1.U_SalesUpID AS 'SlpCode',
        CRD1.LineNum AS 'LineNum'
        FROM
        [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
        [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
        WHERE
        OCRD.CardCode=CRD1.CardCode AND
        OCRD.SlpCode!=0 AND
        OCRD.SlpCode!=-1 AND
        OCRD.CardType = 'C' AND
        CRD1.AdresType = 'S'
        UNION ALL
        SELECT CAST(id as nvarchar) AS 'CardCode',
        raz_social AS 'CardName',
        id_sap AS 'SlpCode',
        '-1' AS 'LineNum'
        FROM cuentas
        ) AS T2,
        (SELECT
        CONCAT(nombres,' ',apellido_p,' ',apellido_m) AS vendedor,
        id_vendedorSap
        FROM Colaboradores
        WHERE estado=1
        ) AS CO
        WHERE
        C.CardCode=T2.Cardcode AND
        T2.SlpCode=CO.id_vendedorSap AND
        C.CardCode=? AND
        T2.LineNum=?", [$id,$LineNum]);

            $contactos = $query->getResult();

        $query = $this->db->query("SELECT * FROM citas WHERE CardCode=? AND DATEPART(isowk,fecha_comp)=?", [$id, $semana]);
        $citas = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'cuenta' => $cuenta,
            'contactos' => $contactos,
            'citas' => $citas
        ]);
    }

    public function getCuentas()
    {
        $tipo = isset($this->request->getJSON()->tipo) ? $this->request->getJSON()->tipo : null;
        $select = isset($this->request->getJSON()->select) ? $this->request->getJSON()->select : null;
        $id_sap = isset($this->request->getJSON()->id_sap) ? $this->request->getJSON()->id_sap : 0;
        $id_zona = isset($this->request->getJSON()->id_zona) ? $this->request->getJSON()->id_zona : 0;
        if (!$select) {
            switch ($tipo) {
                case 'Vendedor':
                    $queryLeads = $this->db->query("SELECT
                    C.*,
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS lider
                    FROM
                    cuentas C,Colaboradores CO
                    WHERE C.id_sap=CO.id_vendedorSap AND
                    C.id_sap = ? AND
                    CO.id_vendedorSap!=-1 AND
                    CO.id_vendedorSap!=0 AND
                    CO.estado=1", [$id_sap]);

                    $querySap = $this->db->query("SELECT
                    C.CardCode,
                    MAX(C.CardName) AS 'CardName',
                    MAX(C.LicTradNum) AS 'LicTradNum',
                    MAX(C.descripcion) AS 'descripcion',
                    MAX(CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m)) AS lider,
                    MAX(C.LineNum) AS 'LineNum'
                    FROM
                    (SELECT
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    OCRD.LicTradNum COLLATE SQL_Latin1_General_CP850_CI_AS AS 'LicTradNum',
                    CRD1.Address AS 'descripcion',
                    CRD1.U_SalesUpID AS 'SlpCode',
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.SlpCode!=-1 AND
                    OCRD.SlpCode!=0 AND
                    OCRD.CardType = 'C' AND
                    CRD1.AdresType = 'S' AND
                    OCRD.CardName IS NOT NULL
                    ) AS C,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM Colaboradores
                    WHERE estado=1
                    ) AS CO
                    WHERE
                    C.SlpCode = CO.id_vendedorSap AND
                    C.SlpCode = ?
                    GROUP BY C.CardCode", [$id_sap]);
                    break;
                case 'Corporativo':
                    $queryLeads = $this->db->query("SELECT C.*,
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS lider
                    FROM cuentas C,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM
                    Colaboradores
                    WHERE estado=1
                    ) AS CO
                    WHERE
                    C.id_sap=CO.id_vendedorSap");

                    $querySap = $this->db->query("SELECT
                    C.CardCode,
                    MAX(C.CardName) AS 'CardName',
                    MAX(C.LicTradNum) AS 'LicTradNum',
                    MAX(C.descripcion) AS 'descripcion',
                    MAX(CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m)) AS lider,
                    MAX(C.LineNum) AS 'LineNum'
                    FROM
                    (SELECT
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    OCRD.LicTradNum COLLATE SQL_Latin1_General_CP850_CI_AS AS 'LicTradNum',
                    CRD1.Address AS 'descripcion',
                    CRD1.U_SalesUpID AS 'SlpCode',
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.SlpCode!=-1 AND
                    OCRD.SlpCode!=0 AND
                    OCRD.CardType = 'C' AND
                    CRD1.AdresType = 'S' AND
                    OCRD.CardName IS NOT NULL
                    ) AS C,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM Colaboradores
                    WHERE estado=1
                    ) AS CO
                    WHERE
                    C.SlpCode = CO.id_vendedorSap
                    GROUP BY C.CardCode");
                    break;
                case 'Gerente':
                    $id_zona = $this->request->getJSON()->id_zona;
                    $queryLeads = $this->db->query("SELECT C.*,
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS lider
                    FROM cuentas C,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM Colaboradores
                    WHERE estado=1
                    ) AS CO,
                    (SELECT
                    id_zona,
                    id_sap FROM det_vendedores
                    ) AS DV
                    WHERE
                    C.id_sap=CO.id_vendedorSap AND
                    C.id_sap=DV.id_sap AND
                    DV.id_zona=?",[$id_zona]);

                    $querySap = $this->db->query("SELECT
                    C.CardCode,
                    MAX(C.CardName) AS 'CardName',
                    MAX(C.LicTradNum) AS 'LicTradNum',
                    MAX(C.descripcion) AS 'descripcion',
                    MAX(CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m)) AS lider,
                    MAX(C.LineNum) AS 'LineNum'
                    FROM
                    (SELECT
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    OCRD.LicTradNum COLLATE SQL_Latin1_General_CP850_CI_AS AS 'LicTradNum',
                    CRD1.Address AS 'descripcion',
                    CRD1.U_SalesUpID AS 'SlpCode',
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.SlpCode!=-1 AND
                    OCRD.SlpCode!=0 AND
                    OCRD.CardType = 'C' AND
                    CRD1.AdresType = 'S' AND
                    OCRD.CardName IS NOT NULL
                    ) AS C,
                    (SELECT
                    nombres,
                    apellido_p,
                    apellido_m,
                    id_vendedorSap
                    FROM Colaboradores
                    WHERE estado=1
                    ) AS CO,
                    (SELECT
                    id_zona,
                    id_sap
                    FROM det_vendedores
                    ) AS DT
                    WHERE
                    C.SlpCode = CO.id_vendedorSap AND
                    CO.id_vendedorSap=DT.id_sap AND
                    DT.id_zona = ?
                    GROUP BY C.CardCode", [$id_zona]);
                    break;
            }
            $cuentas = $querySap->getResult();
            $leads = $queryLeads->getResult();

            return $this->getResponse([
                'message' => 'Data successfully retrieved',
                'cuentas' => $cuentas,
                'leads' => $leads
            ]);
        } else {
            $query = $this->db->query("SELECT
            T1.CardCode,
            MAX(CAST(T1.CardName AS nvarchar)) AS 'CardName'
            FROM
            (SELECT '-1' AS 'LineNum',
            'Sin descripción' AS 'descripcion',
            CAST(C.id AS NVARCHAR) AS 'CardCode',
            C.raz_social AS 'CardName',
            C.id_sap AS 'SlpCode'
            FROM cuentas AS C
            UNION ALL
            SELECT CRD1.LineNum AS 'LineNum',
            CRD1.Address AS 'descripcion',
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            CRD1.U_SalesUpID AS 'SlpCode'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
            WHERE
            OCRD.CardCode=CRD1.CardCode AND
            OCRD.CardName IS NOT NULL AND
            OCRD.CardType = 'C' AND
            CRD1.AdresType = 'S' AND
            OCRD.CardName IS NOT NULL
            ) AS T1,
            (SELECT
            id_vendedorSap
            FROM Colaboradores
            WHERE estado=1
            ) AS Col
            WHERE
            T1.SlpCode=Col.id_vendedorSap AND
            T1.SlpCode=?
            GROUP BY T1.CardCode", [
                $id_sap
            ]);
        }

        $select = $query->getResult();


        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'select' => $select,
        ]);
    }

    public function getSucursales(){
        $json = $this->request->getJSON();
        $CardCode = isset($json->CardCode)?$json->CardCode:NULL;
        $id_sap = isset($json->id_sap)?$json->id_sap:0;
        if($CardCode && $id_sap!=0){
            $query = $this->db->query("SELECT Address AS 'descripcion',LineNum FROM [SAPSERVER].[SBO_ECN].[dbo].CRD1 WHERE CardCode=? AND U_SalesUPID=?",
        [$CardCode,$id_sap]);
        $sucursales = $query->getResult();
        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'sucursales' => $sucursales,
        ]);
    }else{
        return $this->getResponse([
            'error' => 'Error en los datos recibidos.'
        ],500);
    }
}

    public function saveContacto()
    {
        $json = $this->request->getJSON();
        $contacto = new Contacto();
        $contacto->save($json);

        $id_2 = $contacto->getInsertID();
        $this->db->query("UPDATE contactos set id_2=? WHERE id=?",[
            'L'.$id_2,
            $id_2
        ]);

        return $this->getResponse([
            'message' => 'Data successfully saved'
        ]);
    }

    public function addVisita()
    {
        $json = $this->request->getJSON();
        $exp = explode('_', $json->CardCode);
        $json->CardCode = $exp[0];
        $model = new Visita();
        $model->save($json);

        return $this->getResponse([
            'message' => 'Data successfully saved'
        ]);
    }

    public function deleteVisita()
    {
        $json = $this->request->getJSON();
        $model = new Visita();
        $model->delete($json->id_visita);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function deleteActividad()
    {
        $json = $this->request->getJSON();
        $model = new Actividad();
        $model->delete($json->id_actividad);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function getContactos()
    {
        $json = $this->request->getJSON();
        $select = isset($json->select) ? $json->select : false;
        $contactoCuenta = isset($json->contactoCuenta) ? $json->contactoCuenta : false;
        $CardCode = isset($json->CardCode)?$json->CardCode:'';
        $LineNum = isset($json->LineNum)?$json->LineNum:'';

        if ($contactoCuenta) {
            $queryLeads = $this->db->query("SELECT * FROM contactos WHERE CardCode=?", [$json->CardCode]);

            return $this->getResponse([
                'message' => 'Data successfully retrieved',
                'leads' => $queryLeads->getResult()
            ]);
        }

        if (!$select) {
            $tipo = $json->tipo;
            switch ($tipo) {
                case 'Vendedor':

                    $queryLeads = $this->db->query("SELECT DISTINCT C.*,
                    T2.SlpCode,
                    CAST(T2.CardName AS nvarchar) AS 'CardName',
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'lider'
                    FROM contactos C,
                    (SELECT
                    CAST(id AS NVARCHAR) AS 'CardCode',
                    raz_social AS 'CardName',
                    id_sap AS 'SlpCode'
                    FROM cuentas
                    UNION ALL
                    SELECT
					OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
					OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    CRD1.U_SalesUPID AS 'SlpCode'
                    FROM
					[SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
					[SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
					OCRD.CardCode=CRD1.CardCode AND
					OCRD.CardType='C' AND
					CRD1.AdresType = 'S' AND
					OCRD.SlpCode != -1 AND
					OCRD.SlpCode != 0
					) AS T2,
					(SELECT
					id_vendedorSap,
                    nombres,
                    apellido_p,
                    apellido_m
					FROM Colaboradores
					WHERE estado=1
					) AS CO
                    WHERE
                    C.CardCode=T2.CardCode AND
					T2.SlpCode=CO.id_vendedorSap AND
                    T2.SlpCode=?", [$json->id_sap]);

                    $querySAP = $this->db->query("SELECT DISTINCT
                    CON.CntctCode,
                    CON.Name AS 'Name',
                    CAST(T2.CardName AS nvarchar) AS 'CardName',
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'lider',
					CON.Position AS 'Position',
                    CON.Telefono AS 'Telefono',
                    CON.email AS 'email',
                    CON.direccion AS 'direccion',
                    T2.SlpCode AS 'SlpCode'
                    FROM
                    (SELECT CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
                    CONCAT(OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',OCPR.LastName)AS 'Name',
                    OCPR.CardCode AS 'CardCode',
					OCPR.Position AS 'Position',
                    OCPR.Tel1 AS 'Telefono',
                    OCPR.E_MailL AS 'email',
                    OCPR.Address AS 'direccion'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
                    WHERE
                    OCPR.Active='Y'
                    ) AS CON,
                    (SELECT
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    CRD1.U_SalesUpID AS 'SlpCode',
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.CardType = 'C' AND
                    CRD1.AdresType = 'S' AND
                    OCRD.SlpCode != -1 AND
                    OCRD.SlpCode != 0
                    UNION ALL
                    SELECT CAST(id AS nvarchar) AS 'CardCode',
                    raz_social AS 'CardName',
                    id_sap AS 'SlpCode',
                    '-1' AS 'LineNum'
                    FROM cuentas
                    ) AS T2,
                    (SELECT
                    id_vendedorSap,
                    nombres,
                    apellido_p,
                    apellido_m
                    FROM Colaboradores
                    WHERE estado=1
                    ) AS CO
                    WHERE
                    CON.CardCode = T2.CardCode AND
                    T2.SlpCode=CO.id_vendedorSap AND
                    CON.Name != '' AND
                    T2.SlpCode=?", [$json->id_sap]);
                    break;
                case 'Corporativo':

                    $queryLeads = $this->db->query("SELECT
                    DISTINCT C.*,
                    T2.SlpCode,
                    CAST(T2.CardName AS nvarchar) AS 'CardName',
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'lider'
                    FROM contactos C,
                    (SELECT CAST(id AS NVARCHAR) AS 'CardCode',
                    raz_social AS 'CardName',
                    id_sap AS 'SlpCode',
					'-1' AS 'LineNum'
                    FROM cuentas
                    UNION ALL
                    SELECT
					OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
					OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    CRD1.U_SalesUPID AS 'SlpCode',
					CRD1.LineNum AS 'LineNum'
                    FROM
					[SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
					[SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
					OCRD.CardCode=CRD1.CardCode AND
					OCRD.CardType='C' AND
					CRD1.AdresType = 'S' AND
					OCRD.SlpCode != -1 AND
					OCRD.SlpCode != 0
					) AS T2,
                    (SELECT
                    id_sap,
                    id_zona FROM det_vendedores
                    ) AS DT,
					(SELECT
					id_vendedorSap,
                    nombres,
                    apellido_p,
                    apellido_m
					FROM Colaboradores
					WHERE estado=1
					) AS CO
                    WHERE
                    C.CardCode=T2.CardCode AND
					T2.SlpCode=CO.id_vendedorSap AND
					C.LineNum=T2.LineNum");

                    $querySAP = $this->db->query("SELECT
                    DISTINCT CON.CntctCode,
                    CON.Name AS 'Name',
                    CAST(T2.CardName AS nvarchar) AS 'CardName',
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'lider',
					CON.Position AS 'Position',
                    CON.Telefono AS 'Telefono',
                    CON.email AS 'email',
                    CON.direccion AS 'direccion',
                    T2.SlpCode AS 'SlpCode'
                    FROM
                    (SELECT CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
                    CONCAT(OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',OCPR.LastName)AS 'Name',
                    OCPR.CardCode AS 'CardCode',
					OCPR.Position AS 'Position',
                    OCPR.Tel1 AS 'Telefono',
                    OCPR.E_MailL AS 'email',
                    OCPR.Address AS 'direccion'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
                    WHERE
                    OCPR.Active='Y'
                    ) AS CON,
                    (SELECT
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    CRD1.U_SalesUpID AS 'SlpCode',
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.CardType = 'C' AND
                    CRD1.AdresType = 'S' AND
                    OCRD.SlpCode != -1 AND
                    OCRD.SlpCode != 0
                    UNION ALL
                    SELECT CAST(id AS nvarchar) AS 'CardCode',
                    raz_social AS 'CardName',
                    id_sap AS 'SlpCode',
                    '-1' AS 'LineNum'
                    FROM cuentas
                    ) AS T2,
                    (SELECT
                    id_vendedorSap,
                    nombres,
                    apellido_p,
                    apellido_m
                    FROM Colaboradores
                    WHERE estado=1
                    ) AS CO
                    WHERE
                    CON.CardCode = T2.CardCode AND
                    T2.SlpCode=CO.id_vendedorSap AND
                    CON.Name != ''");

                    break;
                case 'Gerente':

                    $queryLeads = $this->db->query("SELECT DISTINCT C.*,
                    T2.SlpCode,
                    CAST(T2.CardName AS nvarchar) AS 'CardName',
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'lider'
                    FROM contactos C,
                    (SELECT CAST(id AS NVARCHAR) AS 'CardCode',
                    raz_social AS 'CardName',
                    id_sap AS 'SlpCode'
                    FROM cuentas
                    UNION ALL
                    SELECT
					OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
					OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    CRD1.U_SalesUPID AS 'SlpCode'
                    FROM
					[SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
					[SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
					OCRD.CardCode=CRD1.CardCode AND
					OCRD.CardType='C' AND
					CRD1.AdresType = 'S' AND
					OCRD.SlpCode != -1 AND
					OCRD.SlpCode != 0
					) AS T2,
                    (SELECT
                    id_sap,
                    id_zona FROM det_vendedores
                    ) AS DT,
					(SELECT
					id_vendedorSap,
                    nombres,
                    apellido_p,
                    apellido_m
					FROM Colaboradores
					WHERE estado=1
					) AS CO
                    WHERE
                    C.CardCode=T2.CardCode AND
                    T2.SlpCode=DT.id_sap AND
					T2.SlpCode=CO.id_vendedorSap AND
                    DT.id_zona=?", [$json->id_zona]);

                    $querySAP = $this->db->query("SELECT DISTINCT
                    CON.CntctCode,
                    CON.Name AS 'Name',
                    CAST(T2.CardName AS nvarchar) AS 'CardName',
                    CONCAT(CO.nombres,' ',CO.apellido_p,' ',CO.apellido_m) AS 'lider',
					CON.Position AS 'Position',
                    CON.Telefono AS 'Telefono',
                    CON.email AS 'email',
                    CON.direccion AS 'direccion',
                    T2.SlpCode AS 'SlpCode'
                    FROM
                    (SELECT CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
                    CONCAT(OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',OCPR.LastName)AS 'Name',
                    OCPR.CardCode AS 'CardCode',
					OCPR.Position AS 'Position',
                    OCPR.Tel1 AS 'Telefono',
                    OCPR.E_MailL AS 'email',
                    OCPR.Address AS 'direccion'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
                    WHERE
                    OCPR.Active='Y'
                    ) AS CON,
                    (SELECT
                    OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
                    OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
                    CRD1.U_SalesUpID AS 'SlpCode',
                    CRD1.LineNum AS 'LineNum'
                    FROM
                    [SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
                    [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
                    WHERE
                    OCRD.CardCode=CRD1.CardCode AND
                    OCRD.CardType = 'C' AND
                    CRD1.AdresType = 'S' AND
                    OCRD.SlpCode != -1 AND
                    OCRD.SlpCode != 0
                    UNION ALL
                    SELECT CAST(id AS nvarchar) AS 'CardCode',
                    raz_social AS 'CardName',
                    id_sap AS 'SlpCode',
                    '-1' AS 'LineNum'
                    FROM cuentas
                    ) AS T2,
                    (SELECT
                    id_vendedorSap,
                    nombres,
                    apellido_p,
                    apellido_m
                    FROM Colaboradores
                    WHERE estado=1
                    ) AS CO,
                    (SELECT
                    id_sap,
                    id_zona
                    FROM det_vendedores
                    ) AS DT
                    WHERE
                    CON.CardCode = T2.CardCode AND
                    T2.SlpCode=CO.id_vendedorSap AND
                    T2.SlpCode=DT.id_sap AND
                    CON.Name != ''
                    AND DT.id_zona = ?",
                    [$json->id_zona]);
                    break;
            }
            return $this->getResponse([
                'message' => 'Data successfully retrieved',
                'leads' => $queryLeads->getResult(),
                'sap' => $querySAP->getResult()
            ]);
        } else {
            $query = $this->db->query("SELECT
            DISTINCT CON.CntctCode,
            CON.Name AS 'Name'
            FROM
            (SELECT
            id_2 AS 'CntctCode',
            contacto AS 'Name',
            CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode'
            FROM contactos
            UNION ALL
            SELECT CAST(OCPR.CntctCode AS varchar) AS 'CntctCode',
            CONCAT(OCPR.FirstName COLLATE SQL_Latin1_General_CP850_CI_AS,' ',OCPR.LastName)AS 'Name',
            OCPR.CardCode AS 'CardCode'
            FROM
            [SAPSERVER].[SBO_ECN].[dbo].OCPR AS OCPR
			WHERE
			OCPR.Active='Y'
			) AS CON,
			(SELECT
			OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
			CRD1.U_SalesUpID AS 'SlpCode',
			CRD1.LineNum AS 'LineNum'
			FROM
			[SAPSERVER].[SBO_ECN].[dbo].OCRD AS OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1 AS CRD1
			WHERE
			OCRD.CardCode=CRD1.CardCode AND
			OCRD.CardType = 'C' AND
			CRD1.AdresType = 'S' AND
			OCRD.SlpCode != -1 AND
			OCRD.SlpCode != 0
			UNION ALL
			SELECT CAST(id AS nvarchar) AS 'CardCode',
			id_sap AS 'SlpCode',
			'-1' AS 'LineNum'
			FROM cuentas
			) AS T2,
			(SELECT
			id_vendedorSap
			FROM Colaboradores
			WHERE estado=1
			) AS CO
            WHERE
            CON.CardCode = T2.CardCode AND
			T2.SlpCode=CO.id_vendedorSap AND
			CON.Name != ''
			AND T2.CardCode = ? AND
			T2.LineNum = ?",[
                $CardCode,
                $LineNum
            ]);

            $select = $query->getResult();


            return $this->getResponse([
                'message' => 'Data successfully retrieved',
                'select' => $select
            ]);
        }
    }

    public function addActividad()
    {
        $json = $this->request->getJSON();
        $model = new Actividad();

        $model->save($json);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function confirmPlan()
    {
        $json = $this->request->getJSON();
        $model = new Plan();

        $query = $this->db->query("SELECT fecha_create FROM planes WHERE id=?", [$json->id_plan]);
        $result = $query->getResult();
        $fecha = $result[0]->fecha_create ? $result[0]->fecha_create : NULL;
        $id_sap = isset($json->id_sap) ? $json->id_sap : 0;
        if ($fecha != NULL) {
            $date = new DateTime($fecha);
            $mes = $date->format('n');
            $año = $date->format('Y');
            $query = $this->db->query(
                "SELECT id FROM planes WHERE fecha_create=(SELECT MIN(fecha_create) AS fecha FROM
                planes WHERE MONTH(fecha_create)=?
                AND YEAR(fecha_create)=? AND confirmacion=1) AND id_sap=?",
                [$mes, $año, $id_sap]
            );
            $result = $query->getResult();
            if (count($result) == 0) {
                $query = $this->db->query("SELECT 								
			    C.FechaCont
			   ,C.DocEntry
			   ,C.DocNum
			   ,C.Cliente
			   ,C.nvendedor
			   ,C.vendedor
			   ,C.zona
			   ,c.oficina
			   ,SUM(C.TOTPARTITEM) AS TOTAL
			   ,C.Referencia 
			   ,[C].[% Cierre] as porc_cierre
			   ,C.U_FECHACIERRE
			   ,C.Comments
			   ,C.Etapa
			   ,C.etapacodigo
			   ,C.industria
			   ,SUM(C.MontoPond) AS MontoPond
			   ,isnull(U_BitrixID,0) as U_BitrixID
			   ,datediff(day,U_FECHACIERRE,getdate()) as diff
			   ,isnull((select top 1 case when p.PRGNTpresupuestal=1 then 'Si' when p.PRGNTpresupuestal=0 then 'No' else ' ' end as oreg from CotizacionesTest_preguntas p where p.docentry=c.docentry order by docentry desc),' ') PRGNTpresupuestal
			   ,isnull((select top 1 case when p.PRGNTcompras=1 then 'Si' when p.PRGNTcompras=0 then 'No' else ' ' end as oreg from CotizacionesTest_preguntas p where p.docentry=c.docentry order by docentry desc),' ') PRGNTcompras
			   ,isnull((select top 1 case when p.PRGNTtipocomprador=1 then 'Local' when p.PRGNTtipocomprador=2 then 'Nacional' when p.PRGNTtipocomprador=3 then 'Internacional' when p.PRGNTtipocomprador=0 then 'N/A' else ' ' end as oreg from CotizacionesTest_preguntas p where p.docentry=c.docentry order by docentry desc),' ') PRGNTtipocomprador
			   FROM cotizacionesTest C 
			   WHERE C.nvendedor=? AND MONTH(C.U_FECHACIERRE)=? AND YEAR(C.U_FECHACIERRE)=?
			   GROUP BY DOCENTRY,C.Etapa,C.industria,C.etapacodigo, C.FechaCont,C.DocNum,C.Cliente,C.Referencia, [C].[% Cierre],C.U_FECHACIERRE,C.Comments,C.nvendedor,C.vendedor,C.zona,C.U_BitrixID,C.oficina
			   ORDER BY C.U_FECHACIERRE", [
                    $json->id_sap,
                    $mes,
                    $año
                ]);

                $result = $query->getResult();
                for ($i = 0; $i < count($result); $i++) {
                    $this->db->query("INSERT INTO proyecciones(
                        DocEntry,
                        DocNum,
                        FechaCont,
                        Cliente,
                        nvendedor,
                        vendedor,
                        zona,
                        oficina,
                        TOTAL,
                        Referencia,
                        porc_cierre,
                        MontoPond,
                        U_FECHACIERRE,
                        Comments,
                        Etapa,
                        etapacodigo,
                        industria,
                        id_plan) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", [
                        $result[$i]->DocEntry,
                        $result[$i]->DocNum,
                        $result[$i]->FechaCont,
                        $result[$i]->Cliente,
                        $result[$i]->nvendedor,
                        $result[$i]->vendedor,
                        $result[$i]->zona,
                        $result[$i]->oficina,
                        $result[$i]->TOTAL,
                        $result[$i]->Referencia,
                        $result[$i]->porc_cierre,
                        $result[$i]->MontoPond,
                        $result[$i]->U_FECHACIERRE,
                        $result[$i]->Comments,
                        $result[$i]->Etapa,
                        $result[$i]->etapacodigo,
                        $result[$i]->industria,
                        $json->id_plan
                    ]);
                }
            }
        } else {
            return $this->getResponse([
                'message' => 'Error en la fecha.',
            ], 500);
        }

        $model->update($json->id_plan, ['confirmacion' => true]);

        return $this->getResponse([
            'message' => 'Data successfully updated'
        ]);
    }

    public function checkIn()
    {
        $json = $this->request->getJSON();
        $model = new Visita();
        $date = new DateTime();
        $hora = $date->format('H:i');
        if ($json->checkout) {
            $model->update($json->id, ['geo_checkout' => $json->geo_checkout, 'hora_checkout' => $hora, 'estatus' => 'Finalizada']);
        } else {
            $model->update($json->id, ['geo_checkin' => $json->geo_checkin, 'hora_checkin' => $hora, 'estatus' => 'En ejecución']);
        }

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function checkCheckIn()
    {
        $json = $this->request->getJSON();
        $query = $this->db->query("SELECT
        estatus
        FROM Citas
        WHERE estatus != 'Programada'
        AND id=?",[$json->id_cita]);
        $check = $query->getFirstRow();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'check' => $check
        ]);
    }

    public function execActividad()
    {
        $json = $this->request->getJSON();
        $model = new Actividad();
        $model->update($json->id, ['estatus' => 'Finalizada']);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function checkActividades()
    {
        $json = $this->request->getJSON();
        $model = new Actividad();

        return $this->response->setStatusCode(200)->setJSON($model->where('estatus', 'Finalizada', true)->where('id_cita', $json->id_cita, true)->find());
    }

    public function addCompromiso()
    {
        $json = $this->request->getJSON();
        $model = new Compromiso();
        $model->save($json);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function getAcompanamientos()
    {
        $json = $this->request->getJSON();
        $query = $this->db->query("SELECT
        A.id,
        CONCAT(C.nombres,' ',C.apellido_p,' ',C.apellido_m) AS 'nombre',
        A.id_sap
        FROM
        acompanamientos A,
        (SELECT
        nombres,
        apellido_p,
        apellido_m,
        id_vendedorSap,
        estado
        FROM Colaboradores
        ) AS C
        WHERE A.id_sap=C.id_vendedorSap AND
        C.estado=1 AND
        A.id_cita = ?", [$json->id_cita]);

        $acompanamientos = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'acompanamientos' => $acompanamientos
        ]);
    }

    public function addAcompanamiento()
    {
        $json = $this->request->getJSON();
        $model = new Acompanamiento();
        $model->save($json);

        return $this->getResponse([
            'message' => 'Data successfully saved'
        ]);
    }

    public function getZonas()
    {
        $query = $this->db->query("SELECT * FROM zonas");
        $zonas = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'zonas' => $zonas
        ]);
    }

    public function getVendedores()
    {
        $json = $this->request->getJSON();
        if ($json->tipo && $json->tipo == 'Corporativo') {
            $query = $this->db->query("SELECT
            C.id_vendedorSap AS id_sap,
            CONCAT(C.nombres,' ',C.apellido_p,' ',C.apellido_m)nombre
            FROM
            (SELECT
            id_vendedorSap,
            nombres,
            apellido_p,
            apellido_m
            FROM Colaboradores
            WHERE estado=1
            ) AS C,
            (SELECT
            id_sap
            FROM det_vendedores
            WHERE tipo='Vendedor'
            ) AS DV
            WHERE
            C.id_vendedorSap=DV.id_sap
            ORDER BY nombre");
        } else {
            $query = $this->db->query("SELECT
            C.id_vendedorSap AS id_sap,
            CONCAT(C.nombres,' ',C.apellido_p,' ',C.apellido_m)nombre
            FROM
            (SELECT
            id_vendedorSap,
            nombres,
            apellido_p,
            apellido_m
            FROM Colaboradores
            WHERE estado=1
            ) AS C,
            (SELECT
            id_sap,
            id_zona
            FROM det_vendedores
            WHERE tipo='Vendedor'
            ) AS DV
            WHERE
            C.id_vendedorSap=DV.id_sap AND
            DV.id_zona = ?
            ORDER BY nombre", [$json->id_zona]);
        }

        $vendedores = $query->getResult();


        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'vendedores' => $vendedores
        ]);
    }

    public function execCompromiso()
    {
        $json = $this->request->getJSON();
        $model = new Compromiso();
        $model->update($json->id, ['estatus' => 'Finalizado']);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function saveCuenta()
    {
        $data = $this->request->getJSON();
        $cuenta = new Cuenta();
        $query = $this->db->query("SELECT id_zona FROM det_vendedores WHERE id_sap = ?", [$data->id_sap]);
        $id_zona = $query->getResult()[0]->id_zona;
        $data->id_zona = 0;
        if ($id_zona) {
            $data->id_zona = $id_zona;
        }
        $cuenta->save($data);

        return $this->getResponse([
            'message' => 'Data successfully saved'
        ]);
    }

    public function addLevantamiento()
    {
        $json = $this->request->getJSON();
        $model = new Levantamiento();
        $model->save($json);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function deleteLevantamiento()
    {
        $json = $this->request->getJSON();
        $model = new Levantamiento();
        $model->delete($json->id);

        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function deleteCompromiso()
    {
        $json = $this->request->getJSON();
        $model = new Compromiso();
        $model->delete($json->id);

        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function deleteAcompanamiento()
    {
        $json = $this->request->getJSON();
        $model = new Acompanamiento();
        $model->delete($json->id);

        return $this->response->setStatusCode(200)->setJSON(1);
    }

    public function getIndustrias()
    {
        $model = new Industria();
        $industrias = $model->findAll();

        return $this->getResponse([
            'message' => 'Data sucessfully retrieved',
            'industrias' => $industrias
        ]);
    }

    public function getSpk1()
    {
        $model = new Spk1();
        $spk1 = $model->findAll();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'spk1' => $spk1
        ]);
    }

    public function addOportunidad()
    {
        $json = $this->request->getJSON();
        $model = new Oportunidad();
        $model->save($json);

        return $this->response->setStatusCode(200)->setJSON(1);
    }
}
