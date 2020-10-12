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

$cm_entitlement_provisioner_texts['en_US'] = array(
      // Titles, per-controller
  'ct.entitlement_provisioner.1'          => 'Entitlement Provisioner',
  'ct.entitlement_provisioner.pl'         => 'Entitlement Provisioner',
  'ct.co_entitlement_provisioner_targets.1' => 'Entitlement Provisioner Target',
  
  // Plugin texts
  'pl.entitlement_provisioner.hostname'         => 'Hostname',
  'pl.entitlement_provisioner.type'             => 'Type',
  'pl.entitlement_provisioner.database'         => 'Database',
  'pl.entitlement_provisioner.db_settings'      => 'Database Configuration',
  'pl.entitlement_provisioner.pl_config'        => 'Entitlement Configuration',
  'pl.entitlement_provisioner.vo_whitelist'     => 'Vo Whitelist',
  'pl.entitlement_provisioner.vo_whitelist.desc'       => 'A comma seperated list that contains VOs (COUs) for which the plugin will generate entitlements.',
  'pl.entitlement_provisioner.vo_roles'                => 'Vo Roles',
  'pl.entitlement_provisioner.vo_roles.desc'           => 'A comma seperated list of default roles to be used for the composition of the entitlements.',
  'pl.entitlement_provisioner.merge_entitlements'      => 'Merge Entitlements',
  'pl.entitlement_provisioner.merge_entitlements.desc' => '',
  'pl.entitlement_provisioner.urn_namespace'           => 'URN Namespace',
  'pl.entitlement_provisioner.urn_namespace.desc'      => 'A string to use as the URN namespace of the generated eduPersonEntitlement values containing group membership and role information',
  'pl.entitlement_provisioner.urn_authority'           => 'URN Authority',
  'pl.entitlement_provisioner.urn_authority.desc'      => 'A string to use as the authority of the generated eduPersonEntitlement URN values containing group membership and role information',
  'pl.entitlement_provisioner.urn_legacy'              => 'URN Legacy',
  'pl.entitlement_provisioner.urn_legacy.desc'         => 'A boolean value for controlling whether to generate eduPersonEntitlement URN values using the legacy syntax.',
  'pl.entitlement_provisioner.vo_group_prefix'         => 'VO Group Prefix',
  'pl.entitlement_provisioner.vo_group_prefix.desc'    => 'A group prefix to be used for the composition of the entitlements.',
  'pl.entitlement_provisioner.entitlement_format'      => 'Entitlement Format',
  'pl.entitlement_provisioner.entitlement_format.desc' => 'Define a regex for entitlements\' format you want to remove. Leave it blank for removing all old entitlements.',
  'pl.entitlement_provisioner.identifier_type'         => 'Identifier Type',
  'pl.entitlement_provisioner.identifier_type.desc'    => 'Define the User\'s Identifier Type',
  'pl.entitlement_provisioner.enable.vowhitelist'      => 'Enable Vo Whitelist',
  'pl.entitlement_provisioner.enable.vowhitelist.desc' => 'Define if Vo Whitelist is enabled'
);
