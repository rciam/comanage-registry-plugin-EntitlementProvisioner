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
  );

  /**
   * Establish a connection (via Cake's ConnectionManager) to the specified SQL server.
   * @param integer $coId
   * @param array $dbconfig
   * @return DataSource|null
   * @throws InvalidArgumentException   Plugins Configuration is not valid
   * @throws MissingConnectionException The database connection failed
   */

  public function connect($coId, $dbconfig = array())
  {

    if (empty($dbconfig)) {
      // Get our connection information
      $args = array();
      $args['conditions']['CoEntitlementProvisionerTarget.co_id'] = $coId;
      $args['contain'] = false;

      $co_entitlement_provisioner_target = $this->find('first', $args);
      if(empty($co_entitlement_provisioner_target)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_entitlement_provisioner_target.1'), $coId)));
      }
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
    $datasource = ConnectionManager::create('connection_' . $coId, $dbconfig);

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
        break;
      case ProvisioningActionEnum::CoPersonUpdated:
        break;
      case ProvisioningActionEnum::CoPersonExpired:
        break;
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
        // An update may cause an existing person to be written to VOMS for the first time
        // or for an unexpectedly removed entry to be replaced

        break;
      case ProvisioningActionEnum::CoGroupUpdated:
        Configure::write('Security.useOpenSsl', true);
        $coProvisioningTargetData['CoEntitlementProvisionerTarget']['password'] = Security::decrypt(base64_decode($coProvisioningTargetData['CoEntitlementProvisionerTarget']['password']), Configure::read('Security.salt'));
        
        $dbconfig['datasource'] = 'Database/' . EntitlementProvisionerDBDriverTypeEnum::type[$coProvisioningTargetData['CoEntitlementProvisionerTarget']['type']];
        $dbconfig['host'] = $coProvisioningTargetData["CoEntitlementProvisionerTarget"]["hostname"];
        $dbconfig['port'] = $coProvisioningTargetData["CoEntitlementProvisionerTarget"]["port"];
        $dbconfig['database'] = $coProvisioningTargetData["CoEntitlementProvisionerTarget"]["databas"];
        $dbconfig['password'] = $coProvisioningTargetData["CoEntitlementProvisionerTarget"]["password"];
        $dbconfig['encoding'] = $coProvisioningTargetData["CoEntitlementProvisionerTarget"]["encoding"];
        $dbconfig['login'] = $coProvisioningTargetData["CoEntitlementProvisionerTarget"]["username"];
        
        //For GROUP UPDATE
        $datasource = $this->connect($provisioningData['CoGroup']['co_id'], $dbconfig);
        $mitre_id = ClassRegistry::init('MitreIdUsers');
        MitreId::config($mitre_id, $datasource, 'user_info');
    
        //Get Person by the epuid
        $person = $mitre_id->find('all', array('conditions'=> array('MitreIdUsers.sub' => $provisioningData['CoGroup']['CoPerson']['actor_identifier'])));
        if(!empty($person)) {
          //Get User Entitlements From MitreId
          $mitre_id_entitlements = ClassRegistry::init('MitreIdEntitlements');
          MitreId::config($mitre_id_entitlements, $datasource, 'user_edu_person_entitlement');
          $current_entitlements = MitreId::getCurrentEntitlements($mitre_id_entitlements, $person[0]['MitreIdUsers']['id']);
          
          //Get New Entitlements From Comanage
          $syncEntitlements = new SyncEntitlements($coProvisioningTargetData['CoEntitlementProvisionerTarget'],$provisioningData['CoGroup']['co_id']);
          $new_entitlements = $syncEntitlements->getEntitlements($provisioningData['CoGroup']['CoPerson']['id']);
    
          //Delete Old Entitlements
          MitreId::deleteOldEntitlements($mitre_id_entitlements, $person[0]['MitreIdUsers']['id'], $current_entitlements, $new_entitlements);
          //Insert New Entitlements
          MitreId::insertNewEntitlements($mitre_id_entitlements, $person[0]['MitreIdUsers']['id'],  $current_entitlements, $new_entitlements);  
        }
        break;
      default:
        // Ignore all other actions
        $this->log(__METHOD__ . '::Provisioning action ' . $op . ' not allowed/implemented', LOG_DEBUG);
        return true;
        break;
    }
  }
}
