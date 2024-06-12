<?php

namespace App\Controllers;

//use CodeIgniter\I18n\Time;

class KnockerWO extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function datoknockerpermiso(){
        $json = $this->request->getJSON();
        $id_colaborador = $json->id_colaborador;
        $query = $this->db->query(
            "IF EXISTS ( 
                SELECT asignperf_perfil
                FROM tbl_knocker_asign_perf 
                WHERE asignperf_user=? and asignperf_status=1
            ) 
            BEGIN
                SELECT 
                case ? 
                when 1455 then 1
                when 298 then 1
                when 34 then 1
                when 1069 then 1
                when 1546 then 1
                when 48 then 1
                when 1477 then 1
                when 199 then 2 
                else t.asignperf_perfil end as asignperf_perfil
                , ISNULL((select COUNT(p.pm_id) FROM tbl_knocker_projects p WHERE t.asignperf_user=p.pm_id and p.status=1),0) es_pm
                , (select r.codigo_region from colaboradores c left join regiones r on c.id_region=r.id_region where c.id_colaborador=?) as region
                FROM tbl_knocker_asign_perf t
                WHERE t.asignperf_user=? and t.asignperf_status=1
            END
            ELSE
            BEGIN
                SELECT 
                case ? 
                when 1455 then 1
                when 298 then 1
                when 34 then 1
                when 1069 then 1
                when 1546 then 1
                when 48 then 1
                when 1477 then 1
                when 199 then 2 
                else 0 end as asignperf_perfil
                , 0 as es_pm
                , (select r.codigo_region from colaboradores c left join regiones r on c.id_region=r.id_region where c.id_colaborador=?) as region
            END",
            [
                $id_colaborador,
                $id_colaborador,
                $id_colaborador,
                $id_colaborador,
                $id_colaborador,
                $id_colaborador
            ]
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
      
    }



