<?php

namespace App\Controllers;

use App\Models\capitalhumano\Propuesta;
use App\Models\capitalhumano\PropuestaDetalle;
use App\Models\capitalhumano\Oferta;
use Mpdf\Mpdf;

class Areas extends BaseController{

    private $db;

    public function __construct(){
        $this->db = db_connect();
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

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Areas

        public function getAreas(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT * FROM Areas WHERE area_estado != 0 AND area LIKE '%$json->searchText%' ORDER BY area")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function updateArea(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Areas] SET [area] = ?, [descripcion] = ? WHERE [id_area] = ?", 
                [
                    $json->area,
                    $json->descripcion == "" ? NULL : $json->descripcion,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function createArea(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[Areas] ([area],[descripcion],[area_estado]) VALUES (?,?,?)", 
                [
                    $json->area,
                    $json->descripcion == "" ? NULL : $json->descripcion,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function deleteArea(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Areas] SET [area_estado] = ? WHERE [id_area] = ?",
                [
                    $json->estado,
                    $json->area
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);

        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Carreras

        public function getCarreras(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT * FROM Carreras WHERE estado != 0 AND carerra LIKE '%$json->searchText%' ORDER BY carerra")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function createCarrera(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[Carreras] ([carerra] ,[estado])
                                        VALUES (? ,?)", 
                [
                    $json->carrera,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function updateCarrera(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Carreras] SET [carerra] = ? WHERE [id_carrera] = ?",
                [
                    $json->carrera,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
            
        }

        public function deleteCarrera(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Carreras] SET [estado] = ? WHERE [id_carrera] = ?",
                [
                    0,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Departamentos

        public function getDepartamentos(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT d.*, a.area, a.id_area, a.descripcion FROM tbl_departamentos d LEFT JOIN Areas a ON d.id_area=a.id_area WHERE d.departamentos_estado != 0 AND (d.departamentos_desc LIKE '%$json->searchText%' OR d.departamentos_codigo LIKE '%$json->searchText%') ORDER BY d.departamentos_desc")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function createDepartamento(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[tbl_departamentos] ([departamentos_codigo] ,[departamentos_desc] ,[id_area] ,[departamentos_estado])
                                        VALUES (? ,? ,? ,?)", 
                [
                    $json->codigo,
                    $json->descripcion == "" ? NULL : $json->descripcion,
                    $json->area == 0 ? NULL : $json->area,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function updateDepartamento(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[tbl_departamentos] SET [departamentos_codigo] = ?, [departamentos_desc] = ?, [id_area] = ? WHERE [departamentos_id] = ?", 
                [
                    $json->codigo,
                    $json->descripcion == "" ? NULL : $json->descripcion,
                    $json->area == 0 ? NULL : $json->area,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);

        }

        public function deleteDepartamento(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[tbl_departamentos] SET [departamentos_estado] = ? WHERE [departamentos_id] = ?",
                [
                    0,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Especialidades

        public function getEspecialidades(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT * FROM tbl_especialidades WHERE estado != 0 AND especialidad LIKE '%$json->searchText%' ORDER BY especialidad")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function createEspecialidad(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[tbl_especialidades] ([especialidad] ,[estado])
                                        VALUES (? ,?)", 
                [
                    $json->especialidad,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function updateEspecialidad(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[tbl_especialidades] SET [especialidad] = ? WHERE [id_especialidad] = ?",
                [
                    $json->especialidad,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
            
        }

        public function deleteEspecialidad(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[tbl_especialidades] SET [estado] = ? WHERE [id_especialidad] = ?",
                [
                    0,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Nacionalidades

        public function getNacionalidades(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT * FROM Nacionalidades WHERE estado != 0 AND nacionalidad LIKE '%$json->searchText%' ORDER BY nacionalidad")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function createNacionalidad(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[Nacionalidades] ([nacionalidad] ,[estado])
                                        VALUES (? ,?)", 
                [
                    $json->nacionalidad,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function updateNacionalidad(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Nacionalidades] SET [nacionalidad] = ? WHERE [id_nacionalidad] = ?",
                [
                    $json->nacionalidad,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
            
        }

        public function deleteNacionalidad(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Nacionalidades] SET [estado] = ? WHERE [id_nacionalidad] = ?",
                [
                    0,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Puestos

        public function getPuestos(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT DISTINCT * FROM Puestos WHERE estado != 0 AND puesto LIKE '%$json->searchText%' ORDER BY puesto")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function createPuesto(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[Puestos] ([puesto], [descripcion_pues] ,[estado])
                                        VALUES (? ,? ,?)", 
                [
                    $json->puesto,
                    $json->descripcion == '' ? NULL : $json->descripcion,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function updatePuesto(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Puestos] SET [puesto] = ?, [descripcion_pues] = ? WHERE [id_puesto] = ?",
                [
                    $json->puesto,
                    $json->descripcion == '' ? NULL : $json->descripcion,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
            
        }

        public function deletePuesto(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Puestos] SET [estado] = ? WHERE [id_puesto] = ?",
                [
                    0,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Regiones

        public function getRegiones(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT * FROM Regiones WHERE estado_region != 0 AND (nombre_region LIKE '%$json->searchText%' OR codigo_region LIKE '%$json->searchText%') ORDER BY codigo_region")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function createRegion(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[Regiones] ([codigo_region], [nombre_region] ,[estado_region])
                                        VALUES (? ,? ,?)", 
                [
                    $json->codigo == '' ? NULL : $json->codigo,
                    $json->nombre == '' ? NULL : $json->nombre,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function updateRegion(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Regiones] SET [codigo_region] = ?, [nombre_region] = ? WHERE [id_region] = ?",
                [
                    $json->codigo == '' ? NULL : $json->codigo,
                    $json->nombre == '' ? NULL : $json->nombre,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
            
        }

        public function deleteRegion(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Regiones] SET [estado_region] = ? WHERE [id_region] = ?",
                [
                    0,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Sucursales

        public function getSucursales(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT p1.*, CASE WHEN t0.estado_region=0 THEN 'Región eliminada' ELSE t0.codigo_region END AS codigo_region FROM Sucursales p1 LEFT JOIN Regiones t0 ON p1.region=t0.id_region WHERE estado != 0 AND (sucursal LIKE '%$json->searchText%' OR avr LIKE '%$json->searchText%') ORDER BY p1.sucursal")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function createSucursal(){
            $json = $this->request->getJSON();
            $result = $this->db->query("INSERT INTO [dbo].[Sucursales] ([sucursal] ,[avr] ,[region] ,[direccion_suc] ,[cot], [estado]) 
                                        VALUES (? ,? ,? ,? ,? ,?)", 
                [
                    $json->sucursal,
                    $json->avr == '' ? NULL : $json->avr,
                    $json->region == 0 ? NULL : $json->region,
                    $json->direccion == '' ? NULL : $json->direccion,
                    1,
                    1
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        public function updateSucursal(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Sucursales] SET [sucursal] = ?, [avr] = ?, [region] = ?, [direccion_suc] = ? WHERE [id_sucursal] = ?",
                [
                    $json->sucursal,
                    $json->avr == '' ? NULL : $json->avr,
                    $json->region == 0 ? NULL : $json->region,
                    $json->direccion == '' ? NULL : $json->direccion,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
            
        }

        public function deleteSucursal(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE [dbo].[Sucursales] SET [estado] = ? WHERE [id_sucursal] = ?",
                [
                    0,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    // Modulo: Propuesta Salarial / Pagina: tablas

        // Modal siguiente paso  # Pagina: tablas
        public function crearPropuesta(){
            $json = $this->request->getJSON();
            $model = new Propuesta();
            $id = $model->insert($json, true);
            return $this->response->setStatusCode(200)->setJSON($id);
        }

        // Tabla de propuestas en proceso # Pagina: tablas
        public function getPropuestas_proceso(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT 
                                            CASE WHEN t1.id_colaborador>0 THEN (SELECT nombres+' '+apellido_p+' '+apellido_m FROM colaboradores WHERE id_colaborador=t1.id_colaborador) ELSE t1.nombres+' '+t1.apellido_p+' '+t1.apellido_m END AS nombre_comp
                                            , t1.id
                                            , t1.estado
                                            , ISNULL(CONVERT(VARCHAR(10),FORMAT(t1.fecha, 'dd/MM/yyyy'),21),'') 'fecha'
                                            , (SELECT nombres+' '+apellido_p+' '+apellido_m FROM colaboradores WHERE id_colaborador=t1.creador) AS creadorn
                                    
                                            , t8.tipoEmpleado_id
                                            , t8.tipoEmpleado_desc
                                            , CASE WHEN t2.estado=0 THEN ' ' ELSE t2.sucursal END AS sucursal_n
                                            , CASE WHEN t10.departamentos_estado=0 THEN ' ' ELSE t10.departamentos_codigo+' - '+t10.departamentos_desc END AS departamentos_desc
                                            , CASE WHEN t3.area_estado=0 THEN ' ' ELSE t3.area END AS area_n
                                            , case when t12.estado=0 THEN ' ' ELSE t12.puesto END AS puesto_n
                                            , CASE WHEN t9.estado_region=0 THEN ' ' ELSE t9.nombre_region END AS codigo_region
                                        
                                        FROM tbl_colab_propsalarial t1 
                                            LEFT JOIN Sucursales t2 ON t1.sucursal=t2.id_sucursal
                                            LEFT JOIN Regiones t9 ON t2.region=t9.id_region
                                            LEFT JOIN tbl_tipoEmpleado t8 ON t1.tipo_empleado=t8.tipoEmpleado_id
                                            LEFT JOIN tbl_departamentos t10 ON t1.depto=t10.departamentos_id
                                            LEFT JOIN Areas t3 ON t10.id_area=t3.id_area
                                            LEFT JOIN Puestos t12 ON t1.puesto=t12.id_puesto
                                                
                                        WHERE 
                                            t1.estado in (1,2) AND
                                            (
                                                t1.id LIKE ('%$json->searchText%') OR
                                                t8.tipoEmpleado_desc LIKE ('%$json->searchText%') OR
                                                t1.fecha LIKE ('%$json->searchText%') OR
                                                t1.nombres LIKE ('%$json->searchText%') OR
                                                t1.apellido_p LIKE ('%$json->searchText%') OR
                                                t1.apellido_m LIKE ('%$json->searchText%') OR
                                                t2.sucursal LIKE ('%$json->searchText%') OR
                                                t10.departamentos_desc LIKE ('%$json->searchText%') OR
                                                t10.departamentos_codigo LIKE ('%$json->searchText%')
                                            )
                                        ORDER BY id DESC")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Tabla de propuestas terminadas # Pagina: tablas
        public function getPropuestas_terminadas(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT 
                                            CASE WHEN t1.id_colaborador>0 THEN (SELECT nombres+' '+apellido_p+' '+apellido_m FROM colaboradores WHERE id_colaborador=t1.id_colaborador) ELSE t1.nombres+' '+t1.apellido_p+' '+t1.apellido_m END AS nombre_comp
                                            , t1.id
                                            , t1.estado
                                            , (SELECT nombres+' '+apellido_p+' '+apellido_m FROM colaboradores WHERE id_colaborador=t1.creador) AS creadorn
                                            , CASE WHEN t1.estado=2 THEN 'Sin registrar en lista maestra' ELSE 'Registrado en lista maestra' END AS estado_n
                                            , isnull(convert(VARCHAR(10),FORMAT(t1.fecha, 'dd/MM/yyyy'),21),'') 'fecha'
                                            , t8.tipoEmpleado_id
                                            , t8.tipoEmpleado_desc
                                            , CASE WHEN t2.estado=0 THEN ' ' ELSE t2.sucursal END AS sucursal_n
                                            , CASE WHEN t10.departamentos_estado=0 THEN ' ' ELSE t10.departamentos_codigo+' - '+t10.departamentos_desc END AS departamentos_desc
                                            , CASE WHEN t3.area_estado=0 THEN ' ' ELSE t3.area END AS area_n
                                            , CASE WHEN t12.estado=0 THEN ' ' ELSE t12.puesto END AS puesto_n
                                            , CASE WHEN t9.estado_region=0 THEN ' ' ELSE t9.nombre_region END AS codigo_region
                                        
                                        FROM tbl_colab_propsalarial t1 
                                            LEFT JOIN Sucursales t2 ON t1.sucursal=t2.id_sucursal
                                            LEFT JOIN Regiones t9 ON t2.region=t9.id_region
                                            LEFT JOIN tbl_tipoEmpleado t8 ON t1.tipo_empleado=t8.tipoEmpleado_id
                                            LEFT JOIN tbl_departamentos t10 ON t1.depto=t10.departamentos_id
                                            LEFT JOIN Areas t3 ON t10.id_area=t3.id_area
                                            LEFT JOIN Puestos t12 ON t1.puesto=t12.id_puesto
                                                
                                        WHERE t1.estado IN (3) AND
                                            (
                                                t1.id LIKE ('%$json->searchText%') OR
                                                t8.tipoEmpleado_desc LIKE ('%$json->searchText%') OR
                                                t1.fecha LIKE ('%$json->searchText%') OR
                                                t1.nombres LIKE ('%$json->searchText%') OR
                                                t1.apellido_p LIKE ('%$json->searchText%') OR
                                                t1.apellido_m LIKE ('%$json->searchText%') OR
                                                t2.sucursal LIKE ('%$json->searchText%') OR
                                                t10.departamentos_desc LIKE ('%$json->searchText%') OR
                                                t10.departamentos_codigo LIKE ('%$json->searchText%')
                                            )
                                        ORDER BY id DESC"
                                        )->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Modal siguiente paso (ion-select) # Pagina: tablas
        public function getColaboradores_propuesta(){
            $result = $this->db->query("SELECT id_colaborador, CONCAT(nombres,' ',apellido_p,' ',apellido_m,' ', CASE WHEN estado=1 THEN '(Activo)' ELSE '(Inactivo)' END) AS nombres FROM Colaboradores ORDER BY nombres, apellido_p, apellido_m")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Modal siguiente paso (ion-select) # Pagina: tablas
        public function getTipoEmpleado_propuesta(){
            $result = $this->db->query("SELECT tipoEmpleado_id, tipoEmpleado_desc FROM tbl_tipoEmpleado WHERE tipoEmpleado_id > 0;")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Modal siguiente paso (ion-select) # Pagina: tablas
        public function getPuestos_propuesta(){
            $result = $this->db->query("SELECT * FROM Puestos WHERE estado!=0 ORDER BY puesto")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Modal siguiente paso (ion-select) # Pagina: tablas
        public function getSucursales_propuesta(){
            $result = $this->db->query("SELECT s.*, r.nombre_region FROM Sucursales s left join regiones r on r.id_region=s.region WHERE s.estado!=0 order by s.sucursal")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Modal siguiente paso (ion-select) # Pagina: tablas
        public function getDepartamentos_propuesta(){
            $result = $this->db->query("SELECT d.departamentos_id, CONCAT(d.departamentos_codigo,' - ',d.departamentos_desc) AS departamento, a.area, a.descripcion FROM tbl_departamentos d LEFT JOIN Areas a ON d.id_area=a.id_area WHERE d.departamentos_estado != 0 ORDER BY d.departamentos_codigo")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Propuesta Salarial / Pagina: Información General / Nueva Propuesta / Propuesta Definitiva

        // Obtiene la información de la propuesta por ID # Pagina: Información General
        public function getPropuesta_porID(){
            $json = $this->request->getJSON();
            $result = $this->db->query(" SELECT 
                                            CASE WHEN t1.id_colaborador>0 THEN (SELECT nombres+' '+apellido_p+' '+apellido_m FROM colaboradores WHERE id_colaborador=t1.id_colaborador) ELSE t1.nombres+' '+t1.apellido_p+' '+t1.apellido_m END AS nombre_comp
                                            
                                            , t1.* 
                                            , ISNULL(t1.tmp_sueldo,0) 'tmp_sueldo'
                                            , ISNULL(t1.contrato,'') 'contrato'
                                            , ISNULL(t1.pago,'') 'pago'
                                            , ISNULL(CONVERT(VARCHAR(10),t1.fecha,21),'') 'fecha'
                                            , ISNULL(CONVERT(VARCHAR(10),t1.fecha_inicio,21),'') 'fecha_inicio'
                                            
                                            , t8.tipoEmpleado_id
                                            , t8.tipoEmpleado_desc
                                            , CASE WHEN t2.estado=0 THEN ' ' ELSE t2.sucursal END AS sucursal_n
                                            , CASE WHEN t10.departamentos_estado=0 THEN ' ' ELSE t10.departamentos_codigo+' - '+t10.departamentos_desc END AS departamentos_desc
                                            , CASE WHEN t3.area_estado=0 THEN ' ' ELSE t3.area END AS area_n
                                            , CASE WHEN t12.estado=0 THEN ' ' ELSE t12.puesto END AS puesto_n
                                            , CASE WHEN t9.estado_region=0 THEN ' ' ELSE t9.nombre_region END AS codigo_region
                                            , t2.region
                                            
                                            , c.id_sucursal
                                            , c.tipo_empleado 'tipo_empleado2'
                                            , c.id_departamentos
                                            , c.id_puesto
                                            , t88.tipoEmpleado_desc 'tipoEmpleado_desc2'
                                            , CASE WHEN t22.estado=0 THEN ' ' ELSE t22.sucursal END AS sucursal_n2
                                            , CASE WHEN t1010.departamentos_estado=0 THEN ' ' ELSE t1010.departamentos_codigo+' - '+t1010.departamentos_desc END AS departamentos_desc2
                                            , CASE WHEN t1212.estado=0 THEN ' ' ELSE t1212.puesto END AS puesto_n2
                                            , c.id_region
                                            , ISNULL(c.tabulador_id,0) tabulador_id
                                            , ISNULL(c.tab_nivel,0) tab_nivel
                                            , ISNULL(c.porcentaje,0) porcentaje
                                            
                                            , ISNULL(tba.hoja,'') hoja
                                            , ISNULL(tba.aumento,0) aumento
                                            , ISNULL(tb.hoja,'') hoja
                                            , ISNULL(tb.posicion_apoyo,'') posicion_apoyo
                                            , ISNULL(tb.categoria,'') categoria
                                            , ISNULL(CONVERT(VARCHAR(19),tb.fecha,21),'') AS fechatb
                                            , CASE WHEN c.tab_nivel=1 THEN tb.nivel_1 WHEN c.tab_nivel=2 THEN tb.nivel_2 WHEN c.tab_nivel=3 THEN tb.nivel_3 WHEN c.tab_nivel=4 THEN tb.nivel_4 ELSE 0 END AS 'base'
                                            
                                        FROM tbl_colab_propsalarial t1 
                                            LEFT JOIN Sucursales t2 ON t1.sucursal=t2.id_sucursal
                                            LEFT JOIN Regiones t9 ON t2.region=t9.id_region
                                            LEFT JOIN tbl_tipoEmpleado t8 ON t1.tipo_empleado=t8.tipoEmpleado_id
                                            LEFT JOIN tbl_departamentos t10 ON t1.depto=t10.departamentos_id
                                            LEFT JOIN Areas t3 ON t10.id_area=t3.id_area
                                            LEFT JOIN Puestos t12 ON t1.puesto=t12.id_puesto
                                                    
                                            LEFT JOIN colaboradores c ON t1.id_colaborador=c.id_colaborador AND t1.id_colaborador>0
                                            LEFT JOIN Sucursales t22 ON c.id_sucursal=t22.id_sucursal
                                            LEFT JOIN tbl_tipoEmpleado t88 ON c.tipo_empleado=t88.tipoEmpleado_id
                                            LEFT JOIN tbl_departamentos t1010 ON c.id_departamentos=t1010.departamentos_id
                                            LEFT JOIN Puestos t1212 ON c.id_puesto=t1212.id_puesto
                                            LEFT JOIN tbl_colab_tabulador tb ON tb.id=c.tabulador_id
                                            LEFT JOIN tbl_colab_tabulador_aumento tba ON tba.id_sucursal=c.id_sucursal
                                                
                                        WHERE t1.id = $json->id")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Guarda la información de la propuesta # Pagina: Información General
        public function updateGuardarInformacion(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE tbl_colab_propsalarial 
                                        SET 
                                            contrato = ?, 
                                            infoextra = ?, 
                                            fecha_inicio = ?, 
                                            tmp_sueldo = ? 
                                        WHERE id = ? ",
                [
                    $json->tipoEmpleado == 1 ? ($json->h_tipocontrato == null ? '' : $json->h_tipocontrato) : '',
                    $json->h_infoextra == null ? date('Y-m-d') : $json->h_infoextra,
                    $json->h_inicio == null ? date('Y-m-d') : $json->h_inicio,
                    $json->temp_sueldobruto == null ? 0 : $json->temp_sueldobruto,
                    $json->id
                ]);
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        // Elimina toda la propuesta "estado = 0" # Pagina: Información General
        public function updateEliminarSolicitud(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE tbl_colab_propsalarial SET estado = 0 WHERE id = $json->id");
            return $this->response->setStatusCode(200)->setJSON($result ? 1 : 0);
        }

        public function NuevaPropuesta(){
            $json = $this->request->getJSON();
            $model = new PropuestaDetalle();
            $id = $model->insert($json, true);
            return $this->response->setStatusCode(200)->setJSON($id);
        }

        public function getTabId_niveles(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT id FROM tbl_colab_tabulador 
                                        WHERE 
                                            hoja='$json->hoja' AND 
                                            posicion_apoyo='$json->posicion' AND 
                                            categoria='$json->categoria' AND
                                            fecha='$json->fecha'")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function getHojaEspecialidades(){
            $result = $this->db->query("SELECT DISTINCT (hoja) AS hoja, CONVERT(VARCHAR(19), fecha, 21) AS fecha FROM tbl_colab_tabulador ORDER BY hoja")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function getHojaPosicionApoyo(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT DISTINCT 
                                            posicion_apoyo, 
                                            CONVERT(VARCHAR(19), fecha, 21) AS fecha
                                        FROM tbl_colab_tabulador
                                        WHERE hoja = '$json->hoja'
                                        ORDER BY posicion_apoyo")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function getHojaSucursal_Aumento(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT 
                                            ISNULL(aumento,0) as aumento 
                                        FROM tbl_colab_tabulador_aumento 
                                        WHERE 
                                            id_sucursal=$json->sucursal AND 
                                            hoja='$json->hoja';")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function getHojaCategorias(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT 
                                            categoria AS cat,
                                            nivel_1 n1,
                                            CONVERT(VARCHAR, CONVERT(VARCHAR, CAST(nivel_1 AS MONEY), 1)) AS n1_n,
                                            nivel_2 n2,
                                            CONVERT(VARCHAR, CONVERT(VARCHAR, CAST(nivel_2 AS MONEY), 1)) AS n2_n,
                                            nivel_3 n3,
                                            CONVERT(VARCHAR, CONVERT(VARCHAR, CAST(nivel_3 AS MONEY), 1)) AS n3_n,
                                            nivel_4 n4,
                                            CONVERT(VARCHAR, CONVERT(VARCHAR, CAST(nivel_4 AS MONEY), 1)) AS n4_n
                                        FROM tbl_colab_tabulador 
                                        WHERE 
                                            hoja='$json->hoja' AND 
                                            posicion_apoyo='$json->posicion' AND 
                                            fecha='$json->fecha' 
                                        ORDER BY n1")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);

        }

        public function getPropuestaDefinitiva(){
            $json = $this->request->getJSON();
            $result = $this->db->query("SELECT
                                            t.id AS idprop, 
                                            ISNULL(t.crecimiento,0) AS crecimiento, 
                                            t.status AS statust, 
                                            t.tab_id, 
                                            t.nivel, 
                                            t.porcentaje, 
                                            CONVERT(VARCHAR(10),tb.fecha,21) AS fecha, 
                                            tb.hoja, 
                                            tb.posicion_apoyo, 
                                            tb.categoria, 
                                            CASE 
                                                WHEN t.nivel=1 THEN tb.nivel_1 
                                                WHEN t.nivel=2 THEN tb.nivel_2 
                                                WHEN t.nivel=3 THEN tb.nivel_3 
                                                WHEN t.nivel=4 THEN tb.nivel_4 
                                                ELSE 0 
                                            END AS base, 
                                            ISNULL(tba.aumento,0) AS aumento
                                        FROM tbl_colab_propsalarial_dtll t
                                            LEFT JOIN tbl_colab_tabulador tb ON tb.id=t.tab_id
                                            LEFT JOIN tbl_colab_tabulador_aumento tba ON tba.hoja=tb.hoja AND tba.id_sucursal=$json->sucursal
                                        WHERE t.cabeza=$json->propuestaId")->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        public function generatePropuestaSalarialDefinitivaPDF(){
            $json = $this->request->getJSON();
            
            if($json->tipo || $json->tipo != ''){
                switch($json->tipo){
                    case 1006: // START case 1006:
                        //$tipo = 1;            // Intranet: NsU
                        //$id_cabecera = 0;     // Intranet: NsU

                        $propuestaID = $json->pdf;
                        $tipoEmpleado = $json->te;

                        $html = '
                            <html>
                                <head>
                                    <style>
                                        body{
                                            font-family: "sofiapro", sans-serif !important;
                                            font-size: 10pt;
                                            color: #58595b;
                                        }
                                        .margin-lados{
                                            margin-left: 55px;
                                            margin-right: 55px;
                                        }
                                    </style>
                                </head>
                                <body>
                        ';

                        if($tipoEmpleado == 1){ // Temporal
                            $query = $this->db->query(" SELECT
                                                            d.id_area, 
                                                            s.region, 
                                                            t.depto, 
                                                            t.sucursal, 
                                                            t.puesto, 
                                                            t.contrato, 
                                                            t.pago, 
                                                            t.infoextra, 
                                                            ISNULL(t.tmp_sueldo,0) tmp_sueldo, 
                                                            CASE 
                                                                WHEN t.id_colaborador>0 THEN (SELECT nombres+' '+apellido_p+' '+apellido_m FROM colaboradores WHERE id_colaborador=t.id_colaborador) 
                                                                ELSE t.nombres+' '+t.apellido_p+' '+t.apellido_m 
                                                            END AS nombre_comp, 
                                                            CASE 
                                                                WHEN t12.estado=0 THEN ' ' 
                                                                ELSE t12.puesto 
                                                            END AS puesto_n,
                                                            CONVERT(VARCHAR(10),t.fecha_inicio,21) fecha_inicio,
                                                            (SELECT nombres+' '+apellido_p+' '+apellido_m FROM Colaboradores WHERE id_colaborador=t.creador) AS creador_n,
                                                            t.creador,
                                                            CASE 
                                                                WHEN t.id_colaborador>0 THEN (SELECT estado FROM colaboradores WHERE id_colaborador=t.id_colaborador) 
                                                                ELSE 0 
                                                            END AS estado_col,
                                                            CASE 
                                                                WHEN s.estado=0 THEN ' ' 
                                                                ELSE s.sucursal 
                                                            END AS sucursal_n,
                                                            CASE 
                                                                WHEN d.departamentos_estado=0 THEN ' ' 
                                                                ELSE d.departamentos_codigo+' - '+d.departamentos_desc 
                                                            END AS departamentos_desc,
                                                            CASE 
                                                                WHEN t3.area_estado=0 THEN ' ' 
                                                                ELSE t3.area 
                                                            END AS area_n,
                                                            CASE 
                                                                WHEN t9.estado_region=0 THEN ' ' 
                                                                ELSE t9.nombre_region 
                                                            END AS codigo_region,
                                                                s.region
                                                        
                                                        FROM tbl_colab_propsalarial t
                                                                LEFT JOIN tbl_departamentos d ON t.depto=d.departamentos_id
                                                                LEFT JOIN Sucursales s ON t.sucursal=s.id_sucursal
                                                                LEFT JOIN Regiones t9 ON s.region=t9.id_region
                                                                LEFT JOIN Puestos t12 ON t.puesto=t12.id_puesto
                                                                LEFT JOIN Areas t3 ON d.id_area=t3.id_area
                                                        WHERE t.id=$propuestaID");
                            $result = $query->getResult();
                            $result = $result[0];
                            $fecha = date('d', strtotime($result->fecha_inicio))." de ".$this->mes(date('m', strtotime($result->fecha_inicio)))." ".date('Y', strtotime($result->fecha_inicio));
                            $sueldoTotal = $result->tmp_sueldo;
                            $fecha_doc = date('d', strtotime(date('Y-m-d')))." de ".$this->mes(date('m', strtotime(date('Y-m-d'))))." ".date('Y', strtotime(date('Y-m-d')));

                            // EMPLEADO TEMPORAL
                            $html.='<table style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black;">
                                        <tr>
                                            <td colspan="2" style="text-align: right;">Hermosillo, Sonora '.$fecha_doc.'</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><b><u>'.$result->nombre_comp.'</u></b><br>
                                            Por medio del presente le doy a conocer la propuesta de contratación esperando esta cumpla con sus expectativas:</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  Puesto:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">'.$result->puesto_n.'</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  Área:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">'.$result->area_n.'</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  Fecha de Inicio:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">'.$fecha.'</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  Tipo de Contrato:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">'.$result->contrato.'</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  Forma de pago:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">Semanal con una semana de desfase, tarjeta de nómina</td>
                                        </tr>
                                    </table>
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black; width: 100%; border-spacing: 0; border-collapse: collapse;">
                                        <tr>
                                            <td colspan="2" style="padding-bottom: 10px;">Esquema de Compensación semanal:</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="border: 2px solid black; padding: 5px 10px 5px 10px;"><b>Propuesta de Compensación</b></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;">Ingreso por nomina</td>
                                            <td style="border-right: 2px solid black; vertial-align: top;"></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;">Sueldo bruto semanal</td>
                                            <td style="border-right: 2px solid black; vertial-align: top;">$'.number_format($sueldoTotal).'</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;">Prestaciones de Ley:</td>
                                            <td style="border-right: 2px solid black; vertial-align: top;"></td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top; border-left: 2px solid black;">•    15 días de aguinaldo (proporcional)</td>
                                            <td style="border-right: 2px solid black; "></td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top; border-left: 2px solid black;">•    6 días de vacaciones al primer año de servicio</td>
                                            <td style="border-right: 2px solid black; "></td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; padding-bottom: 30px; vertical-align: top; border-left: 2px solid black; border-bottom: 2px solid black;">•  25% de prima de vacacional</td>
                                            <td style="border-right: 2px solid black; border-bottom: 2px solid black;"></td>
                                        </tr>
                                    </table>
                                    <br>
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black;">
                                        <tr>
                                            <td>'.nl2br($result->infoextra).'</td>
                                        </tr>
                                    </table>
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black;">
                                        <tr>
                                            <td>Herramientas de trabajo:<br>
                                                <ul>
                                                    <li>Dotación de:</li>
                                                        <ul>
                                                            <li>Herramientas de trabajo</li>
                                                        </ul>
                                                </ul>
                                            </td>
                                        </tr>
                                    </table>
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black;">
                                        <tr>
                                            <td colspan="2" style="">De aceptar dicha propuesta, favor de firmar este documento y enviar a Capital Humano, para iniciar proceso de contratación.</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-left: 80px;">
                                            Quedo a sus órdenes.    <br>
                                            Saludos cordiales,<br>
                                            <br>
                                            '.$result->creador_n.'<br>
                                            Capital Humano<br>
                                            ECN
                                            </td>
                                        </tr>
                                    </table>';
                        
                        }else{ // NO TEMPORAL, FIJO, ETC
                            $query = $this->db->query(" SELECT 
                                                            d.id_area, 
                                                            s.region, 
                                                            td.*, 
                                                            t.depto, 
                                                            t.sucursal, 
                                                            t.puesto, 
                                                            t.contrato, 
                                                            t.pago, 
                                                            t.infoextra, 
                                                            td.porcentaje, 
                                                            tb.posicion_apoyo,
                                                            CASE 
                                                                WHEN t.id_colaborador>0 THEN (SELECT nombres+' '+apellido_p+' '+apellido_m FROM colaboradores WHERE id_colaborador=t.id_colaborador) 
                                                                ELSE t.nombres+' '+t.apellido_p+' '+t.apellido_m 
                                                            END AS nombre_comp,
                                                            CASE 
                                                                WHEN t12.estado=0 THEN ' ' 
                                                                ELSE t12.puesto 
                                                            END AS puesto_n,
                                                            CONVERT(VARCHAR(10),t.fecha_inicio,21) fecha_inicio,
                                                            (SELECT nombres+' '+apellido_p+' '+apellido_m FROM Colaboradores WHERE id_colaborador=t.creador) AS creador_n,
                                                            (SELECT p.puesto FROM Colaboradores c LEFT JOIN puestos p ON c.id_puesto=p.id_puesto WHERE c.id_colaborador=t.creador) AS creador_puesto,
                                                            t.creador,
                                                            CASE 
                                                                WHEN t.id_colaborador>0 THEN (SELECT estado FROM colaboradores WHERE id_colaborador=t.id_colaborador) 
                                                                ELSE 0 
                                                            END AS estado_col,
                                                            CASE 
                                                                WHEN td.nivel=1 THEN tb.nivel_1 
                                                                WHEN td.nivel=2 THEN tb.nivel_2 
                                                                WHEN td.nivel=3 THEN tb.nivel_3 
                                                                WHEN td.nivel=4 THEN tb.nivel_4 
                                                                ELSE 0 
                                                            END AS base,
                                                            ISNULL(tba.aumento,0) AS aumento,
                                                            CASE 
                                                                WHEN s.estado=0 THEN ' ' 
                                                                ELSE s.sucursal 
                                                            END AS sucursal_n,
                                                            CASE 
                                                                WHEN d.departamentos_estado=0 THEN ' ' 
                                                                ELSE d.departamentos_codigo+' - '+d.departamentos_desc 
                                                            END AS departamentos_desc,
                                                            CASE 
                                                                WHEN t3.area_estado=0 THEN ' ' 
                                                                ELSE t3.area 
                                                            END AS area_n,
                                                            CASE 
                                                                WHEN t9.estado_region=0 THEN ' ' 
                                                                ELSE t9.nombre_region 
                                                            END AS codigo_region,
                                                            s.region
                                                        
                                                        FROM tbl_colab_propsalarial_dtll td 
                                                            LEFT JOIN tbl_colab_propsalarial t ON td.cabeza=t.id
                                                            LEFT JOIN tbl_departamentos d ON t.depto=d.departamentos_id
                                                            LEFT JOIN Sucursales s ON t.sucursal=s.id_sucursal
                                                            LEFT JOIN Regiones t9 ON s.region=t9.id_region
                                                            LEFT JOIN Puestos t12 ON t.puesto=t12.id_puesto
                                                            LEFT JOIN Areas t3 ON d.id_area=t3.id_area
                                                        
                                                            LEFT JOIN tbl_colab_tabulador tb ON tb.id=td.tab_id
                                                            LEFT JOIN tbl_colab_tabulador_aumento tba ON tba.hoja=tb.hoja AND tba.id_sucursal=t.sucursal
                                                        
                                                        WHERE 
                                                            t.id=$propuestaID AND 
                                                            td.status=2");
                            $result = $query->getResult();
                            $result = $result[0];
                            $fecha = date('d', strtotime($result->fecha_inicio))." de ".$this->mes(date('m', strtotime($result->fecha_inicio)))." ".date('Y', strtotime($result->fecha_inicio));
                            $porcentaje = $result->porcentaje;
                            $base = $result->base;
                            $fecha_doc = date('d', strtotime(date('Y-m-d')))." de ".$this->mes(date('m', strtotime(date('Y-m-d'))))." ".date('Y', strtotime(date('Y-m-d')));

                            $aumento=(float)($result->aumento + 1);
                            $puntoGuia = $base * $aumento;
                            $ingresoBruto = $puntoGuia * ($porcentaje/100);
                            if($tipoEmpleado == 1 || $result->posicion_apoyo == 'BECARIO'){
                                $puntualidad = 0;
                                $asistencia = 0;
                                $despensa = 0;
                                $fondoAhorro = 0;
                                $sueldoTotal = ( $tipoEmpleado == 1 ? $ingresoBruto/4 : $ingresoBruto + $puntualidad + $asistencia + $despensa + $fondoAhorro );
                            }else{
                                $puntualidad = $ingresoBruto * 0.1055;
                                $asistencia = $ingresoBruto * 0.1055;
                                $despensa = 1154.64;
                                $fondoAhorro = $ingresoBruto * 0.04;
                                $sueldoTotal = $ingresoBruto + $puntualidad + $asistencia + $despensa + $fondoAhorro;
                            }

                            $html.='<table style="margin-left: 50px; margin-right: 50px; font-size: 10pt; color: black;">
                                        <tr>
                                            <td colspan="2" style="text-align: right;">Hermosillo, Sonora '.$fecha_doc.'</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><b>'.$result->nombre_comp.'</b><br>
                                            Por medio del presente le doy a conocer el nuevo esquema salarial con base a las responsabilidades del puesto a cubrir, esperando esta cumpla con sus expectativas:</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  Puesto:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">'.$result->puesto_n.'</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  Región:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">'.$result->codigo_region.'</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-left: 60px; vertical-align: top;">•  A partir de:</td>
                                            <td style="padding-right: 60px; vertial-align: top;">'.$fecha.'</td>
                                        </tr>
                                    </table>
                                    
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 10pt; color: black; width: 100%; border-spacing: 0; border-collapse: collapse;">
                                        <tr>
                                            <td colspan="4" style="border: 2px solid black; padding: 5px 10px 5px 10px;"><b>Propuesta de Compensación</b></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;"><b>Ingresos por Nómina</b></td>
                                            <td style="vertial-align: top;"></td>
                                            <td style="vertial-align: top;"></td>
                                            <td style="border-right: 2px solid black; vertial-align: top;"></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;"></td>
                                            <td style="vertial-align: top;">Sueldo Bruto</td>
                                            <td style="vertial-align: top; padding-left: 10px;">'.number_format($ingresoBruto,2).'</td>
                                            <td style="border-right: 2px solid black; vertial-align: top; padding-left: 10px;">Sueldo Mensualizado sin Variación por Mes</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;"></td>
                                            <td style="vertial-align: top;">Premio de Puntualidad</td>
                                            <td style="vertial-align: top; padding-left: 10px;">'.number_format($puntualidad,2).'</td>
                                            <td style="border-right: 2px solid black; vertial-align: top; padding-left: 10px;">10.55% del Sueldo Bruto con Tope Legal</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-bottom: 20px; padding-left: 10px;"></td>
                                            <td style="vertial-align: top; padding-bottom: 20px;">Premio de Asistencia</td>
                                            <td style="vertial-align: top; padding-bottom: 20px; padding-left: 10px;">'.number_format($asistencia,2).'</td>
                                            <td style="border-right: 2px solid black; vertial-align: top; padding-bottom: 20px; padding-left: 10px;">10.55% del Sueldo Bruto con Tope Legal</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;"></td>
                                            <td style="vertial-align: top;">Ingreso bruto </td>
                                            <td style="vertial-align: top; padding-left: 10px;">'.number_format(($ingresoBruto + $puntualidad + $asistencia),2).'</td>
                                            <td style="border-right: 2px solid black; vertial-align: top;"></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px; padding-top: 20px; padding-bottom: 20px;"><b>Más<br>Prestaciones adicionales</b></td>
                                            <td style="vertial-align: top;"></td>
                                            <td style="vertial-align: top;"></td>
                                            <td style="border-right: 2px solid black; vertial-align: top;"></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;"></td>
                                            <td style="vertial-align: top;">Aportación Fondo de Ahorro</td>
                                            <td style="vertial-align: top; padding-left: 10px;">'.number_format($fondoAhorro,2).'</td>
                                            <td style="border-right: 2px solid black; vertial-align: top; padding-left: 10px;">4% del Sueldo Bruto</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;"></td>
                                            <td style="vertial-align: top;">(retención vía nómina)</td>
                                            <td style="vertial-align: top; padding-left: 10px;"></td>
                                            <td style="border-right: 2px solid black; vertial-align: top; padding-left: 10px;"></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px;"></td>
                                            <td style="vertial-align: top;">Vales Despensa</td>
                                            <td style="vertial-align: top; padding-left: 10px;">'.number_format($despensa,2).'</td>
                                            <td style="border-right: 2px solid black; vertial-align: top;"></td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; border-left: 2px solid black; padding-left: 10px; padding-top: 30px; border-bottom: 2px solid black;"></td>
                                            <td style="vertial-align: top; padding-top: 20px; padding-bottom: 20px; border-bottom: 2px solid black;"><b>Ingreso bruto mensual</b></td>
                                            <td style="vertial-align: top; padding-top: 20px; padding-bottom: 20px; border-bottom: 2px solid black; padding-left: 10px;">'.number_format($sueldoTotal,2).'</td>
                                            <td style="border-right: 2px solid black; vertial-align: top; padding-top: 30px; padding-bottom: 30px; border-bottom: 2px solid black;"></td>
                                        </tr>
                                    </table>
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 10pt; color: black;">
                                        <tr>
                                            <td style=""><b>'.nl2br($result->infoextra).'</b></td>
                                        </tr>
                                    </table>
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 10pt; color: black;">
                                        <tr>
                                            <td style=""><b>La aportacion de fondo de ahorro total sera $'.number_format(($fondoAhorro * 2),2).' mensual, depositada en el mes de Diciembre</b></td>
                                        </tr>
                                    </table>
                                    <br>
                                    <div style="margin-left: 50px; margin-right: 50px; font-size: 8.5pt; color: black;">
                                        Dentro de la ley de ISR se estipula que está obligado al pago de dicho impuesto:<br>
                                        Toda persona sea física o moral que resida en este país, sin importar cuál sea su principal fuente de ingresos económicos o de dónde vengan, es decir que bien puede ser de la venta de o renta de algún producto o de la prestación de cierto servicio sin importar cuál sea, de cualquier forma, todos los contribuyentes participan del pago de este impuesto.<br>
                                        El total del ingreso neto esta sujeto a cambios por prestaciones o descuetos personales, favor de verificar con su recibo de nomina. <br>
                                        LEY DEL SEGURO SOCIAL : Capitulo II de las bases de cotizacion y de las cuotas<br>
                                            <span stye="padding-left: 30px;">Artículo 38. El patrón al efectuar el pago de salarios a sus trabajadores, deberá retener las cuotas que a éstos les corresponde cubrir. </span><br>
                                        <br>
                                        El patrón tendrá el carácter de retenedor de las cuotas que descuente a sus trabajadores y deberá determinar y enterar al Instituto las cuotas obrero patronales, en los términos establecidos por esta Ley y sus reglamentos.
                                    </div>';

                            if($result->estado_col == 0){
                                $html.='
                                    <pagebreak>
                                    <br>
                                    <table style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black; width: 100%; border-spacing: 0; border-collapse: collapse;">
                                        <tr>
                                            <td colspan="3" style=" padding: 10px 5px 10px 5px;">Las prestaciones y beneficios que ofrece la empresa son:</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" style="border: 2px solid black; padding: 10px 5px 10px 5px; font-weight: bold;"><big>PRESTACIONES Y BENEFICIOS A PERSONAL ECN</big></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="border: 2px solid black; padding: 5px 5px 5px 5px;">PRESTACIÓN</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">PERCEPCIÓN</td>
                                        </tr>
                                        <tr>
                                            <td rowspan="2" style="border: 2px solid black; padding: 5px 5px 5px 5px;">SUPERIOR A LA LEY</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">VACACIONES</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">10 DÍAS AL PRIMER AÑO DE SERVICIO, AUMENTO DE ACUERDO A POLÍTICA</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">PRIMA VACACIONAL ANUAL</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">50%</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">BÁSICAS</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">AGUINALDO ANUAL</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">15 DÍAS</td>
                                        </tr>
                                        <tr>
                                            <td rowspan="4" style="border: 2px solid black; padding: 5px 5px 5px 5px;">ECONÓMICAS</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">FONDO DE AHORRO</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">4%</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">VALES DE DESPENSA</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">$'.number_format($despensa,2).'</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">PRÉSTAMO PERSONAL</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">UN MES SALARIO TOPE $15,000.00 DE ACUERDO A POLÍTICA</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">ANTICIPO DE NÓMINA</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">DE ACUERDO A POLÍTICA</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">SEGURIDAD SOCIAL</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">IMSS<br>INFONAVIT</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">SI<br>SI</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">INHERENTES A LA POSICIÓN DE APOYO</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">UNIFORMES</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">DE ACUERDO A POLÍTICA</td>
                                        </tr>
                                        <tr>
                                            <td rowspan="2" style="border: 2px solid black; padding: 5px 5px 5px 5px;">ADICIONALES</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">SEGURO DE GASTOS MÉDICOS MAYORES</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">DE ACUERDO A POLÍTICA</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">ACCESO A PÓLIZA DE SGMM FAMILIAR</td>
                                            <td style="border: 2px solid black; padding: 5px 5px 5px 5px;">SI</td>
                                        </tr>
                                    </table>
                                    <pagebreak>
                                    <br>
                                    <br>
                                    <div style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black;">
                                        Programas de desarrollo interno:<br>
                                        <br>
                                        <ul>
                                        <li>Plan de vida y carrera </li>
                                        <li>Programas de ESR</li>
                                        <li>HIPI (High Performance Intrapreneur)</li>
                                        <li>Vivo nuestros valores</li>
                                        <li>Universidad empresarial </li>
                                        <li>Capacitaciones internas/externas</li>
                                        <li>Oportunidad de Crecimiento vertical u horizontal</li>
                                        </ul>
                                        <br>
                                        Herramientas de trabajo: <br>
                                        <br>
                                        Cabe mencionar que toda herramienta de trabajo entregada al colaborador ECN, es responsabilidad exclusiva del mismo, por lo que deberá administrarla de acuerdo a las políticas de la empresa.<br>
                                        <br>
                                    </div>
                                    <div style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black; text-align: right">
                                        Nota: Toda herramienta de trabajo es gestionada por el Líder Inmediato.<br>
                                    </div>
                                    <div style="margin-left: 50px; margin-right: 50px; font-size: 12pt; color: black;">
                                        <br>
                                        &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; De aceptar dicha propuesta, favor de firmar este documento en ambas hojas y enviar a Capital Humano, para iniciar proceso de contratación.<br>
                                        <br>
                                        <br>
                                        Saludos cordiales,<br>
                                        <br>
                                        '.$result->creador_n.'<br>
                                        '.$result->creador_puesto.'   <br>                           
                                        ECN<br>
                                        <br>
                                    </div>';
                            }
                        }
                        
                        break;  // END case 1006:
                    default: // START default:
                        $html = '';
                        break; // END default:
                } // END SWITCH $json->tipo
            // END IF if($json->tipo || $json->tipo != '')
            }else{ // START ELSE 
                $html = '';
            } // END ELSE

            $html.="</body></html>";

            $pdf = new Mpdf([
                'debug' => true,
                'mode' => 'utf-8'
            ]);
            //$pdf->debug = true;
            $pdf->WriteHTML($html);
            //$pdf->Output('files/propuestasalarial/propuesta-salarial-' . (($propuestaID || $propuestaID != '') ? $propuestaID : '0') . '_' . (($result->nombre_comp || $result->nombre_comp != '') ? $result->nombre_comp : 'nombre') . '.pdf', 'F');
            //$result2 = array(["filename" =>'propuesta-salarial-' . (($propuestaID || $propuestaID != '') ? $propuestaID : '0') . '_' . (($result->nombre_comp || $result->nombre_comp != '') ? $result->nombre_comp : 'nombre') . '.pdf']);
            
            return $this->response->setStatusCode(200)->setContentType('application/pdf')->sendBody($pdf->Output());
            //return $this->response->setStatusCode(200)->setJSON($result2);
        }

        // Eliminar propuesta # Pagina: Propuesta Definitiva
        public function eliminarPropuesta(){
            $json = $this->request->getJSON();
            $result = $this->db->query("UPDATE tbl_colab_propsalarial_dtll SET status = 0 WHERE id = $json->id");
            return $this->response->setStatusCode(200)->setJSON(1);
        }

        // Agregar propuesta como definitiva # Pagina: Propuesta Definitiva
        public function agregarPropuestaDefinitiva(){
            $json = $this->request->getJSON();
            $resultEstado = $this->db->query("UPDATE tbl_colab_propsalarial SET estado=2 WHERE id = $json->id");
            if($resultEstado){
                $resultCrecimiento = $this->db->query("UPDATE tbl_colab_propsalarial_dtll SET status=2, crecimiento = $json->crecimiento WHERE id = $json->idprop");
                if($resultCrecimiento){
                    $result = $this->db->query("UPDATE tbl_colab_propsalarial_dtll SET status=0 WHERE cabeza=$json->id AND status=1");
                    if($result)
                        return $this->response->setStatusCode(200)->setJSON(3);
                    else
                        return $this->response->setStatusCode(200)->setJSON(2);
                }else
                    return $this->response->setStatusCode(200)->setJSON(1);
            }else
                return $this->response->setStatusCode(200)->setJSON(0);
        }

        // Actualizar lista maestra de colaboradores # Pagina: Propuesta Definitiva
        public function updateActualizarColaborador(){
            $hoy = date('Y-m-d');

            $json = $this->request->getJSON();
            $return = array('colaborador' => 0, 'solution' => 0, 'ingreso' => 0, 'te' => 0, 'region' => 0, 'depto' => 0, 
                            'sucursal' => 0, 'area' => 0, 'puesto' => 0, 'colaboradorUpdate' => 0, 'propuestaEstado' => 0);
            
            if($json->colaborador > 0){
                $return['colaborador'] = 1;

                $resultColabLog = $this->db->query("SELECT ingreso_bruto_men, tipo_vendedor, id_region, id_departamentos, id_sucursal, id_area , id_puesto FROM Colaboradores WHERE id_colaborador = $json->colaborador")->getResult();
                $resultColabLog = $resultColabLog[0];
                if($json->te == 1){ // EMPLEADO TEMPORAL
                    $result = $this->db->query("SELECT d.id_area, s.region, t.depto, t.sucursal, t.puesto , t.tmp_sueldo 
                                                FROM tbl_colab_propsalarial t 
                                                LEFT JOIN tbl_departamentos d ON t.depto=d.departamentos_id
                                                LEFT JOIN Sucursales s ON t.sucursal=s.id_sucursal
                                                WHERE t.id = $json->id")->getResult();
                    $result = $result[0];

                    $area = $result->id_area;
                    $region = $result->region;
                    $departamento = $result->depto;
                    $sucursal = $result->sucursal;
                    $puesto = $result->puesto;
                    $sueldo = $result->tmp_sueldo;
                    if($resultColabLog->ingreso_bruto_men != $sueldo){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.ingreso_bruto_men', '$resultColabLog->ingreso_bruto_men', '$sueldo' )";
                        //$resultIngreso = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['ingreso'] = 1;
                        else
                            $return['ingreso'] = 2;

                    }
                    if($resultColabLog->tipo_vendedor != $json->te){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.tipo_vendedor', '$resultColabLog->tipo_vendedor', '$json->te' )";
                        //$resultTE = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['te'] = 1;
                        else
                            $return['te'] = 2;
                    }
                    if($resultColabLog->id_region != $region){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_region', '$resultColabLog->id_region', '$region' )";
                        //$resultRegion = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['region'] = 1;
                        else
                            $return['region'] = 2;
                    }
                    if($resultColabLog->id_departamentos != $departamento){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_departamentos', '$resultColabLog->id_departamentos', '$departamento' )";
                        //$resultDepartamento = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['depto'] = 1;
                        else
                            $return['depto'] = 2;
                    }
                    if($resultColabLog->id_sucursal != $sucursal){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_sucursal', '$resultColabLog->id_sucursal', '$sucursal') ";
                        //$resultSucursal = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['sucursal'] = 1;
                        else
                            $return['sucursal'] = 2;
                    }
                    if($resultColabLog->id_area != $area){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_area', '$resultColabLog->id_area', '$area') ";
                        //$resultArea = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['area'] = 1;
                        else
                            $return['area'] = 2;
                    }
                    if($resultColabLog->id_puesto != $puesto){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_puesto', '$resultColabLog->id_puesto', '$puesto') ";
                        //$resultPuesto = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['puesto'] = 1;
                        else
                            $return['puesto'] = 2;
                    }

                    $resultColaborador = $this->db->query(" UPDATE Colaboradores 
                                                            SET 
                                                                modif_salarial='$json->inicio', 
                                                                ingreso_bruto_men=$sueldo, 
                                                                tipo_vendedor=$json->te, 
                                                                id_region=$region, 
                                                                id_departamentos=$departamento, 
                                                                id_sucursal=$sucursal, 
                                                                id_area=$area, 
                                                                id_puesto=$puesto  
                                                            WHERE id_colaborador=$json->colaborador");
                    if($resultColaborador){
                        $return['colaboradorUpdate'] = 1;
                        $result = $this->db->query("UPDATE tbl_colab_propsalarial SET estado=3 WHERE id=$json->id");
                        if($result)
                            $return['propuestaEstado'] = 1;
                        else
                            $return['propuestaEstado'] = 2;
                    }else{
                        $return['colaboradorUpdate'] = 2;
                        $return['propuestaEstado'] = 2;
                    }

                    if( $return['ingreso'] != 2 && $return['te'] != 2 && $return['region'] != 2 && $return['depto'] != 2 && 
                        $return['sucursal'] != 2 && $return['area'] != 2 && $return['puesto'] != 2 && $return['colaboradorUpdate'] != 2 && 
                        $return['propuestaEstado'] != 2)
                        $return['solution'] = 1;
                    else
                        $return['solution'] = 2;

                }else{ // CUALQUIER EMPLEADO EXCEPTO "TEMPORAL"
                    $return['colaborador'] = 2;
                    
                    $result = $this->db->query("SELECT d.id_area, s.region, td.*, t.depto, t.sucursal, t.puesto 
                                                FROM tbl_colab_propsalarial_dtll td 
                                                LEFT JOIN tbl_colab_propsalarial t ON td.cabeza=t.id
                                                LEFT JOIN tbl_departamentos d ON t.depto=d.departamentos_id
                                                LEFT JOIN Sucursales s ON t.sucursal=s.id_sucursal
                                                where 
                                                    td.cabeza=$json->id AND 
                                                    td.status=2")->getResult();
                    $result = $result[0];

                    $tabId = $result->tab_id;
                    $nivel = $result->nivel;
                    $porcentaje = $result->porcentaje;
                    $area = $result->id_area;
                    $region = $result->region;
                    $departamento = $result->depto;
                    $sucursal = $result->sucursal;
                    $puesto = $result->puesto;
                    if($resultColabLog->ingreso_bruto_men != $json->ingresoBruto){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.ingreso_bruto_men', '$resultColabLog->ingreso_bruto_men', '$json->ingresoBruto' )";
                        //$resultIngreso = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['ingreso'] = 1;
                        else
                            $return['ingreso'] = 2;

                    }
                    if($resultColabLog->tipo_vendedor != $json->te){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.tipo_vendedor', '$resultColabLog->tipo_vendedor', '$json->te' )";
                        //$resultTE = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['te'] = 1;
                        else
                            $return['te'] = 2;
                    }
                    if($resultColabLog->id_region != $region){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_region', '$resultColabLog->id_region', '$region' )";
                        //$resultRegion = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['region'] = 1;
                        else
                            $return['region'] = 2;
                    }
                    if($resultColabLog->id_departamentos != $departamento){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_departamentos', '$resultColabLog->id_departamentos', '$departamento') ";
                        //$resultDepartamento = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['depto'] = 1;
                        else
                            $return['depto'] = 2;
                    }
                    if($resultColabLog->id_sucursal != $sucursal){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_sucursal', '$resultColabLog->id_sucursal', '$sucursal') ";
                        //$resultSucursal = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['sucursal'] = 1;
                        else
                            $return['sucursal'] = 2;
                    }
                    if($resultColabLog->id_area != $area){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_area', '$resultColabLog->id_area', '$area') ";
                        //$resultArea = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['area'] = 1;
                        else
                            $return['area'] = 2;
                    }
                    if($resultColabLog->id_puesto != $puesto){
                        $sql = "INSERT INTO tbl_colaboradores_log ( fecha, idcolaborador, tipocambio, campomodificado, valor_viejo, valor_nuevo ) 
                                VALUES ( '$hoy', $json->colaborador, 6, 'Colaboradores.id_puesto', '$resultColabLog->id_puesto', '$puesto') ";
                        //$resultPuesto = $this->db->query($sql);
                        if($this->db->query($sql) === true)
                            $return['puesto'] = 1;
                        else
                            $return['puesto'] = 2;
                    }

                    $resultColaborador = $this->db->query(" UPDATE Colaboradores 
                                                            SET 
                                                                tabulador_id=$tabId, 
                                                                tab_nivel=$nivel, 
                                                                porcentaje=$porcentaje, 
                                                                modif_salarial='$json->inicio', 
                                                                ingreso_bruto_men=$json->ingresoBruto, 
                                                                tipo_vendedor=$json->te, 
                                                                id_region=$region, 
                                                                id_departamentos=$departamento, 
                                                                id_sucursal=$sucursal, 
                                                                id_area=$area, 
                                                                id_puesto=$puesto  
                                                            WHERE id_colaborador=$json->colaborador");
                    if($resultColaborador){
                        $return['colaboradorUpdate'] = 1;
                        $result = $this->db->query("UPDATE tbl_colab_propsalarial SET estado=3 WHERE id=$json->id");
                        if($result)
                            $return['propuestaEstado'] = 1;
                        else
                            $return['propuestaEstado'] = 2;
                    }else{
                        $return['colaboradorUpdate'] = 2;
                        $return['propuestaEstado'] = 2;
                    }

                    if( $return['ingreso'] != 2 && $return['te'] != 2 && $return['region'] != 2 && $return['depto'] != 2 && 
                        $return['sucursal'] != 2 && $return['area'] != 2 && $return['puesto'] != 2 && $return['colaboradorUpdate'] != 2 && 
                        $return['propuestaEstado'] != 2)
                        $return['solution'] = 1;
                    else
                        $return['solution'] = 2;

                }
            }else{
                // Colaborador nuevo, solo actualizar tabla de propuesta
                $result = $this->db->query("UPDATE tbl_colab_propsalarial SET estado=3 WHERE id=$json->id");
                if($result)
                    $return['solution'] = 1;
                else
                    $return['solution'] = 0;
            }

            return $this->response->setStatusCode(200)->setJSON($return);
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modulo: Ofertas laborales / Pagina: posfinsel / Ver curriculum

        // Se obtiene el listado de ofertas
        public function getOfertas(){
            $json = $this->request->getJSON();
            $area = ($json->area && $json->area != 0) ? "O.id_area=$json->area" : "";
            $pais = ($json->pais && $json->pais != 0) ? "O.id_pais=$json->pais" : "";
            $where = ($area != "" || $pais != "") ? "WHERE " : "";
            $and = ( ( $pais != "" && $area != "" ) ? " AND " : "");
            $query = "  SELECT 
                            O.id_oferta,
                            ISNULL(titulo, '') AS titulo,
                            CASE
                                WHEN subtitulo = 'null' THEN '' 
                                ELSE ISNULL(subtitulo, '') 
                            END AS subtitulo,
                            ISNULL(CONVERT(VARCHAR, fecha_creacion, 21), '') AS fecha_creacion, 
                            ISNULL(P.descripcion, '') AS pais,
                            ISNULL(E.descripcion, '') AS estado_mex,
                            ISNULL(M.nom_loc, '') AS ciudad, 
                            COUNT(pos.id_oferta) AS postulados,
                            ISNULL(Col.nombres, '') AS nombres,
                            ISNULL(Col.apellido_p, '') AS apellido_p,
                            ISNULL(Col.apellido_m, '') AS apellido_m,
                            O.estado,
                            oferta_urgente,
                            ISNULL(A.descripcion, '') AS area,

                            CASE 
                                WHEN ISNULL(M.nom_loc, '') != '' THEN CONCAT(M.nom_loc, ', ', E.descripcion, ', ')
                                ELSE ''
                            END string_CiuEst,
                            CASE
                                WHEN oferta_urgente = 1 THEN 'Oferta Urgente'
                                ELSE ''
                            END urgente_n,
                            CASE 
                                WHEN O.estado = 1 THEN 'Activa'
                                ELSE 'Inactiva'
                            END estado_n,
                            CONCAT(ISNULL(titulo, ''), CASE WHEN subtitulo = 'null' THEN '' WHEN ISNULL(subtitulo, '') != '' THEN CONCAT(' - ', subtitulo) ELSE '' END) titulo_comp
                        FROM Portal_Atraccion_Ofertas O
                        LEFT OUTER JOIN Portal_Atraccion_Paises P ON P.id_pais= O.id_pais
                        LEFT OUTER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent= O.id_estado
                        LEFT OUTER JOIN Portal_Atraccion_Municipios_Mex M ON M.cve_ent= O.id_estado and M.cve_mun= O.id_ciudad
                        LEFT OUTER JOIN Portal_Atraccion_Postulado pos ON pos.id_oferta= O.id_oferta and pos.status=1
                        LEFT OUTER JOIN Colaboradores Col ON Col.id_colaborador= O.id_colaborador
                        LEFT OUTER JOIN Portal_Atraccion_Areas A ON A.id_areas= O.id_area
                        $where
                            $area $and
                            $pais
                        GROUP BY 
                            O.id_oferta,
                            titulo,
                            subtitulo,
                            fecha_creacion,
                            P.descripcion,
                            E.descripcion,
                            M.nom_loc,
                            Col.nombres,
                            Col.apellido_p,
                            Col.apellido_m,
                            O.estado,
                            oferta_urgente,
                            A.descripcion
                        ORDER BY fecha_creacion DESC";
            $result = $this->db->query($query)->getResult();

            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Obtiene el select de las Areas
        public function Ofertas_getSelectAreas_Ofertas(){
            $json = $this->request->getJSON();

            $queryAreas = "SELECT 
                                t1.id_areas,
                                t1.descripcion,
                                (SELECT COUNT(*) FROM Portal_Atraccion_Ofertas t2 WHERE t2.id_area=t1.id_areas ".( ( $json->pais && $json->pais != 0 ) ? " AND t2.id_pais=$json->pais " : "")." ) AS total
                            FROM Portal_Atraccion_Areas t1
                            WHERE 
                                t1.estado = 1";

            $resultAreas = $this->db->query($queryAreas)->getResult();

            return $this->response->setStatusCode(200)->setJSON(array($resultAreas));

        }

        // Obtiene el select de los Paises
        public function Ofertas_getSelectPaises_Ofertas(){
            $json = $this->request->getJSON();

            $queryPaises = "SELECT 
                                t1.id_pais,
                                t1.descripcion,
                                (SELECT COUNT(*) FROM Portal_Atraccion_Ofertas t2 WHERE t2.id_pais=t1.id_pais ".( ( $json->area && $json->area != 0 ) ? " AND t2.id_area=$json->area " : "")." ) AS total
                            FROM Portal_Atraccion_Paises t1
                            WHERE 
                                t1.estado = 1";
            
            $resultPaises = $this->db->query($queryPaises)->getResult();
                    
            return $this->response->setStatusCode(200)->setJSON(array($resultPaises));
        }

        // Se obtienen los detalles del curriculum del usuario
        // SUBMODULOS: Ofertas | Usuarios
        public function Ofertas_verCurriculum(){
            $json = $this->request->getJSON();

            if($json->segment == 'O' || $json->segment == 'U')
                $query1 = " SELECT DISTINCT
                                ISNULL(U.correo, '') AS correo,
                                ISNULL(U.nombres, '') AS nombres,
                                ISNULL(U.apellido_p, '') AS apellido_p,
                                ISNULL(U.apellido_m, '') AS apellido_m,
                                ISNULL(U.fecha_nacimiento, '') AS fecha_nacimiento,
                                CASE 
                                    WHEN U.genero = 1 THEN 'Masculino'
                                    ELSE 'Femenino'
                                END AS genero,
                                CASE 
                                    WHEN U.estado_civil = 1 THEN 'Soltero(a)'
                                    WHEN U.estado_civil = 2 THEN 'Casado(a)'
                                    WHEN U.estado_civil = 3 THEN 'Viudo(a)'
                                    WHEN U.estado_civil = 4 THEN 'Divorciado(a)'
                                    ELSE ''
                                END AS estado_civil,
                                ISNULL(U.linkedin, '') AS linkedin,
                                ISNULL(U.skype, '') AS skype,
                                ISNULL(P.descripcion, '') AS pais,
                                ISNULL(E.descripcion, '') AS estado_mex,
                                ISNULL(M.nom_loc, '') AS ciudad,
                                ISNULL(U.cp, '') AS cp,
                                ISNULL(U.direccion, '') AS direccion,
                                ISNULL(N.nacionalidad, '') AS nacionalidad,
                                ISNULL(U.licencia, '') AS licencia,
                                CASE
                                    WHEN U.vehiculo_propio = 1 THEN 'Si'
                                    ELSE 'No'
                                END AS vehiculo_propio,
                                CASE
                                    WHEN U.discapacidad = 1 THEN 'Si'
                                    ELSE 'No'
                                END AS discapacidad,
                                ISNULL(U.con_hab, '') AS con_hab,
                                CASE 
                                    WHEN U.sit_actual = 1 THEN 'No tengo empleo'
                                    WHEN U.sit_actual = 2 THEN 'Buscando trabajo activamente'
                                    WHEN U.sit_actual = 3 THEN 'Estoy trabajando actualmente'
                                    WHEN U.sit_actual = 4 THEN 'No busco trabajo, pero estoy dispuesto a escuchar ofertas'
                                    WHEN U.sit_actual = 5 THEN 'No tengo interés en un trabajo nuevo'
                                    ELSE ''
                                END AS sit_actual,
                                ISNULL(A.descripcion, '') AS area,
                                ISNULL(FORMAT(salario_m, 'C'), '') as salario,
                                CASE 
                                    WHEN U.dis_viajar = 1 THEN 'Si'
                                    ELSE 'No'
                                END AS dis_viajar,
                                CASE 
                                    WHEN U.dis_camb_res = 1 THEN 'Si'
                                    ELSE 'No'
                                END AS dis_camb_res,
                                ISNULL(U.titulo_curriculum, '') AS titulo_curriculum,
                                ISNULL(U.sobremi, '') AS sobremi,
                                FORMAT(ISNULL(U.fecha_registro, ''), 'HH:mm:s d/MM/yyyy') AS fecha_registro,
                                FORMAT(ISNULL(U.fecha_edicion, ''), 'HH:mm:s d/MM/yyyy') AS fecha_edicion,
                                FORMAT(ISNULL(U.fecha_ultima, ''), 'HH:mm:s d/MM/yyyy') AS fecha_ultima,
                                CASE 
                                    WHEN U.estado = 1 THEN 'Activo'
                                    ELSE 'Inactivo'
                                END AS estado_oferta,
                                ISNULL(U.pdf, '') AS pdf
                            FROM Portal_Atraccion_Usuario U
                            LEFT OUTER JOIN Portal_Atraccion_Paises P ON P.id_pais= U.id_pais
                            LEFT OUTER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent= U.id_estado
                            LEFT OUTER JOIN Portal_Atraccion_Municipios_Mex M ON M.cve_ent= U.id_estado AND M.cve_mun= U.id_ciudad
                            LEFT OUTER JOIN Nacionalidades N ON N.id_nacionalidad= U.id_nacionalidad
                            LEFT OUTER JOIN Portal_Atraccion_Areas A ON A.id_areas= U.id_area
                            WHERE
                                id_usuario = $json->usuario;";
            else
                $query1 = "";


            if($json->segment == 'O' || $json->segment == 'U')
                $query2 = " SELECT 
                                id_usuario,
                                centro_educativo,
                                E.descripcion AS estudios,
                                CASE 
                                    WHEN ISNULL(estado_curso, 0) = 1 THEN 'Culminado'
                                    WHEN ISNULL(estado_curso, 0) = 2 THEN 'Cursando'
                                    WHEN ISNULL(estado_curso, 0) = 3 THEN 'Abandonado/Aplazado'
                                    ELSE 'Abandonado/Aplazado'
                                END AS estado_curso,
                                desde,
                                hasta
                            FROM Portal_Atraccion_Formacion F
                            INNER JOIN Portal_Atraccion_Estudios E ON E.id_estudios=F.id_estudios
                            WHERE id_usuario=$json->usuario";
            else
                $query2 = "";
            
            if($json->segment == 'O' || $json->segment == 'U')
                $query3 = " SELECT 
                                E.id_usuario,
                                E.empresa,
                                E.cargo,
                                G.descripcion AS giro,
                                desde,
                                hasta,
                                E.funciones_logros
                            FROM Portal_Atraccion_Experiencias_Profesionales E
                            INNER JOIN Portal_Atraccion_Giros_Empresariales G ON G.id_giro_empresarial=E.id_giro_empresarial
                            WHERE id_usuario=$json->usuario";
            else
                $query3 = "";
            

            $query4 = '';
            
            if($json->segment == 'O' && $json->tipoCurriculum == 1)
                $query4 = " UPDATE Portal_Atraccion_Postulado
                            SET visto=1
                            WHERE id_usuario='$json->usuario' and id_oferta=$json->oferta";
            if($json->segment == 'O' && $json->tipoCurriculum == 2)
                $query4 = " UPDATE Portal_Atraccion_Finalista
                            SET visto=1
                            WHERE id_usuario='$json->usuario' and id_oferta=$json->oferta";

            $result1 = $this->db->query($query1)->getResult();
            $result2 = $query2 == '' ? '' : $this->db->query($query2)->getResult();
            $result3 = $query3 == '' ? '' : $this->db->query($query3)->getResult();
            $result4 = $query4 != '' ? ( $this->db->query($query4) ? 1 : 0 ) : 0;

            return $this->response->setStatusCode(200)->setJSON(array($result1, $result2, $result3, $result4));
        }

        // NUEVA OFERTA

        public function Ofertas_getSelects(){
            $resultStatus = array("areas" => 0, "estado" => 0, "pais" => 0, "jornada" => 0,
                            "contrato" => 0, "experiencia" => 0, "estudios" => 0,
                            "idioma" => 0, "nivel" => 0);
            $result = array("areas" => null, "estado" => null, "pais" => null, "jornada" => null,
                            "contrato" => null, "experiencia" => null, "estudios" => null,
                            "idioma" => null, "nivel" => null);
            ////////////////////////////////////////////////////////////// 

            $queryAreas = "SELECT * from Portal_Atraccion_Areas where estado=1 order by descripcion";
            $resultAreas = $this->db->query($queryAreas)->getResult();
            if($resultAreas && $resultAreas != null){
                $resultStatus['areas'] = 1;
                $result['areas'] = $resultAreas;
            }else
                $resultStatus['areas'] = 2;
                
            $queryEstado = "SELECT * from Portal_Atraccion_Estados_Mex where estado=1 order by descripcion";
            $resultEstado = $this->db->query($queryEstado)->getResult();
            if($resultEstado && $resultEstado != null){
                $resultStatus['estado'] = 1;
                $result['estado'] = $resultEstado;
            }else
                $resultStatus['estado'] = 2;
            
            $queryPais = "SELECT * from Portal_Atraccion_Paises where estado=1 order by descripcion";
            $resultPais = $this->db->query($queryPais)->getResult();
            if($resultPais && $resultPais != null){
                $resultStatus['pais'] = 1;
                $result['pais'] = $resultPais;
            }else
                $resultStatus['pais'] = 2;

            $queryJornada = "SELECT * from Portal_Atraccion_Tipos_Jornada where estado=1";
            $resultJornada = $this->db->query($queryJornada)->getResult();
            if($resultJornada && $resultJornada != null){
                $resultStatus['jornada'] = 1;
                $result['jornada'] = $resultJornada;
            }else
                $resultStatus['jornada'] = 2;

            $queryContrato = "SELECT * from Portal_Atraccion_Tipo_Contrato where estado=1";
            $resultContrato = $this->db->query($queryContrato)->getResult();
            if($resultContrato && $resultContrato != null){
                $resultStatus['contrato'] = 1;
                $result['contrato'] = $resultContrato;
            }else
                $resultStatus['contrato'] = 2;

            $queryExperiencia = "SELECT * from Portal_Atraccion_Experiencia where estado=1";
            $resultExperiencia = $this->db->query($queryExperiencia)->getResult();
            if($resultExperiencia && $resultExperiencia != null){
                $resultStatus['experiencia'] = 1;
                $result['experiencia'] = $resultExperiencia;
            }else
                $resultStatus['experiencia'] = 2;

            $queryEstudios = "SELECT * from Portal_Atraccion_Estudios where estado=1";
            $resultEstudios = $this->db->query($queryEstudios)->getResult();
            if($resultEstudios && $resultEstudios != null){
                $resultStatus['estudios'] = 1;
                $result['estudios'] = $resultEstudios;
            }else
                $resultStatus['estudios'] = 2;

            $queryIdioma = "SELECT * from Portal_Atraccion_Idiomas where estado=1 order by descripcion";
            $resultIdioma = $this->db->query($queryIdioma)->getResult();
            if($resultIdioma && $resultIdioma != null){
                $resultStatus['idioma'] = 1;
                $result['idioma'] = $resultIdioma;
            }else
                $resultStatus['idioma'] = 2;

            $queryNivel = "SELECT * from Portal_Atraccion_Niveles where estado=1";
            $resultNivel = $this->db->query($queryNivel)->getResult();
            if($resultNivel && $resultNivel != null){
                $resultStatus['nivel'] = 1;
                $result['nivel'] = $resultNivel;
            }else
                $resultStatus['nivel'] = 2;

            return $this->response->setStatusCode(200)->setJSON(array($resultStatus, $result));
        }

        // Obtiene el select de los municipios
        public function Ofertas_getSelectCiudad(){
            $json = $this->request->getJSON();

            $queryCiudad = "SELECT cve_ent, cve_mun, nom_loc from Portal_Atraccion_Municipios_Mex where cve_ent=$json->estado order by nom_loc";
            $result = $this->db->query($queryCiudad)->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // Publica/Alta de la oferta
        public function Ofertas_publicarOferta(){
            $json = $this->request->getJSON();
            $model = new Oferta();
            $id = $model->insert($json, true);

            $idiomas = $json->idioma;
            $niveles = $json->nivel;

            for($i = 0; $i < count($idiomas); $i++){
                $query = "INSERT INTO Portal_Atraccion_oferta_idiomas(id_oferta,id_idiomas,id_niveles,estado) VALUES($id,$idiomas[$i],$niveles[$i],1)";
                $this->db->query($query);
            }

            return $this->response->setStatusCode(200)->setJSON($id);
        }

        // DETALLE OFERTA

        public function Ofertas_getDetalleOferta(){
            $json = $this->request->getJSON();
            $query1 = "  SELECT 
                            ISNULL(titulo, '') AS titulo,
                            CASE
                                WHEN subtitulo = 'null' THEN '' 
                                ELSE ISNULL(subtitulo, '') 
                            END AS subtitulo,
                            ISNULL(A.descripcion, '') AS area,
                            ISNULL(A.id_areas, 0) AS id_area, 
                            ISNULL(P.descripcion, '') AS pais,
                            ISNULL(P.id_pais, 0) AS id_pais,
                            ISNULL(E.descripcion, '') AS lugar_estado,
                            ISNULL(E.cve_ent, 0) AS id_estado,
                            ISNULL(M.nom_loc, '') AS ciudad,
                            ISNULL(M.cve_ent, 0) AS id_ciudad,
                            ISNULL(J.descripcion, '') AS jornada,
                            ISNULL(J.id_jornada_laboral, '') AS id_jornada_laboral,
                            ISNULL(C.descripcion, '') AS contrato,
                            ISNULL(C.id_tipo_contrato, '') AS id_tipo_contrato,
                            salario,
                            ISNULL(CONVERT(VARCHAR, fecha_contratacion, 21), '') AS fecha_contratacion,
                            ISNULL(vacantes, '') AS vacantes, 
                            ISNULL(oferta_urgente, '') AS oferta_urgente, 
                            ISNULL(O.descripcion, '') AS descripcion, 
                            ISNULL(EX.descripcion, '') AS experiencia,
                            ISNULL(EX.id_experiencia, '') AS id_experiencia,
                            ISNULL(TE.descripcion, '')  AS estudios, 
                            ISNULL(TE.id_estudios, '')  AS id_estudios, 
                            ISNULL(O.licencia, '') AS licencia, 
                            CASE WHEN ISNULL(dis_viajar, 0) = 1 THEN 'SI' ELSE 'NO' END AS viajar, 
                            ISNULL(dis_viajar, 0) AS dis_viajar, 
                            CASE WHEN ISNULL(dis_camb_res, 0) = 1 THEN 'SI' ELSE 'NO' END AS res, 
                            ISNULL(dis_camb_res, 0) AS dis_camb_res, 
                            CASE WHEN ISNULL(discapacidad, 0) = 1 THEN 'SI' ELSE 'NO' END AS discapacidad_n,
                            ISNULL(discapacidad, 0) AS discapacidad, 
                            ISNULL(Col.nombres, '') AS nombres, 
                            ISNULL(Col.apellido_p, '') AS apellido_p, 
                            ISNULL(Col.apellido_m, '') AS apellido_m,
                            ISNULL(Col.id_colaborador, 0) AS id_colaborador,
                            ISNULL(fecha_creacion, '') AS fecha_creacion, 
                            ISNULL(ultima_fecha, '') AS ultima_fecha, 
                            ISNULL(O.estado, '') AS estado,  
                        
                            ISNULL(FORMAT(fecha_contratacion, 'd/MM/yyyy'), '') AS fecha_contratacion_n,
                            ISNULL(CONCAT(FORMAT(ultima_fecha, 'hh:mm:ss tt'),' ',FORMAT(ultima_fecha, 'd/MM/yyyy')), '') AS ultima_fecha_n,
                            ISNULL(CONCAT(FORMAT(fecha_creacion, 'hh:mm:ss tt'),' ',FORMAT(fecha_creacion, 'd/MM/yyyy')), '') AS fecha_creacion_n,
                            CASE 
                                WHEN ISNULL(M.nom_loc, '') != '' THEN CONCAT(M.nom_loc, ', ', E.descripcion, ', ')
                                ELSE ''
                            END string_CiuEst,
                            CASE
                                WHEN oferta_urgente = 1 THEN 'Oferta Urgente'
                                ELSE ''
                            END urgente_n,
                            CASE 
                                WHEN O.estado = 1 THEN 'Activa'
                                ELSE 'Inactiva'
                            END estado_n,
                            CONCAT(ISNULL(titulo, ''), CASE WHEN subtitulo = 'null' THEN '' WHEN ISNULL(subtitulo, '') != '' THEN CONCAT(' - ', subtitulo) ELSE '' END) titulo_comp
                        
                        FROM Portal_Atraccion_Ofertas O 
                        LEFT OUTER JOIN Portal_Atraccion_Areas A ON A.id_areas= O.id_area
                        LEFT OUTER JOIN Portal_Atraccion_Paises P ON P.id_pais= O.id_pais
                        LEFT OUTER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent= O.id_estado
                        LEFT OUTER JOIN Portal_Atraccion_Municipios_Mex M ON M.cve_ent= O.id_estado and M.cve_mun= O.id_ciudad
                        LEFT OUTER JOIN Portal_Atraccion_Tipos_Jornada J ON J.id_jornada_laboral= O.id_jornada_laboral
                        LEFT OUTER JOIN Portal_Atraccion_Tipo_Contrato C ON C.id_tipo_contrato= O.id_tipo_contrato
                        LEFT OUTER JOIN Portal_Atraccion_Experiencia EX ON EX.id_experiencia= O.id_experiencia
                        LEFT OUTER JOIN Portal_Atraccion_Estudios TE ON TE.id_estudios= O.id_estudios
                        LEFT OUTER JOIN Colaboradores Col ON Col.id_colaborador= O.id_colaborador
                        WHERE id_oferta = $json->id";
            $result = $this->db->query($query1)->getResult();


            $query2 = "  SELECT
                            ISNULL(t1.postulados, 0) AS postulados,
                            ISNULL(t2.finalistas, 0) AS finalistas,
                            ISNULL(t3.seleccionados, 0) AS seleccionados
                        FROM
                            (SELECT 1 a, COUNT(t1.id_usuario) AS postulados FROM Portal_Atraccion_Postulado t1 WHERE id_oferta=$json->id AND status=1) t1 
                        LEFT JOIN (SELECT 1 a, COUNT(id_usuario) AS finalistas FROM Portal_Atraccion_Finalista WHERE id_oferta=$json->id) t2 ON t1.a = t2.a
                        LEFT JOIN (SELECT 1 a, COUNT(id_usuario) AS seleccionados FROM Portal_Atraccion_Seleccionado WHERE id_oferta=$json->id) t3 ON t1.a = t3.a";
            $result2 = $this->db->query($query2)->getResult();


            $query3 = " SELECT 
                            I.id_idiomas AS idioma,
                            I.descripcion AS idioma_n,
                            N.id_niveles AS nivel,
                            N.descripcion AS nivel_n
                        FROM Portal_Atraccion_oferta_idiomas O
                        INNER JOIN Portal_Atraccion_Idiomas I ON I.id_idiomas=O.id_idiomas
                        INNER JOIN Portal_Atraccion_Niveles N ON N.id_niveles=O.id_niveles
                        WHERE id_oferta = $json->id AND I.estado=1 
                        ORDER BY I.descripcion";
            $result3 = $this->db->query($query3)->getResult();

            return $this->response->setStatusCode(200)->setJSON(array($result[0], $result2[0], $result3));
        }

        // EDITAR OFERTA

        public function Ofertas_actualizarOferta(){
            $json = $this->request->getJSON();
            $json->ultima_fecha = date('Y-m-d h:i:s');
            $model = new Oferta();
            $success = $model->update($json->id_oferta, $json);
            
            $query2 = " DELETE FROM Portal_Atraccion_oferta_idiomas
                        WHERE id_oferta=$json->id_oferta";
            
            if($this->db->query($query2) === true){
                $idiomas = $json->idioma;
                $niveles = $json->nivel;

                for($i = 0; $i < count($json->idioma); $i++){
                    $query3 = "INSERT INTO Portal_Atraccion_oferta_idiomas(id_oferta,id_idiomas,id_niveles,estado) VALUES($json->id_oferta,".$idiomas[$i].",".$niveles[$i].",1)";
                    $result3 = $this->db->query($query3);
                }
            }

            return $this->response->setStatusCode(200)->setJSON(1);
        }

        // SUB-MODULO POSTULADOS | FINALISTAS | SELECCIONADOS
        public function Ofertas_getPosFinSel(){
            $json = $this->request->getJSON();

            $id = $json->id;
            $Fpais = $json->pais;
            $Festado = $json->estado;
            $Fciudad = $json->ciudad;

            $filtros = "";

            if($Fciudad != null)
                $filtros.= " AND U.id_ciudad = $Fciudad";
            if($Festado != null)
                $filtros.= " AND U.id_estado = $Festado";
            if($Fpais != null)
                $filtros.= " AND U.id_pais = $Fpais";

            switch($json->tipo){
                case 'Postulados':
                    $query = "  SELECT 
                                    U.id_usuario,
                                    U.correo,
                                    nombres,
                                    apellido_p,
                                    apellido_m,
                                    titulo_curriculum,
                                    Pa.descripcion AS pais, 
                                    E.descripcion AS estado, 
                                    nom_loc,
                                    (
                                        CAST( DATEDIFF( dd, fecha_nacimiento, GETDATE() ) / 365.25 as int)
                                    ) AS edad, 
                                    fecha_nacimiento,
                                    FORMAT(fecha_nacimiento, 'dd/MM/yyyy') AS fecha_nacimiento_n,
                                    U.estado AS estado_usuario,
                                    N.nacionalidad,
                                    P.visto,
                                    T.prefijo,
                                    T.telefono,
                                    CASE WHEN U.estado = 1 THEN 'Activo' ELSE 'Inactivo' END as estado_n
                                FROM Portal_Atraccion_Usuario U
                                LEFT OUTER JOIN Portal_Atraccion_telefono T ON T.id_usuario=U.id_usuario
                                LEFT OUTER JOIN Portal_Atraccion_Postulado P ON P.id_usuario=U.id_usuario AND P.status=1
                                LEFT OUTER JOIN Portal_Atraccion_Paises Pa ON Pa.id_pais=U.id_pais
                                LEFT OUTER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent=U.id_estado
                                LEFT OUTER JOIN Portal_Atraccion_Municipios_Mex M ON M.cve_mun=U.id_ciudad AND M.cve_ent=U.id_estado
                                LEFT OUTER JOIN Nacionalidades N ON N.id_nacionalidad=U.id_nacionalidad
                                WHERE 
                                    P.id_oferta=$id
                                    $filtros
                                ORDER BY P.fecha DESC";
                    break;
                case 'Finalistas':
                    $query = "  SELECT 
                                    U.id_usuario,
                                    U.correo,
                                    nombres,
                                    apellido_p,
                                    apellido_m,
                                    titulo_curriculum,
                                    Pa.descripcion AS pais, 
                                    E.descripcion AS estado, 
                                    nom_loc,
                                    (CAST(DATEDIFF(dd,fecha_nacimiento,GETDATE()) / 365.25 AS INT)) AS edad, 
                                    fecha_nacimiento,
                                    FORMAT(fecha_nacimiento, 'dd/MM/yyyy') AS fecha_nacimiento_n,
                                    U.estado AS estado_usuario,
                                    N.nacionalidad,
                                    P.visto,
                                    T.prefijo,
                                    T.telefono,
                                    CASE WHEN U.estado = 1 THEN 'Activo' ELSE 'Inactivo' END as estado_n
                                FROM Portal_Atraccion_Usuario U
                                LEFT OUTER JOIN Portal_Atraccion_telefono T ON T.id_usuario=U.id_usuario
                                LEFT OUTER JOIN Portal_Atraccion_Finalista P ON P.id_usuario=U.id_usuario
                                LEFT OUTER JOIN Portal_Atraccion_Paises Pa ON Pa.id_pais=U.id_pais
                                LEFT OUTER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent=U.id_estado
                                LEFT OUTER JOIN Portal_Atraccion_Municipios_Mex M ON M.cve_mun=U.id_ciudad and M.cve_ent=U.id_estado
                                LEFT OUTER JOIN Nacionalidades N ON N.id_nacionalidad=U.id_nacionalidad
                                WHERE 
                                    id_oferta=$id
                                    $filtros
                                ORDER BY P.fecha DESC";
                    break;
                case 'Seleccionados':
                    $query = "  SELECT 
                                    U.id_usuario,
                                    U.correo,
                                    nombres,
                                    apellido_p,
                                    apellido_m,
                                    titulo_curriculum,
                                    Pa.descripcion AS pais, 
                                    E.descripcion AS estado, 
                                    nom_loc,
                                    (CAST(DATEDIFF(dd,fecha_nacimiento,GETDATE()) / 365.25 AS INT)) AS edad, 
                                    fecha_nacimiento,
                                    FORMAT(fecha_nacimiento, 'dd/MM/yyyy') AS fecha_nacimiento_n,
                                    U.estado AS estado_usuario,
                                    N.nacionalidad,
                                    T.prefijo,
                                    T.telefono,
                                    CASE WHEN U.estado = 1 THEN 'Activo' ELSE 'Inactivo' END as estado_n
                                FROM Portal_Atraccion_Usuario U
                                LEFT OUTER JOIN Portal_Atraccion_telefono T ON T.id_usuario=U.id_usuario
                                LEFT OUTER JOIN Portal_Atraccion_Seleccionado P ON P.id_usuario=U.id_usuario
                                LEFT OUTER JOIN Portal_Atraccion_Paises Pa ON Pa.id_pais=U.id_pais
                                LEFT OUTER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent=U.id_estado
                                LEFT OUTER JOIN Portal_Atraccion_Municipios_Mex M ON M.cve_mun=U.id_ciudad AND M.cve_ent=U.id_estado
                                LEFT OUTER JOIN Nacionalidades N ON N.id_nacionalidad=U.id_nacionalidad
                                WHERE 
                                    id_oferta=$id
                                    $filtros
                                ORDER BY P.fecha DESC";
                    break;
                default:
                    $query = "";
                    break;
            }
            
            $result = $this->db->query($query)->getResult();
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        // POSTULADOS FILTROS (Pais, Estado, Ciudad)
        public function Ofertas_getFiltrosPostulados(){
            $json = $this->request->getJSON();
            $queryPais = "  SELECT DISTINCT 
                                P.descripcion,
                                COUNT(U.id_pais) as num,
                                U.id_pais
                            FROM Portal_Atraccion_Usuario U
                            INNER JOIN Portal_Atraccion_Paises P ON P.id_pais=U.id_pais
                            INNER JOIN Portal_Atraccion_Postulado Pos ON Pos.id_usuario=U.id_usuario AND pos.status=1
                            WHERE  
                                P.descripcion IS NOT NULL AND 
                                Pos.id_oferta=$json->id
                            GROUP BY P.descripcion,U.id_pais
                            ORDER BY num DESC";
            $resultPais = $this->db->query($queryPais)->getResult();

            
            $queryEstado = "SELECT DISTINCT 
                                E.descripcion,
                                COUNT(U.id_pais) as num,
                                U.id_pais,
                                U.id_estado
                            FROM Portal_Atraccion_Usuario U
                            INNER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent=U.id_estado
                            INNER JOIN Portal_Atraccion_Postulado Pos ON Pos.id_usuario=U.id_usuario AND pos.status=1
                            WHERE 
                                E.descripcion IS NOT NULL AND 
                                Pos.id_oferta=$json->id
                            GROUP BY E.descripcion, U.id_pais, U.id_estado
                            ORDER BY num DESC";
            $resultEstado = $this->db->query($queryEstado)->getResult();


            $queryCiudad = "SELECT DISTINCT 
                                M.nom_loc,
                                COUNT(U.id_pais) as num,
                                U.id_ciudad,
                                U.id_estado
                            FROM Portal_Atraccion_Usuario U
                            INNER JOIN Portal_Atraccion_Municipios_Mex M on M.cve_ent=U.id_estado and M.cve_mun=U.id_ciudad
                            INNER JOIN Portal_Atraccion_Postulado Pos on Pos.id_usuario=U.id_usuario and pos.status=1
                            WHERE 
                                M.nom_loc IS NOT NULL AND 
                                Pos.id_oferta=$json->id
                            GROUP BY M.nom_loc,U.id_ciudad,U.id_estado
                            ORDER BY num DESC";
            $resultCiudad = $this->db->query($queryCiudad)->getResult();

            return $this->response->setStatusCode(200)->setJSON(array($resultPais, $resultEstado, $resultCiudad));
        }

        // Inserta al postulado a Finalistas/Seleccionados
        // Elimina al postulado de Finalista/Seleccionado
        public function Ofertas_accionesPosFinSelOpciones(){
            $json = $this->request->getJSON();
            switch($json->opcion){
                case 1:
                    $query = "  INSERT INTO Portal_Atraccion_Finalista(id_usuario,id_oferta,fecha,id_colaborador,visto)
                                VALUES('$json->usuario',$json->oferta,GETDATE(),$json->colaborador,0)";
                    return $this->response->setStatusCode(200)->setJSON(array($this->db->query($query) ? 1 : 0));
                case 2:
                    
                    $query1 = " INSERT INTO Portal_Atraccion_Seleccionado(id_usuario,id_oferta,fecha,id_colaborador)
                                VALUES('$json->usuario',$json->oferta,GETDATE(),$json->colaborador)";
                    $result1 = $this->db->query($query1) ? 1 : 0;
                    
                    if($result1 == 1){
                        $query2 = "SELECT correo FROM Portal_Atraccion_Usuario WHERE id_usuario=$json->usuario";
                        $result2 = $this->db->query($query2)->getResult();
                        $correo = $result2[0]->correo;

                        $query3 = "SELECT titulo FROM Portal_Atraccion_Ofertas WHERE id_oferta=$json->oferta";
                        $result3 = $this->db->query($query3)->getResult();
                        $titulo = $result3[0]->titulo;

                        // ** ENCABEZADOS DEL CORREO ** //
                        $from="ECN Automation <intranet@ecnautomation.com>";
                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                        $headers .= "From:" . $from;
                        $subject = "Haz sido seleccionado";
                        $to = $correo;

                        // ** CUERPO DEL CORREO ** //
                        $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                                    <html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" style="width:100%;font-family:arial,  helvetica, sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;padding:0;Margin:0">
                                        <head> 
                                            <meta charset="UTF-8"> 
                                            <meta content="width=device-width, initial-scale=1" name="viewport"> 
                                            <meta name="x-apple-disable-message-reformatting"> 
                                            <meta http-equiv="X-UA-Compatible" content="IE=edge"> 
                                            <meta content="telephone=no" name="format-detection"> 
                                            <title>Nuevo correo electrónico</title> 
                                            <!--[if (mso 16)]>
                                                <style type="text/css">
                                                a {text-decoration: none;}
                                                </style>
                                                <![endif]--> 
                                            <!--[if gte mso 9]><style>sup { font-size: 100% !important; }</style><![endif]--> 
                                            <!--[if gte mso 9]>
                                            <xml>
                                                <o:OfficeDocumentSettings>
                                                <o:AllowPNG></o:AllowPNG>
                                                <o:PixelsPerInch>96</o:PixelsPerInch>
                                                </o:OfficeDocumentSettings>
                                            </xml>
                                            <![endif]--> 
                                            <style type="text/css">
                                            @media only screen and (max-width:600px) {p, ul li, ol li, a { font-size:16px!important; line-height:150%!important } h1 { font-size:30px!important; text-align:center; line-height:120%!important } h2 { font-size:26px!important; text-align:center; line-height:120%!important } h3 { font-size:20px!important; text-align:center; line-height:120%!important } h1 a { font-size:30px!important } h2 a { font-size:26px!important } h3 a { font-size:20px!important } .es-menu td a { font-size:16px!important } .es-header-body p, .es-header-body ul li, .es-header-body ol li, .es-header-body a { font-size:16px!important } .es-footer-body p, .es-footer-body ul li, .es-footer-body ol li, .es-footer-body a { font-size:16px!important } .es-infoblock p, .es-infoblock ul li, .es-infoblock ol li, .es-infoblock a { font-size:12px!important } *[class="gmail-fix"] { display:none!important } .es-m-txt-c, .es-m-txt-c h1, .es-m-txt-c h2, .es-m-txt-c h3 { text-align:center!important } .es-m-txt-r, .es-m-txt-r h1, .es-m-txt-r h2, .es-m-txt-r h3 { text-align:right!important } .es-m-txt-l, .es-m-txt-l h1, .es-m-txt-l h2, .es-m-txt-l h3 { text-align:left!important } .es-m-txt-r img, .es-m-txt-c img, .es-m-txt-l img { display:inline!important } .es-button-border { display:block!important } a.es-button { font-size:20px!important; display:block!important; border-width:10px 0px 10px 0px!important } .es-btn-fw { border-width:10px 0px!important; text-align:center!important } .es-adaptive table, .es-btn-fw, .es-btn-fw-brdr, .es-left, .es-right { width:100%!important } .es-content table, .es-header table, .es-footer table, .es-content, .es-footer, .es-header { width:100%!important; max-width:600px!important } .es-adapt-td { display:block!important; width:100%!important } .adapt-img { width:100%!important; height:auto!important } .es-m-p0 { padding:0px!important } .es-m-p0r { padding-right:0px!important } .es-m-p0l { padding-left:0px!important } .es-m-p0t { padding-top:0px!important } .es-m-p0b { padding-bottom:0!important } .es-m-p20b { padding-bottom:20px!important } .es-mobile-hidden, .es-hidden { display:none!important } tr.es-desk-hidden, td.es-desk-hidden, table.es-desk-hidden { display:table-row!important; width:auto!important; overflow:visible!important; float:none!important; max-height:inherit!important; line-height:inherit!important } .es-desk-menu-hidden { display:table-cell!important } table.es-table-not-adapt, .esd-block-html table { width:auto!important } table.es-social { display:inline-block!important } table.es-social td { display:inline-block!important } }
                                            #outlook a {
                                                padding:0;
                                            }
                                            .ExternalClass {
                                                width:100%;
                                            }
                                            .ExternalClass,
                                            .ExternalClass p,
                                            .ExternalClass span,
                                            .ExternalClass font,
                                            .ExternalClass td,
                                            .ExternalClass div {
                                                line-height:100%;
                                            }
                                            .es-button {
                                                mso-style-priority:100!important;
                                                text-decoration:none!important;
                                            }
                                            a[x-apple-data-detectors] {
                                                color:inherit!important;
                                                text-decoration:none!important;
                                                font-size:inherit!important;
                                                font-family:inherit!important;
                                                font-weight:inherit!important;
                                                line-height:inherit!important;
                                            }
                                            .es-desk-hidden {
                                                display:none;
                                                float:left;
                                                overflow:hidden;
                                                width:0;
                                                max-height:0;
                                                line-height:0;
                                                mso-hide:all;
                                            }
                                            </style> 
                                        </head> 
                                        <body style="width:100%;font-family:arial,  helvetica, sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;padding:0;Margin:0"> 
                                            <div class="es-wrapper-color" style="background-color:#EFEFEF"> 
                                            <!--[if gte mso 9]>
                                                <v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="t">
                                                    <v:fill type="tile" color="#efefef" origin="0.5, 0" position="0.5,0"></v:fill>
                                                </v:background>
                                                <![endif]--> 
                                                <table class="es-wrapper" width="100%" cellspacing="0" cellpadding="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;padding:0;Margin:0;width:100%;height:100%;background-repeat:repeat;background-position:center top"> 
                                                    <tr style="border-collapse:collapse"> 
                                                        <td valign="top" style="padding:0;Margin:0"> 
                                                            <table cellpadding="0" cellspacing="0" class="es-content" align="center" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%"> 
                                                                <tr style="border-collapse:collapse"> 
                                                                    <td align="center" style="padding:0;Margin:0"> 
                                                                        <table bgcolor="#ffffff" class="es-content-body" align="center" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:#FFFFFF;width:600px"> 
                                                                            <tr style="border-collapse:collapse"> 
                                                                                <td align="left" bgcolor="#efefef" style="padding:0;Margin:0;padding-top:20px;padding-left:20px;padding-right:20px;background-color:#EFEFEF"> 
                                                                                    <table width="100%" cellspacing="0" cellpadding="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px"> 
                                                                                        <tr style="border-collapse:collapse"> 
                                                                                            <td class="es-m-p0r" valign="top" align="center" style="padding:0;Margin:0;width:560px"> 
                                                                                                <table width="100%" cellspacing="0" cellpadding="0" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px"> 
                                                                                                    <tr style="border-collapse:collapse"> 
                                                                                                        <td align="center" style="padding:0;Margin:0;padding-bottom:15px;font-size:0px"><img class="adapt-img" src="https://huliwp.stripocdn.email/content/guids/CABINET_1f009046df5f5ee0e4c24b0fbd1694c2/images/77531595273771074.png" alt style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic" height="61"></td> 
                                                                                                    </tr> 
                                                                                                </table>
                                                                                            </td> 
                                                                                        </tr> 
                                                                                    </table>
                                                                                </td> 
                                                                            </tr> 
                                                                            <tr style="border-collapse:collapse"> 
                                                                                <td align="left" style="padding:0;Margin:0;padding-top:20px;padding-left:20px;padding-right:20px"> 
                                                                                    <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px"> 
                                                                                        <tr style="border-collapse:collapse"> 
                                                                                            <td align="center" valign="top" style="padding:0;Margin:0;width:560px"> 
                                                                                                <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;border-left:3px solid #3259A5" role="presentation"> 
                                                                                                    <tr style="border-collapse:collapse"> 
                                                                                                        <td align="left" style="padding:0;Margin:0;padding-left:5px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:24px;font-family:arial,  helvetica, sans-serif;line-height:36px;color:#000000">Haz Sido Seleccionado</p></td> 
                                                                                                    </tr> 
                                                                                                    <tr style="border-collapse:collapse"> 
                                                                                                        <td align="left" style="padding:0;Margin:0;padding-left:5px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:16px;font-family:arial,  helvetica, sans-serif;line-height:24px;color:#EE8624"><em></em></p></td> 
                                                                                                    </tr> 
                                                                                                </table>
                                                                                            </td> 
                                                                                        </tr> 
                                                                                    </table>
                                                                                </td> 
                                                                            </tr> 
                                                                            <tr style="border-collapse:collapse"> 
                                                                                <td align="left" style="Margin:0;padding-bottom:10px;padding-top:20px;padding-left:20px;padding-right:20px"> 
                                                                                    <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px"> 
                                                                                        <tr style="border-collapse:collapse"> 
                                                                                            <td align="center" valign="top" style="padding:0;Margin:0;width:560px"> 
                                                                                                <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px"> 
                                                                                                    <tr style="border-collapse:collapse"> 
                                                                                                        <td align="left" style="padding:0;Margin:0;padding-left:10px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:14px;font-family:arial,  helvetica, sans-serif;line-height:21px;color:#CC0000"><span style="color:#000000">Estimado colaborador:<br>Haz sido seleccionado para el empleo de '.$titulo.', En breve nos comunicaremos con usted.</span><span data-cke-bookmark="1" style="display:none">&nbsp;</span></p></td> 
                                                                                                    </tr> 
                                                                                                </table>
                                                                                            </td> 
                                                                                        </tr> 
                                                                                    </table>
                                                                                </td> 
                                                                            </tr>  
                                                                            <tr style="border-collapse:collapse"> 
                                                                                <td align="left" bgcolor="#efefef" style="padding:0;Margin:0;padding-top:10px;padding-bottom:10px;background-color:#EFEFEF"> 
                                                                                    <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px"> 
                                                                                        <tr style="border-collapse:collapse"> 
                                                                                            <td align="center" valign="top" style="padding:0;Margin:0;width:600px"> 
                                                                                                <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px"> 
                                                                                                    <tr style="border-collapse:collapse"> 
                                                                                                        <td style="padding:0;Margin:0;padding-left:5px;padding-right:5px"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:8px;font-family:arial,  helvetica, sans-serif;line-height:12px;color:#333333;text-align:justify"><b>AVISO DE PRIVACIDAD</b>&nbsp;En cumplimiento a lo establecido en la Ley Federal de Protección de Datos Personales en Posesión de los Particulares, hacemos de su conocimiento&nbsp;que&nbsp;<b>ELECTRO CONTROLES DEL NOROESTE, S.A. DE C.V.</b>, con domicilio ubicado en Boulevard Paseo Río Sonora #69, Col. Proyecto Río Sonora, C.P. 83270, Hermosillo, Sonora,&nbsp;es Responsable del Tratamiento de&nbsp;tus&nbsp;datos personales&nbsp; que están en&nbsp;su&nbsp;posesión.&nbsp;Puedes&nbsp;conocer nuestro Aviso de Privacidad Integral solicitándolo con el Encargado de dar tratamiento a su información personal a cuenta del Responsable:&nbsp;ARA SOFTWARE DESING, S.A. DE C.V., con domicilio en&nbsp;con domicilio en&nbsp;Zacatecas No. 24 Piso 6, Oficina 602, Col. Roma Norte, C.P. 06700, Alcaldía Cuauhtémoc, Cd. de México&nbsp;y con sucursal ubicada en Sinaloa # 576&nbsp;Nte., Local 5, Col. Centro Urb4, C.P. 85000, Cd. Obregón, Sonora.&nbsp;El área encargada de dar trámite a su solicitud de revocar el consentimiento al tratamiento de su información personal o para ejercer sus derechos ARCO es el Centro de Operaciones de&nbsp;Ara&nbsp;Software&nbsp;Desing, en la misma&nbsp;dirección&nbsp;y al teléfono 01 (644) 413 49 38 y/o 413 49 39, o si deseas contactarla por e-mail puedes hacerlo a:&nbsp;<a href="mailto:operaciones@vydp.org" target="_blank" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;font-size:8px;text-decoration:underline;color:#2cb543">operaciones@vydp.org</a>,&nbsp;o bien visitando el sitio:&nbsp;<a href="http://www.vydp.org/#!/-avisos-de-privacidad" target="_blank" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial,  helvetica, sans-serif;font-size:8px;text-decoration:underline;color:#2CB543">http://www.vydp.org/#!/-avisos-de-privacidad</a>.&nbsp;</p></td> 
                                                                                                    </tr> 
                                                                                                </table>
                                                                                            </td> 
                                                                                        </tr> 
                                                                                    </table>
                                                                                </td> 
                                                                            </tr> 
                                                                        </table>
                                                                    </td> 
                                                                </tr> 
                                                            </table>
                                                        </td> 
                                                    </tr> 
                                                </table> 
                                            </div>  
                                        </body>
                                    </html>';
                        
                        // ** FUNCION PARA ENVIAR EL CORREO ** //
                        $resultMail = mail($to, $subject, $message, $headers) ? 1 : 0;
                    }else{
                        $resultMail = 0;
                    }
                    return $this->response->setStatusCode(200)->setJSON(array($result1, $resultMail));
                case 3: 
                    $query = "  DELETE Portal_Atraccion_Finalista
                                WHERE id_oferta=$json->oferta AND id_usuario='$json->usuario'";
                    $result = $this->db->query($query) ? 1 : 0;
                    return $this->response->setStatusCode(200)->setJSON(array($result));
                    break;
                case 4:
                    $query = "  DELETE Portal_Atraccion_Seleccionado
                                WHERE id_oferta=$json->oferta AND id_usuario='$json->usuario'";
                    $result = $this->db->query($query) ? 1 : 0;
                    return $this->response->setStatusCode(200)->setJSON(array($result));
                    break;
            }
        }

        // Cambia el estado de la oferta
        public function Ofertas_cambiarEstado(){
            $json = $this->request->getJSON();

            $id = $json->id;
            $estado = $json->estado == 1 ? 0 : 1;

            $query = "UPDATE Portal_Atraccion_Ofertas SET estado=$estado WHERE id_oferta=$id";
            
            if($this->db->query($query))
                return $this->response->setStatusCode(200)->setJSON(array(1));
            else
                return $this->response->setStatusCode(200)->setJSON(array(0));
            
        }

        //////////////
        // USUARIOS //
        //////////////

        public function Ofertas_getUsuarios(){
            $json = $this->request->getJSON();

            $pais = $json->pais != 0 ? "U.id_pais=$json->pais AND" : '';
            $estado = $json->pais == 0 ? "AND (U.estado=1 OR U.estado=0)" : '';
            $buscar = $json->usuario != null ? $json->usuario : '';

            $query1 = " SELECT DISTINCT 
                            U.id_usuario,
                            U.correo,
                            ISNULL(nombres, '') AS nombres,
                            ISNULL(apellido_p, '') AS apellido_p,
                            ISNULL(apellido_m, '') AS apellido_m,
                            ISNULL(titulo_curriculum, '') AS titulo_curriculum,
                            ISNULL(Pa.descripcion, '') AS pais, 
                            ISNULL(E.descripcion, '') AS estado, 
                            ISNULL(nom_loc, '') AS nom_loc,
                            (CAST(DATEDIFF(dd,fecha_nacimiento,GETDATE()) / 365.25 AS INT)) AS edad, 
                            ISNULL(fecha_nacimiento, '') AS fecha_nacimiento,
                            CASE WHEN ISNULL(fecha_nacimiento, '') = '' THEN '' ELSE FORMAT(ISNULL(fecha_nacimiento, ''), 'dd/MM/yyyy') END AS fecha_nacimiento_n,
                            U.estado AS estado_usuario,
                            CASE WHEN U.estado = 1 THEN 'Activo' ELSE 'Inactivo' END AS estado_usuario_n,
                            ISNULL(N.nacionalidad, '') AS nacionalidad
                        FROM Portal_Atraccion_Usuario U 
                        LEFT OUTER JOIN Portal_Atraccion_Postulado P ON P.id_usuario=U.id_usuario AND p.status=1
                        LEFT OUTER JOIN Portal_Atraccion_Paises Pa ON Pa.id_pais=U.id_pais
                        LEFT OUTER JOIN Portal_Atraccion_Estados_Mex E ON E.cve_ent=U.id_estado
                        LEFT OUTER JOIN Portal_Atraccion_Municipios_Mex M ON M.cve_mun=U.id_ciudad AND M.cve_ent=U.id_estado
                        LEFT OUTER JOIN Nacionalidades N ON N.id_nacionalidad=U.id_nacionalidad
                        WHERE 
                            (
                                $pais
                                (
                                    nombres LIKE '%$buscar%' OR 
                                    apellido_p LIKE '%$buscar%' OR 
                                    apellido_m LIKE '%$buscar%' OR 
                                    U.correo LIKE '%$buscar%' OR 
                                    nombres+' '+apellido_p LIKE '%$buscar%' OR 
                                    nombres+' '+apellido_p+' '+apellido_m LIKE '%$buscar%'
                                )
                            ) 
                            $estado
                        ORDER BY U.correo DESC";
            $result1 = $this->db->query($query1)->getResult();
            
            $query2 = " SELECT * FROM Portal_Atraccion_telefono;";
            $result2 = $this->db->query($query2)->getResult(); 

            return $this->response->setStatusCode(200)->setJSON(array($result1, $result2));
        }

        public function Ofertas_getSelectPaises_Usuarios(){
            $query = "  SELECT DISTINCT 
                            P.descripcion,
                            COUNT(U.id_pais) AS num,
                            U.id_pais
                        FROM Portal_Atraccion_Usuario U
                        INNER JOIN Portal_Atraccion_Paises P ON P.id_pais=U.id_pais
                        WHERE 
                            P.descripcion IS NOT NULL
                        GROUP BY P.descripcion,U.id_pais
                        ORDER BY num DESC";
            $result = $this->db->query($query)->getResult();

            return $this->response->setStatusCode(200)->setJSON(array($result));
        }

        public function Ofertas_onChangeEstado_Usuarios(){
            $json = $this->request->getJSON();
            $estado = $json->estado == 1 ? 0 : 1; 
            $query = "UPDATE Portal_Atraccion_Usuario SET estado=$estado WHERE id_usuario=$json->id";
            $result = $this->db->query($query) ? 1 : 0;
            return $this->response->setStatusCode(200)->setJSON($result);
        }

        //////////////////
        // ESTADISTICAS //
        //////////////////

        public function Ofertas_misEstadisticas_Estadisticas(){
            $json = $this->request->getJSON();
            $query = "  SELECT
                            o.O_total,
                            f.F_total,
                            s.S_total
                        FROM
                            (SELECT 1 AS dato, COUNT(id_oferta) AS O_total FROM Portal_Atraccion_Ofertas WHERE id_colaborador = $json->id) AS o
                            LEFT JOIN (select 1 AS dato, COUNT(id_usuario) AS F_total FROM Portal_Atraccion_Finalista WHERE id_colaborador = $json->id) AS f ON o.dato = f.dato
                            LEFT JOIN (select 1 AS dato, COUNT(id_usuario) AS S_total FROM Portal_Atraccion_Seleccionado WHERE id_colaborador = $json->id) AS s ON s.dato = o.dato;";
            $return = $this->db->query($query)->getResult();

            return $this->response->setStatusCode(200)->setJSON($return);
        }

        /////////////////////
        // CONFIGURACIONES //
        /////////////////////

        public function Ofertas_listsAIP_Configuracion(){
            $queryA = "SELECT * FROM Portal_Atraccion_Areas WHERE estado=1";
            $queryI = "SELECT * from Portal_Atraccion_Idiomas WHERE estado=1";
            $queryP = "SELECT * FROM Portal_Atraccion_Paises WHERE estado=1";

            $resultA = $this->db->query($queryA)->getResult();
            $resultI = $this->db->query($queryI)->getResult();
            $resultP = $this->db->query($queryP)->getResult();

            return $this->response->setStatusCode(200)->setJSON(array($resultA, $resultI, $resultP));
        }

        public function Ofertas_CRUD_Configuracion(){
            $json = $this->request->getJSON();

            $tipo_C = $json->tipo_C;
            $tipo_CRUD = $json->tipo_CRUD;

            $tabla_n = $tipo_C == 'A' ? "Portal_Atraccion_Areas" : ($tipo_C == 'I' ? "Portal_Atraccion_Idiomas" : ($tipo_C == 'P' ? "Portal_Atraccion_Paises" : "")) ;
            $id_n = $tipo_C == 'A' ? "id_areas" : ($tipo_C == 'I' ? "id_idiomas" : ($tipo_C == 'P' ? "id_pais" : "")) ;
            
            // Tipo de CRUD seleccionado
            switch($tipo_CRUD){
                case 'C':
                    $descripcion = $json->datos;
                    $query = "INSERT INTO $tabla_n (descripcion, estado) VALUES('$descripcion', 1);";
                    return $this->response->setStatusCode(200)->setJSON($this->db->query($query) ? 1 : 0);
                case 'U':
                    $list = $json->datos;
                    $total = sizeof($list);
                    $count = 0;

                    for ($i=0; $i < sizeof($list); $i++) { 
                        $descripcion = $list[$i]->descripcion;
                        $nombre = $list[$i]->$id_n;
                        $query = "UPDATE $tabla_n SET descripcion = '$descripcion' WHERE $id_n = $nombre";
                        $count+= $this->db->query($query) ? 1 : 0;
                    }

                    return $this->response->setStatusCode(200)->setJSON(array($total, $count));
                case 'D':
                    $id = $json->datos;
                    $query = "UPDATE $tabla_n SET estado = 0 WHERE $id_n = $id;";
                    return $this->response->setStatusCode(200)->setJSON($this->db->query($query) ? 1 : 0);
            }
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}

?>