<?php

/**
 * COmanage Registry CO Group Controller
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
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::import('Sanitize');
App::uses("StandardController", "Controller");

class EntitlementsController extends StandardController
{

  public $uses = array(
    'CoGroup',
    'EntitlementProvisioner.CoEntitlementProvisionerTarget'
  );

  function index()
  {
    $fn = "index";
    $this->log(get_class($this) . "::{$fn}::@ ", LOG_DEBUG);
    $test = $this->params['url'];
    if ($this->request->is('restful') && !empty($this->params['url']['copersonid'])  && !empty($this->params['url']['coid']) ) {
      // We need to retrieve via a join, which StandardController::index() doesn't
      // currently support.

      try {       
        $syncEntitlements = new SyncEntitlements($this->CoEntitlementProvisionerTarget->getConfiguration($this->params['url']['coid']));
        $groups = $syncEntitlements->getEntitlements($this->params['url']['coid'], $this->params['url']['copersonid']);

        if (!empty($groups)) {
          $this->set('co_groups', $groups);
        } else {
          $this->Api->restResultHeader(204, "CO Person Has No Groups");
          return;
        }
      } catch (InvalidArgumentException $e) {
        $this->Api->restResultHeader(404, "CO Person Unknown");
        return;
      }
    } else {
      parent::index();
    }
  }


  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */

  function isAuthorized()
  {
    $fn = "isAuthorized";
    $this->log(get_class($this) . "::{$fn}::@ ", LOG_DEBUG);
    $roles = $this->Role->calculateCMRoles();

    $managed = false;
    $managedp = false;
    $readonly = false;
    $self = false;

    if (!empty($roles['copersonid'])) {
      // XXX Shouldn't this just use CoGroupMember->findCoPersonGroupRoles?
      $args = array();
      $args['conditions']['CoGroupMember.co_person_id'] = $roles['copersonid'];
      $args['conditions']['CoGroupMember.owner'] = true;
      // Only pull currently valid group memberships
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['contain'] = false;

      $own = $this->CoGroup->CoGroupMember->find('all', $args);

      $args = array();
      $args['conditions']['CoGroupMember.co_person_id'] = $roles['copersonid'];
      $args['conditions']['CoGroupMember.member'] = true;
      // Only pull currently valid group memberships
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['contain'] = false;

      $member = $this->CoGroup->CoGroupMember->find('all', $args);
      
      if (!empty($this->request->params['pass'][0])) {
        $managed = $this->Role->isGroupManager($roles['copersonid'], $this->request->params['pass'][0]);
      }

      if (!empty($this->request->params['named']['copersonid'])) {
        $managedp = $this->Role->isCoAdminForCoPerson(
          $roles['copersonid'],
          $this->request->params['named']['copersonid']
        );
        if ($roles['copersonid'] == $this->request->params['named']['copersonid']) {
          $self = true;
        }
      } elseif ($roles['copersonid'] == $this->Session->read('Auth.User.co_person_id')) {
        $self = true;
      }
    }

    if (!empty($this->request->params['pass'][0])) {
      $readonly = $this->CoGroup->readOnly(filter_var($this->request->params['pass'][0], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
    }

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Determine what operations this user can perform

    // Add a new Group?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Create an admin Group?
    $p['admin'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Delete an existing Group?
    $p['delete'] = (!$readonly && ($roles['cmadmin'] || $managed));

    // Edit an existing Group?
    $p['edit'] = (!$readonly && ($roles['cmadmin'] || $managed));

    // View history for an existing Group?
    $p['history'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed);

    // View all existing Groups?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Reconcile memberships in a members group?
    $p['reconcile'] = ((empty($this->request->params['pass'][0]) || $readonly)
      && ($roles['cmadmin'] || $roles['coadmin']));

    if (
      $this->action == 'index' && $p['index']
      && ($roles['cmadmin'] || $roles['coadmin'])
    ) {
      // Set all permissions for admins so index view links render.

      $p['delete'] = true;
      $p['edit'] = true;
      $p['reconcile'] = true;
      $p['view'] = true;
    }

    $p['member'] = !empty($curlRoles['member']) ? $curlRoles['member'] : array();
    $p['owner'] = !empty($curlRoles['owner']) ? $curlRoles['owner'] : array();

    // (Re)provision an existing CO Group?
    $p['provision'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);

    // Select from a list of potential Groups to join?
    $p['select'] = ($roles['cmadmin']
      || ($managedp && ($roles['coadmin'] || $roles['couadmin']))
      || $self);

    // Select from any Group (not just open or owned)?
    $p['selectany'] = ($roles['cmadmin']
      || ($managedp && ($roles['coadmin'] || $roles['couadmin'])));

    // View an existing Group?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $managed);

    // Search from a list of potential Groups to join?
    $p['search'] = ($roles['cmadmin']
      || ($managedp && ($roles['coadmin'] || $roles['couadmin']))
      || $self);

    if (
      $this->action == 'view'
      && isset($this->request->params['pass'][0])
    ) {
      // Adjust permissions for members and open groups

      if (isset($member) && in_array($this->request->params['pass'][0], $p['member']))
        $p['view'] = true;

      $args = array();
      $args['conditions']['CoGroup.id'] = $this->request->params['pass'][0];
      $args['contain'] = false;

      $g = $this->CoGroup->find('first', $args);

      if (!empty($g) && isset($g['CoGroup']['open']) && $g['CoGroup']['open']) {
        $p['view'] = true;
      }
    }

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