//traer info de proyectos abiertos
public function proyectosWorkOrdersAbiertos(){
    $json = $this->request->getJSON();
    $id_colaborador = $json->id_colaborador;
    $id_perfil = $json->id_perfil;
    $region = $json->region;


    if ($id_perfil==1) {
        $query = $this->db->query(
            "select *, spk+' '+PrjCode+' '+PrjName+' '+U_Cliente+' '+fecha_confirmada+' '+fecha_compromiso+' '+(convert(varchar(15),BackLog,21))+' '+U_Moneda+' '+pm+' '+estado as 'todorow'
            from ( SELECT 
            t0.id_ot,
            t1.id_project,
            t1.PrjCode,
            t2.PrjName,
            isnull(t3.Name,'') as spk,
            (SELECT top 1 t6.name FROM tbl_knocker_tasks t6 WHERE t6.PrjCode=t1.PrjCode and t6.status=1) as service_name,
            t2.U_Cliente,
            (convert(varchar(10),isnull(t0.fecha_confirmada,''),21)) fecha_confirmada,
            t1.service_duration,
            t5.nombres+' '+t5.apellido_p as pm,
            t0.ot_estado,
            case t0.ot_estado
             when 1 then 'En espera'
             when 0 then 'Cancelado'
             when 2 then 'Ejecutado'
             when 3 then 'Terminado'
             when 4 then 'Finalizado'
             when 5 then 'Facturado parcial'
            end as estado,
            (convert(varchar(10),isnull( (  DATEADD(DAY, CEILING(CAST(t1.service_duration AS FLOAT)/8)-1,t0.fecha_confirmada   )  ),''),21)) as fecha_compromiso,
            IIF(T2.U_Moneda='MXP',isnull(T7.BackLogMXP,0),isnull(T7.BackLogUSD,0)) as BackLog,
            T2.U_Moneda,
            t6.numatcard as oc,
			(select STRING_AGG((nombrescolas ), ', ') from 
				(SELECT distinct(jt2.nombres+' '+jt2.apellido_p) 'nombrescolas' FROM 
					tbl_knocker_assignments jt0 LEFT JOIN tbl_knocker_tasks jt1 on jt0.IdTask=jt1.IdTask left join Colaboradores jt2 on jt2.id_colaborador=jt0.IdResource
					WHERE jt0.Status=1 and jt1.PrjId=t1.id_project and jt1.Status=1) nn0 ) 
				as 'nombrescolas'
            FROM 
            tbl_knocker_work_orders t0 LEFT JOIN
            tbl_knocker_projects t1 ON t1.id_project=t0.id_project LEFT JOIN
            SYN_OPRJ t2 ON t2.PrjCode=t1.PrjCode COLLATE DATABASE_DEFAULT LEFT JOIN 
            SYN_SPK2 t3 on t2.U_SPK2=t3.Code LEFT JOIN 
            SYN_OPRC t4 on t4.PrcCode=t2.U_Zona LEFT JOIN 
            SYN_BLS T7 ON T7.PrjCode=t2.PrjCode LEFT JOIN
            Colaboradores t5 ON t1.pm_id=t5.id_colaborador LEFT JOIN
            SYN_ORDR T6 ON T6.Docnum=t2.u_p_interno
            WHERE 
            t0.status=1 and 
            t1.status=1 and 
            t2.u_estatus=1 and
            (t1.PrjCode!='S-01' and t1.PrjCode!='S-02' and t1.PrjCode!='S-03' and t1.PrjCode!='S-04') 
            ) n0
            ORDER BY
            prjcode desc",
            [
                $id_colaborador
            ]
        );
    
    }else{
        if($id_perfil == 7 || $id_perfil == 8 || $id_perfil == 2){
            $query = $this->db->query(
                "select *, spk+' '+PrjCode+' '+PrjName+' '+U_Cliente+' '+fecha_confirmada+' '+fecha_compromiso+' '+(convert(varchar(15),BackLog,21))+' '+U_Moneda+' '+pm+' '+estado as 'todorow'
                from ( SELECT 
                t0.id_ot,
                t1.id_project,
                t1.PrjCode,
                t2.PrjName,
                isnull(t3.Name,'') as spk,
                (SELECT top 1 t6.name FROM tbl_knocker_tasks t6 WHERE t6.PrjCode=t1.PrjCode and t6.status=1) as service_name,
                t2.U_Cliente,
                (convert(varchar(10),isnull(t0.fecha_confirmada,''),21)) fecha_confirmada,
                t1.service_duration,
                t5.nombres+' '+t5.apellido_p as pm,
                t0.ot_estado,
                case t0.ot_estado
                 when 1 then 'En espera'
                 when 0 then 'Cancelado'
                 when 2 then 'Ejecutado'
                 when 3 then 'Terminado'
                 when 4 then 'Finalizado'
                 when 5 then 'Facturado parcial'
                end as estado,
                (convert(varchar(10),isnull( (  DATEADD(DAY, CEILING(CAST(t1.service_duration AS FLOAT)/8)-1,t0.fecha_confirmada   )  ),''),21)) as fecha_compromiso,
                IIF(T2.U_Moneda='MXP',isnull(T7.BackLogMXP,0),isnull(T7.BackLogUSD,0)) as BackLog,
                T2.U_Moneda,
                t6.numatcard as oc,
                (select STRING_AGG((nombrescolas ), ', ') from 
                    (SELECT distinct(jt2.nombres+' '+jt2.apellido_p) 'nombrescolas' FROM 
                        tbl_knocker_assignments jt0 LEFT JOIN tbl_knocker_tasks jt1 on jt0.IdTask=jt1.IdTask left join Colaboradores jt2 on jt2.id_colaborador=jt0.IdResource
                        WHERE jt0.Status=1 and jt1.PrjId=t1.id_project and jt1.Status=1) nn0 ) 
                    as 'nombrescolas'
                FROM 
                tbl_knocker_work_orders t0 LEFT JOIN
                tbl_knocker_projects t1 ON t1.id_project=t0.id_project LEFT JOIN
                SYN_OPRJ t2 ON t2.PrjCode=t1.PrjCode COLLATE DATABASE_DEFAULT LEFT JOIN 
                SYN_SPK2 t3 on t2.U_SPK2=t3.Code LEFT JOIN 
                SYN_OPRC t4 on t4.PrcCode=t2.U_Zona LEFT JOIN 
                SYN_BLS T7 ON T7.PrjCode=t2.PrjCode LEFT JOIN
                Colaboradores t5 ON t1.pm_id=t5.id_colaborador LEFT JOIN
                SYN_ORDR T6 ON T6.Docnum=t2.u_p_interno
                WHERE 
                t0.status=1 and 
                t1.status=1 and 
                t2.u_estatus=1 and
                (t1.PrjCode!='S-01' and t1.PrjCode!='S-02' and t1.PrjCode!='S-03' and t1.PrjCode!='S-04')  
                AND (t4.GrpCode = ? OR t1.id_project in (SELECT 
                distinct t9.PrjId 
                FROM 
                tbl_knocker_assignments t8 LEFT JOIN
                tbl_knocker_tasks t9 on t8.IdTask=t9.IdTask 
                WHERE
                t8.status=1 
                and t8.IdResource = ?
                group by t9.PrjId)
                or t1.id_project in (SELECT 
                distinct T10.id_project 
                from 
                tbl_knocker_projects T10
                WHERE 
                T10.status=1 AND 
                T10.pm_id = ? ) )
            ) n0
            ORDER BY
            prjcode desc",
                [
                    $region,
                    $id_colaborador,
                    $id_colaborador
                ]
            );
        }else{
            $query = $this->db->query(
                "select *, spk+' '+PrjCode+' '+PrjName+' '+U_Cliente+' '+fecha_confirmada+' '+fecha_compromiso+' '+(convert(varchar(15),BackLog,21))+' '+U_Moneda+' '+pm+' '+estado as 'todorow'
                from ( SELECT 
                t0.id_ot,
                t1.id_project,
                t1.PrjCode,
                t2.PrjName,
                isnull(t3.Name,'') as spk,
                (SELECT top 1 t6.name FROM tbl_knocker_tasks t6 WHERE t6.PrjCode=t1.PrjCode and t6.status=1) as service_name,
                t2.U_Cliente,
                (convert(varchar(10),isnull(t0.fecha_confirmada,''),21)) fecha_confirmada,
                t1.service_duration,
                t5.nombres+' '+t5.apellido_p as pm,
                t0.ot_estado,
                case t0.ot_estado
                    when 1 then 'En espera'
                    when 0 then 'Cancelado'
                    when 2 then 'Ejecutado'
                    when 3 then 'Terminado'
                    when 4 then 'Finalizado'
                    when 5 then 'Facturado parcial'
                    end as estado,
                    (convert(varchar(10),isnull( (  DATEADD(DAY, CEILING(CAST(t1.service_duration AS FLOAT)/8)-1,t0.fecha_confirmada   )  ),''),21)) as fecha_compromiso,
                IIF(T2.U_Moneda='MXP',isnull(T7.BackLogMXP,0),isnull(T7.BackLogUSD,0)) as BackLog,
                T2.U_Moneda,
                t6.numatcard as oc,
                (select STRING_AGG((nombrescolas ), ', ') from 
                    (SELECT distinct(jt2.nombres+' '+jt2.apellido_p) 'nombrescolas' FROM 
                        tbl_knocker_assignments jt0 LEFT JOIN tbl_knocker_tasks jt1 on jt0.IdTask=jt1.IdTask left join Colaboradores jt2 on jt2.id_colaborador=jt0.IdResource
                        WHERE jt0.Status=1 and jt1.PrjId=t1.id_project and jt1.Status=1) nn0 ) 
                    as 'nombrescolas'
                FROM 
                tbl_knocker_work_orders t0 LEFT JOIN
                tbl_knocker_projects t1 ON t1.id_project=t0.id_project LEFT JOIN
                SYN_OPRJ t2 ON t2.PrjCode=t1.PrjCode COLLATE DATABASE_DEFAULT LEFT JOIN 
                SYN_SPK2 t3 on t2.U_SPK2=t3.Code LEFT JOIN 
                SYN_OPRC t4 on t4.PrcCode=t2.U_Zona LEFT JOIN 
                SYN_BLS T7 ON T7.PrjCode=t2.PrjCode LEFT JOIN
                Colaboradores t5 ON t1.pm_id=t5.id_colaborador LEFT JOIN
                SYN_ORDR T6 ON T6.Docnum=t2.u_p_interno
                WHERE 
                t0.status=1 and 
                t1.status=1 and 
                t2.u_estatus=1 and
                (t1.PrjCode!='S-01' and t1.PrjCode!='S-02' and t1.PrjCode!='S-03' and t1.PrjCode!='S-04')  
                AND ( t1.id_project in (SELECT 
                    distinct t9.PrjId 
                    FROM 
                    tbl_knocker_assignments t8 LEFT JOIN
                    tbl_knocker_tasks t9 on t8.IdTask=t9.IdTask 
                    WHERE
                    t8.status=1 
                    and t8.IdResource = ?
                    group by t9.PrjId)
                    or t1.id_project in (SELECT 
                    distinct T10.id_project 
                    from 
                    tbl_knocker_projects T10
                    WHERE 
                    T10.status=1 AND 
                    T10.pm_id = ? ) )
                ) n0
                ORDER BY
                prjcode desc",
                [
                    $id_colaborador,
                    $id_colaborador
                ]                
            );
        }
        
    }
    
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}
    
