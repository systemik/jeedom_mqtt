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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';


class MQTT extends eqLogic {
    /*     * *************************Attributs****************************** */
     	public static function pull($_options) {

		}

	public static $_dico = 
			array(
			'C' => array( 
				0=>'Présentation',
				1=>'Paramétrage',
				2=>'Récupération',
				3=>'Interne',
				4=>'OTA',
				),
			'I' => array( 
				'I_BATTERY_LEVEL'=> 0,
				'I_TIME'=> 1,
				'I_VERSION'=> 2,
				'I_ID_REQUEST'=> 3,
				'I_ID_RESPONSE'=> 4,
				'I_INCLUSION_MODE'=> 5,
				'I_CONFIG'=> 6,
				'I_PING'=> 7,
				'I_PING_ACK'=> 8,
				'I_LOG_MESSAGE'=> 9,
				'I_CHILDREN'=> 10,
				'I_SKETCH_NAME'=> 11,
				'I_SKETCH_VERSION'=> 12,
				'I_REBOOT'=> 13,
			 ),
			'N' => array( // Type de donnée
				0=>'Température',
				1=>'Humidité',
				2=>'Relais',
				3=>'Dimmer',
				4=>'Pression',
				5=>'Prévision',
				6=>'Niveau de pluie',
				7=>'Débit de pluie',
				8=>'Vitesse de vent',
				9=>'Rafale de vent',
				10 =>'Direction du vent',
				11 =>'UV',
				12 =>'Poids',
				13 =>'Distance',
				14 =>'Impédance',
				15 =>'Sécurité activée',
				16=>'Activation',
				17=>'Puissance',
				18=>'KWh',
				19=>'Activation Scène',
				20=>'Désactivation Scène',
				21=>'Mode de chauffage',
				22=>'Radiateur',
				23=>'Niveau de Lumière',
				24=>'Variable1',
				25=>'Variable2',
				26=>'Variable3',
				27=>'Variable4',
				28=>'Virtuel',
				29=>'Lever',
				30=>'Descente',
				31=>'Arrêt',
				32=>'Envoi IR',
				33=>'Réception IR',
				34=>'Débit Eau',
				35=>'Volume Eau',
				36=>'Verrou',
				37=>'Poussière',
				38=>'Voltage',
				39=>'Courant',
				97=>'Connexion',
				98=>'Inactivité',
				99=>'Batterie'
			 ),
			 'S' => array( // 'S_TYPE', 'Nom', 'widget', 'variable, 'unité', 'historique', 'affichage'
				0 => array('S_DOOR','Ouverture','doorIMG','binary','','','1',),
				1 => array('S_MOTION','Mouvement','Intrusions','binary','','','1',),
				2 => array('S_SMOKE','Fumée','SmokeSensorIMG','binary','','','1',),
				3 => array('S_LIGHT','Relais','lightIMG','binary','','','',),
				4 => array('S_DIMMER','Variateur','lightIMG','numeric','%','','',),
				5 => array('S_COVER','Store','StoreIMG','binary','','','1',),
				6 => array('S_TEMP','Température','tempIMG','numeric','°C','1','1',),
				7 => array('S_HUM','Humidité','HydroIMG','numeric','%','1','1',),
				8 => array('S_BARO','Baromètre','tile','string','Pa','1','1',),
				9 => array('S_WIND','Vent','Anenometre','numeric','','','1',),
				10 => array('S_RAIN','Pluie','badge','numeric','cm','1','1',),
				11 => array('S_UV','UV','badge','numeric','uvi','1','1',),
				12 => array('S_WEIGHT','Poids','badge','numeric','kg','1','1',),
				13 => array('S_POWER','Energie','badge','numeric','','1','1',),
				14 => array('S_HEATER','Radiateur','chauffage','binary','','','1',),
				15 => array('S_DISTANCE','Distance','badge','numeric','cm','','1',),
				16 => array('S_LIGHT_LEVEL','Luminosité','luminositeIMG','numeric','','','1',),
				17 => array('S_ARDUINO_NODE','Noeud Arduino','tile','string','','','1',),
				18 => array('S_ARDUINO_RELAY','Noeud Répéteur','tile','string','','','1',),
				19 => array('S_LOCK','Verrou','lock','binary','','','1',),
				20 => array('S_IR','Infrarouge','tile','string','','','1',),
				21 => array('S_WATER','Eau','badge','numeric','','1','1',),
				22 => array('S_AIR_QUALITY','Qualité d Air','badge','numeric','','1','1',),
				23 => array('S_CUSTOM','Custom','tile','string','','','1',),
				24 => array('S_DUST','Poussière','badge','numeric','mm','1','1',),
				25 => array('S_SCENE_CONTROLLER','Controleur de Scène','alert','binary','','','1',),
				97 => array('GATEWAY','Connexion avec Gateway','tile','string','','','',),
				98 => array('INNA_NODE','Inactivité des Nodes','tile','string','','','',),
				99 => array('BATTERIE','Etat de la batterie','Sky-progressBar','numeric','%','','1',)
			)

			);

