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

function temperature_install()
{
    jeedom::getApiKey('temperature');

    config::save('functionality::cron5::enable', 0, 'temperature');
    config::save('functionality::cron10::enable', 0, 'temperature');
    config::save('functionality::cron15::enable', 0, 'temperature');
    config::save('functionality::cron30::enable', 1, 'temperature');
    config::save('functionality::cronhourly::enable', 0, 'temperature');

    $cron = cron::byClassAndFunction('temperature', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }

    //message::add('Plugin Température', 'Merci pour l\'installation du plugin.');
}

function temperature_update()
{
    jeedom::getApiKey('temperature');
    $cron = cron::byClassAndFunction('temperature', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }

    if (config::byKey('functionality::cron5::enable', 'temperature', -1) == -1) {
        config::save('functionality::cron5::enable', 0, 'temperature');
    }

    if (config::byKey('functionality::cron10::enable', 'temperature', -1) == -1) {
        config::save('functionality::cron10::enable', 0, 'temperature');
    }

    if (config::byKey('functionality::cron15::enable', 'temperature', -1) == -1) {
        config::save('functionality::cron15::enable', 0, 'temperature');
    }

    if (config::byKey('functionality::cron30::enable', 'temperature', -1) == -1) {
        config::save('functionality::cron30::enable', 1, 'temperature');
    }

    if (config::byKey('functionality::cronHourly::enable', 'temperature', -1) == -1) {
        config::save('functionality::cronHourly::enable', 0, 'temperature');
    }

    $plugin = plugin::byId('temperature');
    $eqLogics = eqLogic::byType($plugin->getId());
    log::add('temperature', 'debug', '│ :fg-warning:' . (__('Étape', __FILE__)) . ' 1/4 :/fg:───▶︎ ' . (__('Mise en place des nouveautés', __FILE__)));
    foreach ($eqLogics as $eqLogic) {
        //updateLogicalId($eqLogic, 'alert_1',null,null);
        updateLogicalId($eqLogic, 'IndiceChaleur', 'heat_index', 1);
        updateLogicalId($eqLogic, 'alerte_humidex', 'alert_2', null);
        updateLogicalId($eqLogic, 'info_inconfort', 'td', null);
        updateLogicalId($eqLogic, 'palerte_humidex', 'alert_1', null);
        updateLogicalId($eqLogic, 'heat_index', 'humidex', 0, 'Indice de Chaleur (Humidex)', 'DELETE'); // Modification du 7/12/2020
        updateLogicalId($eqLogic, 'windchill', null, 1, 'Température ressentie'); // Modification du 7/12/2020
        updateLogicalId($eqLogic, 'td', null, null, 'Message'); // Modification du 7/12/2020
        updateLogicalId($eqLogic, 'td_num', null, null, 'Message numérique'); // Modification du 7/12/2020
    }
    log::add('temperature', 'debug', '│ :fg-warning:' . (__('Étape', __FILE__)) . ' 2/4 :/fg:───▶︎ ' . (__('Suppression des commandes obsolètes', __FILE__)));
    //Suppression commande car il y a un soucis avec modification du 13/09/2024
    removeLogicId('td');
    //resave eqs for new cmd:
    log::add('temperature', 'debug', '│ :fg-warning:' . (__('Étape', __FILE__)) . ' 3/4 :/fg:───▶︎ ' . (__('Sauvegarde des équipements', __FILE__)));
    try {
        $eqs = eqLogic::byType('temperature');
        foreach ($eqs as $eq) {
            $eq->save(true);
        }
    } catch (Exception $e) {
        $e = print_r($e, 1);
        log::add('temperature', 'error', 'temperature_update ERROR: ' . $e);
    }
    log::add('temperature', 'debug', '│ :fg-warning:' . (__('Étape', __FILE__)) . ' 4/4 :/fg:───▶︎ ' . (__('Mise à jour des équipement', __FILE__)));
    //message::add('Plugin Température', 'Merci pour la mise à jour de ce plugin, consultez le changelog.');
    foreach (eqLogic::byType('temperature') as $temperature) {
        $temperature->getInformations();
    }
}

function updateLogicalId($eqLogic, $from, $to, $_historizeRound = null, $name = null, $unite = null)
{
    $command = $eqLogic->getCmd(null, $from);
    if (is_object($command)) {
        if ($to != null) {
            $command->setLogicalId($to);
        }
        if ($_historizeRound != null) {
            log::add('temperature', 'debug', '[INFO] ' . __('Correction de l\'Arrondi (Nombre de décimale) pour', __FILE__) . ' : ' . $from . ' ->  ' . __('Par la valeur', __FILE__) . ' : ' . $_historizeRound);
            $command->setConfiguration('historizeRound', $_historizeRound);
        }
        if ($name != null) {
            $command->setName($name);
        }
        if ($unite != null) {
            if ($unite == 'DELETE') {
                $unite = null;
            }
            $command->setUnite($unite);
        }
        $command->save(true);
    }
}
function removeLogicId($cmdDel)
{
    $eqLogics = eqLogic::byType('temperature');
    foreach ($eqLogics as $eqLogic) {
        $cmd = $eqLogic->getCmd(null, $cmdDel);
        if (is_object($cmd)) {
            $cmd->remove();
        }
    }
}
function temperature_remove()
{
    $cron = cron::byClassAndFunction('temperature', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }
}