//traer info de proyectos cerrados
public function proyectosWorkOrdersCerrados(){
    $json = $this->request->getJSON();
    $id_colaborador = $json->id_colaborador;
    $id_perfil = 1;//$json->id_perfil;
    $region = $json->region;

    if ($id_perfil==1) {
        $query = $this->db->query(
            "select *, spk+' '+PrjCode+' '+PrjName+' '+U_Cliente+' '+fecha_confirmada+' '+fecha_compromiso+' '+(convert(varchar(15),BackLog,21))+' '+U_Moneda+' '+pm+' '+estado as 'todorow'
            from ( SELECT 
            t0.id_ot,
            t1.id_project,
            t1.PrjCode,
            t2.PrjName,
            isnull(t3.Name,'') as spk,
            (SELECT top 1 t6.name FROM tbl_knocker_tasks t6 WHERE t6.PrjCode=t1.PrjCode and t6.status=1) as service_name,
            t2.U_Cliente,
            (convert(varchar(10),isnull(t0.fecha_confirmada,''),21)) fecha_confirmada,
            t1.service_duration,
            t5.nombres+' '+t5.apellido_p as pm,
            t0.ot_estado,
            case t0.ot_estado
             when 1 then 'En espera'
             when 0 then 'Cancelado'
             when 2 then 'Ejecutado'
             when 3 then 'Terminado'
             when 4 then 'Finalizado'
             when 5 then 'Facturado parcial'
            end as estado,
            (convert(varchar(10),isnull( (  DATEADD(DAY, CEILING(CAST(t1.service_duration AS FLOAT)/8)-1,t0.fecha_confirmada   )  ),''),21)) as fecha_compromiso,
            IIF(T2.U_Moneda='MXP',isnull(T7.BackLogMXP,0),isnull(T7.BackLogUSD,0)) as BackLog,
            T2.U_Moneda,
            t6.numatcard as oc
            FROM 
            tbl_knocker_work_orders t0 LEFT JOIN
            tbl_knocker_projects t1 ON t1.id_project=t0.id_project LEFT JOIN
            SYN_OPRJ t2 ON t2.PrjCode=t1.PrjCode COLLATE DATABASE_DEFAULT LEFT JOIN 
            SYN_SPK2 t3 on t2.U_SPK2=t3.Code LEFT JOIN 
            SYN_OPRC t4 on t4.PrcCode=t2.U_Zona LEFT JOIN 
            SYN_BLS T7 ON T7.PrjCode=t2.PrjCode LEFT JOIN
            Colaboradores t5 ON t1.pm_id=t5.id_colaborador LEFT JOIN
            SYN_ORDR T6 ON T6.Docnum=t2.u_p_interno
            WHERE 
            t0.status=1 and 
            t1.status=1 and 
            t2.u_estatus=0 and
            (t1.PrjCode!='S-01' and t1.PrjCode!='S-02' and t1.PrjCode!='S-03' and t1.PrjCode!='S-04') 
            ) n0 where fecha_compromiso >= dateadd(year,-2,getdate()) and estado<>'Finalizado'
            ORDER BY
            prjcode desc",
            [
                $id_colaborador
            ]
        );
    
    }else{
        if($id_perfil == 7 || $id_perfil == 8 || $id_perfil == 2){
            $query = $this->db->query(
                "select *, spk+' '+PrjCode+' '+PrjName+' '+U_Cliente+' '+fecha_confirmada+' '+fecha_compromiso+' '+(convert(varchar(15),BackLog,21))+' '+U_Moneda+' '+pm+' '+estado as 'todorow'
                from ( SELECT 
                t0.id_ot,
                t1.id_project,
                t1.PrjCode,
                t2.PrjName,
                isnull(t3.Name,'') as spk,
                (SELECT top 1 t6.name FROM tbl_knocker_tasks t6 WHERE t6.PrjCode=t1.PrjCode and t6.status=1) as service_name,
                t2.U_Cliente,
                (convert(varchar(10),isnull(t0.fecha_confirmada,''),21)) fecha_confirmada,
                t1.service_duration,
                t5.nombres+' '+t5.apellido_p as pm,
                t0.ot_estado,
                case t0.ot_estado
                 when 1 then 'En espera'
                 when 0 then 'Cancelado'
                 when 2 then 'Ejecutado'
                 when 3 then 'Terminado'
                 when 4 then 'Finalizado'
                 when 5 then 'Facturado parcial'
                end as estado,
                (convert(varchar(10),isnull( (  DATEADD(DAY, CEILING(CAST(t1.service_duration AS FLOAT)/8)-1,t0.fecha_confirmada   )  ),''),21)) as fecha_compromiso,
                IIF(T2.U_Moneda='MXP',isnull(T7.BackLogMXP,0),isnull(T7.BackLogUSD,0)) as BackLog,
                T2.U_Moneda,
                t6.numatcard as oc
                FROM 
                tbl_knocker_work_orders t0 LEFT JOIN
                tbl_knocker_projects t1 ON t1.id_project=t0.id_project LEFT JOIN
                SYN_OPRJ t2 ON t2.PrjCode=t1.PrjCode COLLATE DATABASE_DEFAULT LEFT JOIN 
                SYN_SPK2 t3 on t2.U_SPK2=t3.Code LEFT JOIN 
                SYN_OPRC t4 on t4.PrcCode=t2.U_Zona LEFT JOIN 
                SYN_BLS T7 ON T7.PrjCode=t2.PrjCode LEFT JOIN
                Colaboradores t5 ON t1.pm_id=t5.id_colaborador LEFT JOIN
                SYN_ORDR T6 ON T6.Docnum=t2.u_p_interno
                WHERE 
                t0.status=1 and 
                t1.status=1 and 
                t2.u_estatus=0 and
                (t1.PrjCode!='S-01' and t1.PrjCode!='S-02' and t1.PrjCode!='S-03' and t1.PrjCode!='S-04')  
                AND (t4.GrpCode = ? OR t1.id_project in (SELECT 
                distinct t9.PrjId 
                FROM 
                tbl_knocker_assignments t8 LEFT JOIN
                tbl_knocker_tasks t9 on t8.IdTask=t9.IdTask 
                WHERE
                t8.status=1 
                and t8.IdResource = ?
                group by t9.PrjId)
                or t1.id_project in (SELECT 
                distinct T10.id_project 
                from 
                tbl_knocker_projects T10
                WHERE 
                T10.status=1 AND 
                T10.pm_id = ? ) )
            ) n0 where fecha_compromiso >= dateadd(year,-2,getdate()) and estado<>'Finalizado'
            ORDER BY
            prjcode desc",
                [
                    $region,
                    $id_colaborador,
                    $id_colaborador
                ]
            );
        }else{
            $query = $this->db->query(
                "select *, spk+' '+PrjCode+' '+PrjName+' '+U_Cliente+' '+fecha_confirmada+' '+fecha_compromiso+' '+(convert(varchar(15),BackLog,21))+' '+U_Moneda+' '+pm+' '+estado as 'todorow'
                from ( SELECT 
                t0.id_ot,
                t1.id_project,
                t1.PrjCode,
                t2.PrjName,
                isnull(t3.Name,'') as spk,
                (SELECT top 1 t6.name FROM tbl_knocker_tasks t6 WHERE t6.PrjCode=t1.PrjCode and t6.status=1) as service_name,
                t2.U_Cliente,
                (convert(varchar(10),isnull(t0.fecha_confirmada,''),21)) fecha_confirmada,
                t1.service_duration,
                t5.nombres+' '+t5.apellido_p as pm,
                t0.ot_estado,
                case t0.ot_estado
                    when 1 then 'En espera'
                    when 0 then 'Cancelado'
                    when 2 then 'Ejecutado'
                    when 3 then 'Terminado'
                    when 4 then 'Finalizado'
                    when 5 then 'Facturado parcial'
                    end as estado,
                    (convert(varchar(10),isnull( (  DATEADD(DAY, CEILING(CAST(t1.service_duration AS FLOAT)/8)-1,t0.fecha_confirmada   )  ),''),21)) as fecha_compromiso,
                IIF(T2.U_Moneda='MXP',isnull(T7.BackLogMXP,0),isnull(T7.BackLogUSD,0)) as BackLog,
                T2.U_Moneda,
                t6.numatcard as oc
                FROM 
                tbl_knocker_work_orders t0 LEFT JOIN
                tbl_knocker_projects t1 ON t1.id_project=t0.id_project LEFT JOIN
                SYN_OPRJ t2 ON t2.PrjCode=t1.PrjCode COLLATE DATABASE_DEFAULT LEFT JOIN 
                SYN_SPK2 t3 on t2.U_SPK2=t3.Code LEFT JOIN 
                SYN_OPRC t4 on t4.PrcCode=t2.U_Zona LEFT JOIN 
                SYN_BLS T7 ON T7.PrjCode=t2.PrjCode LEFT JOIN
                Colaboradores t5 ON t1.pm_id=t5.id_colaborador LEFT JOIN
                SYN_ORDR T6 ON T6.Docnum=t2.u_p_interno
                WHERE 
                t0.status=1 and 
                t1.status=1 and 
                t2.u_estatus=0 and
                (t1.PrjCode!='S-01' and t1.PrjCode!='S-02' and t1.PrjCode!='S-03' and t1.PrjCode!='S-04')  
                AND ( t1.id_project in (SELECT 
                    distinct t9.PrjId 
                    FROM 
                    tbl_knocker_assignments t8 LEFT JOIN
                    tbl_knocker_tasks t9 on t8.IdTask=t9.IdTask 
                    WHERE
                    t8.status=1 
                    and t8.IdResource = ?
                    group by t9.PrjId)
                    or t1.id_project in (SELECT 
                    distinct T10.id_project 
                    from 
                    tbl_knocker_projects T10
                    WHERE 
                    T10.status=1 AND 
                    T10.pm_id = ? ) )
                ) n0 where fecha_compromiso >= dateadd(year,-2,getdate()) and estado<>'Finalizado'
                ORDER BY
                prjcode desc",
                [
                    $id_colaborador,
                    $id_colaborador
                ]                
            );
        }
        
    }
    
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}



