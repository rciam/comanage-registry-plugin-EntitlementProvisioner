<?php

/**
 * COmanage Registry CO VOMs Provisioner Target Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v3.1.x
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::import('Model', 'ConnectionManager');
App::uses("CoProvisionerPluginTarget", "Model");
App::uses("MitreId", "Model");
App::uses('Security', 'Utility');
App::uses('Hash', 'Utility');

/**
 * Class VomsProvisionerTarget
 */
class CoEntitlementProvisionerTarget extends CoProvisionerPluginTarget
{
  // XXX All the classes/models that have tables should start with CO for the case of provisioners
  // Define class name for cake
  public $name = "CoEntitlementProvisionerTarget";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array('CoProvisioningTarget');

  // Default display field for cake generated views
  public $displayField = "vo";
 

  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.1.0
   */

  public function beforeSave($options = array())
  {
    //remove new lines and whitespaces for "VO Whitelist" field
    if(isset($this->data['CoEntitlementProvisionerTarget']['vo_whitelist'])) {
      $this->data['CoEntitlementProvisionerTarget']['vo_whitelist'] = str_replace(array("\r", "\n"), '', $this->data['CoEntitlementProvisionerTarget']['vo_whitelist']);
      $values = explode(',', $this->data['CoEntitlementProvisionerTarget']['vo_whitelist']);
      foreach($values as $key=>$value){
          $values[$key] = trim($value);
      }
      $this->data['CoEntitlementProvisionerTarget']['vo_whitelist'] = implode(',', $values);
    }
    if(isset($this->data['CoEntitlementProvisionerTarget']['password'])) {
      $key = Configure::read('Security.salt');
      Configure::write('Security.useOpenSsl', true);
      $password = base64_encode(Security::encrypt($this->data['CoEntitlementProvisionerTarget']['password'], $key));
      $this->data['CoEntitlementProvisionerTarget']['password'] = $password;
    }
  }

