<?php
App::uses('CoProvisionerPluginTarget', 'Model');
App::uses('CakeLog', 'Log');

class SyncEntitlements{
  public  $state = array();
  public  $config = null;
  public  $nested_cous_paths;
  public  $members_entitlements;
  public  $coId;
  
  /**
   * __construct
   *
   * @param  array $config
   * @param  integer $coId
   * @return void
   */
  public function __construct($config, $coId){
    $this->state['Attributes']['eduPersonEntitlement'] = array();
    $this->config = $config;
    $this->coId = $coId;
    $this->nested_cous_paths = array();
  }

  /**
   * Get all the memberships and affiliations in the specified CO for the specified user. The COUs while have a cou_id
   * The plain Groups will have cou_id=null
   * @param integer $co_person_id The CO Person that we will retrieve the memberships for
   * @return array Array contents: [group_name, cou_id, affiliation, title, member, owner]
   */
  public function getMemberships($co_person_id){
    $membership_query = QueryConstructor::getMembershipQuery($this->coId, $co_person_id);
    $CoGroup = ClassRegistry::init('CoGroup');
    return $CoGroup->query($membership_query);
  }
  
  /**
   * get_vo_group_prefix
   *
   * @return string
   */
  private function get_vo_group_prefix(){
    $Co = ClassRegistry::init('Co');
    return empty($this->config['vo_group_prefix']) ? urlencode($Co->field('name', array('Co.id' => $this->coId))).':group' : urlencode($this->config['vo_group_prefix']);
  }

  /**
   * Construct the plain group entitlements. No nesting supported.
   * @param array $memberships_groups
   */
  private function groupEntitlementAssemble($memberships_groups){
    if(empty($memberships_groups)) {
      return;
    }
    foreach($memberships_groups as $group) {
      $roles = array();
      // especially for comanage
      $group = $group[0];
      if($group['member'] === true) {
        $roles[] = "member";
      }
      if($group['owner'] === true) {
        $roles[] = "owner";
      }
      if(!array_key_exists('eduPersonEntitlement', $this->state['Attributes'])) {
        $this->state['Attributes']['eduPersonEntitlement'] = array();
      }
      // todo: Move this to configuration
      $voGroupPrefix = $this->get_vo_group_prefix();
      foreach($roles as $role) {
        $this->state['Attributes']['eduPersonEntitlement'][] =
          $this->config['urn_namespace']          // URN namespace
          . ":group:" . $voGroupPrefix . ":"   // Group Prefix
          . urlencode($group['group_name'])      // VO
          . ":role=" . $role             // role
          . "#" . $this->config['urn_authority']; // AA FQDN
        // Enable legacy URN syntax for compatibility reasons?
        if($this->config['urn_legacy']) {
          $this->state['Attributes']['eduPersonEntitlement'][] =
            $this->config['urn_namespace']          // URN namespace
            . ':' . $this->config['urn_authority']  // AA FQDN
            . ':' . $role                // role
            . "@"                        // VO delimiter
            . urlencode($group['group_name']);     // VO
        }
      }
    }
  }


  /**
   * Returns nested COU path ready to use in an AARC compatible entitlement
   * @param array $cous
   */

  private function getCouTreeStructure($cous) {
    
    foreach($cous as $cou) {
      $cou = $cou[0];
      if(empty($cou['group_name']) || empty($cou['cou_id'])) {
        continue;
      }

      $recursive_query = QueryConstructor::getRecursiveQuery($cou['cou_id']);
      $CoGroup = ClassRegistry::init('CoGroup');
      $result = $CoGroup->query($recursive_query);

      foreach($result as $row) {
        $row = $row[0];
        /// If ':' does exist
        if(strpos($row['path'], ':') !== false) {
          $path_group_list = explode(':', $row['path']);
          $path_group_list = array_map(function($group){
            return urlencode($group);
          }, $path_group_list);

          $this->nested_cous_paths += [
            $cou['cou_id'] => [
              'path'           => implode(':', $path_group_list),
              'path_id_list'   => explode(':', $row['path_id']),
              'path_full_list' => array_combine(
                explode(':', $row['path_id']), // keys
                $path_group_list     // values
              ),
            ],
          ];
        }
      }
    }
    CakeLog::write('debug', __METHOD__ . "::getCouTreeStructure: nested_cous_paths => " . var_export($this->nested_cous_paths, true), LOG_DEBUG);
  }

