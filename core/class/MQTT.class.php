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

	public static function deamon() {
        log::add('MQTT', 'info', 'Lancement du démon MQTT');
        $mosqHost = config::byKey('mqttAdress', 'MQTT', 0);
        $mosqPort = config::byKey('mqttPort', 'MQTT', 0);
        $mosqId = config::byKey('mqttId', 'MQTT', 0);
        //https://github.com/mqtt/mqtt.github.io/wiki/mosquitto-php
	$client = new Mosquitto\Client($mosqId);
	$client->onConnect('connect');
	$client->onDisconnect('disconnect');
	$client->onSubscribe('subscribe');
	$client->onMessage('message');
	$client->connect($mosqHost, $mosqPort, 60);
	$client->subscribe('#', 1); // Subscribe to all messages

	$client->loopForever();
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


			
		}

	
		
	
	}


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

