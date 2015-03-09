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

    /************************Methode static*************************** */

	public static function cron() {
            if (!self::deamonRunning()) {
                		self::runDeamon();
            		}
        }
	
	public static function runDeamon() {
        log::add('MQTT', 'info', 'Lancement du démon MQTT');
	$mqttAdress = config::byKey('mqttAdress', 'MQTT', 0);

		if ($mqttAdress == '' ) {
			throw new Exception(__('Plugin non configuré', __FILE__));
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

