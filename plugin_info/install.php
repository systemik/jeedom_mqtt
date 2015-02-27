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

/*
function install() {
    if (mySensors::deamonRunning()) {
        mySensors::stopDeamon();
    }
}

function update() {
    if (mySensors::deamonRunning()) {
        mySensors::stopDeamon();
    }
}

function remove() {
    if (mySensors::deamonRunning()) {
        mySensors::stopDeamon();
    }
}

*/

function mySensors_install() {
    $cron = cron::byClassAndFunction('mySensors', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('mySensors');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('*/15 * * * *');
        $cron->save();
        $sensor_path = realpath(dirname(__FILE__) . '/../../node');
        exec('cd ' . $sensor_path . '; npm install');
        exec('sudo apt-get -y install avrdude');
    }
}

function mySensors_update() {
    $cron = cron::byClassAndFunction('mySensors', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('mySensors');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('*/15 * * * *');
        $cron->save();
    }
    if (method_exists('mySensors', 'stopDeamon')) {
        mySensors::stopDeamon();
    }
    $cron->stop();
    $sensor_path = dirname(__FILE__) . '/../node';
    log::add('mySensors','info','Chemin d installation ' . $sensor_path);		
    exec('cd ' . $sensor_path . '; npm install');
    exec('sudo apt-get -y install avrdude');
}

function mySensors_remove() {
    $cron = cron::byClassAndFunction('mySensors', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }
    if (method_exists('mySensors', 'stopDeamon')) {
        mySensors::stopDeamon();
    }
}

?>