      /**
     * @param string $cou_name the name of the COU
     * @param array $cou_nested Array containing the tree structure of the relevant COUs as composed in getCouTreeStructure
     * @return string cou_name or empty string
     */
    private function getCouRootParent($cou_name, $cou_nested)
    {
      foreach ($cou_nested as $hierarchy) {
        if (!in_array($cou_name, $hierarchy['path_full_list'])) {
          continue;
        }
        return array_values($hierarchy['path_full_list'])[0];
      }
      return '';
    }
  
     /**
     * Add eduPersonEntitlements in the State(no filtering happens here.)
     * @param array $personRoles
     * @param array &$state
     * @param string $vo_name
     * @param string $group_name
     * @param array $memberEntitlements
     * @param integer $cou_id
     * @todo Remove old style entitlements
     * @todo Remove $group_name variable
     */
    private function couEntitlementAssemble($personRoles, $vo_name, $group_name = "", $cou_id = null)
    {
      foreach ($personRoles as $key => $role) {
        // We need this to filter the cou_id or any other irrelevant information
        if (is_string($key) && $key === 'cou_id') {
          continue;
        }
        // Do not create entitlements for the admins group here.
        if (strpos($vo_name, ':admins') !== false) {
          continue;
        }
        if (!empty($role) && is_array($role) && count($role) > 0) {
          $this->couEntitlementAssemble($role, $this->state, $vo_name, $key, $personRoles['cou_id']);
          continue;
        }
        $group = !empty($group_name) ? ":" . $group_name : "";
        $entitlement =
          $this->config['urn_namespace']      // URN namespace
          . ":group:"                         // group literal
          . urlencode($vo_name)               // VO
          . $group . ":role=" . $role         // role
          . "#" . $this->config['urn_authority'];        // AA FQDN
        if (is_array($this->members_entitlements)
            && !is_string($key)
            && $role === 'member') {
          if (!empty($personRoles['cou_id'])) { // Under admin this is not defined
            $this->members_entitlements += [$personRoles['cou_id'] => $entitlement];
          } else {
            $this->members_entitlements['admins'][$cou_id] = $entitlement;
          }
        }
        $this->state['Attributes']['eduPersonEntitlement'][] = $entitlement;
        // TODO: remove in the near future
        if ($this->config['urn_legacy']) {
            $this->state['Attributes']['eduPersonEntitlement'][] =
                  $this->config['urn_namespace']          // URN namespace
                  . ':' . $this->config['urn_authority']  // AA FQDN
                  . $group . ':' . $role       // role
                  . "@"                        // VO delimiter
                  . urlencode($vo_name);       // VO
          } // Depricated syntax
      }
    }

    /**
     * @param integer $couid
     * @param array $cou_nested
     * @return bool
    */
    private function isRootCou($couid, $cou_nested)
    {
      foreach ($cou_nested as $hierarchy) {
        $root_key = array_keys($hierarchy['path_full_list'])[0];
        if ($root_key == $couid) {
          return true;
        }
      }
      return false;
    }

