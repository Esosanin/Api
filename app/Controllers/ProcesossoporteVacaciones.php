<?php

namespace App\Controllers;

class ProcesossoporteVacaciones extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /*public function getMacroForm(){
        $json = $this->request->getJSON();
        $fecha_ingreso = $json->fecha_ingreso;
        $fecha_nacimiento = $json->fecha_nacimiento;
        $estado = $json->estado;
        $query = $this->db->query(
            "SELECT TOP 20 * FROM Colaboradores WHERE
            fecha_nacimiento>=? 
            AND fecha_ingreso<=?
            AND estado=? ",
            [
                $fecha_nacimiento,
                $fecha_ingreso,
                $estado
            ]
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
      
    }*/

    public function getMiInfo()
    {
        $json = $this->request->getJSON();
        $id_colaborador = $json->id_colaborador;
        $query = $this->db->query(
            " select *, (J.diastotal - J.usados) as disponibles, (j.diasdescansoganados-j.usadosdescanso) as 'usadosdisponiblesdescanso' from (SELECT 
            t.id_colaborador
            , t.sexo
            , t.id_area
            , t.fi_vacaciones 
            , CONVERT(VARCHAR(10), t.fi_vacaciones, 21) AS f_union --FECHA QUE SE UNIO EL USUARIO A ECN
            , (SELECT TOP 1 t0.nombres+' '+t0.apellido_p+' '+t0.apellido_m FROM Colaboradores t0 WHERE t0.id_colaborador=t.id_aprobador) AS jefe
            , CONVERT(VARCHAR(10), (DATEADD(year, (((DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12) +1), (t.fi_vacaciones))), 21) as caducidad
            , t.id_aprobador AS jefe_inmediato
            , (DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12 as antiguedadanios
            --, (DATEDIFF(month,t.fecha_ingreso, GETDATE()) + 1) as antiguedadaniosmas1
			--, CONVERT(VARCHAR(4), (DATEADD(year, (((DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12) ), (t.fi_vacaciones))), 21) as 'asdsd'
            , iif((CONVERT(VARCHAR(4), (DATEADD(year, (((DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12) ), (t.fi_vacaciones))), 21))>=2023, 
			(case (DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12 when 0 then 0
            when 1 then 12 
			when 2 then 14
            when 3 then 16
            when 4 then 18
            when 5 then 20
            when 6 then 22 when 7 then 22 when 8 then 22 when 9 then 22 when 10 then 22
            when 11 then 24 when 12 then 24 when 13 then 24 when 14 then 24 when 15 then 24
            when 16 then 26 when 17 then 26 when 18 then 26 when 19 then 26 when 20 then 26
            when 21 then 28 when 22 then 28 when 23 then 28 when 24 then 28 when 25 then 28
            else 30 end )		
			,
			(case (DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12 when 0 then 0
            when 1 then 10 when 2 then 10
            when 3 then 12
            when 4 then 14
            when 5 then 16 when 6 then 16 when 7 then 16 when 8 then 16 when 9 then 16
            when 10 then 18 when 11 then 18 when 12 then 18 when 13 then 18 when 14 then 18
            when 15 then 20 when 16 then 20 when 17 then 20 when 18 then 20 when 19 then 20
            when 20 then 22 when 21 then 22 when 22 then 22 when 23 then 22 when 24 then 22
            else 25 end )
			) as 'diastotal'
            , isnull((select count(j.id) as cantidad from tbl_vacaciones_solicitudes j where j.status=1 and j.estado in (1) and j.aprobador=t.id_colaborador),0) as esjefe
            , isnull((select count(j.id) as cantidad from tbl_vacaciones_solicitudes j where j.status=1 and j.estado in (2) and j.aprobador=t.id_colaborador),0) as esch

            ,(
            ISNULL(
                (
                SELECT SUM(t0.dias_solicitados) AS usados 
                FROM 
                    tbl_vacaciones_solicitudes t0 LEFT JOIN 
                    Colaboradores t1 ON t1.id_colaborador = t0.id_colaborador AND t0.fecha_registro >= t1.fecha_ingreso
                WHERE 
                    t0.id_colaborador = t.id_colaborador AND 
                    t0.estado NOT IN (0,4,5) AND 
                    t0.tipo_solicitud = 2 AND
                    t0.status = 1 AND
                    t0.v_antiguedad = ((DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12)
                ),
            0 ) 
            + 
            ISNULL(
                (
                SELECT SUM(t0.v_dias_adelantados) AS usados 
                FROM 
                    tbl_vacaciones_solicitudes t0 LEFT JOIN 
                    Colaboradores t1 ON t1.id_colaborador = t0.id_colaborador AND t0.fecha_registro >= t1.fecha_ingreso
                WHERE 
                    t0.id_colaborador = t.id_colaborador AND
                    t0.estado NOT IN (0,4,5) AND 
                    t0.tipo_solicitud = 2 AND
                    t0.status = 1 AND
                    t0.v_antiguedad = ((DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12)
                ), 
            0 )
        ) AS usados,
        ISNULL(
            (
            SELECT SUM(t0.v_dias_adelantados) AS usados 
            FROM 
                tbl_vacaciones_solicitudes t0 LEFT JOIN 
                Colaboradores t1 ON t1.id_colaborador = t0.id_colaborador AND t0.fecha_registro >= t1.fecha_ingreso
            WHERE
                t0.id_colaborador = t.id_colaborador AND
                t0.estado NOT IN (0,4,5) AND 
                t0.tipo_solicitud = 2 AND
                t0.status = 1 AND
                t0.v_antiguedad > ((DATEDIFF(month,t.fecha_ingreso, GETDATE()))/12)
            ),
        0 ) AS adelantados,
        (select count(t0.id) from tbl_vacaciones_solicitudes t0 where t0.estado=1 and t0.status=1 and t0.aprobador=t.id_colaborador) as 'poraprobarcomolider',
        (select count(t0.id) from tbl_vacaciones_solicitudes t0 where t0.estado=2 and t0.status=1) as 'poraprobarcomoch',
        isnull( (select SUM(t0.dias_solicitados) as usados from tbl_vacaciones_solicitudes t0 where t0.tipo_solicitud=1 and t0.d_motivo=0 and t0.status=1 and t0.estado not in (0, 4, 5, 11) and year(t0.fecha_inicial)>=2022 and year(t0.fecha_registro)>=2022 and t0.id_colaborador=t.id_colaborador) ,0) as usadosdescanso,
        isnull(( select sum(v.fecha) as fecha from VW_Colab_DiasDescansoGanados v where v.id_colaborador=t.id_colaborador ),0) as 'diasdescansoganados',
        (select count(p.asignperf_perfil) FROM tbl_knocker_asign_perf p where p.asignperf_status=1 and p.asignperf_perfil in (3, 7) and p.asignperf_user=t.id_colaborador) as 'esproyectos'
        FROM 
            Colaboradores t 
        WHERE 
            t.id_colaborador = ? 

            ) J
            ", [$id_colaborador]
        );
        $result = $query->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getMisSolicitudes()
    {
        $json = $this->request->getJSON();
        $id_colaborador = $json->id_colaborador;
        $query = $this->db->query(
            "select 
            t0.id
            , convert(varchar(19),t0.fecha_registro, 21) fecha_registro
            , convert(varchar(10),t0.fecha_inicial, 21) fecha_inicial
            , convert(varchar(10),t0.fecha_final, 21) fecha_final
            , t0.dias_solicitados+isnull(t0.v_dias_adelantados,0) as dias_solicitados
            , case when t0.estado=0 then 'Cancelado por colaborador' when t0.estado=3 then 'Recibida por CH' when t0.estado=4 then 'Rechazado por el líder' when t0.estado=5 then 'Rechazado por CH' else 'Error' end as estado
            , (select t.nombres+' '+t.apellido_p+' '+t.apellido_m from Colaboradores t where t.id_colaborador=t0.aprobador) aprobador
            , case when t0.tipo_solicitud=0 then 'Permiso' when t0.tipo_solicitud=1 then 'Descanso' when t0.tipo_solicitud=2 then 'Vacaciones' else 'Error' end as tipo 
            , (SELECT STRING_AGG(('Actividad: '+j.actividad+'. Responsable: '+j.responsable+'. Fecha limite: '+j.fecha_limite+'. Observaciones: '+j.observaciones), '<br> ') FROM tbl_vacaciones_planTrabajo j WHERE j.id_solicitud = t0.id) as 'plantrabajo'
            , iif(tipo_solicitud=0,(case when con_sueldo=1 then '(con sueldo)' else '(sin sueldo)' end), '') as 'con_sueldo'
            from tbl_vacaciones_solicitudes t0
            where t0.id_colaborador=? 
            and t0.estado in (0,3,4,5) and t0.status=1 
            order by t0.fecha_registro desc", [$id_colaborador]
        );
        $result = $query->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getMisSolicitudesPendientes()
    {
        $json = $this->request->getJSON();
        $id_colaborador = $json->id_colaborador;
        $query = $this->db->query(
            "select 
            id
            , convert(varchar(19),fecha_registro, 21) fecha_registro
            , convert(varchar(10),fecha_inicial, 21) fecha_inicial
            , convert(varchar(10),fecha_final, 21) fecha_final
            , convert(varchar(10),v_regreso_labores, 21) v_regreso_labores
            , dias_solicitados+isnull(v_dias_adelantados,0) as dias_solicitados
            , v_dias_totales
            , v_antiguedad
            , v_comentarios
            , case when estado=1 then 'Pendiente (con el líder)' else 'Aprobado (líder), pendiente (con CH)' end as estado
            , (select t.nombres+' '+t.apellido_p+' '+t.apellido_m from Colaboradores t where t.id_colaborador=aprobador) aprobador
            , case when tipo_solicitud=0 then 'Permiso' when tipo_solicitud=1 then 'Descanso' when tipo_solicitud=2 then 'Vacaciones' else 'Error' end as tipo
            , tipo_solicitud
            , (SELECT STRING_AGG(('Actividad: '+j.actividad+'. Responsable: '+j.responsable+'. Fecha limite: '+j.fecha_limite+'. Observaciones: '+j.observaciones), '<br> ') FROM tbl_vacaciones_planTrabajo j WHERE j.id_solicitud = t0.id) as 'plantrabajo' 
            from tbl_vacaciones_solicitudes t0
            where 
            id_colaborador=? 
            and estado in (1,2) 
            and status=1 
            order by fecha_registro", [$id_colaborador]
        );
        $result = $query->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getMisSolicitudesPorAprobar()
    {
        $json = $this->request->getJSON();
        $id_colaborador = $json->id_colaborador;
        $query = $this->db->query(
            "select 
            id
            , convert(varchar(19),fecha_registro, 21) fecha_registro
            , convert(varchar(10),fecha_inicial, 21) fecha_inicial
            , convert(varchar(10),fecha_final, 21) fecha_final
            , dias_solicitados+isnull(v_dias_adelantados,0) as dias_solicitados
            , case 
            when estado=0 then 'Cancelado por colaborador' 
            when estado=1 then 'Pendiente (con el líder)' 
            when estado=2 then 'Aprobado (líder), pendiente (con CH)' 
            when estado=3 then 'Recibida por CH' 
            when estado=4 then 'Rechazado por el líder' 
            else 'Error' end as estado
            , (select top 1 t.nombres+' '+t.apellido_p+' '+t.apellido_m from Colaboradores t where t.id_colaborador=t0.id_colaborador) colaborador
            , case 
            when tipo_solicitud=0 then 'Permiso' 
            when tipo_solicitud=1 then 'Descanso' 
            when tipo_solicitud=2 then 'Vacaciones' 
            else 'Error' end as tipo ,
			(SELECT STRING_AGG(('Actividad: '+j.actividad+'. Responsable: '+j.responsable+'. Fecha limite: '+j.fecha_limite+'. Observaciones: '+j.observaciones), '<br> ') FROM tbl_vacaciones_planTrabajo j WHERE j.id_solicitud = t0.id) as 'plantrabajo'
            from tbl_vacaciones_solicitudes t0 where aprobador=? and estado=1 and status=1 order by id", [$id_colaborador]
        );
        $result = $query->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getMisSolicitudesPorAprobarCH()
    {
        $json = $this->request->getJSON();
        $id_colaborador = $json->id_colaborador;
        $query = $this->db->query(
            "select 
            id
            , convert(varchar(19),fecha_registro, 21) fecha_registro
            , convert(varchar(19),fecha_aprobacion, 21) fecha_aprobacion
            , convert(varchar(10),fecha_inicial, 21) fecha_inicial
            , convert(varchar(10),fecha_final, 21) fecha_final
            , dias_solicitados+isnull(v_dias_adelantados,0) as dias_solicitados
            , case 
            when estado=0 then 'Cancelado por colaborador' 
            when estado=1 then 'Pendiente (con el líder)' 
            when estado=2 then 'Aprobado (líder), pendiente (con CH)' 
            when estado=3 then 'Recibida por CH' 
            when estado=4 then 'Rechazado por el líder' 
            else 'Error' end as estado
            , (select top 1 t.nombres+' '+t.apellido_p+' '+t.apellido_m from Colaboradores t where t.id_colaborador=t0.id_colaborador) colaborador
            , (select top 1 t.nombres+' '+t.apellido_p+' '+t.apellido_m from Colaboradores t where t.id_colaborador=t0.aprobador) aprobador
            , case 
            when tipo_solicitud=0 then 'Permiso' 
            when tipo_solicitud=1 then 'Descanso' 
            when tipo_solicitud=2 then 'Vacaciones' 
            else 'Error' end as tipo 
            , iif(tipo_solicitud=0,(case when con_sueldo=1 then '(con sueldo)' else '(sin sueldo)' end), '') as 'con_sueldo'
            , (SELECT STRING_AGG(('Actividad: '+j.actividad+'. Responsable: '+j.responsable+'. Fecha limite: '+j.fecha_limite+'. Observaciones: '+j.observaciones), '<br> ') FROM tbl_vacaciones_planTrabajo j WHERE j.id_solicitud = t0.id) as 'plantrabajo' 
            from tbl_vacaciones_solicitudes t0 
            where estado=2 and status=1", [$id_colaborador]
        );
        $result = $query->getResult();
        return $this->response->setStatusCode(200)->setJSON($result);
    }
    public function getcancelarsolicitudVM(){
        $json = $this->request->getJSON();
        $id_solicitud = $json;
        $query = $this->db->query(
            "update tbl_vacaciones_solicitudes set estado=0, fecha_aprobacion=getdate() where id = ?  ", [$id_solicitud]
        );
        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function getaprobarsolicitudVM(){
        $json = $this->request->getJSON();
        $id_solicitud = $json;
        $query = $this->db->query(
            "update tbl_vacaciones_solicitudes set estado=2, fecha_aprobacion=getdate() where id = ?  ", [$id_solicitud]
        );
        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function getrechazarsolicitudVM(){
        $json = $this->request->getJSON();
        $id_solicitud = $json;
        $query = $this->db->query(
            "update tbl_vacaciones_solicitudes set estado=4, fecha_aprobacion=getdate() where id = ?  ", [$id_solicitud]
        );
        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function getaprobarsolicitudCHVM(){
        $json = $this->request->getJSON();
        $id_solicitud = $json;
        $query = $this->db->query(
            "update tbl_vacaciones_solicitudes set estado=3, aprobado_ch=getdate() where id = ?  ", [$id_solicitud]
        );
        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function getrechazarsolicitudCHVM(){
        $json = $this->request->getJSON();
        $id_solicitud = $json;
        $query = $this->db->query(
            "update tbl_vacaciones_solicitudes set estado=5, aprobado_ch=getdate() where id = ?  ", [$id_solicitud]
        );
        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function getaprobarsolicitudconsueldoVM(){
        $json = $this->request->getJSON();
        $id_solicitud = $json;
        $query = $this->db->query(
            "update tbl_vacaciones_solicitudes set estado=2, con_sueldo=1, fecha_aprobacion=getdate() where id = ?  ", [$id_solicitud]
        );
        return $this->response->setStatusCode(200)->setJSON(1);
    }
    public function getaprobarsolicitudsinsueldoVM(){
        $json = $this->request->getJSON();
        $id_solicitud = $json;
        $query = $this->db->query(
            "update tbl_vacaciones_solicitudes set estado=2, con_sueldo=0, fecha_aprobacion=getdate() where id = ?  ", [$id_solicitud]
        );
        return $this->response->setStatusCode(200)->setJSON(1);
    }



    
//solicitar permiso
public function getMacroForm(){
    $json = $this->request->getJSON();
    $fecha_inicial = $json->fecha_inicial;
    $fecha_final = $json->fecha_final;
    $id_colaborador = $json->id_colaborador;
    $p_motivo = $json->p_motivo;
    $query = $this->db->query(
        "
        declare @fecha1 datetime = ?
        declare @fecha2 datetime = ?
        declare @findesemana int = 0
        declare @otrodia int = 1
        
        while (@fecha1 < @fecha2)
            begin
                if (DATEPART(WEEKDAY, @fecha1) = 1)
                    begin 
                    set @findesemana = @findesemana + 1
                    end
                else if (DATEPART(WEEKDAY, @fecha1) = 7)
                    begin
                    set @findesemana = @findesemana + 1
                    end
                else
                    begin
                        set @otrodia = @otrodia + 1
                    end                  
                set @fecha1 = DATEADD(DAY, 1, @fecha1)
            end
        
        INSERT INTO tbl_vacaciones_solicitudes (fecha_inicial, fecha_final, p_motivo, tipo_solicitud, id_colaborador, estado, status, aprobador, fecha_registro, dias_solicitados) values
        ( ?, ?, ?, 0, ?, 1, 1, (select jefe_inmediato from colaboradores where id_colaborador=?), getdate(), ( @otrodia - (select count(fecha_festiva) from tbl_vacaciones_diasfestivos where jornada_completa=1 and fecha_festiva between ? and ?) ) )",
        [
            $fecha_inicial,
            $fecha_final,
            $fecha_inicial,
            $fecha_final,
            $p_motivo,
            $id_colaborador,
            $id_colaborador,
            $fecha_inicial,
            $fecha_final
        ]
    );
    return $this->response->setStatusCode(200)->setJSON(1);
}

//solicitar descanso
public function getMacroFormD(){
    $json = $this->request->getJSON();
    $fecha_inicial = $json->fecha_inicial;
    $fecha_final = $json->fecha_final;
    $id_colaborador = $json->id_colaborador;
    $d_motivo = $json->d_motivo;
    $d_especificar = $json->d_especificar;
    $esproyectos = $json->esproyectos;
    $diasdisponiblesdescanso = $json->diasdisponiblesdescanso;

    if($esproyectos < 1 ){//cuando la persona que solicita no es de proyectos

        $query = $this->db->query(
            "
            declare @fecha1 datetime = ?
            declare @fecha2 datetime = ?
            declare @findesemana int = 0
            declare @otrodia int = 1
            
            while (@fecha1 < @fecha2)
                begin
                    if (DATEPART(WEEKDAY, @fecha1) = 1)
                        begin 
                        set @findesemana = @findesemana + 1
                        end
                    else if (DATEPART(WEEKDAY, @fecha1) = 7)
                        begin
                        set @findesemana = @findesemana + 1
                        end
                    else
                        begin
                            set @otrodia = @otrodia + 1
                        end                  
                    set @fecha1 = DATEADD(DAY, 1, @fecha1)
                end
            
            INSERT INTO tbl_vacaciones_solicitudes (fecha_inicial, fecha_final, d_motivo, d_especificar, tipo_solicitud, id_colaborador, estado, status, aprobador, fecha_registro, dias_solicitados) values
            ( ?, ?, ?, ?, 1, ?, 1, 1, (select jefe_inmediato from colaboradores where id_colaborador=?), getdate(), ( @otrodia - (select count(fecha_festiva) from tbl_vacaciones_diasfestivos where jornada_completa=1 and fecha_festiva between ? and ?) ) )",
            [
                $fecha_inicial,
                $fecha_final,
                $fecha_inicial,
                $fecha_final,
                $d_motivo,
                $d_especificar,
                $id_colaborador,
                $id_colaborador,
                $fecha_inicial,
                $fecha_final
            ]
        );
        return $this->response->setStatusCode(200)->setJSON(1);

    }else{///cuando la persona que solicita si es de proyectos

        $query00 = $this->db->query(
            "declare @fecha1 datetime = ?
            declare @fecha2 datetime = ?
            declare @findesemana int = 0
            declare @otrodia int = 1
            
            while (@fecha1 < @fecha2)
                begin
                    if (DATEPART(WEEKDAY, @fecha1) = 1)
                        begin 
                        set @findesemana = @findesemana + 1
                        end
                    else if (DATEPART(WEEKDAY, @fecha1) = 7)
                        begin
                        set @findesemana = @findesemana + 1
                        end
                    else
                        begin
                            set @otrodia = @otrodia + 1
                        end                  
                    set @fecha1 = DATEADD(DAY, 1, @fecha1)
                end
            
            select ( @otrodia - (select count(fecha_festiva) from tbl_vacaciones_diasfestivos where jornada_completa=1 and fecha_festiva between ? and ?) ) as 'diasseleccionados' ",
            [
                $fecha_inicial,
                $fecha_final,
                $fecha_inicial,
                $fecha_final
            ]
        );
        $result00 = $query00->getResult();
        foreach ($result00 as $res00){
            $diassolicitados = $res00->diasseleccionados;
            if($diassolicitados<= $diasdisponiblesdescanso){
                $query = $this->db->query(
                    "INSERT INTO tbl_vacaciones_solicitudes 
                    (fecha_inicial, fecha_final, d_motivo, d_especificar
                    , tipo_solicitud, id_colaborador, estado
                    , status, aprobador, fecha_registro, dias_solicitados) 
                    values
                    ( ?, ?, ?, ?, 1, ?, 1, 1, (select jefe_inmediato from colaboradores where id_colaborador=?)
                    , getdate()
                    , ? )",
                    [
                        $fecha_inicial,
                        $fecha_final,
                        $d_motivo,
                        $d_especificar,
                        $id_colaborador,
                        $id_colaborador,
                        $diassolicitados
                    ]
                );
                return $this->response->setStatusCode(200)->setJSON(1);
            }else{
                return $this->response->setStatusCode(200)->setJSON(0);
            }
        }
    }
    
}

//solicitar vacaciones
public function getMacroFormV(){
    $json = $this->request->getJSON();
    $fecha_inicial = $json->fecha_inicial;
    $fecha_final = $json->fecha_final;
    $id_colaborador = $json->id_colaborador;
    $v_regreso_labores = $json->v_regreso_labores;
    $v_comentarios = $json->v_comentarios;
    $v_antiguedad = $json->v_antiguedad;
    $v_dias_totales = $json->v_dias_totales;
    $diasdisponiblesdeantiguedad = $json->diasdisponiblesdeantiguedad;
    $query00 = $this->db->query(
        "
        declare @fecha1 datetime = ?
        declare @fecha2 datetime = ?
        declare @findesemana int = 0
        declare @otrodia int = 1
        
        while (@fecha1 < @fecha2)
            begin
                if (DATEPART(WEEKDAY, @fecha1) = 1)
                    begin 
                    set @findesemana = @findesemana + 1
                    end
                else if (DATEPART(WEEKDAY, @fecha1) = 7)
                    begin
                    set @findesemana = @findesemana + 1
                    end
                else
                    begin
                        set @otrodia = @otrodia + 1
                    end                  
                set @fecha1 = DATEADD(DAY, 1, @fecha1)
            end
        
        select ( @otrodia - (select count(fecha_festiva) from tbl_vacaciones_diasfestivos where jornada_completa=1 and fecha_festiva between ? and ?) ) as 'diasseleccionados' ",
        [
            $fecha_inicial,
            $fecha_final,
            $fecha_inicial,
            $fecha_final
        ]
    );
    $result00 = $query00->getResult();
    foreach ($result00 as $res00){
        $diassolicitados = $res00->diasseleccionados;
        if($diassolicitados<= $diasdisponiblesdeantiguedad){

            $query = $this->db->query(
                " INSERT INTO tbl_vacaciones_solicitudes 
                (fecha_inicial, fecha_final, v_regreso_labores, v_comentarios, v_antiguedad, v_dias_totales, tipo_solicitud
                , id_colaborador, estado, status, aprobador, fecha_registro, dias_solicitados) 
                values
                ( ?, ?, ?, ?, ?, ?, 2, ?, 1, 1, (select jefe_inmediato from colaboradores where id_colaborador=?), getdate(), ? )",
                [
                    $fecha_inicial,
                    $fecha_final,
                    $v_regreso_labores,
                    $v_comentarios,
                    $v_antiguedad,
                    $v_dias_totales,
                    $id_colaborador,
                    $id_colaborador,
                    $diassolicitados
                ]
            );
            $query1 = $this->db->query(
                " select top 1 id from tbl_vacaciones_solicitudes 
                where 
                fecha_inicial = ? and 
                fecha_final = ? and 
                v_regreso_labores = ? and 
                v_comentarios = ? and 
                v_antiguedad = ? and 
                v_dias_totales = ? and 
                tipo_solicitud = 2 and 
                id_colaborador = ? and 
                estado=1 and 
                dias_solicitados = ? and
                year(fecha_registro) = year(getdate()) and 
                month(fecha_registro) = month(getdate()) and 
                day(fecha_registro) = day(getdate()) " , [
                    $fecha_inicial,
                    $fecha_final,
                    $v_regreso_labores,
                    $v_comentarios,
                    $v_antiguedad,
                    $v_dias_totales,
                    $id_colaborador,
                    $diassolicitados
                    ]
            );
            $result1 = $query1->getResult();
            foreach ($result1 as $res) {
                $id_solicitud = $res->id;
                $actividad1 = $json->actividad1 == null ? "" : $json->actividad1;
                $responsables1 = $json->responsables1 == null ? "" : $json->responsables1;
                $fechalimite1 = $json->fechalimite1 == null ? "" : $json->fechalimite1;
                $observaciones1 = $json->observaciones1 == null ? "" : $json->observaciones1;
                $query2 = $this->db->query(
                    "insert into tbl_vacaciones_planTrabajo (id_solicitud, actividad, responsable, fecha_limite, observaciones) values (?,?,?,?,?)"
                    ,[
                        $id_solicitud,
                        $actividad1,
                        $responsables1,
                        $fechalimite1,
                        $observaciones1
                    ]
                );
                $actividad2 = $json->actividad2 == null ? "" : $json->actividad2;
                $responsables2 = $json->responsables2 == null ? "" : $json->responsables2;
                $fechalimite2 = $json->fechalimite2 == null ? "" : $json->fechalimite2;
                $observaciones2 = $json->observaciones2 == null ? "" : $json->observaciones2;
                if($actividad2=="" && $responsables2=="" && $fechalimite2=="" && $observaciones2==""){
                    //nada
                }else{
                    $query3 = $this->db->query(
                        "insert into tbl_vacaciones_planTrabajo (id_solicitud, actividad, responsable, fecha_limite, observaciones) values (?,?,?,?,?)"
                        ,[
                            $id_solicitud,
                            $actividad2,
                            $responsables2,
                            $fechalimite2,
                            $observaciones2
                        ]
                    );
                }
                $actividad3 = $json->actividad3 == null ? "" : $json->actividad3;
                $responsables3 = $json->responsables3 == null ? "" : $json->responsables3;
                $fechalimite3 = $json->fechalimite3 == null ? "" : $json->fechalimite3;
                $observaciones3 = $json->observaciones3 == null ? "" : $json->observaciones3;
                if($actividad3=="" && $responsables3=="" && $fechalimite3=="" && $observaciones3==""){
                    //nada
                }else{
                    $query4 = $this->db->query(
                        "insert into tbl_vacaciones_planTrabajo (id_solicitud, actividad, responsable, fecha_limite, observaciones) values (?,?,?,?,?)"
                        ,[
                            $id_solicitud,
                            $actividad3,
                            $responsables3,
                            $fechalimite3,
                            $observaciones3
                        ]
                    );
                }
            
            }

        }else{//if $diassolicitados<= $diasdisponiblesdeantiguedad fin
            return $this->response->setStatusCode(200)->setJSON(0);
        }
        
    }//$result00 fin
    return $this->response->setStatusCode(200)->setJSON(1);

}// fin getMacroFormV


//solicitar vacaciones version 0
public function contardiasV(){
    $json = $this->request->getJSON();
    $fecha_inicial = $json->fecha_inicial;
    $fecha_final = $json->fecha_final;
    $id_colaborador = $json->id_colaborador;
    $v_regreso_labores = $json->v_regreso_labores;
    $v_comentarios = $json->v_comentarios;
    $v_antiguedad = $json->v_antiguedad;
    $v_dias_totales = $json->v_dias_totales;
    $diasdisponiblesdeantiguedad = $json->diasdisponiblesdeantiguedad;
    $query1 = $this->db->query(
        "
        declare @fecha1 datetime = ?
        declare @fecha2 datetime = ?
        declare @findesemana int = 0
        declare @otrodia int = 1
        
        while (@fecha1 < @fecha2)
            begin
                if (DATEPART(WEEKDAY, @fecha1) = 1)
                    begin 
                    set @findesemana = @findesemana + 1
                    end
                else if (DATEPART(WEEKDAY, @fecha1) = 7)
                    begin
                    set @findesemana = @findesemana + 1
                    end
                else
                    begin
                        set @otrodia = @otrodia + 1
                    end                  
                set @fecha1 = DATEADD(DAY, 1, @fecha1)
            end
        
        select ( @otrodia - (select count(fecha_festiva) from tbl_vacaciones_diasfestivos where jornada_completa=1 and fecha_festiva between ? and ?) ) )",
        [
            $fecha_inicial,
            $fecha_final,
            $fecha_inicial,
            $fecha_final
        ]
    );
    $result1 = $query1->getResult();
    foreach ($result1 as $res) {
        $id_solicitud = $res->id;
        $actividad1 = $json->actividad1 == null ? "" : $json->actividad1;
        $responsables1 = $json->responsables1 == null ? "" : $json->responsables1;
        $fechalimite1 = $json->fechalimite1 == null ? "" : $json->fechalimite1;
        $observaciones1 = $json->observaciones1 == null ? "" : $json->observaciones1;
        $query2 = $this->db->query(
            "insert into tbl_vacaciones_planTrabajo (id_solicitud, actividad, responsable, fecha_limite, observaciones) values (?,?,?,?,?)"
            ,[
                $id_solicitud,
                $actividad1,
                $responsables1,
                $fechalimite1,
                $observaciones1
            ]
        );
        $actividad2 = $json->actividad2 == null ? "" : $json->actividad2;
        $responsables2 = $json->responsables2 == null ? "" : $json->responsables2;
        $fechalimite2 = $json->fechalimite2 == null ? "" : $json->fechalimite2;
        $observaciones2 = $json->observaciones2 == null ? "" : $json->observaciones2;
        if($actividad2=="" && $responsables2=="" && $fechalimite2=="" && $observaciones2==""){
            //nada
        }else{
            $query3 = $this->db->query(
                "insert into tbl_vacaciones_planTrabajo (id_solicitud, actividad, responsable, fecha_limite, observaciones) values (?,?,?,?,?)"
                ,[
                    $id_solicitud,
                    $actividad2,
                    $responsables2,
                    $fechalimite2,
                    $observaciones2
                ]
            );
        }
        $actividad3 = $json->actividad3 == null ? "" : $json->actividad3;
        $responsables3 = $json->responsables3 == null ? "" : $json->responsables3;
        $fechalimite3 = $json->fechalimite3 == null ? "" : $json->fechalimite3;
        $observaciones3 = $json->observaciones3 == null ? "" : $json->observaciones3;
        if($actividad3=="" && $responsables3=="" && $fechalimite3=="" && $observaciones3==""){
            //nada
        }else{
            $query4 = $this->db->query(
                "insert into tbl_vacaciones_planTrabajo (id_solicitud, actividad, responsable, fecha_limite, observaciones) values (?,?,?,?,?)"
                ,[
                    $id_solicitud,
                    $actividad3,
                    $responsables3,
                    $fechalimite3,
                    $observaciones3
                ]
            );
        }
       
    }
    return $this->response->setStatusCode(200)->setJSON(1);

}
    


}
