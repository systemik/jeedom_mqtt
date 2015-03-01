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
    
    if (config::byKey('jeeNetwork::mode') == 'slave') {
		echo '<div class="alert alert-danger"><b>{{La configuration du plugin se fait uniquement sur le Jeedom principal}}</b></div>';
	} else {
    
    $statusGateway = config::byKey('gateway','MQTT');
    $nodeHost = config::byKey('nodeHost','MQTT');
    if (!$nodeHost || $nodeHost == "master" || $nodeHost == "network" || $nodeHost == "none" ) {
		$statusNode = MQTT::deamonRunning();
	} else {
		$jeeNetwork = jeeNetwork::byId($nodeHost);
		$jsonrpc = $jeeNetwork->getJsonRpc();
		if (!$jsonrpc->sendRequest('deamonRunning', array('plugin' => 'MQTT'))) {
			throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
		}
		$statusNode = $jsonrpc->getResult();
	}
	if (!$statusGateway || $statusNode != 'ok' ) {
		echo '<div class="alert alert-danger"><b>{{Connexion}} : </b>';
	} else {
		echo '<div class="alert alert-success"><b>{{Connexion}} : </b>';
	}
    if ($statusNode != 'ok' ) {
		echo '{{Le service MQTT (nodejs) n\'est pas démarré}} ';
	} else {
		echo '{{Le service MQTT (nodejs) est en marche}} ';
	}
	if (!$statusGateway) {
		echo '{{et la Gateway est non connectée}}</div>';
	} else {
		$libVer = config::byKey('gateLib','MQTT');
		if ($libVer=='') {
			$libVer = '{{inconnue}}';
		}
		echo '{{et la Gateway est connectée (version MQTT }}' . $libVer . ')</div>';
	}
	

    
    echo '    <div id=globalSensors class="form-group">
            <label class="col-lg-4 control-label">{{Connexion à la Gateway : }}</label>
            <div class="col-lg-4">
				<select class="configKey form-control" id="select_mode" data-l1key="nodeHost">
					<option value="none">{{Aucun}}</option>
                    <option value="master">{{Jeedom maître}}</option>';
                    
                    foreach (jeeNetwork::byPlugin('MQTT') as $jeeNetwork) {
						echo '<option value="' . $jeeNetwork->getId(). '">{{Jeedom esclave}} ' . $jeeNetwork->getName() . ' (' . $jeeNetwork->getId(). ')</option>';
					}
                    
    echo'                <option value="network">{{Gateway Réseau}}</option>
                </select>
            </div>
        </div>
        
       
<div id="div_local" class="form-group">
            <label class="col-lg-4 control-label">{{Adresse de la Gateway}} :</label>
            <div class="col-lg-4">
                <select id="select_port" style="margin-top:5px;display:none" class="configKey form-control" data-l1key="nodeGateway">';
                    
                    foreach (jeedom::getUsbMapping() as $name => $value) {
                        echo '<option value="' . $name . '">' . $name . ' (' . $value . ')</option>';
                    }
					echo '<option value="serie">{{Port série non listé (port manuel)}}</option>';
                    
       echo '         </select>
				
				<input id="manual_port" class="configKey form-control" data-l1key="nodeAdress" style="margin-top:5px;display:none" placeholder="/dev/ttyS0 {{ou}} 192.168.1.1:5003"/>
				            </div>
        </div>
		
		<div id="div_inclusion" class="form-group">		
		<label class="col-lg-4 control-label" >{{Inclusion}} :</label>
			<div class="col-lg-2">
			<select id="select_include" class="configKey form-control" data-l1key="include_mode">
                    	<option value="on">{{Activée}}</option>	
                    	<option value="off">{{Désactivée}}</option>
                    	</select>
			</div>
		</div>
				
				<div class="alert alert-success"><b>{{Sauvegarde}} : </b>{{La sauvegarde de la configuration redémarre automatiquement le service, il faut attendre environ 1 minute pour qu\'il soit joignable}}</div>' ;
			}	
				?>
				
				<script>
				$( "#select_port" ).change(function() {
					$( "#select_port option:selected" ).each(function() {
						if($( this ).val() == "serie"){
						 $("#manual_port").show();
						 $("#manual_port").attr("placeholder", "ex : /dev/ttyS0").blur();
						}
						else {
							$("#manual_port").hide();
						}
						});
					
				});			
				$( "#select_mode" ).change(function() {
					$( "#select_mode option:selected" ).each(function() {
						if($( this ).val() == "network"){
							$("#manual_port").show();
							$("#manual_port").attr("placeholder", "ex : 192.168.1.1:5003").blur();
							$("#select_port").hide();
						}
						else if($( this ).val() == "none"){
							$("#select_port").hide();
							$("#manual_port").hide();
						}
						else{
							$("#select_port").show();							
							$("#manual_port").hide();
						    $.ajax({// fonction permettant de faire de l'ajax
						        type: "POST", // méthode de transmission des données au fichier php
						        url: "plugins/MQTT/core/ajax/MQTT.ajax.php", // url du fichier php
						        data: {
						            action: "getUSB",
						            id: $( this ).val(),
						        },
						        dataType: 'json',
						        global: false,
						        error: function (request, status, error) {
						            handleAjaxError(request, status, error);
						        },
						        success: function (data) { // si l'appel a bien fonctionné
						            if (data.state != 'ok') {
						                $('#div_alert').showAlert({message: data.result, level: 'danger'});
						                return;
						            }
									var options = '';
									for (var i in data.result) {
										options += '<option value="'+i+'">'+i+'('+data.result[i]+')</option>';
									}
									if (options == '') {
										$("#manual_port").show();
										$("#manual_port").attr("placeholder", "ex : /dev/ttyS0").blur();
									}
									options += '<option value="serie">{{Port série non listé (port manuel)}}</option>';
									$("#select_port").html(options);
									//$("#select_port").value('serie');
									$.ajax({// fonction permettant de faire de l'ajax
								        type: "POST", // méthode de transmission des données au fichier php
								        url: "plugins/MQTT/core/ajax/MQTT.ajax.php", // url du fichier php
								        data: {
								            action: "confUSB",
								        },
								        dataType: 'json',
								        global: false,
								        error: function (request, status, error) {
								            handleAjaxError(request, status, error);
								        },
								        success: function (data) { // si l'appel a bien fonctionné
								            if (data.state != 'ok') {
								                $('#div_alert').showAlert({message: data.result, level: 'danger'});
								                return;
								            }
											$("#select_port").value(data.result);
								        }
								    });
						        }
						    });		
						}
					});						
				});
				
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
