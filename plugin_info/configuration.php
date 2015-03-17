<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>


<form class="form-horizontal">
    <fieldset>
    <?php
    $statusMQTT = config::byKey('status','MQTT');
	if ($statusMQTT != '1' ) {
		echo '<div class="alert alert-danger"><b>{{Connexion}} : </b> {{Jeedom n\'est pas connecté à Mosquitto}}</div>';
	} else {
		echo '<div class="alert alert-success"><b>{{Connexion}} : </b> {{Jeedom est connecté à Mosquitto}}</div>';
	}
	?>

    
    <div class="form-group">
            <label class="col-lg-4 control-label">{{IP de Mosquitto : }}</label>
            <div class="col-lg-4">
				<input id="mosquitto_por" class="configKey form-control" data-l1key="mqttAdress" style="margin-top:5px" placeholder="127.0.0.1"/>
            </div>
        </div>
    <div class="form-group">
            <label class="col-lg-4 control-label">{{Port de Mosquitto : }}</label>
            <div class="col-lg-4">
				<input id="mosquitto_por" class="configKey form-control" data-l1key="mqttPort" style="margin-top:5px" placeholder="1883"/>
            </div>
        </div>
    <div class="form-group">
            <label class="col-lg-4 control-label">{{Identifiant de Connexion : }}</label>
            <div class="col-lg-4">
				<input id="mosquitto_por" class="configKey form-control" data-l1key="mqttId" style="margin-top:5px" placeholder="Jeedom"/>
            </div>
        </div>              
				
				<div class="alert alert-success"><b>{{Sauvegarde}} : </b>{{La sauvegarde de la configuration redémarre automatiquement le service, il faut attendre environ 1 minute pour qu'il soit joignable}}</div>' ;
				
			<script>	
							
	     function MQTT_postSaveConfiguration(){
             $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/MQTT/core/ajax/MQTT.ajax.php", // url du fichier php
            data: {
                action: "postSave",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=MQTT]').click();
        }
    });				
			
		}			
			
				
			</script>

    </fieldset>
</form>
