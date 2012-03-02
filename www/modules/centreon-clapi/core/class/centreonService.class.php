<?php
/**
 * Copyright 2005-2010 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 *
 */

require_once "centreonObject.class.php";
require_once "centreonTimePeriod.class.php";
require_once "centreonACL.class.php";
require_once "Centreon/Object/Instance/Instance.php";
require_once "Centreon/Object/Command/Command.php";
require_once "Centreon/Object/Timeperiod/Timeperiod.php";
require_once "Centreon/Object/Host/Host.php";
require_once "Centreon/Object/Host/Extended.php";
require_once "Centreon/Object/Host/Group.php";
require_once "Centreon/Object/Host/Host.php";
require_once "Centreon/Object/Host/Macro/Custom.php";
require_once "Centreon/Object/Service/Service.php";
require_once "Centreon/Object/Service/Macro/Custom.php";
require_once "Centreon/Object/Service/Extended.php";
require_once "Centreon/Object/Contact/Contact.php";
require_once "Centreon/Object/Contact/Group.php";
require_once "Centreon/Object/Relation/Host/Template/Host.php";
require_once "Centreon/Object/Relation/Contact/Service.php";
require_once "Centreon/Object/Relation/Contact/Group/Service.php";
require_once "Centreon/Object/Relation/Host/Service.php";


/**
 * Centreon Service objects
 *
 * @author sylvestre
 */