  public function getConfiguration($coId)
  {
    $args = array();
    $args['joins'] = array(
      array(
        'table' => 'cm_co_provisioning_targets',
        'alias' => 'co_provisioning_targets',
        'type' => 'INNER',
        'conditions' => array(
          'CoEntitlementProvisionerTarget.co_provisioning_target_id = co_provisioning_targets.id'
        )
      )
    );
    $args['conditions']['co_provisioning_targets.co_id'] = $coId;
    $args['conditions']['co_provisioning_targets.plugin'] = 'EntitlementProvisioner';

    $entitlementProvisioners = $this->find('all', $args);

    //Return only the first result. What if we have more than one?? Is it possible?
    return $entitlementProvisioners[0]['CoEntitlementProvisionerTarget'];
  }

  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO PROVISIONING TARGET ID must be provided'
    ),
    'type' => array(
      'rule' => array(
        'inList',
        array(
          EntitlementProvisionerDBDriverTypeEnum::Mysql,
          EntitlementProvisionerDBDriverTypeEnum::Postgres
        )
      ),
      'required' => true
    ),
    'hostname' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'port' => array(
      'numeric' => array(
        'rule' => 'naturalNumber',
        'message' => 'Please provide the number of DB port',
        'required' => false,
        'allowEmpty' => true,
        'last' => 'true',
      ),
      'valid_range' => array(
        'rule' => array('range', 1024, 65535),
        'message' => 'Port must be between 1024-65535',
        'required' => false,
        'allowEmpty' => true,
        'last' => 'true',
      ),
    ),
    'username' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    // 'database' is a MySQL reserved keyword
    'databas' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'persistent' => array(
      'rule' => 'boolean',
      'required' => true,
      'allowEmpty' => false
    ),
    'encoding' => array(
      'rule' => array(
        'inList',
        array(
          EntitlementProvisionerDBEncodingTypeEnum::utf_8,
          EntitlementProvisionerDBEncodingTypeEnum::iso_8859_7,
          EntitlementProvisionerDBEncodingTypeEnum::latin1,
          EntitlementProvisionerDBEncodingTypeEnum::latin2,
          EntitlementProvisionerDBEncodingTypeEnum::latin3,
          EntitlementProvisionerDBEncodingTypeEnum::latin4
        )
      ),
      'required' => true,
      'allowEmpty' => false
    ),
    'vo_whitelist' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'vo_roles' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'merge_entitlements' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'urn_namespace' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'urn_authority' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'urn_legacy' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'vo_group_prefix' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'entitlement_format' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'identifier_type' => array(
      'rule' => array(
        'inList',
          array(
                EntitlementProvisionerIdentifierEnum::Badge,
                EntitlementProvisionerIdentifierEnum::Enterprise,
                EntitlementProvisionerIdentifierEnum::ePPN,
                EntitlementProvisionerIdentifierEnum::ePTID,
                EntitlementProvisionerIdentifierEnum::ePUID,
                EntitlementProvisionerIdentifierEnum::Mail,
                EntitlementProvisionerIdentifierEnum::National,
                EntitlementProvisionerIdentifierEnum::Network,
                EntitlementProvisionerIdentifierEnum::OpenID,
                EntitlementProvisionerIdentifierEnum::ORCID,
                EntitlementProvisionerIdentifierEnum::ProvisioningTarget,
                EntitlementProvisionerIdentifierEnum::Reference,
                EntitlementProvisionerIdentifierEnum::SORID,
                EntitlementProvisionerIdentifierEnum::UID
          )
      ),
      'required' => true
    ),
  );

  /**
   * Establish a connection (via Cake's ConnectionManager) to the specified SQL server.
   * @param integer $coId
   * @param array $dbconfig
   * @return DataSource|null
   * @throws InvalidArgumentException   Plugins Configuration is not valid
   * @throws MissingConnectionException The database connection failed
   */

  public function connect($coPersonId, $dbconfig = array(), $co_entitlement_provisioner_target = NULL)
  {

    if (empty($dbconfig)) {
      
      Configure::write('Security.useOpenSsl', true);
      $dbconfig = array(
        'datasource' => 'Database/' . EntitlementProvisionerDBDriverTypeEnum::type[$co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['type']],
        'persistent' => $co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['persistent'],
        'host'       => $co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['hostname'],
        'login'      => $co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['username'],
        'password'   => Security::decrypt(base64_decode($co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['password']), Configure::read('Security.salt')),
        'database'   => $co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['databas'],
        'encoding'   => $co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['encoding'],
        'port'       => $co_entitlement_provisioner_target['CoEntitlementProvisionerTarget']['port'],
      );
      
    }


    // Port Value
    if (empty($dbconfig['port'])) {
      if ($dbconfig['datasource'] === 'Database/Mysql') {
        $dbconfig['port'] = EntitlementProvisionerDBPortsEnum::Mysql;
      } else if ($dbconfig['datasource'] === 'Database/Postgres') {
        $dbconfig['port'] = EntitlementProvisionerDBPortsEnum::Postgres;
      }
    }

    // Database connection per CO
    $datasource = ConnectionManager::create('connection_' . $coPersonId, $dbconfig);
    return $datasource;
  }

  /**
   * Provision for the specified CO Person.
   *
   * @param Array CO Provisioning Target data
   * @param ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   * @since  COmanage Registry v0.8
   */

  public function provision($coProvisioningTargetData, $op, $provisioningData)
  {
    $this->log(__METHOD__ . "::@", LOG_DEBUG);
    $this->log(__METHOD__ . "::action => " . $op, LOG_DEBUG);

    switch ($op) {
      case ProvisioningActionEnum::CoPersonAdded:
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        $co_id = $provisioningData['Co']['id'];
        $co_person_identifier = $provisioningData['CoPerson']['actor_identifier'];
        $co_person_id = $provisioningData['CoPerson']['id'];
        break;
      case ProvisioningActionEnum::CoPersonUpdated:
        $co_id = $provisioningData['Co']['id'];
        $co_person_identifier = $provisioningData['CoPerson']['actor_identifier'];
        $co_person_id = $provisioningData['CoPerson']['id'];
        $co_person_identifier = Hash::extract($provisioningData['Identifier'], '{n}[type=' . $coProvisioningTargetData['CoEntitlementProvisionerTarget']['identifier_type'] . '].identifier')[0];
        break;
      case ProvisioningActionEnum::CoPersonExpired:
        break;
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
        // An update may cause an existing person to be written to VOMS for the first time
        // or for an unexpectedly removed entry to be replaced
        break;
      case ProvisioningActionEnum::CoGroupUpdated:
        $co_id = $provisioningData['CoGroup']['co_id'];
        //$co_person_identifier = $provisioningData['CoGroup']['CoPerson']['actor_identifier'];
        $co_person_id = $provisioningData['CoGroup']['CoPerson']['id'];
        $identifier = ClassRegistry::init('Identifier');
        $co_person_identifier = $identifier->field('identifier', array('co_person_id' => $co_person_id, 'type' => $coProvisioningTargetData['CoEntitlementProvisionerTarget']['identifier_type']));
        break;
      case ProvisioningActionEnum::CoGroupDeleted: 
        break;
      default:
        // Ignore all other actions
        $this->log(__METHOD__ . '::Provisioning action ' . $op . ' not allowed/implemented', LOG_DEBUG);
        return true;
        break;
      }
      
        
        // Check if its an action we want to provision
        if(!empty($_REQUEST['_method']) && $_REQUEST['_method'] == 'PUT' && !empty($_REQUEST['data']['CoPersonRole'] && $_REQUEST['data']['CoPersonRole']['status'] == 'S')) { //SUSPEND
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoPersonRole Form] Suspended User with id:' . $co_person_id, LOG_DEBUG);
        }
        else if((!empty($_REQUEST['_method']) && ($_REQUEST['_method'] == 'PUT' || $_REQUEST['_method'] == 'POST')) && !empty($_REQUEST['data']['CoPersonRole']) && $_REQUEST['data']['CoPersonRole']['status'] == 'A') { //ACTIVE
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoPersonRole Form] Active User with id:' . $co_person_id, LOG_DEBUG);
        }
        else if(!empty($_REQUEST['_method']) && $_REQUEST['_method'] == 'PUT' && !empty($_REQUEST['data']['CoPersonRole'])) { //Another Action of Co Person Role
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoPersonRole Form] Action for User with id:' . $co_person_id, LOG_DEBUG);
        }
        else if(strpos(array_keys($_REQUEST)[0],'/co_person_roles/delete/')!==FALSE) { //delete co person role
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [Co Person Roles] delete role from user with id:' . $co_person_id, LOG_DEBUG);
        }
        else if(strpos(array_keys($_REQUEST)[0],'/co_group_members/delete/')!==FALSE) { //delete co group member
          if(!empty($co_person_id))
            $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoGroupMember] delete from group, user with id:' . $co_person_id, LOG_DEBUG);
        }
        else if(strpos(array_keys($_REQUEST)[0],'/co_group_members/add_json')!==FALSE) { //add co group member from rest api
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoGroupMember] REST API CALL: add group to user with id:' . $co_person_id, LOG_DEBUG);
        }
        else if(strpos(array_keys($_REQUEST)[0],'/co_groups/delete')!==FALSE) { //delete co group 
          $co_group_id = explode('/', array_keys($_REQUEST)[0])[3];
          $CoGroup = ClassRegistry::init('CoGroup');
          $group_name = $CoGroup->field('name', array('id' => $co_group_id));
          $co_id = $CoGroup->field('co_id', array('id' => $co_group_id));
          $delete_group = true;
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoGroup] Delete Group with id:' . $co_group_id, LOG_DEBUG);
        }
        else if(strpos(array_keys($_REQUEST)[0],'/co_groups/edit')!==FALSE) { //delete co group 
          $co_group_id = explode('/', array_keys($_REQUEST)[0])[3];
          $CoGroup = ClassRegistry::init('CoGroup');
          $group_name = $CoGroup->field('name', array('id' => $co_group_id));
          $new_group_name = $_REQUEST['data']['CoGroup']['name'];
          if($group_name != $new_group_name) {
            $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoGroup] Rename Group with id:' . $co_group_id, LOG_DEBUG);
            $rename_group = true;
          }
        }
        else if(strpos(array_keys($_REQUEST)[0],'/cous/delete')!==FALSE) { //delete co group 
          $cou_id = explode('/', array_keys($_REQUEST)[0])[3];
          $Cou = ClassRegistry::init('Cou');
          $cou_name = $Cou->field('name', array('id' => $cou_id));
          $delete_cou = TRUE;
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [Cou] Delete Cou with id:' . $cou_id, LOG_DEBUG);
        }
        else if(strpos(array_keys($_REQUEST)[0],'/co_group_members/')!==FALSE) { //co group member action
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => [CoGroupMember Action] for user with id:' . $co_person_id, LOG_DEBUG);
        } 
        else if(!empty($_REQUEST['_method']) && $_REQUEST['_method'] == 'POST' && !empty($_REQUEST['data']['CoPerson']) && $_REQUEST['data']['CoPerson']['confirm'] == '1' && isset($_REQUEST['/co_people/expunge/'. $co_person_id])) { //DELETE
          $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => Delete User with id:' . $co_person_id, LOG_DEBUG);
          $deleteAll = true;
        }
        else {
          return false;
        }
        if(empty($co_person_id))
          $connect_id = $co_group_id;
        else
          $connect_id = $co_person_id;
          
        $datasource = $this->connect($connect_id, array(), $coProvisioningTargetData);
        $mitre_id = ClassRegistry::init('MitreIdUsers');
        MitreId::config($mitre_id, $datasource, 'user_info', $coProvisioningTargetData['CoEntitlementProvisionerTarget']['entitlement_format']);
        if(!empty($group_name) && !empty($delete_group)) { //group Deleted
          MitreId::deleteEntitlementsByGroup($mitre_id, $group_name, $coProvisioningTargetData['CoEntitlementProvisionerTarget']['urn_namespace'], $coProvisioningTargetData['CoEntitlementProvisionerTarget']['urn_legacy'], $coProvisioningTargetData['CoEntitlementProvisionerTarget']['urn_authority'], SyncEntitlements::get_vo_group_prefix_static($coProvisioningTargetData['CoEntitlementProvisionerTarget']['vo_group_prefix'], $co_id));
        }
        else if(!empty($rename_group)) { //group Renamed
          
        }
        else if(!empty($cou_id) && !empty($cou_name)) { //cou Deleted

        }
        else {
          //Get Person by the epuid
          $person = $mitre_id->find('all', array('conditions'=> array('MitreIdUsers.sub' => $co_person_identifier)));
          if(empty($person)) {
            $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => person id not found in mitre' . $co_person_id . ' and identifier: ' . $co_person_identifier, LOG_DEBUG);            
            ConnectionManager::drop('connection_' . $connect_id);
            return false;
          } 
          //Get User Entitlements From MitreId
          $mitre_id_entitlements = ClassRegistry::init('MitreIdEntitlements');
          if(!empty($deleteAll)) {
            MitreId::deleteAllEntitlements($mitre_id_entitlements, $person[0]['MitreIdUsers']['id']);  
          }
          else{
            MitreId::config($mitre_id_entitlements, $datasource, 'user_edu_person_entitlement', $coProvisioningTargetData['CoEntitlementProvisionerTarget']['entitlement_format']);
            $current_entitlements = MitreId::getCurrentEntitlements($mitre_id_entitlements, $person[0]['MitreIdUsers']['id']);
            $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => current_entitlements from MitreId' . var_export($current_entitlements, true), LOG_DEBUG);           
            //Get New Entitlements From Comanage
            $syncEntitlements = new SyncEntitlements($coProvisioningTargetData['CoEntitlementProvisionerTarget'],$co_id);
            $new_entitlements = $syncEntitlements->getEntitlements($co_person_id);
            $this->log(__METHOD__ . '::Provisioning action ' . $op . ' => new_entitlements from comanage' . var_export($new_entitlements, true), LOG_DEBUG);           
      
            //Delete Old Entitlements
            MitreId::deleteOldEntitlements($mitre_id_entitlements, $person[0]['MitreIdUsers']['id'], $current_entitlements, $new_entitlements);
            //Insert New Entitlements
            MitreId::insertNewEntitlements($mitre_id_entitlements, $person[0]['MitreIdUsers']['id'],  $current_entitlements, $new_entitlements); 
          }
          
        }
      ConnectionManager::drop('connection_' . $connect_id);
    
    
    
  }
}
