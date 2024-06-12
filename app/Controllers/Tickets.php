<?php

namespace App\Controllers;

use CodeIgniter\I18n\Time;

class Tickets extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }
    public function getTicketsUsuario()
    {
        $json = $this->request->getJSON();
        $id_colaborador = $json->id_colaborador;
        $query = $this->db->query(
            "SELECT T.id_ticket,TA.nombre_area,T.titulo_ticket, 
        TS.nombre_subcategoria,T.ticket_estado,T.calificacion_ticket, T.descripcion_ticket, T.comentario_responsable,
        C.nombres responsable, C.apellido_p responsable_ap, C.apellido_m responsable_am, T.fechaHora_ticket, T.fechaHora_ticket_Atencion, T.fechaHora_ticket_Finalizacion
        FROM
        tbl_Ticket T INNER JOIN tbl_AreasDepartamentos TA
        ON T.id_area = TA.id_area
        INNER JOIN tbl_SubCategoriasArea TS
        ON T.id_subarea = TS.id_subcategoria
        INNER JOIN Colaboradores C
		ON TS.id_responsable = C.id_colaborador
        where T.id_solicitante = ? and T.status=1
        ORDER BY ticket_estado asc",
            [$id_colaborador]
        );

        $result = $query->getResult();

        //Formato de fecha
        foreach ($result as $res) {
            if ($res->fechaHora_ticket) {
                $t1 = Time::parse($res->fechaHora_ticket);
                $res->fechaHora_ticket = $t1->toLocalizedString('d') .
                    " de " . $t1->toLocalizedString('MMM') . " " . $t1->toLocalizedString('Y') .
                    " a las " . $t1->toLocalizedString('h:mm a') . " (" . $t1->humanize() . ")";
            }
            if ($res->fechaHora_ticket_Atencion) {
                $t2 = Time::parse($res->fechaHora_ticket_Atencion);
                $res->fechaHora_ticket_Atencion = $t2->toLocalizedString('d') .
                    " de " . $t2->toLocalizedString('MMM') . " " . $t2->toLocalizedString('Y') .
                    " a las " . $t2->toLocalizedString('h:mm a') . " (" . $t2->humanize() . ")";
            }
            if ($res->fechaHora_ticket_Finalizacion) {
                $t3 = Time::parse($res->fechaHora_ticket_Finalizacion);
                $res->fechaHora_ticket_Finalizacion = $t3->toLocalizedString('d') .
                    " de " . $t3->toLocalizedString('MMM') . " " . $t3->toLocalizedString('Y') .
                    " a las " . $t3->toLocalizedString('h:mm a') . " (" . $t3->humanize() . ")";
            }
        }

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getArchivos()
    {
        $json = $this->request->getJSON();
        $id_ticket = $json->id_ticket;
        $query = $this->db->query("SELECT A.nombre_archivo as Archivo, tipo_archivo
        from tbl_Ticket T left join tbl_ArchivosTickets A
        ON T.id_ticket = A.id_ticket
        WHERE T.id_ticket =$id_ticket");
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function getAreas()
    {
        $query = $this->db->query(
            "SELECT id_area,nombre_area 
            FROM tbl_AreasDepartamentos 
        WHERE  area_estado = 1"
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }
    public function getCategorias()
    {
        $id_area = $this->request->getJSON();
        $query = $this->db->query(
            "SELECT S1.id_subcategoria, S1.id_areaC, S1.nombre_subcategoria, S1.requisitos
            FROM tbl_SubCategoriasArea S1 INNER JOIN tbl_AreasDepartamentos A1 
            ON S1.id_areaC = A1.id_area
            WHERE A1.area_estado = 1
            AND S1.subcat_estado = 1 AND A1.id_area = ?",
            [$id_area]
        );
        $result = $query->getResult();

        return $this->response->setStatusCode(200)->setJSON($result);
    }

    public function nuevaSolicitud()
    {
        $post = $this->request->getPost();
        $datos = json_decode($post['datos']);
        $query = $this->db->query('EXEC sp_Registro_Ticket ' . $datos->id_solicitante . ',' . $datos->id_area . ',"' . $datos->titulo_ticket . '",' . $datos->id_subarea . ',"' . $datos->descripcion_ticket . '",null');
        $result = $query->getResult();
        $archivos = $this->request->getFiles();
        if (count($archivos) > 0) {
            $id = $result[0]->id;
            for ($i = 0; $i < count($archivos); $i++) {
                if ($archivos['archivos'][$i]->isValid() && !$archivos['archivos'][$i]->hasMoved()) {
                    if ($archivos['archivos'][$i]->move("C:/Users/Eduardo/Documents/intranet/docs/tickets")) {
                        $nombre_archivo = $archivos['archivos'][$i]->getName();
                        $query2 = $this->db->query('EXEC sp_Registro_Archivo ' . $id . ',' . $datos->id_solicitante . ',"' . $nombre_archivo . '",1');
                        $query2->getResult();
                    }
                }
            }
        }
    }

    public function getTicketsTI()
    {
        $id_colaborador = $this->request->getJSON()->id_colaborador;
        $query = $this->db->query('EXEC sp_select_peticiones_usuarios_08012016 ?', [
            $id_colaborador
        ]);

        return $this->response->setStatusCode(200)->setJSON($query->getResult());
    }

    public function getCategoriasTI()
    {
        $query = $this->db->query("SELECT Modulo_ID,Modulo_Nombre
        FROM tbl_Modulo
        WHERE Modulo_Estado=1");

        return $this->response->setStatusCode(200)->setJSON($query->getResult());
    }

    public function getColaboradores()
    {
        $query = $this->db->query("SELECT id_colaborador, CONCAT(nombres, ' ', apellido_p, ' ', apellido_m) AS nombre FROM Colaboradores WHERE estado = 1 ORDER BY nombre");

        return $this->response->setStatusCode(200)->setJSON($query->getResult());
    }

    public function createTicketTI()
    {
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
        $id_area = $this->db->query("SELECT id_area FROM Colaboradores WHERE id_colaborador = ?", [$ticketData->usuario])->getResult()[0]->id_area;
        $id_asignado = $this->db->query("SELECT Modulo_Colaboradores_ID FROM tbl_modulo where Modulo_ID=?", [$ticketData->categoria])->getResult()[0]->Modulo_Colaboradores_ID;
        $this->db->query("INSERT INTO tbl_peticion (Peticion_Usuario_ID,
            Peticion_Area,
            Peticion_Titulo,
            Peticion_Descripcion,
            Peticion_Prioridad,

            Peticion_Medio,
            Peticion_Estatus,
            Peticion_Hora,
            Peticion_Fecha,
            Peticion_Modulo_ID,

            Peticion_foto,
            Peticion_Colaboradores_ID)

            VALUES 
            (?,?,?,?,?,?,?,?,?,?,?,?)", [
            $ticketData->usuario,
            $id_area,
            $ticketData->titulo,
            $ticketData->descripcion,
            $ticketData->prioridad,
            $ticketData->medio,
            1,
            date('Y-M-d H:i:s'),
            date('Y-M-d H:i:s'),
            $ticketData->categoria,
            $nombre_archivo,
            $id_asignado
            ]);

        return $this->response->setStatusCode(200);

    }

    public function getTicketTI()
    {
        $id_ticket = $this->request->getJSON()->id_ticket;
        $query = $this->db->query('EXEC sp_select_detalle_peticion2_02102015 ?', [
            $id_ticket
        ]);

        return $this->response->setStatusCode(200)->setJSON($query->getResult());
    }

    // public function upload()
    // {
    //     if ($_FILES) {
    //         //$target_path = "E:\xampp\htdocs\intranet\docs\tickets";
    //         $target_path = "C:/Users/Eduardo/Documents/intranet/docs/tickets/";

    //         $target_path = $target_path . basename($_FILES['file']['name']);

    //         if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
    //             echo 1;
    //         } else {
    //             echo 0;
    //         }
    //     }
    // }
}