    /************************Methode static*************************** */

	public static function cron() {
        if (config::byKey('nodeRun', 'MQTT', 0) != '0') {
            if (!self::deamonRunning()) {
                		self::runDeamon();
            		}
        }
    }
	
	public static function runDeamon() {
        log::add('MQTT', 'info', 'Lancement du démon MQTT');
        
        if (config::byKey('nodeRun', 'MQTT', 0) == '1') { //je suis le maitre
			$nodeHost = config::byKey('nodeHost', 'MQTT');
			$nodeGateway = config::byKey('nodeGateway', 'MQTT');
			$nodeAdress = config::byKey('nodeAdress', 'MQTT');
			$include_mode = config::byKey('include_mode', 'MQTT');
			log::add('MQTT','info','Récupération de la configuration : Host ' . $nodeHost . ' Port ' . $nodeGateway . ' Serie ' . $nodeSerial . ' Network ' . $nodeNetwork . ' Inclusion ' . $include_mode);
		} else if (config::byKey('nodeRun', 'MQTT', 0) == '2') { //je suis esclave
			$jsonrpc = jeeNetwork::getJsonRpcMaster();
			$jsonrpc->sendRequest('getConfig',array('plugin' => 'MQTT'));
			$result = $jsonrpc->getResult();
			$nodeGateway = $result['nodeGateway'];
			$nodeAdress = $result['nodeAdress'];
			$include_mode = $result['include_mode'];	
			log::add('MQTT','info','Récupération de la configuration : Port ' . $nodeGateway . ' Serie ' . $nodeSerial . ' Network ' . $nodeNetwork . ' Inclusion ' . $include_mode);		
		} else {
			return false;
		}
		
		$gateMode = "Serial";
		$gatePort = "0";
		$inclusion = $include_mode;
        
        if($nodeHost == "network") {
			$gateMode = "Network";
			$netAd = explode(":",$nodeAdress);
			$usbGateway = $netAd[0];
			$gatePort = $netAd[1];	
		} else {
			if($nodeGateway == "serie") {
				$usbGateway = $nodeAdress;
			} else {
				$usbGateway = jeedom::getUsbMapping($nodeGateway);
			}
		}
		
		
		log::add('MQTT','info','Configuration utilisée : Gateway ' . $usbGateway . ' Mode ' . $gateMode . ' Port ' . $gatePort . ' Inclusion ' . $inclusion);		
		
		if ($usbGateway == '' ) {
			throw new Exception(__('Le port : ', __FILE__) . $port . __(' n\'existe pas', __FILE__));
		}
		
		if (config::byKey('nodeRun', 'MQTT', 0) == '2') { //Je suis l'esclave
			$url  = config::byKey('jeeNetwork::master::ip') . '/core/api/jeeApi.php?api=' . config::byKey('jeeNetwork::master::apikey');
		} else {
			if (!config::byKey('internalPort')) {
				$url = 'http://127.0.0.1' . config::byKey('internalComplement') . '/core/api/jeeApi.php?api=' . config::byKey('api');
			} else {
				$url = 'http://127.0.0.1:' . config::byKey('internalPort') . config::byKey('internalComplement') . '/core/api/jeeApi.php?api=' . config::byKey('api');
			}
		}
	
	$sensor_path = realpath(dirname(__FILE__) . '/../../node');	
        $cmd = 'nice -n 19 nodejs ' . $sensor_path . '/MQTT.js ' . $url . ' ' . $usbGateway . ' ' . $gateMode . ' ' . $gatePort . ' ' . $inclusion;
		
        log::add('MQTT', 'info', 'Lancement démon MQTT : ' . $cmd);
        $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('MQTT') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('MQTT', 'error', $result);
            return false;
        }
        sleep(2);
        if (!self::deamonRunning()) {
            sleep(10);
            if (!self::deamonRunning()) {
                log::add('MQTT', 'error', '{{Impossible de lancer le démon MQTT, vérifiez le port}}', 'unableStartDeamon');
                return false;
            }
        }
        message::removeAll('MQTT', 'unableStartDeamon');
        log::add('MQTT', 'info', 'Démon MQTT lancé');
    }
	
	
	public static function deamonRunning() {
   
		$pid = trim( shell_exec ('ps ax | grep "MQTT/node/MQTT.js" | grep -v "grep" | wc -l') );
		
		if ($pid != '' && $pid != '0') {
                return true;
        }

        return false;
    }

    public static function stopDeamon() {
        if (!self::deamonRunning())
			return true;
			
		$pid = trim(shell_exec('ps ax | grep "MQTT/node/MQTT.js" | grep -v "grep" | awk \'{print $1}\''));
		if ( $pid == '' ){
			return true;
		}
		
        exec('kill ' . $pid);
        log::add('MQTT', 'info', 'Arrêt du service MQTT');
        $check = self::deamonRunning();
        $retry = 0;
        while ($check) {
            $check = self::deamonRunning();
            $retry++;
            if ($retry > 10) {
                $check = false;
            } else {
                sleep(1);
            }
        }
        exec('kill -9 ' . $pid);
        $check = self::deamonRunning();
        $retry = 0;
        while ($check) {
            $check = self::deamonRunning();
            $retry++;
            if ($retry > 10) {
                $check = false;
            } else {
                sleep(1);
            }
        }
		config::save('gateway', '0',  'MQTT');

        return self::deamonRunning();
    }
    
 	public static function saveConfig( $config ) {
		config::save('nodeRun', $config,  'MQTT');
	    log::add('MQTT','info','Sauvegarde de la configuration' . $config);
    }   
	
	/**
	* retourne le numéro du prochain mysensorid dispo
	*/
	protected static function getNextSensorId() {
	
		$max = 0;

		//recherche dans tous les eqlogic 
		foreach( self::byType( 'MQTT' ) as $elogic) {
		
			if ($max <  $elogic->getConfiguration('nodeid') ) {
				$max = $elogic->getConfiguration('nodeid');
			}
		}
		return $max + 1;
	}
	
	
	public static function sendToController( $destination, $sensor, $command, $acknowledge, $type, $payload ) {
		$nodeHost = config::byKey('nodeHost', 'MQTT', 0);
		if ($nodeHost != 'master' && $nodeHost != 'network') {
			$jeeSlave = jeeNetwork::byId($nodeHost);
			$urlNode = getIpFromString($jeeSlave->getIp());
		} else {
			$urlNode = "127.0.0.1";
		}
		log::add('MQTT', 'info', $urlNode);
		$msg = $destination . ";" . $sensor . ";" . $command . ";" . $acknowledge . ";" .$type . ";" . $payload;
		log::add('MQTT', 'info', $msg);
		$fp = fsockopen($urlNode, 8019, $errno, $errstr);
		   if (!$fp) {
		   echo "ERROR: $errno - $errstr<br />\n";
		} else {
	
		   fwrite($fp, $msg);
		   fclose($fp);
		}

	}
	
	public static function getValue() {
		$nodeid = init('id');
		$sensor = init('sensor');
		$type = init('donnees');
		$cmdId = 'Sensor'.$sensor;
		$elogic = self::byLogicalId($nodeid, 'MQTT');
		if (is_object($elogic)) { 
			$elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
			$elogic->save();
			$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
			if (is_object($cmdlogic)) {
				if ($cmdlogic->getConfiguration('sensorCategory') == "23" && $type == "28") {
					$idvirt = str_replace("#","",$cmdlogic->getConfiguration('value'));
					$cmdvirt = cmd::byId($idvirt);
					
					if (is_object($cmdvirt)) {
						echo $cmdvirt->execCmd();
						log::add('MQTT', 'info', 'Valeur virtuelle transmise');
					} else {
						echo "Virtuel KO";
						//echo $cmdlogic->getCmdValue();
						log::add('MQTT', 'info', 'Valeur virtuelle non définie' . $cmdlogic->getConfiguration('value'));
					}
				} else {
					echo $cmdlogic->execCmd();
					log::add('MQTT', 'info', 'Valeur de capteur transmise');
				}
			}else{
				echo "Valeur KO";
				log::add('MQTT', 'info', 'Valeur non définie');
			}
			$cmdlogic->event($value);
			
		}
	}
	
	public static function saveValue() {
		$nodeid = init('id');
		$sensor = init('sensor');
		$value = init('value');
		$type = init('donnees');
		$daType = $type;
		$cmdId = 'Sensor'.$sensor;
		$elogic = self::byLogicalId($nodeid, 'MQTT');
		if (is_object($elogic)) { 
			$elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
			$elogic->save();
			$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
			if (is_object($cmdlogic)) {
				$cmdlogic->setConfiguration('value', $value);
				$cmdlogic->setConfiguration('sensorType', $daType);
				$cmdlogic->save();
				$cmdlogic->event($value);
			}
		}
	}
	
	public static function saveBatteryLevel() {
		$nodeid = init('id');
		$value = init('value');
		$elogic = self::byLogicalId($nodeid, 'MQTT');
		if (is_object($elogic)) { 
			$elogic->setConfiguration('battery',$value);
			$elogic->batteryStatus($value);
			$elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
			$elogic->save();			
		}
	
	}
	
	public static function saveSketchNameEvent() {
		$nodeid = init('id');
		$value = init('value');
		$elogic = self::byLogicalId($nodeid, 'MQTT');
		if (is_object($elogic)) {
				if ( $elogic->getConfiguration('SketchName', '') != $value ) {
					$elogic->setConfiguration('SketchName',$value);
					//si le sketch a changé sur le node, alors on set le nom avec le sketch
					$elogic->setName($value.' - '.$nodeid);					
					$elogic->save();
				}
		}
		else {
				$mys = new MQTT();
				$mys->setEqType_name('MQTT');
				$mys->setLogicalId($nodeid);
				$mys->setConfiguration('nodeid', $nodeid);
				$mys->setConfiguration('SketchName',$value);
				$mys->setName($value.' - '.$nodeid);
				$mys->setIsEnable(true);
				$mys->save();
		}
	}
	
	public static function saveGateway() {
		$status = init('status');
		config::save('gateway', $status,  'MQTT');
	}	

	public static function saveSketchVersion() {
		$nodeid = init('id');
		$value = init('value');
		$elogic = self::byLogicalId($nodeid, 'MQTT');
		sleep(1);
		if (is_object($elogic)) { 
			if ( $elogic->getConfiguration('SketchVersion', '') != $value ) {
				$elogic->setConfiguration('SketchVersion',$value);
				$elogic->save();
			}
		}
	}
	
	public static function saveLibVersion() {
		sleep(1);
		$nodeid = init('id');
		$value = init('value');
		if ($nodeid == '0') {
			config::save('gateLib', $value,  'MQTT');
			log::add('MQTT', 'info', 'Gateway Lib ' . $value . config::byKey('gateLib','MQTT'));			
		}
		$elogic = self::byLogicalId($nodeid, 'MQTT');
		if (is_object($elogic)) { 
			if ( $elogic->getConfiguration('LibVersion', '') != $value ) {
				$elogic->setConfiguration('LibVersion',$value);
				$elogic->save();
			}
		}
	}	
	
	public static function saveSensor() {
		sleep(1);
		$nodeid = init('id');
		$value = init('value');
		$sensor = init('sensor');
		//exemple : 0 => array('S_DOOR','Ouverture','door','binary','','','1',),
		$name = self::$_dico['S'][$value][1];
		if ($name == false ) {
			$name = 'UNKNOWN';
		}
		$unite = self::$_dico['S'][$value][4];
		$sType = $value;
		$info = self::$_dico['S'][$value][3];
		$widget = self::$_dico['S'][$value][2];
		$history = self::$_dico['S'][$value][5];
		$visible = self::$_dico['S'][$value][6];
		$cmdId = 'Sensor'.$sensor;
		$elogic = self::byLogicalId($nodeid, 'MQTT');
		if (is_object($elogic)) {
			$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
			if (is_object($cmdlogic)) {
				if ( $cmdlogic->getConfiguration('sensorCategory', '') != $sType ) {
					$cmdlogic->setConfiguration('sensorCategory', $sType);
					$cmdlogic->save();
				}
			}
			else {
				$mysCmd = new MQTTCmd();
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);
				$mysCmd->setCache('enable', 0);
				$mysCmd->setEventOnly(1);
				$mysCmd->setConfiguration('sensorCategory', $sType);
				$mysCmd->setConfiguration('sensor', $sensor);
				$mysCmd->setEqLogic_id($elogic->getId());
				$mysCmd->setEqType('MQTT');
				$mysCmd->setLogicalId($cmdId);
				$mysCmd->setType('info');
				$mysCmd->setSubType($info);
				$mysCmd->setName( $name . " " . $sensor );
				$mysCmd->setUnite( $unite );
				$mysCmd->setIsVisible($visible);
				if ($info != 'string') {
					$mysCmd->setIsHistorized($history);
				}
				$mysCmd->setTemplate("mobile",$widget );
				$mysCmd->setTemplate("dashboard",$widget );
				$mysCmd->save();
			}
			if ($name == 'Relais') {
				$relonId = 'Relais'.$sensor.'On';
				$reloffId = 'Relais'.$sensor.'Off';
				$onlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$relonId);
				$offlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$reloffId);
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				$cmId = $cmdlogic->getId();
				if (!is_object($offlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '0');
					$mysCmd->setConfiguration('cmdtype', '2');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($reloffId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setTemplate("dashboard","light" );
					$mysCmd->setTemplate("mobile","light" );
					$mysCmd->setDisplay('parameters',array('displayName' => 1));
					$mysCmd->setName( "Off ". $sensor );
					$mysCmd->save();
				}
				if (!is_object($onlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '1');
					$mysCmd->setConfiguration('cmdtype', '2');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($relonId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setTemplate("dashboard","light" );
					$mysCmd->setTemplate("mobile","light" );
					$mysCmd->setDisplay('parameters',array('displayName' => 1));
					$mysCmd->setName( "On " . $sensor );
					$mysCmd->save();
				}

			}
			if ($name == 'Verrou') {
				$relonId = 'Verrou'.$sensor.'On';
				$reloffId = 'Verrou'.$sensor.'Off';
				$onlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$relonId);
				$offlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$reloffId);
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				$cmId = $cmdlogic->getId();
				if (!is_object($offlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '1');
					$mysCmd->setConfiguration('cmdtype', '36');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($reloffId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setTemplate("dashboard","lock" );
					$mysCmd->setTemplate("mobile","lock" );
					$mysCmd->setDisplay('parameters',array('displayName' => 1));
					$mysCmd->setName( "Off ". $sensor );
					$mysCmd->save();
				}
				if (!is_object($onlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '0');
					$mysCmd->setConfiguration('cmdtype', '36');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($relonId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setTemplate("dashboard","lock" );
					$mysCmd->setTemplate("mobile","lock" );
					$mysCmd->setDisplay('parameters',array('displayName' => 1));
					$mysCmd->setName( "On " . $sensor );
					$mysCmd->save();
				}

			}			
			if ($name == 'Variateur') {
				$dimmerId = 'Dimmer'.$sensor;
				$dimlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$dimmerId);
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				$cmId = $cmdlogic->getId();
				if (!is_object($dimlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '#slider#');
					$mysCmd->setConfiguration('cmdtype', '3');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($dimmerId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('slider');
					$mysCmd->setValue($cmId);
					$mysCmd->setTemplate("dashboard","light" );
					$mysCmd->setTemplate("mobile","light" );
					$mysCmd->setDisplay('parameters',array('displayName' => 1));
					$mysCmd->setName( "Set " . $sensor );
					$mysCmd->save();
				}
				$relonId = 'Dimmer'.$sensor.'On';
				$reloffId = 'Dimmer'.$sensor.'Off';
				$onlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$relonId);
				$offlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$reloffId);
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				$cmId = $cmdlogic->getId();
				if (!is_object($offlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '0');
					$mysCmd->setConfiguration('cmdtype', '3');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($reloffId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "Off Dimmer ". $sensor );
					$mysCmd->save();
				}
				if (!is_object($onlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '100');
					$mysCmd->setConfiguration('cmdtype', '3');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($relonId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "On Dimmer " . $sensor );
					$mysCmd->save();
				}
			}
			if ($name == 'Inrarouge') {
				$dimmerId = 'EnvoiIR'.$sensor;
				$dimlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$dimmerId);
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				$cmId = $cmdlogic->getId();
				if (!is_object($dimlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '');
					$mysCmd->setConfiguration('cmdtype', '32');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($dimmerId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('message');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "Envoi IR " . $sensor );
					$mysCmd->save();
				}				
			}
			if ($name == 'Radiateur') {
				$relonId = 'Radiateur'.$sensor.'On';
				$reloffId = 'Radiateur'.$sensor.'Off';
				$onlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$relonId);
				$offlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$reloffId);
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				$cmId = $cmdlogic->getId();
				if (!is_object($offlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '0');
					$mysCmd->setConfiguration('cmdtype', '22');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($reloffId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "Off Radiateur ". $sensor );
					$mysCmd->save();
				}
				if (!is_object($onlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '1');
					$mysCmd->setConfiguration('cmdtype', '22');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($relonId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "On Radiateur " . $sensor );
					$mysCmd->save();
				}				
			}
			if ($name == 'Store') {
				$relonId = 'Store'.$sensor.'Up';
				$reloffId = 'Store'.$sensor.'Down';
				$relstopId = 'Store'.$sensor.'Stop';
				$onlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$relonId);
				$offlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$reloffId);
				$stoplogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$relstopId);
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				$cmId = $cmdlogic->getId();
				if (!is_object($offlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '1');
					$mysCmd->setConfiguration('cmdtype', '29');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($reloffId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "Relever Store ". $sensor );
					$mysCmd->save();
				}
				if (!is_object($onlogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '1');
					$mysCmd->setConfiguration('cmdtype', '30');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($relonId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "Baisser Store " . $sensor );
					$mysCmd->save();
				}	
				if (!is_object($stoplogic)) {
					$mysCmd = new MQTTCmd();
					$mysCmd->setEventOnly(0);
				$cmds = $elogic->getCmd();
				$order = count($cmds);
				$mysCmd->setOrder($order);					
					$mysCmd->setConfiguration('cmdCommande', '1');
					$mysCmd->setConfiguration('request', '1');
					$mysCmd->setConfiguration('cmdtype', '31');
					$mysCmd->setConfiguration('sensor', $sensor);
					$mysCmd->setEqLogic_id($elogic->getId());
					$mysCmd->setEqType('MQTT');
					$mysCmd->setLogicalId($relonId);
					$mysCmd->setType('action');
					$mysCmd->setSubType('other');
					$mysCmd->setValue($cmId);
					$mysCmd->setName( "Arrêt Store " . $sensor );
					$mysCmd->save();
				}
			}
			
		}

	
		
	
	}


