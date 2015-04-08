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

	public static function daemon() {
        	log::add('MQTT', 'info', 'Lancement du démon MQTT');
        	
        	$mosqHost = config::byKey('mqttAdress', 'MQTT', 0);
		$mosqPort = config::byKey('mqttPort', 'MQTT', 0);
        	$mosqId = config::byKey('mqttId', 'MQTT', 0);
        	log::add('MQTT', 'info', 'Paramètres utilisés, Host : ' . $mosqHost . ', Port : ' . $mosqPort . ', ID : ' . $mosqId);
        	if (isset($mosqHost) && isset($mosqPort) && isset($mosqId)) {
        		//https://github.com/mqtt/mqtt.github.io/wiki/mosquitto-php
			$client = new Mosquitto\Client($mosqId);
			$client->onConnect('MQTT::connect');
			$client->onDisconnect('MQTT::disconnect');
			$client->onSubscribe('MQTT::subscribe');
			$client->onMessage('MQTT::message');
			$client->connect($mosqHost, $mosqPort, 60);
			$client->subscribe('#', 1); // Subscribe to all messages
			$client->loopForever();
        	} else {
        		log::add('MQTT', 'info', 'Toutes les paramètres ne sont pas définis');
        	}
    	}
    	
    	public function stopDaemon() { 
			$cron = cron::byClassAndFunction('MQTT', 'daemon');
			$cron->stop();
		}
    	
    	public static function connect( $r ) {
    		log::add('MQTT', 'info', 'Connecté à Mosquitto ' . $r);
    		config::save('status', '1',  'MQTT');
    	}
    	
    	public static function disconnect( ) {
    		log::add('MQTT', 'info', 'Déconnecté de Mosquitto');
    		config::save('status', '0',  'MQTT');
    	}
    	
    	public static function subscribe( ) {
    		log::add('MQTT', 'info', 'Subscribe ');
    	}
    	
    	public static function message( $message ) {
    		log::add('MQTT', 'info', 'Message ' . $message->payload . ' sur ' . $message->topic);
    		$topic = $message->topic;
    		$topicArray = explode("/", $topic);
			$cmdId = end($topicArray);
			$key = count($topicArray) - 1;
			unset($topicArray[$key]);
			$nodeid = (implode($topicArray,'/'));
    		$value = $message->payload;
    		log::add('MQTT', 'info', 'Message : ' . $value . ' pour information : ' . $cmdId . ' sur : ' . $nodeid);
			$elogic = self::byLogicalId($nodeid, 'MQTT');
			if (is_object($elogic)) { 
				$elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
				$elogic->save();
				$cmdlogic = MQTTCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
				if (is_object($cmdlogic)) {
					log::add('MQTT', 'info', 'Cmdlogic existe, pas de creation');
					$cmdlogic->setConfiguration('topic', $topic);
					$cmdlogic->setConfiguration('value', $value);
					$cmdlogic->save();
					$cmdlogic->event($value);
				} else {
					log::add('MQTT', 'info', 'Cmdlogic n existe pas, creation');
					$topCmd = new MQTTCmd();
					$topCmd->setEqLogic_id($elogic->getId());
					$topCmd->setEqType('MQTT');
					$topCmd->setCache('enable', 0);
					$topCmd->setEventOnly(1);
					$topCmd->setIsVisible(1);
					$topCmd->setIsHistorized(0);
					$topCmd->setSubType('string');
					$topCmd->setLogicalId($cmdId);
					$topCmd->setType('info');
					$topCmd->setLogicalId($cmdId);
					$topCmd->setType('info');
					$topCmd->setName( $cmdId );
					$topCmd->setConfiguration('topic', $topic);
					$topCmd->setConfiguration('value', $value);
					$topCmd->save();
					$topCmd->event($value);
				}
			} else {
				log::add('MQTT', 'info', 'Equipement n existe pas, creation');
				$topic = new MQTT();
				$topic->setEqType_name('MQTT');
				$topic->setLogicalId($nodeid);
				$topic->setName($nodeid);
				$topic->setIsEnable(true);
				$topic->save();
				$topic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
				$topic->save();
				$topCmd = new MQTTCmd();
				$topCmd->setEqLogic_id($topic->getId());
				$topCmd->setEqType('MQTT');
				$topCmd->setCache('enable', 0);
				$topCmd->setEventOnly(1);
				$topCmd->setIsVisible(1);
				$topCmd->setIsHistorized(0);
				$topCmd->setSubType('string');
				$topCmd->setLogicalId($cmdId);
				$topCmd->setType('info');
				$topCmd->setName( $cmdId );
				$topCmd->setConfiguration('topic', $topic);
				$topCmd->setConfiguration('value', $value);
				$topCmd->save();
				$topCmd->event($value);
			}
    	}

	public static function publishMosquitto( $subject, $message ) {
		log::add('MQTT', 'info', 'Envoi du message ' . $message . ' vers ' . $subject);
        $mosqHost = config::byKey('mqttAdress', 'MQTT', 0);
		$mosqPort = config::byKey('mqttPort', 'MQTT', 0);
        $mosqId = config::byKey('mqttId', 'MQTT', 0);
		$publish = new Mosquitto\Client();
		$publish->connect($mosqHost, $mosqPort, 60);
		$publish->publish($subject, $message, 1, false);
		$publish->disconnect();
		unset($publish);
	}

	public function getInfo($_infos = '') {
		$return = array();
        $return['nodeId'] = array(
            'value' => $this->getLogicalId(),
            );
        $return['lastActivity'] = array(
            'value' => $this->getConfiguration('updatetime', ''),
            );            
		return $return;
	}
	

    /*     * *********************Methode d'instance************************* */


    /*     * **********************Getteur Setteur*************************** */
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
					$topic = $this->getConfiguration('topic');
					
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
					
					MQTT::publishMosquitto( 
						$topic ,
						$request ); 
					
					$result = $request;

					
					return $result;
			}
			
			return true;
		
    }
	
     

    /*     * **********************Getteur Setteur*************************** */
}