    /**
     * @param array $orphan_memberships
     * @param array $coGroupMemberships
     */
    private function mergeEntitlements($orphan_memberships, $coGroupMemberships)
    {
      CakeLog::write('debug', __METHOD__ . "::mergeEntitlements: members_entitlements => " . var_export($this->members_entitlements, true), LOG_DEBUG);
      CakeLog::write('debug', __METHOD__ . "::mergeEntitlements: cou_tree_structure => " . var_export($this->nested_cous_paths, true), LOG_DEBUG);
      CakeLog::write('debug', __METHOD__ . "::mergeEntitlements: orphan_memberships => " . var_export($orphan_memberships, true), LOG_DEBUG);

      if (empty($this->nested_cous_paths)) {
        return;
      }

      // Retrieve only the entitlements that need handling.
      $filtered_cou_ids = [];
      foreach ($this->nested_cous_paths as $node) {
          $filtered_cou_ids[] = $node['path_id_list'];
      }
      $filtered_cou_ids = array_values(array_unique(array_merge(...$filtered_cou_ids)));
      CakeLog::write('debug', __METHOD__ . "::mergeEntitlements: filtered_cou_ids => " . var_export($filtered_cou_ids, true), LOG_DEBUG);

      // XXX Get the COU ids that also have an admin role
      $filtered_admin_cou_ids = !empty($this->members_entitlements['admins']) ? array_keys($this->members_entitlements['admins']) : array();
      CakeLog::write('debug', __METHOD__ . "::mergeEntitlements: filtered_admin_cou_ids => " . var_export($filtered_admin_cou_ids, true), LOG_DEBUG);

      $filtered_entitlements = array_filter(
          $this->members_entitlements,
          static function ($cou_id) use ($filtered_cou_ids) {
              return in_array(
                  $cou_id,
                  $filtered_cou_ids
              );  // Do not use strict since array_merge returns values as strings
          },
          ARRAY_FILTER_USE_KEY
      );
      
      CakeLog::write('debug', __METHOD__ . "::mergeEntitlements: filtered_entitlements => " . var_export($filtered_entitlements, true), LOG_DEBUG);

      // XXX Create the list of all potential groups
      $allowed_cou_ids = array_keys($filtered_entitlements);
      $list_of_candidate_full_nested_groups = [];
      foreach ($this->nested_cous_paths as $sub_tree) {
          $full_candidate_cou_id = '';
          $full_candidate_entitlement = '';
          foreach ($sub_tree['path_full_list'] as $cou_id => $cou_name) {
              if (in_array($cou_id, $allowed_cou_ids, true)) {
                  $key = array_search($cou_id, $sub_tree['path_id_list']);
                  $cou_name_hierarchy = array_slice($sub_tree['path_full_list'], 0, $key + 1);
                  $full_candidate_entitlement = implode(':', $cou_name_hierarchy);
                  $cou_id_hierarchy = array_slice($sub_tree['path_id_list'], 0, $key + 1);
                  $full_candidate_cou_id = implode(':', $cou_id_hierarchy);
              }
          }
          if (!empty($full_candidate_cou_id) && !empty($full_candidate_entitlement)) {
              $list_of_candidate_full_nested_groups[$full_candidate_cou_id] = $full_candidate_entitlement;
          }
      }

      CakeLog::write('debug', __METHOD__ . "::mergeEntitlements: list_of_candidate_full_nested_groups => " . var_export($list_of_candidate_full_nested_groups, true), LOG_DEBUG);

      // XXX Filter the ones that are subgroups from another
      if ($this->config['merge_entitlements']) {
          $path_id_arr = array_keys($list_of_candidate_full_nested_groups);
          $path_id_cp = array_keys($list_of_candidate_full_nested_groups);
          foreach ($path_id_arr as $path_id_str) {
              foreach ($path_id_cp as $path_id_str_cp) {
                  if (strpos($path_id_str_cp, $path_id_str) !== false
                      && strlen($path_id_str) < strlen($path_id_str_cp)) {
                      unset($path_id_arr[array_search($path_id_str, $path_id_arr)]);
                      continue;
                  }
              }
          }

          $list_of_candidate_full_nested_groups = array_filter(
              $list_of_candidate_full_nested_groups,
              static function ($keys) use ($path_id_arr) {
                  return in_array($keys, $path_id_arr, true);
              },
              ARRAY_FILTER_USE_KEY
          );
      }

      foreach ($list_of_candidate_full_nested_groups as $cou_ids => $vo_nested) {
          $entitlement =
              $this->config['urn_namespace']                 // URN namespace
              . ":group:"                         // group literal
              . $vo_nested                        // VO
              . ":role=member"                    // role
              . "#" . $this->config['urn_authority'];        // AA FQDN

          $this->state['Attributes']['eduPersonEntitlement'][] = $entitlement;

          // Add the admin roles nested entitlements
          foreach (explode(':', $cou_ids) as $cou_id) {
              if (in_array($cou_id, $filtered_admin_cou_ids)) {
                  $entitlement =
                      $this->config['urn_namespace']               // URN namespace
                      . ":group:"                         // group literal
                      . $vo_nested                        // VO
                      . ":admins:role=member"             // admin role
                      . "#" . $this->config['urn_authority'];         // AA FQDN

                  $this->state['Attributes']['eduPersonEntitlement'][] = $entitlement;
                  break;
              }
          }
      }
      $voWhitelist = explode(',', $this->config['vo_whitelist']);
      // XXX Add all the parents with the default roles in the state
      foreach ($this->nested_cous_paths as $cou_id => $sub_tree) {
          // XXX Split the full path and encode each part.
          $parent_vo = array_values($sub_tree['path_full_list'])[0];
          if (!in_array($parent_vo, $voWhitelist, true)) {
              continue;
          }
          // XXX Also exclude the ones that are admin groups
          $cou_exist = array_filter($coGroupMemberships, static function($membership) use ($cou_id){
            return (!empty($membership[0]['cou_id'])
                    && (integer)$membership[0]['cou_id'] === $cou_id
                    && (!empty($membership[0]['affiliation']) || !empty($membership[0]['title'])));
          });
          if (empty($cou_exist)) {
            continue;
          }
          $voRolesDef = explode(',', $this->config['vo_roles']);
          foreach ($voRolesDef as $role) {
              $entitlement =
                  $this->config['urn_namespace']   // URN namespace
                  . ":group:"                      // group literal
                  . $parent_vo                     // VO
                  . ":role=" . $role               // role
                  . "#" . $this->config['urn_authority'];     // AA FQDN

              $this->state['Attributes']['eduPersonEntitlement'][] = $entitlement;
          }
      }

      // XXX Add all orphan admins COU groups in the state
      foreach ($orphan_memberships as $membership) {
          $membership = $membership[0];
          CakeLog::write('debug', __METHOD__ . "::membeship: membeship => " . var_export($membership, true), LOG_DEBUG);
          if ($membership['member'] || $membership['owner']) {
              $membership_roles = [];
              if ($membership['member']) {
                  $membership_roles[] = 'member';
              }
              if ($membership['owner']) {
                  $membership_roles[] = 'owner';
              }
              $vo_name = $membership['group_name'];
              if (array_key_exists($membership['cou_id'], $this->nested_cous_paths)) {
                  $vo_name = $this->nested_cous_paths[$membership['cou_id']]['path'] . ':admins';
              }
              foreach ($membership_roles as $role) {
                  $entitlement =
                      $this->config['urn_namespace']                 // URN namespace
                      . ":group:"                         // group literal
                      . $vo_name                          // VO
                      . ":role=" . $role                  // role
                      . "#" . $this->config['urn_authority'];        // AA FQDN
                  $this->state['Attributes']['eduPersonEntitlement'][] = $entitlement;
              }
          }
      }

      // XXX Remove duplicates
      if(!empty($this->state['Attributes']['eduPersonEntitlement'])) {
        $this->state['Attributes']['eduPersonEntitlement'] = array_unique($this->state['Attributes']['eduPersonEntitlement']);
      }

      // XXX Remove all non root non nested cou entitlements from the $this->state['Attributes']['eduPersonEntitlement']
      $re = '/(.*):role=member(.*)/m';
      foreach ($filtered_entitlements as $couid => $entitlement) {
          if ($this->isRootCou($couid, $this->nested_cous_paths)) {
              continue;
          }
          $voRoles = explode(',', $this->config['vo_roles']);
          foreach ($voRoles as $role) {
              $replacement = '$1:role=' . $role . '$2';
              $replaced_entitlement = preg_replace($re, $replacement, $entitlement);
              $key = array_search($replaced_entitlement, $this->state['Attributes']['eduPersonEntitlement']);
              if (!is_bool($key)) {
                  unset($replaced_entitlement, $this->state['Attributes']['eduPersonEntitlement'][$key]);
              }
          }
      }
    }