public function woDatosDetalleProyectoOK()
{
    $json = $this->request->getJSON();
    $id_colaborador = $json->id_colaborador;
    $id_ot = $json->id_ot;
    
    $query = $this->db->query(
        "SELECT 
        TOP 1
        w.id_project
        , k.prjcode
        , p.prjname
        , IIF( isnull(c1.id_cotizacion,0) > 0, c1.id_cotizacion, isnull(c2.id_cotizacion,0) ) as 'id_cotizacion'
        , ( SELECT ot_estado FROM tbl_knocker_work_orders WHERE id_project = k.id_project ) as 'ot_estado'
        , IIF( ? IN (SELECT pm_id FROM tbl_knocker_projects WHERE id_project = k.id_project ),1,0) as 'ot_es_pm'
        , ( IIF( ? IN (SELECT distinct
				t0.IdResource
				FROM 
				tbl_knocker_assignments t0 LEFT JOIN
				tbl_knocker_tasks t1 on t0.IdTask=t1.IdTask
				WHERE 
				t0.Status=1 and 
				t1.PrjId=k.id_project and
  				t1.Status=1),1,0) ) as 'ot_es_recurso'
        , case ? when 298 then 1 when 23 then 1 when 1550 then 1 when 1040 then 1 when 1069 then 1 else 0 end as 'anticipo'
        , isnull((SELECT ISNULL(SUM(t0.effort_horas),0) as hrs_reg 
            FROM tbl_knocker_tasks_effort t0 LEFT JOIN tbl_knocker_tasks t1 ON t0.idTask=t1.IdTask 
            WHERE t0.status=1 AND t1.Status=1 AND T0.effortStatus=2 AND t1.PrjId=w.id_project),0) as 'hrs_reg'
        , (SELECT count(id_comentario) FROM tbl_knocker_wo_comentario WHERE id_user = ? AND id_ot = w.id_ot) as 'com_encuesta'
   FROM 
        tbl_knocker_work_orders w 
        LEFT JOIN tbl_knocker_projects k ON w.id_project=k.id_project
        LEFT JOIN SYN_OPRJ p ON p.PrjCode=k.PrjCode collate database_default
        LEFT OUTER JOIN tbl_app_cotizacionesCoe c1 ON c1.no_pedSAP=p.U_P_Interno 
        LEFT OUTER JOIN tbl_app_cotizacionesCoe c2 ON c2.no_pedSAP_ser=p.U_P_Interno collate database_default
   WHERE 
       w.id_ot = ? ", [$id_colaborador,$id_colaborador,$id_colaborador,$id_colaborador,$id_ot]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function woDatosDetalleHistComentarios()
{
    $json = $this->request->getJSON();
    //$id_proyecto = $json->id_proyecto;
    $id_cotizacion = $json->id_cotizacion;
    
    $query = $this->db->query(
        "SELECT 
            t.appComentarios_comentarios
            , t.appComentarios_colaborador_id 
            , isnull(convert(varchar(10),t.appComentarios_comentariosFecha,21), '--') as 'appComentarios_comentariosFecha'
            , c.nombres+' '+c.apellido_p as 'nombrecolaborador' 
        FROM tbl_appComentarios t 
        LEFT JOIN colaboradores c on t.appComentarios_colaborador_id=c.id_colaborador
        WHERE t.appComentarios_cotizacion_id = ?", [$id_cotizacion]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function woDatosDetalleArchivosCot()
{
    $json = $this->request->getJSON();
    //$id_proyecto = $json->id_proyecto;
    $id_cotizacion = $json->id_cotizacion;
    
    $query = $this->db->query(
        "SELECT 
            anexos_archivo
            ,anexos_id
            ,case
                when anexos_adjuntar=1 then 'modulos/cotizaciones/Anexos/'+anexos_archivo
                else 'modulos/cotizaciones/Anexos/Cotizaciones/'+anexos_archivo 
                end as anexos_adjuntar
        FROM tbl_appAnexos 
        WHERE anexos_cotizacion_id = ?
            and anexos_estado=1  
            and (anexos_adjuntar=1 OR anexos_adjuntar=2)", [$id_cotizacion]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function woDatosDetalleArchivosOCP()
{
    $json = $this->request->getJSON();
    //$id_proyecto = $json->id_proyecto;
    $id_cotizacion = $json->id_cotizacion;
    
    $query = $this->db->query(
        "SELECT 
            anexos_archivo
            ,anexos_id
            ,case
                when anexos_adjuntar=3 then 'modulos/cotizaciones/Anexos/Ordenes_Compra/'+anexos_archivo
                else 'modulos/cotizaciones/Anexos/Ordenes_Compra_Prov/'+anexos_archivo 
                end as anexos_adjuntar
        FROM tbl_appAnexos 
        WHERE 
            anexos_cotizacion_id = ?
            and anexos_estado=1  and (anexos_adjuntar=3 OR anexos_adjuntar=4)", [$id_cotizacion]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function woWOPermisos()
{
    $jsonDet = $this->request->getJSON();
    $ot_estado = $jsonDet->ot_estado;
    $ot_es_pm = $jsonDet->ot_es_pm;
    $ot_es_recurso = $jsonDet->ot_es_recurso;
    $GetSurveyResources=0;
    $GetSurveyQuestions=0;
    $GetSurveyAVG=0;
    $GetSurveyComent=0;
    
    if($ot_es_recurso==1 && $ot_es_pm==1){
        switch($ot_estado){
            case 0://cancelada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 1://en espera
                $OTFile=1;
                $btn_UPOTFile=1;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=1;
                $btn_CancelarWO=1;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=1;
                $div_correo_encuesta=1;
                break;
            case 2://ejecutada
                $OTFile=1;
                $btn_UPOTFile=1;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=1;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=1;
                $div_facturacion=1;
                $li_EncuestaOT=1;
                $OT_fecha_terminacion=1;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;

                $GetSurveyResources=1;
				$GetSurveyQuestions=1;
				$GetSurveyComent=1;
                break;
            case 3://terminada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=1;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;

                $GetSurveyResources=1;
				$GetSurveyQuestions=1;
				$GetSurveyAVG=1;
				$GetSurveyComent=1;
                break;
            case 4://facturada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=0;
                $btn_sendComentOT=0;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 5://facturada parcial
                $OTFile=1;
                $btn_UPOTFile=1;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=1;
                $btn_CancelarWO=1;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=1;
                $div_correo_encuesta=1;
                break;
        }
    }else if($ot_es_recurso==1 && $ot_es_pm==0){
        switch($ot_estado){
            case 0://cancelada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 1://en espera
                $OTFile=1;
                $btn_UPOTFile=1;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=1;
                $btn_CancelarWO=0;//--
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=1;
                $div_correo_encuesta=1;
                break;
            case 2://ejecutada
                $OTFile=0;//--
                $btn_UPOTFile=0;//--
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;//--
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;//--
                $div_facturacion=0;//--
                $li_EncuestaOT=0;//--
                $OT_fecha_terminacion=0;//--
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 3://terminada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=1;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 4://facturada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=0;
                $btn_sendComentOT=0;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 5://facturada parcial
                $OTFile=1;
                $btn_UPOTFile=1;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=1;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=1;
                $div_correo_encuesta=0;
                break;
        }
    }else if($ot_es_recurso==0 && $ot_es_pm==1){
        switch($ot_estado){
            case 0://cancelada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 1://en espera
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=1;
                $btn_CancelarWO=1;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=1;
                $div_correo_encuesta=1;
                break;
            case 2://ejecutada
                $OTFile=1;
                $btn_UPOTFile=1;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=1;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=1;
                $div_facturacion=1;
                $li_EncuestaOT=1;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=1;
                $div_correo_encuesta=0;

                $GetSurveyResources=1;
				$GetSurveyQuestions=1;
				$GetSurveyComent=1;
                break;
            case 3://terminada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=1;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;

                $GetSurveyResources=1;
				$GetSurveyQuestions=1;
				$GetSurveyAVG=1;
				$GetSurveyComent=1;
                break;
            case 4://facturada
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=0;
                $btn_sendComentOT=0;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=0;
                $btn_CancelarWO=0;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=0;
                $div_correo_encuesta=0;
                break;
            case 5://facturada parcial
                $OTFile=0;
                $btn_UPOTFile=0;
                $coment_ot=1;
                $btn_sendComentOT=1;
                $btn_TerminarWO=0;
                $btn_EjecutarWO=1;
                $btn_CancelarWO=1;
                $btn_RegEPWO=0;
                $div_facturacion=0;
                $li_EncuestaOT=0;
                $OT_fecha_terminacion=0;
                $OT_fecha_confirmada=1;
                $div_correo_encuesta=1;
                break;
        }
    }else if($ot_es_recurso==0 && $ot_es_pm==0){
        $OTFile=0;
        $btn_UPOTFile=0;
        $coment_ot=0;
        $btn_sendComentOT=0;
        $btn_TerminarWO=0;
        $btn_EjecutarWO=0;
        $btn_CancelarWO=0;
        $btn_RegEPWO=0;
        $div_facturacion=0;
        $li_EncuestaOT=0;
        $OT_fecha_terminacion=0;
        $OT_fecha_confirmada=0;
        $div_correo_encuesta=0;
    }
    
    $query = $this->db->query(
        "SELECT 
        ? as 'OTFile'
        , ? as 'btn_UPOTFile'
        , ? as 'coment_ot'
        , ? as 'btn_sendComentOT'
        , ? as 'btn_TerminarWO'
        , ? as 'btn_EjecutarWO'
        , ? as 'btn_CancelarWO'
        , ? as 'btn_RegEPWO'
        , ? as 'div_facturacion'
        , ? as 'li_EncuestaOT'
        , ? as 'OT_fecha_terminacion'
        , ? as 'OT_fecha_confirmada'
        , ? as 'div_correo_encuesta'
        , ? as 'GetSurveyResources'
        , ? as 'GetSurveyQuestions'
        , ? as 'GetSurveyAVG'
        , ? as 'GetSurveyComent'
            ", [$OTFile
        , $btn_UPOTFile
        , $coment_ot
        , $btn_sendComentOT
        , $btn_TerminarWO
        , $btn_EjecutarWO
        , $btn_CancelarWO
        , $btn_RegEPWO
        , $div_facturacion
        , $li_EncuestaOT
        , $OT_fecha_terminacion
        , $OT_fecha_confirmada
        , $div_correo_encuesta
        , $GetSurveyResources
        , $GetSurveyQuestions
        , $GetSurveyAVG
        , $GetSurveyComent]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function woDatosDetalle()
{
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    
    $query = $this->db->query(
        "SELECT 
        convert(varchar(10),t0.fecha_confirmada,21) as 'fecha_confirmada',
        iif(isnull(t0.fecha_confirmada,0)=0, 0, 1) as 'existe_fecha_confirmada',
        t0.ot_estado,
        convert(varchar(10),t0.fecha_terminacion,21) as 'fecha_terminacion',
        iif(isnull(t0.fecha_terminacion,0)=0, 0, 1) as 'existe_fecha_terminacion',
        isnull((SELECT count(id_comentario) FROM tbl_knocker_wo_comentario WHERE id_user = ? AND id_ot = t0.id_ot),0) as 'com_encuesta',
        isnull((SELECT COUNT(distinct id_user) encuestas_done FROM tbl_knocker_wo_survey WHERE id_ot=t0.id_ot and status=1 and etapa=0),0) as 'encuestas_hechas',
        isnull((SELECT COUNT(distinct j0.IdResource) AS tot_encuestas FROM tbl_knocker_assignments j0 LEFT JOIN tbl_knocker_tasks j1 on j0.IdTask=j1.IdTask WHERE j0.Status=1 and j1.PrjId=t0.id_project and j1.Status=1),0) as 'encuestas_total',
        (SELECT t1.service_duration FROM tbl_knocker_projects t1 where t1.id_project=t0.id_project)  as  duration,
        convert(varchar(10),(SELECT DATEADD(DAY, CEILING(CAST((SELECT t1.service_duration FROM tbl_knocker_projects t1 where t1.id_project=t0.id_project) AS FLOAT)/8)-1 , t0.fecha_confirmada )),21) as fecha_compromiso
        FROM 
        tbl_knocker_work_orders t0 
        WHERE 
        t0.id_ot = ? ", [$id_colaborador,$id_ot]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function woHistComentsOT()
{
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $query = $this->db->query(
        "SELECT 
        t0.coment_ot,
        convert(varchar(10),t0.coment_date,21) as coment_date,
        t1.nombres+' '+t1.apellido_p as colaborador
        FROM 
        tbl_knocker_wo_coments t0 LEFT JOIN
        Colaboradores t1 on t0.coment_user_id=t1.id_colaborador
        WHERE 
        t0.coment_status=1 AND
          t0.id_ot = ?", [$id_ot]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function GetOTAttachments()
{
    $jsonDet = $this->request->getJSON();
    $id_ot = $jsonDet->id_ot;
    $ot_estado = $jsonDet->ot_estado;
    $ot_es_pm = $jsonDet->ot_es_pm;
    $ot_es_recurso = $jsonDet->ot_es_recurso;
    $query = $this->db->query(
        "SELECT 
        att_id
        , left(att_name, 30) + '...' as 'att_name'
        , 'modulos/knocker/Anexos/'+att_name as 'att_name_dir'
        , att_user_id 
        , case ? when 1 then (iif( ? >=1, 1, 0 ))
            when 2 then (iif( ? >=1, 1, 0 ))
            else 0 end as 'permisoborrar'
        FROM tbl_knocker_wo_attachments 
        WHERE att_status=1 and att_type=1 and id_ot = ?
        ", [$ot_estado, $ot_es_recurso, $ot_es_pm, $id_ot]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}


public function GetAnexosWO()
{
    $jsonDet = $this->request->getJSON();
    $id_ot = $jsonDet->id_ot;
    $ot_es_pm = $jsonDet->ot_es_pm;
    $ot_es_recurso = $jsonDet->ot_es_recurso;
    $query = $this->db->query(
        "SELECT 
        att_id
        , left(att_name, 30) + '...' as 'att_name'
        ,'modulos/knocker/Anexos/'+att_name as 'att_name_dir'
        ,att_user_id
        , (iif( ? >=1, 1,  (iif( ? >=1, 1, 0 ))  )) as 'permisoborrar'
        FROM tbl_knocker_wo_attachments 
        WHERE att_status=1 and att_type=2 and id_ot = ?
        ", [$ot_es_recurso, $ot_es_pm, $id_ot]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}

public function SendFormFactAntiComp(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $coment_anticipo_completo = $json->coment_anticipo_completo;
    //monto completo
    $query00 = $this->db->query(
        "INSERT INTO 
        tbl_knocker_wo_facturacion 
        (id_ot,tipo_facturacion,facturacion_monto,fecha_terminacion,anticipo) 
        VALUES 
        ( ? , 1, 0, GETDATE(), 1) ",
        [ $id_ot ]
    );

    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (?, ?, ?)",
        [
            $coment_anticipo_completo,
            $id_colaborador,
            $id_ot
        ]
    ); 
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function SendFormFactAntiParc(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $coment_anticipo_parcial = $json->coment_anticipo_parcial;
    $monto_facturar = $json->monto_facturar;
    //monto parcial
    $query00 = $this->db->query(
        "INSERT INTO 
        tbl_knocker_wo_facturacion 
        (id_ot,tipo_facturacion,facturacion_monto,fecha_terminacion,anticipo) 
        VALUES 
        ( ? , 2, ? , GETDATE(), 1) ",
        [ $id_ot, $monto_facturar ]
    );

    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (?, ?, ?)",
        [
            $coment_anticipo_parcial,
            $id_colaborador,
            $id_ot
        ]
    );     
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function SendFormComentarioHist(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $comentOt = $json->comentOt;

    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (?, ?, ?)",
        [
            $comentOt,
            $id_colaborador,
            $id_ot
        ]
    ); 
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function SendFormFechaConfirmada(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $OTfechaConfirmada = $json->OTfechaConfirmada;
    $coment_ot="Fecha de confirmación actualizada: ".$OTfechaConfirmada;
    $query00 = $this->db->query(
        "UPDATE tbl_knocker_work_orders SET fecha_confirmada= ?  WHERE id_ot= ? ",
        [ 
            $OTfechaConfirmada, 
            $id_ot 
        ]
    );

    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (?, ?, ?)",
        [
            $coment_ot,
            $id_colaborador,
            $id_ot
        ]
    );  

    $query2 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_log (id_ot,tipo,user_id) VALUES (? ,1,?)",
        [
            $id_ot,
            $id_colaborador
        ]
    );  
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function SendFormFechaTerminacion(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $OTfechaTerminacion = $json->OTfechaTerminacion;
    $coment_ot="Fecha de terminación actualizada: ".$OTfechaTerminacion;
    $query000 = $this->db->query(
        "SELECT IIF ( GETDATE() >= ?  , 1, 0) as update_row",
        [ $OTfechaTerminacion ]
    );
    $result000 = $query000->getResult();

    foreach ($result000 as $res000){
        $update_row = $res000->update_row;
        if ($update_row==1){
            $query00 = $this->db->query(
                "UPDATE tbl_knocker_work_orders SET fecha_terminacion = ?  WHERE id_ot = ? ",
                [ 
                    $OTfechaTerminacion, 
                    $id_ot 
                ]
            );
            $query1 = $this->db->query(
                "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (?, ?, ?)",
                [
                    $coment_ot,
                    $id_colaborador,
                    $id_ot
                ]
            );  
            $query2 = $this->db->query(
                "INSERT INTO tbl_knocker_wo_log (id_ot,tipo,user_id) VALUES (? ,6,?)",
                [
                    $id_ot,
                    $id_colaborador
                ]
            );  
            return $this->response->setStatusCode(200)->setJSON(1);
        }else{
            return $this->response->setStatusCode(200)->setJSON(0);
        }
    }
    
    
    
    

}

public function SendFormComentariosCancelarOT(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $comentcancelot = $json->comentcancelot;
    $comentOt="Orden de trabajo cancelada";
    $query3 = $this->db->query(
        "UPDATE tbl_knocker_work_orders SET ot_estado=0 WHERE id_ot = ?",
        [
            $id_ot
        ]
    );
    $query0 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (? ,? ,? )",
        [
            $comentcancelot,
            $id_colaborador,
            $id_ot
        ]
    ); 
    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_log (id_ot,tipo,user_id) VALUES (?,0,?)",
        [
            $id_ot,
            $id_colaborador
        ]
    );
    $query2 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (?,?,?)",
        [
            $comentOt,
            $id_colaborador,
            $id_ot
        ]
    );
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function SendFormTermFactComp(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $coment_anticipo_completo = $json->coment_anticipo_completo;
    $query3 = $this->db->query(
        "UPDATE tbl_knocker_work_orders SET ot_estado=3 WHERE id_ot = ?",
        [
            $id_ot
        ]
    );
    //monto completo
    $query00 = $this->db->query(
        "INSERT INTO 
        tbl_knocker_wo_facturacion 
        (id_ot,tipo_facturacion,facturacion_monto,fecha_terminacion,anticipo) 
        VALUES 
        ( ? , 1, 0, GETDATE(), 1) ",
        [ $id_ot ]
    );

    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_log (id_ot,tipo,user_id) VALUES (?,3,?)",
        [
            $id_ot,
            $id_colaborador
        ]
    ); 

    $query2 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES ('Orden de trabajo terminada', ?, ?)",
        [
            $id_colaborador,
            $id_ot
        ]
    ); 
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function SendFormTermFactParc(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $coment_anticipo_parcial = $json->coment_anticipo_parcial;
    $monto_facturar = $json->monto_facturar;
    $query3 = $this->db->query(
        "UPDATE tbl_knocker_work_orders SET ot_estado=3 WHERE id_ot = ?",
        [
            $id_ot
        ]
    );
    //monto parcial
    $query00 = $this->db->query(
        "INSERT INTO 
        tbl_knocker_wo_facturacion 
        (id_ot,tipo_facturacion,facturacion_monto,fecha_terminacion,anticipo) 
        VALUES 
        ( ? , 2, ? , GETDATE(), 1) ",
        [ $id_ot, $monto_facturar ]
    );

    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES (?, ?, ?)",
        [
            $coment_anticipo_parcial,
            $id_colaborador,
            $id_ot
        ]
    );     
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function backEPWO()
{
    $json = $this->request->getJSON();
    $id_ot = $json;
    $query3 = $this->db->query(
        "UPDATE tbl_knocker_work_orders SET ot_estado=1 WHERE id_ot = ?",
        [
            $id_ot
        ]
    );
    return $this->response->setStatusCode(200)->setJSON(1);
    //falta
    /*
    $sql="SELECT 
					t0.id_project,
					(SELECT PrjCode FROM tbl_knocker_projects WHERE id_project=t0.id_project and status=1) as PrjCode
					FROM tbl_knocker_work_orders t0 WHERE t0.id_ot=$id_ot";
			$res=sqlsrv_query($conn,$sql);
			$row=sqlsrv_fetch_array($res);
			$id_project=$row['id_project'];
			$PrjCode=$row['PrjCode'];
			$to="";

			$sql2="SELECT distinct
					(SELECT t2.nombres+' '+t2.apellido_p FROM Colaboradores t2 WHERE t2.id_colaborador=t0.IdResource) as name,
					(SELECT t2.email FROM Colaboradores t2 WHERE t2.id_colaborador=t0.IdResource) as email
					FROM 
					tbl_knocker_assignments t0 LEFT JOIN
					tbl_knocker_tasks t1 on t0.IdTask=t1.IdTask
					WHERE 
					t0.Status=1 and 
					t1.PrjId=$id_project and
  					t1.Status=1";
			$res2=sqlsrv_query($conn,$sql2);
			while ($row2=sqlsrv_fetch_array($res2)) {
				$email_to=$row2['email'];
				$to.=$email_to.",";
			}


			$asunto="Orden de trabajo regresada";

			$message = "<html lang='en'>
                          <head>
                            <meta charset='UTF-8'>
                            <title>Titutlo</title>
                          </head>
                          <body>";

			$message .="<table >
                        <tr>
                        	<td style='width:150px; '></td>
                        	<td style='width:500px; border-bottom: solid 1px #D8D8D8;'>";

			$message .= "    <img border=0 src='http://".$_SERVER['HTTP_HOST']."/intranet/images/app_images/about/ecn3.png' alt='ecn' style='width:30%; height:30%;'/>
	                         ";
			$message .="</td>
							<td style='width:500px; border-bottom: solid 1px #D8D8D8; vertical-align:middle;'><h1 style='text-align: right; margin-right:10px; font-family:Helvetica;'></h1></td>
				            <td style='width:150px; '></td>";
			$message.="</tr>
			        <tr>
			         <td style='width:150px; '></td>
			         <td style='width:1000px; border-bottom: solid 1px #D8D8D8;' colspan=2>
					  <br>
					  <h2 style='font-family:Helvetica;'>Estimado(a) colaborador(a):</h2>
					  <h3 style='font-family:Helvetica;'>Se te informa que la orden de trabajo del servicio '{$PrjCode}' ha sido regresada por el PM.</h3>
			          <br>
			         </td>
			         <td style='width:150px; '></td>
			        </tr>
			        <tr>
			         <td style='width:150px; '></td>
			         <td style='width:1000px;' colspan=2>
			         <br>
					  <p style='font-family:Helvetica; font-size:0.7em; color:#848484;'>&copy;".$year." ecn.com.mx. Todos los derechos reservados ecn.</p>
					  <p style='font-family:Helvetica; font-size:0.7em; color:#848484;'>Este correo fue enviado automáticamente, por favor no respondas a este mensaje</p>
			         <td style='width:150px; '></td>
			        </tr>
					</table>
					</body>
					</html>";

			$subject = $asunto;
			$message = $message;
			$to=$to;
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
			$headers .= "From:Intranet ECN (ProjectDone) <".$from.">";
			mail($to,$subject,$message,$headers);
    */
}

public function ejecutarWO()
{
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $mailencuestasatisf = $json->mailencuestasatisf;
    $query3 = $this->db->query(
        "UPDATE tbl_knocker_work_orders SET ot_estado=2 WHERE id_ot = ?",
        [
            $id_ot
        ]
    );
    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_log (id_ot,tipo,user_id) VALUES (?,2,?)",
        [
            $id_ot,
            $id_colaborador
        ]
    );
    $query2 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_coments (coment_ot,coment_user_id,id_ot) VALUES ('Orden de trabajo ejecutada', ?, ?)",
        [
            $id_colaborador,
            $id_ot
        ]
    );
    /*
    $id_ot = isset($_POST['id_ot']) ? $_POST['id_ot'] : 0;
		$c_email = isset($_POST['c_email']) ? $_POST['c_email'] : 0;

		
			$sql="SELECT 
				T2.prjcode,
				t2.prjname,
				t2.u_p_interno,
				t4.name,
				t4.firstname,
				t4.lastname,
				t4.e_maill as c_email,
				getdate() as fecha,
				t3.numatcard as oc
				FROM
				tbl_knocker_work_orders T0 left join
				tbl_knocker_projects T1 ON T0.id_project=T1.id_project LEFT JOIN
				syn_oprj t2 on t1.PrjCode=t2.prjcode collate database_default left join
				syn_ordr t3 on t2.u_p_interno=t3.docnum LEFT JOIN
				syn_ocpr t4 on t3.cardcode=t4.cardcode and t3.CntctCode=t4.CntctCode
				WHERE
				T0.id_ot=$id_ot";
			$res=sqlsrv_query($conn,$sql);
			$row=sqlsrv_fetch_array($res);
			$to_email=$row['c_email'];
			$prjcode=$row['prjcode'];
			$fecha=date_format($row['fecha'],'dmY');
			$oc=$row['oc'];

	   if (is_null($c_email) || $c_email == "") {
	    $email_to=$to_email;
	   }else{
	   	 $email_to=$c_email;
	   }
		
		$sql2="INSERT INTO  tbl_knocker_satisfaccion (id_ot,prjcode,correo) values ($id_ot,'$prjcode','$email_to')";
		$res2=sqlsrv_query($conn,$sql2);

		$n_proyecto=substr($prjcode, strrpos($prjcode, '-')+1 );


		$mensaje = "<a href='http://intranet.ecn.com.mx:8060/satisfaccion/encuesta.php?prjcode=KClaoixKAOCaiwuAinq-{$fecha}{$n_proyecto}' target='_blank'> <img src='http://intranet.ecn.com.mx:8060/intranet/php/knocker/encuesta1.png' style='width:50%;'/> </a> ";
		$from="ECN Automation <intranet@ecnautomation.com>";
	    $to = $email_to;
		//$to = "eduardo.colores@ecnautomation.com";          
		$subject = "Encuesta de satisfacción del servicio: '{$prjcode}',  OC:'{$oc}' ";
		$message = $mensaje;
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		$headers .= "From:" . $from;
		mail($to,$subject,$message,$headers);
    */
    return $this->response->setStatusCode(200)->setJSON(1);
}

public function getEncuestaComentarios()
{
    $jsonDet = $this->request->getJSON();
    $id_ot = $jsonDet->id_ot;
    $id_colaborador = $jsonDet->id_colaborador;
    $query = $this->db->query(
        "SELECT * FROM tbl_knocker_wo_comentario WHERE id_user = ? AND id_ot = ? ", [$id_colaborador, $id_ot]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}

public function sendEncuestaSugerenciaOT(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $id_colaborador = $json->id_colaborador;
    $encuestacomentsugerencia = $json->encuestacomentsugerencia;
    $query0 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_comentario (id_user , id_ot , comentario) VALUES ( ?, ?, ? )",
        [
            $id_colaborador,
            $id_ot,
            $encuestacomentsugerencia
        ]
    );
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function getPersonasPorEvaluar()
{
    $jsonDet = $this->request->getJSON();
    $id_ot = $jsonDet->id_ot;
    $id_proyecto = $jsonDet->id_proyecto;
    $query = $this->db->query(
        "SELECT distinct
        t0.IdResource,
        (SELECT t2.nombres+' '+t2.apellido_p FROM Colaboradores t2 WHERE t2.id_colaborador=t0.IdResource) as name,
        IIF(t0.IdResource IN (SELECT DISTINCT(id_user) FROM tbl_knocker_wo_survey WHERE id_ot=? and status=1 and etapa=0),1,0 ) as terminado
        FROM 
        tbl_knocker_assignments t0 LEFT JOIN
        tbl_knocker_tasks t1 on t0.IdTask=t1.IdTask
        WHERE 
        t0.Status=1 and 
        t1.PrjId=? and
        t1.Status=1" , 
        [
            $id_ot,
            $id_proyecto
        ]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}

public function getEvaluacionAvg()
{
    $jsonDet = $this->request->getJSON();
    $id_ot = $jsonDet->id_ot;
    $id_proyecto = $jsonDet->id_proyecto;
    $query = $this->db->query(
        "SELECT 
        CAST((AVG(t0.calificacion)) as decimal (10,2)) as calificacion 
        FROM 
            (SELECT t0.id_user, AVG(CAST(t1.response_value AS DECIMAL)) as calificacion  
            FROM tbl_knocker_wo_survey t0 left join tbl_knocker_wo_responses t1 on t0.id_response=t1.id_response 
            WHERE t0.id_ot=? AND t0.status=1 AND t0.etapa=0 GROUP BY t0.id_user) T0" , 
        [
            $id_ot
        ]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}

public function sendRecursosEncuestaWO(){
    $json = $this->request->getJSON();
    $id_ot = $json->id_ot;
    $recursoId = $json->recursoId;//recurso evaluado 
    $coordprevserv = $json->coordprevserv;//id pregunta 1
    $entregaconfelectimp = $json->entregaconfelectimp;//id pregunta 2
    $puntualidad = $json->puntualidad;//id pregunta 3
    $uniforme = $json->uniforme;//id pregunta 4
    $equiposeguridad = $json->equiposeguridad;//id pregunta 5
    $conocimientotecnicoeq = $json->conocimientotecnicoeq;//id pregunta 6
    $certezadetecproblema = $json->certezadetecproblema;//id pregunta 7
    $herramientayeqipo = $json->herramientayeqipo;//id pregunta 8
    $calidadreporteservicio = $json->calidadreporteservicio;//id pregunta 9
    $tratopersonal = $json->tratopersonal;//id pregunta 10
    $query1 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 1, ?)",
        [ $recursoId, $id_ot, 
            $coordprevserv
        ]
    );
    $query2 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 2, ?)",
        [ $recursoId, $id_ot, 
            $entregaconfelectimp
        ]
    );
    $query3 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 3, ?)",
        [ $recursoId, $id_ot, 
            $puntualidad
        ]
    );
    $query4 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 4, ?)",
        [ $recursoId, $id_ot, 
            $uniforme
        ]
    );
    $query5 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 5, ?)",
        [ $recursoId, $id_ot, 
            $equiposeguridad
        ]
    );
    $query6 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 6, ?)",
        [ $recursoId, $id_ot, 
            $conocimientotecnicoeq
        ]
    );
    $query7 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 7, ?)",
        [ $recursoId, $id_ot, 
            $certezadetecproblema
        ]
    );
    $query8 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 8, ?)",
        [ $recursoId, $id_ot, 
            $herramientayeqipo
        ]
    );
    $query9 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 9, ?)",
        [ $recursoId, $id_ot, 
            $calidadreporteservicio
        ]
    );
    $query10 = $this->db->query(
        "INSERT INTO tbl_knocker_wo_survey (id_user,id_ot,id_question,id_response) VALUES (?, ?, 10, ?)",
        [ $recursoId, $id_ot, 
            $tratopersonal
        ]
    );
    
    return $this->response->setStatusCode(200)->setJSON(1);

}