class CentreonService extends CentreonObject
{
    const ORDER_HOSTNAME = 0;
    const ORDER_SVCDESC  = 1;
    const ORDER_SVCTPL   = 2;
    const NB_UPDATE_PARAMS = 4;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->object = new Centreon_Object_Service();
        $this->params = array('service_is_volatile'         		   => '2',
                              'service_active_checks_enabled'          => '2',
                              'service_passive_checks_enabled'         => '2',
                              'service_parallelize_check'              => '2',
                              'service_obsess_over_service'            => '2',
                              'service_check_freshness'                => '2',
                              'service_event_handler_enabled'          => '2',
                              'service_flap_detection_enabled'         => '2',
                              'service_process_perf_data'		       => '2',
                              'service_retain_status_information'	   => '2',
        					  'service_retain_nonstatus_information'   => '2',
                              'service_notifications_enabled'		   => '2',
                              'service_register'					   => '1',
                              'service_activate'				       => '1'
                              );
                              $this->nbOfCompulsoryParams = 3;
                              $this->register = 1;
                              $this->activateField = 'service_activate';
    }

    /**
     * Returns type of host service relation
     *
     * @param int $serviceId
     * @return int
     */
    public function hostTypeLink($serviceId)
    {
        $sql = "SELECT host_host_id, hostgroup_hg_id FROM host_service_relation WHERE service_service_id = ?";
        $res = $this->db->query($sql, array($serviceId));
        $rows = $res->fetch();
        if (count($rows)) {
            if (isset($rows['host_host_id']) && $rows['host_host_id']) {
                return 1;
            } elseif (isset($rows['hostgroup_hg_id']) && $rows['hostgroup_hg_id']) {
                return 2;
            }
        }
        return 0;
    }

    /**
     * Check parameters
     *
     * @param string $hostName
     * @param string $serviceDescription
     * @return bool
     */
    protected function serviceExists($hostName, $serviceDescription)
    {
        $relObj = new Centreon_Object_Relation_Host_Service();
        $elements = $relObj->getMergedParameters(array('host_id'), array('service_id'), -1, 0, null, null, array('host_name' => $hostName,
        																										 'service_description' => $serviceDescription), "AND");
        if (count($elements)) {
            return true;
        }
        return false;
    }

    /**
     * Display all services
     *
     * @param string $parameters
     * @return void
     */
    public function show($parameters = null)
    {
        $filters = array('service_register' => $this->register);
        if (isset($parameters)) {
            $filters["service_description"] = "%".$parameters."%";
        }
        $commandObject = new Centreon_Object_Command();
        $paramsHost = array('host_id', 'host_name');
        $paramsSvc = array('service_id', 'service_description', 'command_command_id', 'command_command_id_arg',
                        'service_normal_check_interval', 'service_retry_check_interval', 'service_max_check_attempts',
                        'service_active_checks_enabled', 'service_passive_checks_enabled');
        $relObject = new Centreon_Object_Relation_Host_Service();
        $elements = $relObject->getMergedParameters($paramsHost, $paramsSvc, -1, 0, "host_name,service_description", "ASC", $filters, "AND");
        $paramHostString = str_replace("_", " ", implode($this->delim, $paramsHost));
        echo $paramHostString . $this->delim;
        $paramSvcString = str_replace("service_", "", implode($this->delim, $paramsSvc));
        $paramSvcString = str_replace("command_command_id", "check command", $paramSvcString);
        $paramSvcString = str_replace("command_command_id_arg", "check command arguments", $paramSvcString);
        $paramSvcString = str_replace("_", " ", $paramSvcString);
        echo $paramSvcString."\n";
        foreach ($elements as $tab) {
            if (isset($tab['command_command_id']) && $tab['command_command_id']) {
                $tmp = $commandObject->getParameters($tab['command_command_id'], array($commandObject->getUniqueLabelField()));
                if (isset($tmp[$commandObject->getUniqueLabelField()])) {
                    $tab['command_command_id'] = $tmp[$commandObject->getUniqueLabelField()];
                }
            }
            echo implode($this->delim, $tab) . "\n";
        }
    }

    /**
     * Delete service
     *
     * @param string $parameters
     * @return void
     * @throws CentreonClapiException
     */
    public function del($parameters)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < 2) {
            throw new Exception(self::MISSINGPARAMETER);
        }
        $hostName = $params[0];
        $serviceDesc = $params[1];
        $relObject = new Centreon_Object_Relation_Host_Service();
        $elements = $relObject->getMergedParameters(array("host_id"), array("service_id"), -1, 0, null, null, array("host_name" => $hostName,
                                                                                                                    "service_description" => $serviceDesc), "AND");
        if (!count($elements)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $this->object->delete($elements[0]['service_id']);
    }

    /**
     * Add a contact
     *
     * @param string $parameters
     * @return void
     * @throws CentreonClapiException
     */
    public function add($parameters)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < $this->nbOfCompulsoryParams) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        if ($this->serviceExists($params[self::ORDER_HOSTNAME], $params[self::ORDER_SVCDESC]) == true) {
            throw new CentreonClapiException(self::OBJECTALREADYEXISTS);
        }
        $hostObject = new Centreon_Object_Host();
        $tmp = $hostObject->getIdByParameter($hostObject->getUniqueLabelField(), $params[self::ORDER_HOSTNAME]);
        if (!count($tmp)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND . ":" . $params[self::ORDER_HOSTNAME]);
        }
        $hostId = $tmp[0];
        $addParams = array();
        $addParams['service_description'] = $params[self::ORDER_SVCDESC];
        $template = $params[self::ORDER_SVCTPL];
        $tmp = $this->object->getList($this->object->getPrimaryKey(), -1, 0, null, null, array('service_description' => $template, 'service_register' => '0'), "AND");
        if (!count($tmp)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND . ":" . $template);
        }
        $addParams['service_template_model_stm_id'] = $tmp[0][$this->object->getPrimaryKey()];
        $this->params = array_merge($this->params, $addParams);
        $serviceId = parent::add();

        $relObject = new Centreon_Object_Relation_Host_Service();
        $relObject->insert($hostId, $serviceId);

        $extended = new Centreon_Object_Service_Extended();
        $extended->insert(array($extended->getUniqueLabelField() => $serviceId));
    }

    /**
     * Returns command id
     *
     * @param string $commandName
     * @return int
     * @throws CentreonClapiException
     */
    protected function getCommandId($commandName)
    {
        $obj = new Centreon_Object_Command();
        $tmp = $obj->getIdByParameter($obj->getUniqueLabelField(), $commandName);
        if (count($tmp)) {
            $id = $tmp[0];
        } else {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND . ":" . $commandName);
        }
        return $id;
    }

    /**
     * Set parameters
     *
     * @param string $parameters
     * @return void
     * @throws CentreonClapiException
     */
    public function setparam($parameters = null)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < self::NB_UPDATE_PARAMS) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $hostName = $params[0];
        $serviceDesc = $params[1];
        $relObject = new Centreon_Object_Relation_Host_Service();
        $elements = $relObject->getMergedParameters(array("host_id"), array("service_id"), -1, 0, null, null, array("host_name" => $hostName,
                                                                                                                    "service_description" => $serviceDesc), "AND");
        if (!count($elements)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $objectId = $elements[0]['service_id'];
        $extended = false;
        switch ($params[2]) {
            case "check_command":
                $params[2] = "command_command_id";
                $params[3] = $this->getCommandId($params[3]);
                break;
            case "check_command_arguments":
                $params[2] = "command_command_id_arg1";
                break;
            case "event_handler":
                $params[2] = "command_command_id2";
                $params[3] = $this->getCommandId($params[3]);
                break;
            case "event_handler_arguments":
                $params[2] = "command_command_id_arg2";
                break;
            case "check_period":
                $params[2] = "timeperiod_tp_id";
                $tpObj = new CentreonTimePeriod();
                $params[3] = $tpObj->getTimeperiodId($params[3]);
                break;
            case "notification_period":
                $params[2] = "timeperiod_tp_id2";
                $tpObj = new CentreonTimePeriod();
                $params[3] = $tpObj->getTimeperiodId($params[3]);
                break;
            case "flap_detection_options":
                break;
            case "template":
                $params[2] = "service_template_model_stm_id";
                $tmp = $this->object->getList($this->object->getPrimaryKey(), -1, 0, null, null, array('service_description' => $params[3], 'service_register' => '0'), "AND");
                if (!count($tmp)) {
                    throw new CentreonClapiException(self::OBJECT_NOT_FOUND . ":" . $template);
                }
                $params[3] = $tmp[0][$this->object->getPrimaryKey()];
                break;
            case "notes":
                $extended = true;
                break;
            case "notes_url":
                $extended = true;
                break;
            case "action_url":
                $extended = true;
                break;
            case "icon_image":
                $extended = true;
                break;
            case "icon_image_alt":
                $extended = true;
                break;
            default:
                $params[2] = "service_".$params[2];
                break;
        }
        if ($extended == false) {
            $updateParams = array($params[2] => $params[3]);
            parent::setparam($objectId, $updateParams);
        } else {
            $params[2] = "esi_".$params[2];
            $extended = new Centreon_Object_Service_Extended();
            $extended->update($objectId, array($params[2] => $params[3]));
        }
    }

    /**
     * Wrap macro
     *
     * @param string $macroName
     * @return string
     */
    protected function wrapMacro($macroName)
    {
        $wrappedMacro = "\$_SERVICE".strtoupper($macroName)."\$";
        return $wrappedMacro;
    }

    /**
     * Get macro list of a service
     *
     * @param string $hostName
     * @param string $serviceDescription
     * @return void
     * @throws CentreonClapiException
     */
    public function getmacro($parameters)
    {
        $tmp = explode($this->delim, $parameters);
        if (count($tmp) < 2) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $hostName = $tmp[0];
        $serviceDescription = $tmp[1];
        $relObject = new Centreon_Object_Relation_Host_Service();
        $elements = $relObject->getMergedParameters(array('host_id'), array('service_id'), -1, 0, null, null, array("host_name" => $hostName,
                                                                                                                    "service_description" => $serviceDescription), "AND");
        if (!count($elements)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $macroObj = new Centreon_Object_Service_Macro_Custom();
        $macroList = $macroObj->getList(array("svc_macro_name", "svc_macro_value"), -1, 0, null, null, array("svc_svc_id" => $elements[0]['service_id']));
        echo "macro name;macro value\n";
        foreach ($macroList as $macro) {
            echo $macro['svc_macro_name'] . $this->delim . $macro['svc_macro_value'] . "\n";
        }
    }

    /**
     * Inserts/updates custom macro
     *
     * @param string $parameters
     * @return void
     * @throws CentreonClapiException
     */
    public function setmacro($parameters)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < 4) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $hostName = $params[0];
        $serviceDescription = $params[1];
        $relObject = new Centreon_Object_Relation_Host_Service();
        $elements = $relObject->getMergedParameters(array('host_id'), array('service_id'), -1, 0, null, null, array("host_name" => $hostName,
                                                                                                                    "service_description" => $serviceDescription), "AND");
        if (!count($elements)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $macroObj = new Centreon_Object_Service_Macro_Custom();
        $macroList = $macroObj->getList($macroObj->getPrimaryKey(), -1, 0, null, null, array("svc_svc_id"      => $elements[0]['service_id'],
                                                                                			 "svc_macro_name" => $this->wrapMacro($params[2])),
                                                                                		"AND");
        if (count($macroList)) {
            $macroObj->update($macroList[0][$macroObj->getPrimaryKey()], array('svc_macro_value' => $params[3]));
        } else {
            $macroObj->insert(array('svc_svc_id'       => $elements[0]['service_id'],
                                    'svc_macro_name'  => $this->wrapMacro($params[2]),
                                    'svc_macro_value' => $params[3]));
        }
    }

    /**
     * Delete custom macro
     *
     * @param string $parameters
     * @return void
     * @throws CentreonClapiException
     */
    public function delmacro($parameters)
    {
        $params = explode($this->delim, $parameters);
        if (count($params) < 3) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $hostName = $params[0];
        $serviceDescription = $params[1];
        $relObject = new Centreon_Object_Relation_Host_Service();
        $elements = $relObject->getMergedParameters(array('host_id'), array('service_id'), -1, 0, null, null, array("host_name" => $hostName,
                                                                                                                    "service_description" => $serviceDescription), "AND");
        if (!count($elements)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $macroObj = new Centreon_Object_Service_Macro_Custom();
        $macroList = $macroObj->getList($macroObj->getPrimaryKey(), -1, 0, null, null, array("svc_svc_id"      => $elements[0]['service_id'],
                                                                                			 "svc_macro_name" => $this->wrapMacro($params[2])),
                                                                                		"AND");
        if (count($macroList)) {
            $macroObj->delete($macroList[0][$macroObj->getPrimaryKey()]);
        }
    }

    /**
     * Get Object Name
     *
     * @param int $id
     * @return string
     */
    public function getObjectName($id)
    {
        $tmp = $this->object->getParameters($id, array('service_description'));
        if (isset($tmp['service_description'])) {
            return $tmp['service_description'];
        }
        return "";
    }

    /**
     * Magic method
     *
     * @param string $name
     * @param array $args
     * @return void
     * @throws CentreonClapiException
     */
    public function __call($name, $arg)
    {
        $name = strtolower($name);
        if (!isset($arg[0])) {
            throw new CentreonClapiException(self::MISSINGPARAMETER);
        }
        $args = explode($this->delim, $arg[0]);
        $relObject = new Centreon_Object_Relation_Host_Service();
        $elements = $relObject->getMergedParameters(array('host_id'), array('service_id'), -1, 0, null, null, array("host_name" => $args[0],
                                                                                                                    "service_description" => $args[1]), "AND");
        if (!count($elements)) {
            throw new CentreonClapiException(self::OBJECT_NOT_FOUND);
        }
        $serviceId = $elements[0]['service_id'];
        if (preg_match("/^(get|set|add|del)([a-zA-Z_]+)/", $name, $matches)) {
            switch ($matches[2]) {
                case "host":
                    $class = "Centreon_Object_Host";
                    $relclass = "Centreon_Object_Relation_Host_Service";
                    break;
                case "contact":
                    $class = "Centreon_Object_Contact";
                    $relclass = "Centreon_Object_Relation_Contact_Service";
                    break;
                case "contactgroup":
                    $class = "Centreon_Object_Contact_Group";
                    $relclass = "Centreon_Object_Relation_Contact_Group_Service";
                    break;
                default:
                    throw new CentreonClapiException(self::UNKNOWN_METHOD);
                    break;
            }
            if (class_exists($relclass) && class_exists($class)) {
                $relobj = new $relclass();
                $obj = new $class();
                if ($matches[1] == "get") {
                    $tab = $relobj->getTargetIdFromSourceId($relobj->getFirstKey(), $relobj->getSecondKey(), $serviceId);
                    echo "id".$this->delim."name"."\n";
                    foreach($tab as $value) {
                        $tmp = $obj->getParameters($value, array($obj->getUniqueLabelField()));
                        echo $value . $this->delim . $tmp[$obj->getUniqueLabelField()] . "\n";
                    }
                } else {
                    if (!isset($args[1])) {
                        throw new CentreonClapiException(self::MISSINGPARAMETER);
                    }
                    if ($matches[2] == "contact") {
                        $args[2] = str_replace(" ", "_", $args[2]);
                    }
                    $relation = $args[2];
                    $relations = explode("|", $relation);
                    $relationTable = array();
                    foreach($relations as $rel) {
                        if ($matches[1] != "del" && $matches[2] == "host" && $this->serviceExists($rel, $args[1])) {
                            throw new CentreonClapiException(self::OBJECTALREADYEXISTS);
                        }
                        $tab = $obj->getIdByParameter($obj->getUniqueLabelField(), array($rel));
                        if (!count($tab)) {
                            throw new CentreonClapiException(self::OBJECT_NOT_FOUND . ":".$rel);
                        }
                        $relationTable[] = $tab[0];
                    }
                    if ($matches[1] == "set") {
                        $relobj->delete(null, $serviceId);
                    }
                    $existingRelationIds = $relobj->getTargetIdFromSourceId($relobj->getFirstKey(), $relobj->getSecondKey(), $serviceId);
                    foreach($relationTable as $relationId) {
                        if ($matches[1] == "del") {
                            $relobj->delete($relationId, $serviceId);
                        } elseif ($matches[1] == "set" || $matches[1] == "add") {
                            if (!in_array($relationId, $existingRelationIds)) {
                                $relobj->insert($relationId, $serviceId);
                            }
                        }
                    }
                }
            } else {
                throw new CentreonClapiException(self::UNKNOWN_METHOD);
            }
        } else {
            throw new CentreonClapiException(self::UNKNOWN_METHOD);
        }
    }
}