  /**
   * getEntitlements
   *
   * @param  mixed $coPersonId
   * @return array 
   */
  public function getEntitlements($coPersonId) {
    // XXX Get all the memberships from the the CO for the user
    $coGroupMemberships = SyncEntitlements::getMemberships($coPersonId);
    // XXX if this is empty return
    if(empty($coGroupMemberships)) {
      if(!array_key_exists('eduPersonEntitlement', $this->state['Attributes'])) {
        $this->state['Attributes']['eduPersonEntitlement'] = array();
      }
      return;
    }
    // XXX Extract the group memberships
    $group_memberships = array_filter(
      $coGroupMemberships,
      static function ($value) {
        if(is_null($value[0]['cou_id'])) {
          return $value;
        }
      }
    );

    CakeLog::write('debug', __METHOD__ . "::group_memberships => " . var_export($group_memberships, true), LOG_DEBUG);
    
    
    $cou_memberships = array_filter(
      $coGroupMemberships,
      static function($value) {
        if(!is_null($value[0]['cou_id'])) {
          return $value;
        }
      }
    );

    CakeLog::write('debug', __METHOD__ . "::cou_memberships => " . var_export($cou_memberships, true), LOG_DEBUG);

    // XXX Construct the plain group Entitlements
    $this->groupEntitlementAssemble($group_memberships);

    // XXX Get the Nested COUs for the user
    $this->getCouTreeStructure($cou_memberships);

    // Define the array that will hold the member entitlements
    $this->members_entitlements = [];
    $voRoles = explode(',', $this->config['vo_roles']);
    $voWhitelist = explode(',', $this->config['vo_whitelist']);
    // Iterate over the COUs and construct the entitlements
    foreach ($cou_memberships as $idx => $cou) {
      $cou = $cou[0];
        if (empty($cou['group_name'])) {
            continue;
        }
      
      $vo_roles = array();
      
      if (!in_array($cou['group_name'], $voWhitelist, true)) {
          // XXX Check if there is a root COU that is in the voWhitelist
          // XXX :admins this is not part of the voWhiteList that's why i do not get forward
          $parent_cou_name = $this->getCouRootParent($cou['group_name'], $this->nested_cous_paths);
          if (!in_array($parent_cou_name, $voWhitelist, true)
              && strpos($cou['group_name'], ':admins') === false) {
              // XXX Remove a child COU that has no parent in the voWhitelist OR
              // XXX Remove if it does not represent an admins group AND
              unset($cou_memberships[$idx]);
              continue;
          }
          if (!in_array($parent_cou_name, $voWhitelist, true)
              && !strpos($cou['group_name'], ':admins') === false){
              continue;
          }
      }
      if (!array_key_exists('eduPersonEntitlement', $this->state['Attributes'])) {
        $this->state['Attributes']['eduPersonEntitlement'] = array();
    }

    $voName = $cou['group_name'];
    
    CakeLog::write('debug', __METHOD__ . "::voName => " . var_export($voName, true), LOG_DEBUG);

    // Assemble the roles
    // If there is nothing to assemble then keep the default ones
    // TODO: Move this to function
    $cou['title'] = !empty($cou['title']) ? $cou['title'] : "";
    $cou['affiliation'] = !empty($cou['affiliation']) ? $cou['affiliation'] : "";
    // Explode both
    $cou_titles = explode(',', $cou['title']);
    $cou_affiliations = explode(',', $cou['affiliation']);

    // XXX Translate the ownership and membership of the group to a role
    if (filter_var($cou['owner'], FILTER_VALIDATE_BOOLEAN)) {
        $vo_roles[] = 'owner';
    }
    if (filter_var($cou['member'], FILTER_VALIDATE_BOOLEAN)) {
        $vo_roles[] = 'member';
    }
    $vo_roles = array_unique(array_merge($cou_titles, $cou_affiliations, $vo_roles));
    $vo_roles = array_filter(
        $vo_roles,
        static function ($value) {
            return !empty($value);
        }
    );
    // Lowercase all roles
    $vo_roles = array_map('strtolower', $vo_roles);
    // Merge the default roles with the ones constructed from the COUs
    $vo_roles = array_unique(array_merge($vo_roles, $voRoles));
    // Get the admins group if exists
    $cou_admins_group = array_values(
        array_filter(
            $cou_memberships,
            static function ($value) use ($voName) {
                if ($value['group_name'] === ($voName . ':admins')) {
                    return $value;
                }
            }
        )
    );

    CakeLog::write('debug', __METHOD__ . "::cou_admins_group => " . var_export($cou_admins_group, true), LOG_DEBUG);

    // Handle as a role the membership and ownership of admins group
    if (!empty($cou_admins_group[0]['member']) && filter_var($cou_admins_group[0]['member'], FILTER_VALIDATE_BOOLEAN)) {
        $vo_roles['admins'][] = 'member';
    }
    if (!empty($cou_admins_group[0]['owner']) && filter_var($cou_admins_group[0]['owner'], FILTER_VALIDATE_BOOLEAN)) {
        $vo_roles['admins'][] = 'owner';
    }

    // XXX This is needed in mergeEntitlements function
    $vo_roles['cou_id'] = $cou['cou_id'];
    // todo: Move upper to voRoles Create function

    CakeLog::write('debug', __METHOD__ . "::retrieveCOPersonData voRoles[{$voName}] => " . var_export($vo_roles, true), LOG_DEBUG);

    $this->couEntitlementAssemble($vo_roles, $voName, "");
    // XXX Remove the ones already done
    unset($cou_memberships[$idx]);
    }
      // Fix nested COUs entitlements
      $this->mergeEntitlements($cou_memberships, $coGroupMemberships);

      if (!empty($this->state['Attributes']['eduPersonEntitlement'])) {
          CakeLog::write('debug', __METHOD__ . "::retrieveCOPersonData AFTER: eduPersonEntitlement= => " . var_export($this->state['Attributes']['eduPersonEntitlement'], true), LOG_DEBUG);
      }

    return $this->state['Attributes']['eduPersonEntitlement'];
    //return $group_memberships;
  }
}
