<?php
App::uses('CoProvisionerPluginTarget', 'Model');
App::uses('CakeLog', 'Log');

class SyncEntitlements{
  public  $state = array();
  public  $config = null;
  public  $nested_cous_paths;
  public  $coId;
  
  /**
   * __construct
   *
   * @param  array $config
   * @param  integer $coId
   * @return void
   */
  public function __construct($config, $coId){
    $this->state['Attributes'] = array();
    $this->config = $config;
    $this->coId = $coId;
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
          . "#" . $this->urnAuthority; // AA FQDN
        // Enable legacy URN syntax for compatibility reasons?
        if($this->config['urn_legacy']) {
          $this->state['Attributes']['eduPersonEntitlement'][] =
            $this->config['urn_namespace']          // URN namespace
            . ':' . $this->config['urnAuthority']  // AA FQDN
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
    $cous = $cous[0];
    foreach($cous as $cou) {
      if(empty($cou['group_name']) || empty($cou['cou_id'])) {
        continue;
      }

      $recursive_query = QueryConstructor::getRecursiveQuery($cou['cou_id']);
      $result = $this->CoGroup->query($recursive_query);

      foreach($result as $row) {
        $row = $row[0];
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
    CakeLog::write('debug', __METHOD__ . "::getCouTreeStructure: nested_cous_paths= => " . var_export($this->nested_cous_paths, true), LOG_DEBUG);
  }
  
  /**
   * getEntitlements
   *
   * @param  mixed $coPersonId
   * @return array 
   */
  public function getEntitlements($coPersonId) {
    // XXX Get all the memberships from the the CO for the user
    $co_memberships = SyncEntitlements::getMemberships($coPersonId);
    // XXX if this is empty return
    if(empty($co_memberships)) {
      if(!array_key_exists('eduPersonEntitlement', $this->state['Attributes'])) {
        $this->state['Attributes']['eduPersonEntitlement'] = array();
      }
      return;
    }
    // XXX Extract the group memberships
    $group_memberships = array_filter(
      $co_memberships,
      static function ($value) {
        if(is_null($value[0]['cou_id'])) {
          return $value;
        }
      }
    );

    CakeLog::write('debug', __METHOD__ . "::group_memberships => " . var_export($group_memberships, true), LOG_DEBUG);
    // XXX Construct the plain group Entitlements
    $this->groupEntitlementAssemble($group_memberships);

    // XXX Get the Nested COUs for the user
    $nested_cous = [];
    $this->getCouTreeStructure($co_memberships);
    return $this->state['Attributes']['eduPersonEntitlement'];
    //return $group_memberships;
  }
}
