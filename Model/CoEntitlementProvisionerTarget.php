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

App::uses("CoProvisionerPluginTarget", "Model");
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
/*
    public $hasMany = array(
        "CoEntitlementProvisionerServer" => array(
            'className' => 'EntitlementProvisioner.CoEntitlementProvisionerServer',
            'dependent' => true
        ),
    );*/

      /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.1.0
   */

  public function beforeSave($options = array())
  {
      if (isset($this->data['CoEntitlementProvisionerTarget']['password'])) {
          $key = Configure::read('Security.salt');
          Configure::write('Security.useOpenSsl', true);
          $password = base64_encode(Security::encrypt($this->data['CoEntitlementProvisionerTarget']['password'], $key));
          $this->data['CoEntitlementProvisionerTarget']['password'] = $password;
      }
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
    );

    /**
     * Establish a connection (via Cake's ConnectionManager) to the specified SQL server.
     * @param integer $coId
     * @param array $dbconfig
     * @return DataSource|null
     * @throws InvalidArgumentException   Plugins Configuration is not valid
     * @throws MissingConnectionException The database connection failed
     */
    /*
    public function connect($coId, $dbconfig = array())
    {
        // Get our connection information
        $args = array();
        $args['conditions']['RciamStatsViewer.co_id'] = $coId;
        $args['contain'] = false;

        $rciamstatsviewer = $this->find('first', $args);

        if (
            empty($rciamstatsviewer)
            && empty($dbconfig)
        ) {
            throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.rciam_stats_viewers.1'), $coId)));
        }

        Configure::write('Security.useOpenSsl', true);
        if (empty($dbconfig)) {
            $dbconfig = array(
                'datasource' => 'Database/' . EntitlementProvisionerDBDriverTypeEnum::type[$rciamstatsviewer['RciamStatsViewer']['type']],
                'persistent' => $rciamstatsviewer['RciamStatsViewer']['persistent'],
                'host'       => $rciamstatsviewer['RciamStatsViewer']['hostname'],
                'login'      => $rciamstatsviewer['RciamStatsViewer']['username'],
                'password'   => Security::decrypt(base64_decode($rciamstatsviewer['RciamStatsViewer']['password']), Configure::read('Security.salt')),
                'database'   => $rciamstatsviewer['RciamStatsViewer']['databas'],
                'encoding'   => $rciamstatsviewer['RciamStatsViewer']['encoding'],
                'port'       => $rciamstatsviewer['RciamStatsViewer']['port'],
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
*/
    /**
     * Provision for the specified CO Person.
     *
     * @param Array CO Provisioning Target data
     * @param ProvisioningActionEnum Registry transaction type triggering provisioning
     * @param Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
     * @return Boolean True on success
     * @throws RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @since  COmanage Registry v0.8
     */

    public function provision($coProvisioningTargetData, $op, $provisioningData)
    {
        $this->log(__METHOD__ . "::@", LOG_DEBUG);
        $this->log(__METHOD__ . "::action => " . $op, LOG_DEBUG);
    }
}
