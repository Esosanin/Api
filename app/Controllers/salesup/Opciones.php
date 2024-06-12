<?php

namespace App\Controllers\salesup;

use App\Controllers\BaseController;
use App\Models\salesup\Meta;
use App\Models\salesup\Oportunidad;

class Opciones extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getVendedoresSinRegistrar()
    {
        $query = $this->db->query("SELECT CONCAT(nombres,' ',apellido_p,' ',apellido_m) as nombreC,
        id_vendedorSap FROM Colaboradores WHERE estado = 1 AND id_vendedorSap!=0 AND id_vendedorSap!=-1
        AND id_vendedorSap NOT IN (SELECT id_sap FROM det_vendedores WHERE id_sap IS NOT NULL)");

        $vendedores = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'vendedores' => $vendedores
        ]);
    }

    public function altaVendedor()
    {
        $input = $this->getRequestInput($this->request);
        $this->db->query("INSERT INTO det_vendedores VALUES(?,?,?,?)", [
            $input['tipo'],
            $input['id_sap'],
            $input['id_zona'],
            $input['especialidad']
        ]);

        $this->getResponse([
            'message' => 'Data successfully saved'
        ]);
    }

    public function getMetas()
    {
        $json = $this->request->getJSON();
        $query = $this->db->query("
        SELECT M.* FROM metas_salesup M LEFT JOIN det_vendedores DV ON M.id_sap=DV.id_sap
        JOIN zonas Z ON M.id_zona = Z.id WHERE DV.id_zona = ? OR M.id_zona = ?", [
            $json->id_zona,
            $json->id_zona
        ]);
        $metas = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'metas' => $metas
        ]);
    }

    public function getOportunidades()
    {
        $json = $this->request->getJSON();
        if (isset($json->id_sap)) {
            $query = $this->db->query("SELECT
            O.*,
            T2.CardName AS 'CardName',
            T2.descripcion AS 'descripcion2'
            FROM
            oportunidades O,
            (SELECT
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            CRD1.LineNum AS 'LineNum',
            CRD1.Address AS 'descripcion',
            CRD1.U_SalesUPID AS 'SlpCode'
            FROM 
            [SAPSERVER].[SBO_ECN].[dbo].OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1
            WHERE
            OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
                OCRD.SlpCode!=0 AND
                CRD1.AdresType = 'S' AND
                OCRD.CardType = 'C' AND
                OCRD.CardName IS NOT NULL
            UNION ALL
            SELECT
            CAST(id AS nvarchar) AS 'CardCode',
            raz_social AS 'CardName',
            '-1' AS 'LineNum',
            'Sin descripcón' AS 'descripcion',
            id_sap AS 'SlpCode'
            FROM cuentas
            ) AS T2
            WHERE
            O.CardCode = T2.CardCode AND
            O.LineNum = T2.LineNum AND
            O.id_sap = T2.SlpCode AND
            O.id_sap = ?", [$json->id_sap]);

        } else {
            $query = $this->db->query("SELECT
            O.*,
            T2.CardName AS 'CardName',
            T2.descripcion AS 'descripcion2'
            FROM
            oportunidades O,
            (SELECT
            OCRD.CardCode COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardCode',
            OCRD.CardName COLLATE SQL_Latin1_General_CP850_CI_AS AS 'CardName',
            CRD1.LineNum AS 'LineNum',
            CRD1.Address AS 'descripcion',
            CRD1.U_SalesUpID AS 'SlpCode'
            FROM 
            [SAPSERVER].[SBO_ECN].[dbo].OCRD,
            [SAPSERVER].[SBO_ECN].[dbo].CRD1
            WHERE
            OCRD.CardCode=CRD1.CardCode AND
            OCRD.SlpCode!=-1 AND
                OCRD.SlpCode!=0 AND
                CRD1.AdresType = 'S' AND
                OCRD.CardType = 'C' AND
                OCRD.CardName IS NOT NULL
            UNION ALL
            SELECT
            CAST(id AS nvarchar) AS 'CardCode',
            raz_social AS 'CardName',
            '-1' AS 'LineNum',
            'Sin descripcón' AS 'descripcion',
            id_sap AS 'SlpCode'
            FROM cuentas
            ) AS T2
            WHERE
            O.CardCode = T2.CardCode AND
            O.LineNum = T2.LineNum AND
            O.id_sap = T2.SlpCode AND
            O.CardCode = ?", [$json->CardCode]);
        }

        $oportunidades = $query->getResult();

        return $this->getResponse([
            'message' => 'Data successfully retrieved',
            'oportunidades' => $oportunidades
        ]);
    }

    public function deleteOportunidad()
    {
        $json = $this->request->getJSON();
        $model = new Oportunidad();
        $model->delete($json->id);

        return $this->getResponse([
            'message' => 'Data successfully deleted'
        ]);
    }

    public function deleteMeta()
    {
        $json = $this->request->getJSON();
        $model = new Meta();
        $model->delete($json->id);

        return $this->getResponse([
            'message' => 'Data successfully deleted'
        ]);
    }

    public function saveMeta()
    {
        $json = $this->request->getJSON();
        $model = new Meta();
        $model->save($json);

        return $this->getResponse([
            'message' => 'Data successfully saved'
        ]);
    }
}
