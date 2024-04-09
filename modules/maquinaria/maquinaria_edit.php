<?php


$notification = array('class'=>'hide','message'=>'');
$template_vars = array();

$template_path = $site_config['modules'] . $modules_config['maquinaria']['path'] . 'templates/';
$plantilla = new template($template_path . 'maquinaria_edit.html');


$id_admin = $_SESSION['id_admin'];
$id_proveedor = $_SESSION['id_proveedor'];
$id = (isset($_GET['id'])) ? $_GET['id'] : null;
$id_proveedor_planta = (isset($_GET['pp_id'])) ? $_GET['pp_id'] : '';

if($_SERVER['REQUEST_METHOD'] == "POST"){

    if (isset($_POST['borrar'])) {
        $sql = "select id_documentacion from documentaciones where tipo_entidad_documentacion = 4 and id_entidad_asociada_documentacion = {$_POST['id']}";
        $query = $db->query($sql);
        $rs = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach($rs as $values){
            $dirname = 'documentos/'.$values['id_documentacion'];
			array_map('unlink', glob("$dirname/*.*"));
			rmdir($dirname);
        }

        $sql = "delete from documentaciones where tipo_entidad_documentacion = 4 and id_entidad_asociada_documentacion = {$_POST['id']}";
    
        if($db->query($sql)){
            
            $sql_delentidad = "delete from maquinarias where id_maquinaria = {$_POST['id']}";
            if($db->query($sql_delentidad)){
                header('location:?s=proveedor_planta&id='.$id_proveedor_planta);
            }
        }else{
            echo 'Hubo un error al eliminar los documentos';
        }
    }


	//Busqueda de errores
	$cleaner = new cleaner();

	$nombre = $cleaner->clean_data($_POST['nombre'],'text',255,0,array('not_empty'=>true));
	$identificacion = $cleaner->clean_data($_POST['identificacion'],'text',255,0,array('not_empty'=>true));

	$without_errors = (!$nombre['error'] && !$identificacion['error']) ? true : false;
    

}else{

	$array_data = array('nombre_maquinaria'=>'',
						'identificacion_maquinaria'=>''
                    );
	
	if($id){
        $sql = "SELECT * FROM maquinarias WHERE id_maquinaria = {$id}";

        $query = $db->query($sql);
        $rs = $query->fetchAll(PDO::FETCH_ASSOC);

            if(count($rs) > 0){

                foreach($rs as $values){
                    foreach($values as $keyname => $values_data){
                        if(array_key_exists($keyname, $array_data) && !is_null($values_data)){
                            $array_data[$keyname] = $values_data;
                        }
                    }
                }
            }
        
        
    }

    $nombre = array('valor'=>$array_data['nombre_maquinaria'] ,'error'=>true);
    $identificacion = array('valor'=>$array_data['identificacion_maquinaria'],'error'=>true);

    $without_errors = false;

}

if($without_errors){

	$exec_sql = true; // variable de control para saber si se debe o no ejecutar la actualización dependiendo si la subida de imagenes falló

    if($id != 0){
        $sql = "UPDATE maquinarias SET
				        nombre_maquinaria = '{$nombre['valor']}',
				        identificacion_maquinaria = '{$identificacion['valor']}'";
		$sql .= " WHERE id_maquinaria = {$id}";


		$notification['message'] = 'La entidad fue actualizada';

    } else {

        $sql = "INSERT INTO maquinarias 
                        (id_proveedor_planta,
                        nombre_maquinaria,
                        identificacion_maquinaria)
                    VALUES
                        ({$id_proveedor_planta},
                        '{$nombre['valor']}',
						'{$identificacion['valor']}')";

        $notification['message'] = 'Se ha creado exitosamente la entidad';
    }


	$exec_sql = $db->exec($sql);

	if($exec_sql){

			$notification['class'] = '';
            header('location:index.php?s=proveedor_planta&id='.$id_proveedor_planta);

	}else{
			print_r($db->errorInfo());
			$notification['message'] = '¡Atención! Hubo un error al intentar guardar los datos';
			$notification['class'] = 'alert-danger';

	}

}

/*BUCLE TIPO VEHICULO*/
$tipos_maquinarias = array();
$tipos_maquinarias[1] = 'Autoelevador';
$tipos_maquinarias[2] = 'Plataforma';
$tipos_maquinarias[3] = 'Grua';
$tipos_maquinarias[4] = 'Bobcat';
$tipos_maquinarias[7] = 'Otro';

$plantilla->capturar_bucle('TIPO_MAQUINARIA');

foreach($tipos_maquinarias as $i => $valor){

	$selected = ($valor == $nombre['valor']) ? 'selected="selected"' : '';
	$plantilla->reemplazar_contenido_bucle(array('id'=>$i,
												 'nombre'=>$valor,
												 'selected'=>$selected));
}
$plantilla->reemplazar_bucle();


$plantilla->capturar_bucle('EDIT');
if($id){
    $plantilla->reemplazar_contenido_bucle(array());
}
$plantilla->reemplazar_bucle();

$template_vars['titulo_pagina'] = (!empty($id)) ? 'Edicion de Maquinaria ' . $nombre['valor']  : 'Agregar Maquinaria';

$template_vars['id'] = $id;
$template_vars['pp_id'] = $id_proveedor_planta;
$template_vars['nombre'] = $nombre['valor'];
$template_vars['identificacion'] = $identificacion['valor'];


$plantilla->asignar_variables($template_vars);
$body = $plantilla->procesar_plantilla();
