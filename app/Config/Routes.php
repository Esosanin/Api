<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.


//login auth y recuperar contraseña
$routes->group('auth', function ($routes) {
    $routes->post('login', 'Auth::login');
    $routes->post('recover', 'Auth::recoverPassRequest');
    $routes->post('tokenCheck', 'Auth::tokenCheck');
    $routes->post('recoverPass', 'Auth::recoverPass');
});


$routes->group('api', function ($routes) {

    $routes->group('colaboradores', function ($routes) {
        $routes->get('generatePDF/(:num)', 'Colaboradores::generatePDF/$1');
        $routes->post('generateExcel', 'Colaboradores::generateExcel');
        $routes->post('getCuenta', 'Colaboradores::getCuenta');
        $routes->get('getColaborador/(:num)', 'Colaboradores::getColaborador/$1');
        $routes->get('getNacionalidades', 'Colaboradores::getNacionalidades');
        $routes->get('getCarreras', 'Colaboradores::getCarreras');
        $routes->get('getEstudios', 'Colaboradores::getEstudios');
        $routes->post('getPuestoPotentor', 'Colaboradores::getPuestoPotentor');
        $routes->get('getPuestosPotentor', 'Colaboradores::getPuestosPotentor');
        $routes->get('getDepartamentos', 'Colaboradores::getDepartamentos');
        $routes->get('getHojaEspecialidad', 'Colaboradores::getHojaEspecialidad');
        $routes->get('getGeografias', 'Colaboradores::getGeografias');
        $routes->get('getPuestos', 'Colaboradores::getPuestos');
        $routes->get('getEspecialidades', 'Colaboradores::getEspecialidades');
        $routes->get('getTipoEmpleado', 'Colaboradores::getTipoEmpleado');
        $routes->get('getSucursales', 'Colaboradores::getSucursales');
        $routes->post('saveColaborador', 'Colaboradores::saveColaborador');
        $routes->get('getAll', 'Colaboradores::getColaboradores');
        $routes->get('getSangre', 'Colaboradores::getSangre');
        $routes->get('proyectos', 'Colaboradores::getProyectos');
        $routes->post('checada', 'Colaboradores::checkInOut');
        $routes->post('check', 'Colaboradores::check');
        $routes->post('semanales', 'Colaboradores::getColaboradoresSemanales');
        $routes->post('verifyPass', 'Colaboradores::verifyPass');
    });

    $routes->get('segurosFlotilla', 'Seguros::segurosFlotilla');

    $routes->group('tickets', function ($routes) {
        $routes->post('ticketsUsuario', 'Tickets::getTicketsUsuario');
        $routes->post('archivos', 'Tickets::getArchivos');
        $routes->get('areas', 'Tickets::getAreas');
        $routes->post('categorias', 'Tickets::getCategorias');
        $routes->post('upload', 'Tickets::upload');
        $routes->post('nuevo', 'Tickets::nuevaSolicitud');
        $routes->post('ticketsTI', 'Tickets::getTicketsTI');
        $routes->post('ticketTI', 'Tickets::getTicketTI');
        $routes->get('getCategoriasTI', 'Tickets::getCategoriasTI');
        $routes->get('getColaboradores', 'Tickets::getColaboradores');
        $routes->post('createTicketTI', 'Tickets::createTicketTI');
    });

    $routes->group('gastos-viaticos', function ($routes) {
        $routes->post('getSolicitudesGasto', 'Gastosviaticos::getSolicitudesGasto');
        $routes->post('getGastoInforme', 'Gastosviaticos::getGastoInforme');
        $routes->post('getDetalleInforme', 'Gastosviaticos::getDetalleInforme');
        $routes->post('getDetalleSolicitud', 'Gastosviaticos::getDetalleSolicitud');
        $routes->get('getTipoGasto', 'Gastosviaticos::getTipoGasto');
        $routes->post('saveGasto', 'Gastosviaticos::saveGasto');
        $routes->post('deleteGasto', 'Gastosviaticos::deleteGasto');
        $routes->post('generarPDF', 'Gastosviaticos::generarPDF');
        $routes->post('enviarRevision', 'Gastosviaticos::enviarRevision');
        $routes->post('crearInforme', 'Gastosviaticos::crearInforme');
        $routes->post('subirXML', 'Gastosviaticos::subirXML');
        $routes->post('cancelGasto', 'Gastosviaticos::cancelGasto');
        $routes->post('comprobarGasto', 'Gastosviaticos::comprobarGasto');
        $routes->post('terminarInforme', 'Gastosviaticos::terminarInforme');
        $routes->post('getAdeudos', 'Gastosviaticos::getAdeudos');
        $routes->post('verAnexoAdeudo', 'Gastosviaticos::verAnexoAdeudo');
        $routes->post('saveAnexoAdeudo', 'Gastosviaticos::saveAnexoAdeudo');
    });

    $routes->group('salesup', function ($routes) {
        $routes->group('planes', function ($routes) {
            $routes->post('checkPlan', 'salesup\Planes::checkPlan');
            $routes->post('addPlan', 'salesup\Planes::addPlan');
            $routes->post('getPlanes', 'salesup\Planes::getPlanes');
            $routes->post('getVisitas', 'salesup\Planes::getVisitas');
            $routes->post('getActividades', 'salesup\Planes::getActividades');
            $routes->post('getCompromisos', 'salesup\Planes::getCompromisos');
            $routes->post('getLevantamientos', 'salesup\Planes::getLevantamientos');
            $routes->post('getCuentas', 'salesup\Planes::getCuentas');
            $routes->get('getCuenta/(:any)', 'salesup\Planes::getCuenta/$1');
            $routes->post('saveCuenta', 'salesup\Planes::saveCuenta');
            $routes->post('saveContacto', 'salesup\Planes::saveContacto');
            $routes->post('actualizarProy', 'salesup\Planes::actualizarProy');
            $routes->post('addVisita', 'salesup\Planes::addVisita');
            $routes->post('deleteVisita', 'salesup\Planes::deleteVisita');
            $routes->post('getContactos', 'salesup\Planes::getContactos');
            $routes->post('addActividad', 'salesup\Planes::addActividad');
            $routes->post('confirmPlan', 'salesup\Planes::confirmPlan');
            $routes->post('checkIn', 'salesup\Planes::checkIn');
            $routes->post('checkCheckIn', 'salesup\Planes::checkCheckIn');
            $routes->post('execActividad', 'salesup\Planes::execActividad');
            $routes->post('checkActividades', 'salesup\Planes::checkActividades');
            $routes->post('deleteActividad', 'salesup\Planes::deleteActividad');
            $routes->post('addCompromiso', 'salesup\Planes::addCompromiso');
            $routes->post('getAcompanamientos', 'salesup\Planes::getAcompanamientos');
            $routes->post('addAcompanamiento', 'salesup\Planes::addAcompanamiento');
            $routes->post('execCompromiso', 'salesup\Planes::execCompromiso');
            $routes->post('addLevantamiento', 'salesup\Planes::addLevantamiento');
            $routes->post('deleteLevantamiento', 'salesup\Planes::deleteLevantamiento');
            $routes->post('deleteCompromiso', 'salesup\Planes::deleteCompromiso');
            $routes->post('deleteAcompanamiento', 'salesup\Planes::deleteAcompanamiento');
            $routes->post('getVendedores', 'salesup\Planes::getVendedores');
            $routes->get('getIndustrias', 'salesup\Planes::getIndustrias');
            $routes->get('getSpk1', 'salesup\Planes::getSpk1');
            $routes->post('getOportunidades', 'salesup\Planes::getOportunidades');
            $routes->post('addOportunidad', 'salesup\Planes::addOportunidad');
            $routes->post('getPonderado', 'salesup\Planes::getPonderado');
            $routes->get('getZonas', 'salesup\Planes::getZonas');
            $routes->get('getCotizaciones/(:num)', 'salesup\Planes::getCotizaciones/$1');
            $routes->post('saveImagenLevantamiento', 'salesup\Planes::saveImagenLevantamiento');
            $routes->post('deleteImagen', 'salesup\Planes::deleteImagen');
            $routes->get('getImgLevantamiento/(:num)', 'salesup\Planes::getImgLevantamiento/$1');
            $routes->get('getEtapas', 'salesup\Planes::getEtapas');
            $routes->post('getSucursales', 'salesup\Planes::getSucursales');
            //$routes->post('getProyecciones', 'salesup\Planes::getProyecciones');
        });

        $routes->group('indicadores', function ($routes) {
            $routes->post('getHorasMetas', 'salesup\Indicadores::getHorasMetas');
        });

        $routes->group('opciones', function ($routes) {
            $routes->post('getOportunidades', 'salesup\Opciones::getOportunidades');
            $routes->post('deleteOportunidad', 'salesup\Opciones::deleteOportunidad');
            $routes->post('getMetas', 'salesup\Opciones::getMetas');
            $routes->post('deleteMeta', 'salesup\Opciones::deleteMeta');
            $routes->post('saveMeta', 'salesup\Opciones::saveMeta');
            $routes->post('altaVendedor', 'salesup\Opciones::altaVendedor');
            $routes->get('getVendedoresSinRegistrar', 'salesup\Opciones::getVendedoresSinRegistrar');
        });
    });

    $routes->group('ProcesosSoporteVacaciones', function($routes){
        $routes->post('traerMiInfo','ProcesossoporteVacaciones::getMiInfo');
        $routes->post('traerMisSolicitudes','ProcesossoporteVacaciones::getMisSolicitudes');
        $routes->post('traerMisSolicitudesPendientes','ProcesossoporteVacaciones::getMisSolicitudesPendientes');
        $routes->post('traerMisSolicitudesPorAprobar','ProcesossoporteVacaciones::getMisSolicitudesPorAprobar');
        $routes->post('traerMisSolicitudesPorAprobarCH','ProcesossoporteVacaciones::getMisSolicitudesPorAprobarCH');
        $routes->post('macroFormulario','ProcesossoporteVacaciones::getMacroForm');
        $routes->post('macroFormularioD','ProcesossoporteVacaciones::getMacroFormD');
        $routes->post('macroFormularioV','ProcesossoporteVacaciones::getMacroFormV');
        $routes->post('cancelarsolicitudVM','ProcesossoporteVacaciones::getcancelarsolicitudVM');
        $routes->post('aprobarLider','ProcesossoporteVacaciones::getaprobarsolicitudVM');
        $routes->post('rechazarLider','ProcesossoporteVacaciones::getrechazarsolicitudVM');
        $routes->post('aprobarCapHum','ProcesossoporteVacaciones::getaprobarsolicitudCHVM');
        $routes->post('rechazarCapHum','ProcesossoporteVacaciones::getrechazarsolicitudCHVM');
        $routes->post('aprobarconsueldoLider','ProcesossoporteVacaciones::getaprobarsolicitudconsueldoVM');
        $routes->post('aprobarsinsueldoLider','ProcesossoporteVacaciones::getaprobarsolicitudsinsueldoVM');
    });

    $routes->group('KnockerWO', function($routes){
        //$routes->get('traerMacro','KnockerWO::getMacro');
        $routes->post('traerdatoknockerpermiso','KnockerWO::datoknockerpermiso');
        $routes->post('traerproyectoswoabiertos','KnockerWO::proyectosWorkOrdersAbiertos');
        $routes->post('traerproyectoswocerrados','KnockerWO::proyectosWorkOrdersCerrados');
        $routes->post('traerWODatosDetalleProyectoOK','KnockerWO::woDatosDetalleProyectoOK');
        $routes->post('traerWODatosDetalleHistComentarios','KnockerWO::woDatosDetalleHistComentarios');
        $routes->post('traerWODatosDetalleArchivosCot','KnockerWO::woDatosDetalleArchivosCot');
        $routes->post('traerWODatosDetalleArchivosOCP','KnockerWO::woDatosDetalleArchivosOCP');
        $routes->post('traerWOPermisos','KnockerWO::woWOPermisos');
        $routes->post('traerWODatosDetalle','KnockerWO::woDatosDetalle');
        $routes->post('traerWoHistComentsOT','KnockerWO::woHistComentsOT');
        $routes->post('traerOTAttachments','KnockerWO::GetOTAttachments');
        $routes->post('traerAnexosWO','KnockerWO::GetAnexosWO');
        $routes->post('mandarFormFactAntiComp','KnockerWO::SendFormFactAntiComp');
        $routes->post('mandarFormFactAntiParc','KnockerWO::SendFormFactAntiParc');
        $routes->post('mandarFormComentarioHist','KnockerWO::SendFormComentarioHist');
        $routes->post('mandarFormFechaConfirmada','KnockerWO::SendFormFechaConfirmada');
        $routes->post('mandarFormFechaTerminacion','KnockerWO::SendFormFechaTerminacion');
        $routes->post('mandarComentariosCancelarOT','KnockerWO::SendFormComentariosCancelarOT');
        $routes->post('mandarFormTermFactComp','KnockerWO::SendFormTermFactComp');
        $routes->post('mandarFormTermFactParc','KnockerWO::SendFormTermFactParc');
        $routes->post('regresarEPWO','KnockerWO::backEPWO');
        $routes->post('ejecutarOT','KnockerWO::ejecutarWO');
        $routes->post('mandarFormEjecutarOT','KnockerWO::ejecutarWO');
        $routes->post('mandarEncuestaSugerenciaOT','KnockerWO::sendEncuestaSugerenciaOT');
        $routes->post('traerEncuestaComentarios','KnockerWO::getEncuestaComentarios');
        $routes->post('traerPersonasPorEvaluar','KnockerWO::getPersonasPorEvaluar');
        $routes->post('traerEvaluacionProm','KnockerWO::getEvaluacionAvg');
        $routes->post('mandarRecursosEncuesta','KnockerWO::sendRecursosEncuestaWO');
        $routes->post('traerEvaluacionesEncuesta','KnockerWO::getEvaluacionesEncuestaOT');
        $routes->post('borrarArchivoAdjuntoOT','KnockerWO::deleteAttFileWO');
        $routes->post('nuevo','KnockerWO::nuevaSolicitud');
    });

    $routes->group('areas', function($routes){
        // Modulo: Areas
        $routes->post('getAreas', 'Areas::getAreas');
        $routes->post('editArea', 'Areas::updateArea');
        $routes->post('createArea', 'Areas::createArea');
        $routes->post('deleteArea', 'Areas::deleteArea');

        // Modulo: Carreras
        $routes->post('getCarreras', 'Areas::getCarreras');
        $routes->post('createCarrera', 'Areas::createCarrera');
        $routes->post('editCarrera', 'Areas::updateCarrera');
        $routes->post('deleteCarrera', 'Areas::deleteCarrera');

        // Modulo: Departamentos
        $routes->post('getDepartamentos', 'Areas::getDepartamentos');
        $routes->post('createDepartamento', 'Areas::createDepartamento');
        $routes->post('editDepartamento', 'Areas::updateDepartamento');
        $routes->post('deleteDepartamento', 'Areas::deleteDepartamento');

        // Modulo: Especialidades
        $routes->post('getEspecialidades', 'Areas::getEspecialidades');
        $routes->post('createEspecialidad', 'Areas::createEspecialidad');
        $routes->post('editEspecialidad', 'Areas::updateEspecialidad');
        $routes->post('deleteEspecialidad', 'Areas::deleteEspecialidad');

        // Modulo: Nacionalidades
        $routes->post('getNacionalidades', 'Areas::getNacionalidades');
        $routes->post('createNacionalidad', 'Areas::createNacionalidad');
        $routes->post('editNacionalidad', 'Areas::updateNacionalidad');
        $routes->post('deleteNacionalidad', 'Areas::deleteNacionalidad');

        // Modulo: Puestos
        $routes->post('getPuestos', 'Areas::getPuestos');
        $routes->post('createPuesto', 'Areas::createPuesto');
        $routes->post('editPuesto', 'Areas::updatePuesto');
        $routes->post('deletePuesto', 'Areas::deletePuesto');
        
        // Modulo: Region
        $routes->post('getRegiones', 'Areas::getRegiones');
        $routes->post('createRegion', 'Areas::createRegion');
        $routes->post('editRegion', 'Areas::updateRegion');
        $routes->post('deleteRegion', 'Areas::deleteRegion');
        
        // Modulo: Sucursales
        $routes->post('getSucursales', 'Areas::getSucursales');
        $routes->post('createSucursal', 'Areas::createSucursal');
        $routes->post('editSucursal', 'Areas::updateSucursal');
        $routes->post('deleteSucursal', 'Areas::deleteSucursal');

        // Modulo: Propuesta salarial
        $routes->get('getColaboradores_propuesta', 'Areas::getColaboradores_propuesta');
        $routes->get('getTipoEmpleado_propuesta', 'Areas::getTipoEmpleado_propuesta');
        $routes->get('getPuestos_propuesta', 'Areas::getPuestos_propuesta');
        $routes->get('getSucursales_propuesta', 'Areas::getSucursales_propuesta');
        $routes->get('getDepartamentos_propuesta', 'Areas::getDepartamentos_propuesta');
        $routes->post('getPropuestas_proceso', 'Areas::getPropuestas_proceso');
        $routes->post('getPropuestas_terminadas', 'Areas::getPropuestas_terminadas');
        $routes->post('getPropuesta_porID', 'Areas::getPropuesta_porID');

        $routes->post('updateEliminarSolicitud', 'Areas::updateEliminarSolicitud');
        $routes->post('updateGuardarInformacion', 'Areas::updateGuardarInformacion');
        $routes->post('crearPropuesta', 'Areas::crearPropuesta');
        $routes->post('NuevaPropuesta', 'Areas::NuevaPropuesta');
        $routes->post('getTabId_niveles', 'Areas::getTabId_niveles');
        $routes->get('getHojaEspecialidades', 'Areas::getHojaEspecialidades');
        $routes->post('getHojaPosicionApoyo', 'Areas::getHojaPosicionApoyo');
        $routes->post('getHojaSucursal_Aumento', 'Areas::getHojaSucursal_Aumento');
        $routes->post('getHojaCategorias', 'Areas::getHojaCategorias');
        $routes->post('getPropuestaDefinitiva', 'Areas::getPropuestaDefinitiva');

        $routes->post('generatePropuestaSalarialDefinitivaPDF', 'Areas::generatePropuestaSalarialDefinitivaPDF');
        $routes->post('eliminarPropuesta', 'Areas::eliminarPropuesta');
        $routes->post('agregarPropuestaDefinitiva', 'Areas::agregarPropuestaDefinitiva');
        $routes->post('updateActualizarColaborador', 'Areas::updateActualizarColaborador');

        // Modulo: Ofertas laborales
        $routes->post('getOfertas', 'Areas::getOfertas');
        $routes->post('Ofertas_getSelectAreas_Ofertas', 'Areas::Ofertas_getSelectAreas_Ofertas');
        $routes->post('Ofertas_getSelectPaises_Ofertas', 'Areas::Ofertas_getSelectPaises_Ofertas');
        $routes->get('Ofertas_getSelects', 'Areas::Ofertas_getSelects');
        $routes->post('Ofertas_getSelectCiudad', 'Areas::Ofertas_getSelectCiudad');
        $routes->post('Ofertas_publicarOferta', 'Areas::Ofertas_publicarOferta');
        $routes->post('Ofertas_getDetalleOferta', 'Areas::Ofertas_getDetalleOferta');
        $routes->post('Ofertas_actualizarOferta', 'Areas::Ofertas_actualizarOferta');

        // Sub-modulo: Postulados
        $routes->post('Ofertas_getPosFinSel', 'Areas::Ofertas_getPosFinSel');
        $routes->post('Ofertas_getFiltrosPostulados', 'Areas::Ofertas_getFiltrosPostulados');
        $routes->post('Ofertas_accionesPosFinSelOpciones', 'Areas::Ofertas_accionesPosFinSelOpciones');
        $routes->post('Ofertas_cambiarEstado', 'Areas::Ofertas_cambiarEstado');
        $routes->post('Ofertas_verCurriculum', 'Areas::Ofertas_verCurriculum');

        // Sub-modulo: Usuarios
        $routes->post('Ofertas_getUsuarios', 'Areas::Ofertas_getUsuarios');
        $routes->get('Ofertas_getSelectPaises_Usuarios', 'Areas::Ofertas_getSelectPaises_Usuarios');
        $routes->post('Ofertas_onChangeEstado_Usuarios', 'Areas::Ofertas_onChangeEstado_Usuarios');

        // Sub-modulo: Estadisticas
        $routes->post('Ofertas_misEstadisticas_Estadisticas', 'Areas::Ofertas_misEstadisticas_Estadisticas');

        // Sub-modulo: Configuraciones
        $routes->get('Ofertas_listsAIP_Configuracion', 'Areas::Ofertas_listsAIP_Configuracion');
        $routes->post('Ofertas_CRUD_Configuracion', 'Areas::Ofertas_CRUD_Configuracion');
    });

    $routes->group('servicios', function($routes){
        $routes->post('Reporteservicio_consulta', 'Servicios::Reporteservicio_consulta');
        $routes->post('Embudo_getEmbudo', 'Servicios::Embudo_getEmbudo');
        $routes->post('Embudo_getCotizacion', 'Servicios::Embudo_getCotizacion');
        $routes->get('Embudo_getSelectBitrix', 'Servicios::Embudo_getSelectBitrix');
        $routes->post('Embudo_getCotizacion_docentry', 'Servicios::Embudo_getCotizacion_docentry');
        $routes->post('Embudo_updateQuotation', 'Servicios::Embudo_updateQuotation');

        $routes->post('createPDF', 'Servicios::createPDF');
        $routes->post('createXLS', 'Servicios::createXLS');
    });

    $routes->group('finanzas', function($routes){

        // MODULO: SOLICITUDES REGISTRADAS
        $routes->post('SR_getSolicitudesFinanzas_Pendientes', 'Finanzas::SR_getSolicitudesFinanzas_Pendientes');
        $routes->post('SR_getSolicitudesFinanzas_Aprobadas', 'Finanzas::SR_getSolicitudesFinanzas_Aprobadas');
        $routes->post('SR_getSolicitudesFinanzas_Depositadas', 'Finanzas::SR_getSolicitudesFinanzas_Depositadas');
        $routes->post('SR_updateSolicitudesFinanzas_acciones', 'Finanzas::SR_updateSolicitudesFinanzas_acciones');
        $routes->post('SR_getSolicitud_Detalles', 'Finanzas::SR_getSolicitud_Detalles');
        $routes->post('SR_habilitarCheckbox', 'Finanzas::SR_habilitarCheckbox');
        $routes->post('SR_getTotalConceptos', 'Finanzas::SR_getTotalConceptos');
        $routes->post('SR_generarPDF_solicitud', 'Finanzas::SR_generarPDF_solicitud');
        $routes->post('SR_guardarConceptosSolicitados', 'Finanzas::SR_guardarConceptosSolicitados');

        // MODULO: INFORMES REGISTRADOS
        $routes->post('IR_getInformes', 'Finanzas::IR_getInformes');
        $routes->post('IR_getGastos', 'Finanzas::IR_getGastos');
        $routes->post('IR_getDetallesGastos', 'Finanzas::IR_getDetallesGastos');
        $routes->post('IR_gasto_acciones', 'Finanzas::IR_gasto_acciones');
        $routes->post('IR_genera_pdf_comprobacion', 'Finanzas::IR_genera_pdf_comprobacion');
        $routes->post('IR_gastoRegresar', 'Finanzas::IR_gastoRegresar');
        $routes->post('IR_gasto_montoAdeudo', 'Finanzas::IR_gasto_montoAdeudo');
        $routes->post('IR_gastoDepositado', 'Finanzas::IR_gastoDepositado');
        $routes->post('IR_gastoOcultar', 'Finanzas::IR_gastoOcultar');
        $routes->post('IR_gastoAnexos', 'Finanzas::IR_gastoAnexos');

        // MODULO: SOLICITUDES EN DEPOSITO
        $routes->post('SD_getSolicitudes', 'Finanzas::SD_getSolicitudes');

        // MODULO: GENERAR EXTRACTO DE VIATICOS
        $routes->post('GE_generarArchivo', 'Finanzas::GE_generarArchivo');

        // MODULO: SALDOS EN VIATICOS DE COLABORADORES
        $routes->post('SVC_getColaboradores', 'Finanzas::SVC_getColaboradores');

        // MODULO: VIATICOS - HOSPEDAJES
        $routes->post('VH_getViaticosHospedaje', 'Finanzas::VH_getViaticosHospedaje');
        $routes->post('VH_getServiciosReservaciones', 'Finanzas::VH_getServiciosReservaciones');

        // SUBMODULO: SERVICIOS - RESERVACIONES

        $routes->post('SR_ACCIONES_eliminarDetalle', 'Finanzas::SR_ACCIONES_eliminarDetalle');
        $routes->post('SR_ACCIONES_masCotizVuelos', 'Finanzas::SR_ACCIONES_masCotizVuelos');
        $routes->post('SR_ACCIONES_recotizar', 'Finanzas::SR_ACCIONES_recotizar');
        $routes->post('SR_ACCIONES_editarServicio', 'Finanzas::SR_ACCIONES_editarServicio');
        $routes->post('SR_ACCIONES_cancelarServicio', 'Finanzas::SR_ACCIONES_cancelarServicio');
        $routes->post('SR_ACCIONES_reagendar', 'Finanzas::SR_ACCIONES_reagendar');
        $routes->post('SR_ACCIONES_versionServicio', 'Finanzas::SR_ACCIONES_versionServicio');

        // TODOS
        $routes->post('SR_selectCotizacion', 'Finanzas::SR_selectCotizacion');
        $routes->post('SR_eliminarCotizacion', 'Finanzas::SR_eliminarCotizacion');
        $routes->post('SR_verCotizaciones', 'Finanzas::SR_verCotizaciones');
        $routes->post('SR_agregarCotizacion', 'Finanzas::SR_agregarCotizacion');
        $routes->post('SR_verCompras', 'Finanzas::SR_verCompras');

        // VUELOS
        $routes->post('SR_extraCompra', 'Finanzas::SR_extraCompra');
        $routes->post('SR_borrarInfoCompra', 'Finanzas::SR_borrarInfoCompra');
        $routes->post('SR_addFileVuelos', 'Finanzas::SR_addFileVuelos');

        // HOSPEDAJE
        $routes->post('SR_enviarCompra', 'Finanzas::SR_enviarCompra');
        $routes->post('SR_enviarCotizLider', 'Finanzas::SR_enviarCotizLider');

        $routes->post('SR_modals_ServReserv', 'Finanzas::SR_modals_ServReserv');
        $routes->post('SR_modalsEstructura', 'Finanzas::SR_modalsEstructura');
        $routes->post('SR_desgloseEscalas', 'Finanzas::SR_desgloseEscalas');



    });

    $routes->group('project-done', function($routes){
        // MODULO: KNOCKER-TASKS
        $routes->post('KR_T_getTasks', 'Projectdone::KR_T_getTasks');
        $routes->post('KR_T_verHorasDiarias', 'Projectdone::KR_T_verHorasDiarias');
        $routes->post('KR_T_editarEffort', 'Projectdone::KR_T_editarEffort');
        $routes->post('KR_T_getDiaFestivo', 'Projectdone::KR_T_getDiaFestivo');
        $routes->post('KR_T_addEffort', 'Projectdone::KR_T_addEffort');
        $routes->post('KR_T_deleteEffort', 'Projectdone::KR_T_deleteEffort');
        $routes->post('KR_E_getTasks', 'Projectdone::KR_E_getTasks');


        // MODULO: KNOCKER-EFFORT-APROB
        $routes->post('KR_E_getHrsDescansos', 'Projectdone::KR_E_getHrsDescansos');
        $routes->post('KR_E_aprobarEffort', 'Projectdone::KR_E_aprobarEffort');
        $routes->post('KR_E_aprobarEffortAll', 'Projectdone::KR_E_aprobarEffortAll');
        $routes->post('KR_E_rechazarEffort', 'Projectdone::KR_E_rechazarEffort');
        $routes->post('KR_E_updateEffortTask', 'Projectdone::KR_E_updateEffortTask');

    });

    $routes->post('getKnocker','Colaboradores::getKnocker');
});

//GENERAR QR EN ADJUNTOS DE PROJECT DONE
$routes->get('anexosQR/(:any)', 'GenerarQR::generarQR/$1');



/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