public function getInfo($_infos = '') {
    $return = array();
        $return['nodeId'] = array(
            'value' => $this->getConfiguration('nodeid', ''),
            );
        $return['libVersion'] = array(
            'value' => $this->getConfiguration('LibVersion', ''),
            );
        $return['sketchNom'] = array(
            'value' => $this->getConfiguration('SketchName', ''),
            );
        $return['sketchVersion'] = array(
            'value' => $this->getConfiguration('SketchVersion', ''),
            );
        $batterie = $this->getConfiguration('battery', '');
        	if ($batterie == '') {
			$rebatterie = 'secteur';
		}
		else {
			$rebatterie = $batterie . ' %';
		}
	$return['perBatterie'] = array(
       	    'value' => $rebatterie,
            );
        $return['lastActivity'] = array(
            'value' => $this->getConfiguration('updatetime', ''),
            );            
return $return;
}

	
    public static function event() {

		$messageType = init('messagetype');
		switch ($messageType) {
		
			case 'saveValue' : self::saveValue(); break;
			case 'saveSketchName' : self::saveSketchNameEvent(); break;
			case 'saveSketchVersion' : self::saveSketchVersion(); break;
			case 'saveLibVersion' : self::saveLibVersion(); break;
			case 'saveSensor' : self::saveSensor(); break;
			case 'saveBatteryLevel' : self::saveBatteryLevel(); break;
			case 'saveGateway' : self::saveGateway(); break;
			case 'getValue' : self::getValue(); break;
		
		}
		
	
	/*
        $cmd = MQTTCmd::byId(init('id'));
        if (!is_object($cmd)) {
            throw new Exception('Commande ID virtuel inconnu : ' . init('id'));
        }
        $value = init('value');
        $virtualCmd = virtualCmd::byId($cmd->getConfiguration('infoId'));
        if (is_object($virtualCmd)) {
            if ($virtualCmd->getEqLogic()->getEqType_name() != 'virtual') {
                throw new Exception(__('La cible de la commande virtuel n\'est pas un équipement de type virtuel', __FILE__));
            }
            if ($this->getSubType() != 'slider' && $this->getSubType() != 'color') {
                $value = $this->getConfiguration('value');
            }
            $virtualCmd->setConfiguration('value', $value);
            $virtualCmd->save();
        } else {
            $cmd->setConfiguration('value', $value);
            $cmd->save();
        }
        $cmd->event($value);
		
    }*/

    /*     * *********************Methode d'instance************************* */


    /*     * **********************Getteur Setteur*************************** */
	}
	
}

class MQTTCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

	
	public function execute($_options = null) {


            switch ($this->getType()) {
			
				case 'info' : 
					return $this->getConfiguration('value');
					break;
					
                case 'action' :
					$request = $this->getConfiguration('request');
					
                    switch ($this->getSubType()) {
                        case 'slider':
                            $request = str_replace('#slider#', $_options['slider'], $request);
                            break;
                        case 'color':
                            $request = str_replace('#color#', $_options['color'], $request);
                            break;
                        case 'message':
							if ($_options != null)  {
								
								$replace = array('#title#', '#message#');
								$replaceBy = array($_options['title'], $_options['message']);
								if ( $_options['title'] == '') {
									throw new Exception(__('Le sujet ne peuvent être vide', __FILE__));
								}
								$request = str_replace($replace, $replaceBy, $request);
							
							}
							else	
							 $request = 1;
						
                            break;
						default : $request == null ?  1 : $request;
						
					}
						
					$eqLogic = $this->getEqLogic();
					
					MQTT::sendToController( 
						$eqLogic->getConfiguration('nodeid') ,
						$this->getConfiguration('sensor'),
						$this->getConfiguration('cmdCommande'),
						1,
						$this->getConfiguration('cmdtype'),
						$request ); 
					
					$result = $request;

					
					return $result;
			}
			
			return true;
		
    }
	
     

    /*     * **********************Getteur Setteur*************************** */
}

