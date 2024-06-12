<?php

namespace App\Controllers\salesup;

use App\Controllers\BaseController;

class Indicadores extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getHorasMetas()
    {
        $json = $this->request->getJSON();
        helper('date');
        $año = date('Y', now());
        //INDICADORES SEMANALES
        if (isset($json->semana)) {
            //POR ZONA
            if (isset($json->id_zona)) {
                $query = $this->db->query("SELECT ISNULL(SUM(CAST(DATEDIFF(n,C.hora_checkin,C.hora_checkout) / 60.0 AS FLOAT)),0) AS horas
                FROM citas C JOIN det_vendedores DV ON C.id_sap=DV.id_sap WHERE C.estatus = 'Finalizada' AND DV.id_zona = ?
                AND DATEPART(isowk,C.fecha_comp) = ?", [
                    $json->id_zona,
                    $json->semana
                ]);
                $horas = $query->getResult()[0]->horas;

                $query = $this->db->query("SELECT * FROM metas_salesup WHERE id_zona = ? AND id_sap = 0", [$json->id_zona]);
                $metas = $query->getResult();

                $query = $this->db->query("SELECT COUNT(O.id) AS oportunidades FROM oportunidades O JOIN det_vendedores DV
                ON O.id_sap=DV.id_sap WHERE DV.id_zona = ? AND DATEPART(isowk,O.fecha) = ?", [
                    $json->id_zona,
                    $json->semana
                ]);
                $oportunidades = $query->getResult()[0]->oportunidades;

                switch ($json->id_zona) {
                    case 1:
                        $zona = 'SON';
                        break;
                    case 2:
                        $zona = 'PAC';
                        break;
                    case 3:
                        $zona = 'BC';
                        break;
                    case 4:
                        $zona = 'CHI';
                        break;
                    case 5:
                        $zona = 'MTY';
                        break;
                    case 6:
                        $zona = 'DCU';
                        break;
                    case 7:
                        $zona = 'OCC';
                        break;
                    case 8:
                        $zona = 'BAJ';
                        break;
                    case 9:
                        $zona = 'LAG';
                        break;
                    case 10:
                        $zona = 'CDMX';
                        break;
                    case 11:
                        $zona = 'QRO';
                        break;
                    case 12:
                        $zona = 'VL';
                        break;
                }
                $query = $this->db->query("SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0) AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([No. Pedido])) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022NuevoCount] WHERE YEAR([Fecha])=$año AND DATEPART(isowk,[Fecha])=? AND Zona=?),0) AS cuenta
                FROM  
                (
                  SELECT CASE WHEN [Norma de Reparto] = 'MYS' THEN 'SVA'
                              WHEN [Norma de Reparto] = 'ASP' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'ASP-D' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'SPS' THEN 'SPF'
                              WHEN [Norma de Reparto] = 'DIST' THEN 'UEP' 
                              ELSE [Norma de Reparto] END AS [Norma de Reparto],
                               [Monto Total Venta] AS [Venta]
                  FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022Nuevo]  
                  WHERE	YEAR([Fecha Registro])=$año 
                          AND DATEPART(isowk,[Fecha Registro])=? 
                        AND Zona=? 
                ) AS TA0  
                PIVOT  
                (  
                  SUM([Venta])   
                  FOR [Norma de Reparto] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                  
                  
                ) AS PivotTable
                ", [
                    $json->semana,
                    $zona,
                    $json->semana,
                    $zona
                ]);

                $pedidos = $query->getResult();

                $query = $this->db->query(
                    "SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0)
                    AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([DocEntry])) FROM
                    [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR([DocDate])=$año AND DATEPART(isowk,[DocDate])=? AND Zona=?),0) AS cuenta,
                    ISNULL((SELECT SUM([MontoPonTotPartReload]) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR(U_FECHACIERRE)=$año AND DATEPART(isowk,U_FECHACIERRE)=? AND Zona=?),0) AS proyeccion
                    FROM  
                    (
                      SELECT CASE WHEN [NR] = 'MYS' THEN 'SVA'
                                  WHEN [NR] = 'ASP' THEN 'SVA'	
                                  WHEN [NR] = 'ASP-D' THEN 'SVA'	
                                  WHEN [NR] = 'SPS' THEN 'SPF'
                                  WHEN [NR] = 'DIST' THEN 'UEP' 
                                  ELSE [NR] END AS [NR],
                                   [TotPartItem] AS [Venta]
                      FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] 
                      WHERE	YEAR([DocDate])=$año 
                              AND DATEPART(isowk,[DocDate])=?
                            AND Zona=? 
                    ) AS TA0  
                    PIVOT  
                    (  
                      SUM([Venta])   
                      FOR [NR] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                      
                      
                    ) AS PivotTable",
                    [
                        $json->semana,
                        $zona,
                        $json->semana,
                        $zona,
                        $json->semana,
                        $zona
                    ]
                );

                $cotizaciones = $query->getResult();

                $acompanamientos = 0;
                $metaAcompanamientos = [];
            } else {
                //POR VENDEDOR
                $query = $this->db->query("SELECT ISNULL(SUM(CAST(DATEDIFF(n,hora_checkin,hora_checkout) / 60.0 AS FLOAT)),0) AS horas
                FROM citas WHERE estatus='Finalizada' AND id_sap = ? AND DATEPART(isowk,fecha_comp)=?", [
                    $json->id_sap,
                    $json->semana
                ]);
                $horas = $query->getResult()[0]->horas;

                $query = $this->db->query("SELECT * FROM metas_salesup WHERE id_sap = ?", [$json->id_sap],);
                $metas = $query->getResult();

                $query = $this->db->query("SELECT COUNT(id) AS oportunidades FROM oportunidades WHERE id_sap = ? AND DATEPART(isowk,fecha) = ?", [
                    $json->id_sap,
                    $json->semana
                ]);
                $oportunidades = $query->getResult()[0]->oportunidades;

                $query = $this->db->query("SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0) AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([No. Pedido])) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022NuevoCount] WHERE YEAR([Fecha])=$año AND DATEPART(isowk,[Fecha])=? AND nvendedor=?),0) AS cuenta
                FROM  
                (
                  SELECT CASE WHEN [Norma de Reparto] = 'MYS' THEN 'SVA'
                              WHEN [Norma de Reparto] = 'ASP' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'ASP-D' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'SPS' THEN 'SPF'
                              WHEN [Norma de Reparto] = 'DIST' THEN 'UEP' 
                              ELSE [Norma de Reparto] END AS [Norma de Reparto],
                               [Monto Total Venta] AS [Venta]
                  FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022Nuevo]  
                  WHERE	YEAR([Fecha Registro])=$año 
                          AND DATEPART(isowk,[Fecha Registro])=? 
                        AND nvendedor=? 
                ) AS TA0  
                PIVOT  
                (  
                  SUM([Venta])   
                  FOR [Norma de Reparto] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                  
                  
                ) AS PivotTable
                ", [
                    $json->semana,
                    $json->id_sap,
                    $json->semana,
                    $json->id_sap
                ]);

                $pedidos = $query->getResult();

                $query = $this->db->query(
                    "SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0)
                    AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([DocEntry])) FROM
                    [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR([DocDate])=$año AND DATEPART(isowk,[DocDate])=? AND nvendedor=?),0) AS cuenta,
                    ISNULL((SELECT SUM([MontoPonTotPartReload]) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR(U_FECHACIERRE)=$año AND DATEPART(isowk,U_FECHACIERRE)=? AND nvendedor=?),0) AS proyeccion
                    FROM  
                    (
                      SELECT CASE WHEN [NR] = 'MYS' THEN 'SVA'
                                  WHEN [NR] = 'ASP' THEN 'SVA'	
                                  WHEN [NR] = 'ASP-D' THEN 'SVA'	
                                  WHEN [NR] = 'SPS' THEN 'SPF'
                                  WHEN [NR] = 'DIST' THEN 'UEP' 
                                  ELSE [NR] END AS [NR],
                                   [TotPartItem] AS [Venta]
                      FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] 
                      WHERE	YEAR([DocDate])=$año 
                              AND DATEPART(isowk,[DocDate])=?
                            AND nvendedor=? 
                    ) AS TA0  
                    PIVOT  
                    (  
                      SUM([Venta])   
                      FOR [NR] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                      
                      
                    ) AS PivotTable",
                    [
                        $json->semana,
                        $json->id_sap,
                        $json->semana,
                        $json->id_sap,
                        $json->semana,
                        $json->id_sap
                    ]
                );

                $cotizaciones = $query->getResult();

                $query = $this->db->query("SELECT COUNT(id) AS acompanamientos FROM acompanamientos WHERE id_cita IN(SELECT id FROM citas
                WHERE DATEPART(isowk,fecha_comp) = ? AND id_sap = ? AND estatus='Finalizada')",[$json->semana,$json->id_sap]);

                $acompanamientos = $query->getResult()[0]->acompanamientos;

                $query = $this->db->query("SELECT meta_acomp FROM metas_salesup WHERE id_sap = ?",[$json->id_sap]);
                $metaAcompanamientos = $query->getResult();
            }
        } else {
            //INDICADORES MENSUALES
            if (isset($json->id_zona)) {
                // POR ZONA
                $query = $this->db->query("SELECT ISNULL(SUM(CAST(DATEDIFF(n,C.hora_checkin,C.hora_checkout) / 60.0 AS FLOAT)),0) AS horas
                FROM citas C JOIN det_vendedores DV ON C.id_sap=DV.id_sap WHERE C.estatus = 'Finalizada' AND DV.id_zona = ?
                AND month(C.fecha_comp) = ?", [
                    $json->id_zona,
                    $json->mes
                ]);
                $horas = $query->getResult()[0]->horas;

                $query = $this->db->query("SELECT * FROM metas_salesup WHERE id_zona = ? AND id_sap = 0", [$json->id_zona]);
                $metas = $query->getResult();

                $query = $this->db->query("SELECT COUNT(O.id) AS oportunidades FROM oportunidades O JOIN det_vendedores DV
                ON O.id_sap=DV.id_sap WHERE DV.id_zona = ? AND month(O.fecha) = ?", [
                    $json->id_zona,
                    $json->mes
                ]);
                $oportunidades = $query->getResult()[0]->oportunidades;

                switch ($json->id_zona) {
                    case 1:
                        $zona = 'SON';
                        break;
                    case 2:
                        $zona = 'PAC';
                        break;
                    case 3:
                        $zona = 'BC';
                        break;
                    case 4:
                        $zona = 'CHI';
                        break;
                    case 5:
                        $zona = 'MTY';
                        break;
                    case 6:
                        $zona = 'DCU';
                        break;
                    case 7:
                        $zona = 'OCC';
                        break;
                    case 8:
                        $zona = 'BAJ';
                        break;
                    case 9:
                        $zona = 'LAG';
                        break;
                    case 10:
                        $zona = 'CDMX';
                        break;
                    case 11:
                        $zona = 'QRO';
                        break;
                    case 12:
                        $zona = 'VL';
                        break;
                }
                $query = $this->db->query("SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0) AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([No. Pedido])) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022NuevoCount] WHERE YEAR([Fecha])=$año AND MONTH([Fecha])=? AND Zona=?),0) AS cuenta
                FROM  
                (
                  SELECT CASE WHEN [Norma de Reparto] = 'MYS' THEN 'SVA'
                              WHEN [Norma de Reparto] = 'ASP' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'ASP-D' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'SPS' THEN 'SPF'
                              WHEN [Norma de Reparto] = 'DIST' THEN 'UEP' 
                              ELSE [Norma de Reparto] END AS [Norma de Reparto],
                               [Monto Total Venta] AS [Venta]
                  FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022Nuevo]  
                  WHERE	YEAR([Fecha Registro])=$año 
                          AND MONTH([Fecha Registro])=? 
                        AND Zona=? 
                ) AS TA0  
                PIVOT  
                (  
                  SUM([Venta])   
                  FOR [Norma de Reparto] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                  
                  
                ) AS PivotTable
                ", [
                    $json->mes,
                    $zona,
                    $json->mes,
                    $zona
                ]);

                $pedidos = $query->getResult();

                $query = $this->db->query(
                    "SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0)
                    AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([DocEntry])) FROM
                    [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR([DocDate])=$año AND MONTH([DocDate])=? AND Zona=?),0) AS cuenta,
                    ISNULL((SELECT SUM([MontoPonTotPartReload]) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR(U_FECHACIERRE)=$año AND MONTH(U_FECHACIERRE)=? AND Zona=?),0) AS proyeccion
                    FROM  
                    (
                      SELECT CASE WHEN [NR] = 'MYS' THEN 'SVA'
                                  WHEN [NR] = 'ASP' THEN 'SVA'	
                                  WHEN [NR] = 'ASP-D' THEN 'SVA'	
                                  WHEN [NR] = 'SPS' THEN 'SPF'
                                  WHEN [NR] = 'DIST' THEN 'UEP' 
                                  ELSE [NR] END AS [NR],
                                   [TotPartItem] AS [Venta]
                      FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] 
                      WHERE	YEAR([DocDate])=$año 
                              and MONTH([DocDate])=?
                            and Zona=?
                    ) AS TA0  
                    PIVOT  
                    (  
                      SUM([Venta])   
                      FOR [NR] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                      
                      
                    ) AS PivotTable",
                    [
                        $json->mes,
                        $zona,
                        $json->mes,
                        $zona,
                        $json->mes,
                        $zona
                    ]
                );

                $cotizaciones = $query->getResult();

                $acompanamientos = 0;
                $metaAcompanamientos = [];
            } else {
                //POR VENDEDOR
                $query = $this->db->query("SELECT ISNULL(SUM(CAST(DATEDIFF(n,hora_checkin,hora_checkout) / 60.0 AS FLOAT)),0) AS horas
                FROM citas WHERE estatus='Finalizada' AND id_sap = ? AND DATEPART(m,fecha_comp)=?", [
                    $json->id_sap,
                    $json->mes
                ]);
                $horas = $query->getResult()[0]->horas;

                $query = $this->db->query("SELECT * FROM metas_salesup WHERE id_sap = ?", [$json->id_sap],);
                $metas = $query->getResult();

                $query = $this->db->query("SELECT COUNT(id) AS oportunidades FROM oportunidades WHERE id_sap = ? AND DATEPART(m,fecha) = ?", [
                    $json->id_sap,
                    $json->mes
                ]);
                $oportunidades = $query->getResult()[0]->oportunidades;

                $query = $this->db->query("SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0) AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([No. Pedido])) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022NuevoCount] WHERE YEAR([Fecha])=$año AND MONTH([Fecha])=? AND nvendedor=?),0) AS cuenta
                FROM  
                (
                  SELECT CASE WHEN [Norma de Reparto] = 'MYS' THEN 'SVA'
                              WHEN [Norma de Reparto] = 'ASP' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'ASP-D' THEN 'SVA'	
                              WHEN [Norma de Reparto] = 'SPS' THEN 'SPF'
                              WHEN [Norma de Reparto] = 'DIST' THEN 'UEP' 
                              ELSE [Norma de Reparto] END AS [Norma de Reparto],
                               [Monto Total Venta] AS [Venta]
                  FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_VentasDasboard16052022Nuevo]  
                  WHERE	YEAR([Fecha Registro])=$año 
                          AND MONTH([Fecha Registro])=? 
                        AND nvendedor=? 
                ) AS TA0  
                PIVOT  
                (  
                  SUM([Venta])   
                  FOR [Norma de Reparto] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                  
                  
                ) AS PivotTable", [
                    $json->mes,
                    $json->id_sap,
                    $json->mes,
                    $json->id_sap
                ]);

                $pedidos = $query->getResult();

                $query = $this->db->query(
                    "SELECT ISNULL([UEP],0) AS monto_uep, ISNULL([EH],0) AS monto_eh, ISNULL([SCI],0) AS monto_sci, ISNULL([SVA],0) AS monto_sva, ISNULL([SPF],0)
                    AS monto_spf,ISNULL([STM],0) AS monto_stm, ISNULL((SELECT COUNT(DISTINCT([DocEntry])) FROM
                    [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR([DocDate])=$año AND MONTH([DocDate])=? AND nvendedor=?),0) AS cuenta,
                    ISNULL((SELECT SUM([MontoPonTotPartReload]) FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] WHERE YEAR(U_FECHACIERRE)=$año AND MONTH(U_FECHACIERRE)=? AND nvendedor=?),0) AS proyeccion
                    FROM  
                    (
                      SELECT CASE WHEN [NR] = 'MYS' THEN 'SVA'
                                  WHEN [NR] = 'ASP' THEN 'SVA'	
                                  WHEN [NR] = 'ASP-D' THEN 'SVA'	
                                  WHEN [NR] = 'SPS' THEN 'SPF'
                                  WHEN [NR] = 'DIST' THEN 'UEP' 
                                  ELSE [NR] END AS [NR],
                                   [TotPartItem] AS [Venta]
                      FROM [SAPSERVER].[SBO_ECN].[dbo].[VW_CotizacionesDasboardMonto] 
                      WHERE	YEAR([DocDate])=$año 
                              AND MONTH([DocDate])=?
                            AND nvendedor=? 
                    ) AS TA0  
                    PIVOT  
                    (  
                      SUM([Venta])   
                      FOR [NR] IN ([UEP], [EH], [SCI], [SVA], [SPF],[STM])  
                      
                      
                    ) AS PivotTable",
                    [
                        $json->mes,
                        $json->id_sap,
                        $json->mes,
                        $json->id_sap,
                        $json->mes,
                        $json->id_sap
                    ]
                );

                $cotizaciones = $query->getResult();

                $query = $this->db->query("SELECT COUNT(id) AS acompanamientos FROM acompanamientos WHERE id_cita IN(SELECT id FROM citas
                WHERE MONTH(fecha_comp) = ? AND id_sap = ? AND estatus='Finalizada')",[$json->mes,$json->id_sap]);

                $acompanamientos = $query->getResult()[0]->acompanamientos;

                $query = $this->db->query("SELECT meta_acomp FROM metas_salesup WHERE id_sap = ?",[$json->id_sap]);

                $metaAcompanamientos = $query->getResult();
            }
        }

        if (count($metas) > 0) {
            $metas = $metas[0];
        }

        if (count($metaAcompanamientos) > 0) {
            $metaAcompanamientos = $metaAcompanamientos[0]->meta_acomp;
        }else{
            $metaAcompanamientos = 0;
        }
        if (count($pedidos) > 0) {
            $pedidos = $pedidos[0];
        }
        if (count($cotizaciones) > 0) {
            $cotizaciones = $cotizaciones[0];
        }

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'horas' => $horas,
            'metas' => $metas,
            'oportunidades' => $oportunidades,
            'pedidos' => $pedidos,
            'cotizaciones' => $cotizaciones,
            'acompanamientos' => $acompanamientos,
            'metaAcompanamientos' => $metaAcompanamientos
        ]);
    }
}