public function getEvaluacionesEncuestaOT()
{
    $jsonDet = $this->request->getJSON();
    $id_ot = $jsonDet->id_ot;
    $query = $this->db->query(
        "SELECT t.*, j.pregunta, j.web 
        FROM tbl_knocker_wo_survey t 
        left join tbl_knocker_wo_questions j on t.id_question=j.id_pregunta
        WHERE t.id_ot=? and t.status=1 and t.etapa=0" , 
        [
            $id_ot
        ]
    );
    $result = $query->getResult();
    return $this->response->setStatusCode(200)->setJSON($result);
}

public function deleteAttFileWO(){
    $jsonDet = $this->request->getJSON();
    $id_anexo = $jsonDet->id_anexo;
    $query = $this->db->query(
        "update tbl_knocker_wo_attachments set att_status=0 where att_id = ?  ", [$id_anexo]
    );
    return $this->response->setStatusCode(200)->setJSON(1);
}

/*
$post = $this->request->getPost();
$ticketData = json_decode($post['datos']);
$archivos = $this->request->getFiles();
    if (count($archivos) > 0) {
        if ($archivos['archivo']->isValid() && !$archivos['archivo']->hasMoved()) {
            if ($archivos['archivo']->move("C:/Users/Eduardo/Documents/intranet/docs/tickets")) {
                $nombre_archivo = $archivos['archivo']->getName();
            }
        }
    }else{
        $nombre_archivo = NULL;
    }

//si se manda un solo archivo, en vez de getFiles() sería el getFile(), y ya la variable $archivo tendría el $archivo->isValid(); etc
//igual cuando se manda un formdata, se puede hacer de la manera normal del php, en la api, con el $_FILES y el move_uploaded_file


*/




/*public function nuevaSolicitud(){
        $json = $this->request->getJSON();
        $archivos = $json->archivos;
        //$query = $this->db->query('EXECUTE sp_Registro_Ticket ' . $json->id_solicitante . ',' . $json->id_area . ',"' . $json->titulo_ticket . '",' . $json->id_subarea . ',"' . $json->descripcion_ticket.'"');
        //$result = $query->getResult();
        if(count($archivos) > 0){
            //$id = $result[0]->id;
        for ($i = 0; $i < count($archivos); $i++) {
            $nombre_archivo = $archivos[$i]->name;
            //$query2 = $this->db->query('EXEC sp_Registro_Archivo ' . $id . ',' . $json->id_solicitante . ',"' . $nombre_archivo . '",1');
            //$query2->getResult();
        }
        }

        return $this->response->setStatusCode(200)->setJSON($json);
    }*/



}
