<?php

namespace App\Controllers;

class GenerarQR extends BaseController
{

    public function generarQR($file)
    {

        $this->response->setContentType("Content-type: application/pdf");
        $this->response->setHeader("Content-Disposition","inline; filename=".$file);

        $localFile = 'E:/xampp/htdocs/intranet/modulos/knocker/Anexos/' . $file;
        if(file_exists($localFile)){
            readfile($localFile);
            $this->response->setStatusCode(200);
        }else{
            $this->response->setStatusCode(404);
        }
    }
}
