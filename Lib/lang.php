<?php
/**
 * COmanage Registry RCAuth Source Plugin Language File
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
 
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_mitre_id_provisioner_texts['en_US'] = array(
      // Titles, per-controller
  'ct.mitre_id_provisioner.1'          => 'MitreId Provisioner',
  'ct.mitre_id_provisioner.pl'         => 'MitreId Provisioner',
  'ct.co_mitre_id_provisioner_targets.1' => 'MitreId Provisioner Target',
  
  'fd.server'                          => 'Server',
  'fd.server.url'                      => 'Server URL',
  'fd.server.username'                 => 'Username',
  'fd.server.port'                     => 'Port',
  'fd.server.persistent'               => 'Persistent',
  'fd.server.encoding'                 => 'Encoding',
  'fd.server.test_connection'          => 'Test Connection',

  // Plugin texts
  'pl.mitre_id_provisioner.hostname'         => 'Hostname',
  'pl.mitre_id_provisioner.type'             => 'Type',
  'pl.mitre_id_provisioner.database'         => 'Database',
  'pl.mitre_id_provisioner.db_settings'      => 'Database Configuration',
  'pl.mitre_id_provisioner.pl_config'        => 'Entitlement Configuration',
  'pl.mitre_id_provisioner.vo_whitelist'     => 'Vo Whitelist',
  'pl.mitre_id_provisioner.vo_whitelist.desc'       => 'A comma seperated list that contains VOs (COUs) for which the plugin will generate entitlements.',
  'pl.mitre_id_provisioner.vo_roles'                => 'Vo Roles',
  'pl.mitre_id_provisioner.vo_roles.desc'           => 'A comma seperated list of default roles to be used for the composition of the entitlements.',
  'pl.mitre_id_provisioner.merge_entitlements'      => 'Merge Entitlements',
  'pl.mitre_id_provisioner.merge_entitlements.desc' => '',
  'pl.mitre_id_provisioner.urn_namespace'           => 'URN Namespace',
  'pl.mitre_id_provisioner.urn_namespace.desc'      => 'A string to use as the URN namespace of the generated eduPersonEntitlement values containing group membership and role information',
  'pl.mitre_id_provisioner.urn_authority'           => 'URN Authority',
  'pl.mitre_id_provisioner.urn_authority.desc'      => 'A string to use as the authority of the generated eduPersonEntitlement URN values containing group membership and role information',
  'pl.mitre_id_provisioner.urn_legacy'              => 'URN Legacy',
  'pl.mitre_id_provisioner.urn_legacy.desc'         => 'A boolean value for controlling whether to generate eduPersonEntitlement URN values using the legacy syntax.',
  'pl.mitre_id_provisioner.vo_group_prefix'         => 'VO Group Prefix',
  'pl.mitre_id_provisioner.vo_group_prefix.desc'    => 'A group prefix to be used for the composition of the entitlements.',
  'pl.mitre_id_provisioner.entitlement_format'      => 'Entitlement Format',
  'pl.mitre_id_provisioner.entitlement_format.desc' => 'Define a regex for entitlements\' format you want to remove. Leave it blank for removing all old entitlements.',
  'pl.mitre_id_provisioner.identifier_type'         => 'Identifier Type',
  'pl.mitre_id_provisioner.identifier_type.desc'    => 'Define the User\'s Identifier Type',
  'pl.mitre_id_provisioner.enable.vowhitelist'      => 'Enable Vo Whitelist',
  'pl.mitre_id_provisioner.enable.vowhitelist.desc' => 'Define if Vo Whitelist is enabled',
  'pl.mitre_id_provisioner.enable.formatvowht'      => 'Include VO Whitelist entries to Entitlement Format',
  'pl.mitre_id_provisioner.enable.formatvowht.short'=> 'VO Whitelist into Format',

  //Database
  'er.mitre_id_provisioner.db.save'    => 'Save failed',
  'er.mitre_id_provisioner.db.blackhauled'    => 'Token expired.Please try again.',
  'er.mitre_id_provisioner.db.connect' => 'Failed to connect to database: %1$s',
  'er.mitre_id_provisioner.db.action'  => 'Database action failed [PDO Code:%1$s]',
  'rs.mitre_id_provisioner.db.connect' => 'Database Connect Successful'
);
