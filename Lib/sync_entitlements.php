<?php
App::uses("CoProvisionerPluginTarget", "Model");

class SyncEntitlements{

  public  $state = array();
  public  $coEntitlementProvisioningTarget = null;
  public  $nested_cous_paths;

  public function __construct($coEntitlementProvisioningTarget){
    $this->state['Attributes'] = array();
    $this->coEntitlementProvisioningTarget = $coEntitlementProvisioningTarget;
  }

  /**
   * Get all the memberships and affiliations in the specified CO for the specified user. The COUs while have a cou_id
   * The plain Groups will have cou_id=null
   * @param integer $co_id The CO Id that we will retrieve all memberships for the CO Person
   * @param integer $co_person_id The CO Person that we will retrieve the memberships for
   * @return array Array contents: [group_name, cou_id, affiliation, title, member, owner]
   * @throws Exception
   * @uses SimpleSAML_Logger::debug
   * @uses SimpleSAML\Database::getInstance
   */
  public static function getMemberships($obj, $co_id, $co_person_id){

    // Strip the cou_id from the unnecessary characters
    //$queryParams = array(
    //  'co_id'        => array($co_id),
    //  'co_person_id' => array($co_person_id),
    //);

    // XXX Since i voWhitelist only the parent VO/COU i can not filter VOs with the query
    $membership_query =
      "SELECT"
      . " DISTINCT substring(groups.name, '^(?:(?:COU?[:])+)?(.+?)(?:[:]mem.+)?$') as group_name,"
      . " string_agg(DISTINCT groups.cou_id::text, ',') as cou_id,"
      . " CASE WHEN groups.name ~ ':admins' THEN null"
      . " ELSE string_agg(DISTINCT nullif(role.affiliation, ''), ',')"
      . " END AS affiliation,"
      . " CASE WHEN groups.name ~ ':admins' THEN null"
      . " ELSE string_agg(DISTINCT nullif(role.title, ''), ',')"
      . " END AS title,"
      . " bool_or(members.member) as member,"
      . " bool_or(members.owner) as owner"
      . " FROM cm_co_groups AS groups"
      . " INNER JOIN cm_co_group_members AS members ON groups.id=members.co_group_id"
      . " AND members.co_group_member_id IS NULL"
      . " AND NOT members.deleted"
      . " AND groups.co_group_id IS NULL"
      . " AND NOT groups.deleted"
      . " AND groups.name not ilike '%members:all'"
      . " AND groups.name not ilike 'CO:admins'"
      . " AND groups.name not ilike 'CO:members:active'"
      . " AND members.co_person_id= " . $co_person_id
      . " AND groups.co_id = " . $co_id
      . " AND groups.status = 'A'"
      . " LEFT OUTER JOIN cm_cous AS cous ON groups.cou_id = cous.id"
      . " AND NOT cous.deleted"
      . " AND cous.cou_id IS NULL"
      . " LEFT OUTER JOIN cm_co_person_roles AS ROLE ON cous.id = role.cou_id"
      . " AND role.co_person_role_id IS NULL"
      . " AND role.status = 'A'"
      . " AND NOT role.deleted    AND role.co_person_id = members.co_person_id"
      . " GROUP BY"
      . " groups.name";




    $result = $obj->CoGroup->query($membership_query);

    /* $stmt = $db->read($membership_query, $queryParams);
        if($stmt->execute()) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[] = $row;
            }
            SimpleSAML_Logger::debug("[attrauthcomanage] getMemberships: result="
                . var_export($result, true)
            );
            return $result;
        } else {
            throw new Exception('Failed to communicate with COmanage Registry: ' . var_export($db->getLastError(), true));
        }*/

    return $result;
  }

  /**
   * Construct the plain group entitlements. No nesting supported.
   * @param array $memberships_groups
   * @param integer $co_id
   * @param string $voPrefix
   * @todo Replace voPrefix with a configuration variable
   * @todo Replace $co_id with a configuration variable
   */
  private function groupEntitlemeAssemble($memberships_groups, $co_id, $voPrefix){
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
      $groupPrefix = ($co_id === 5) ? $voPrefix . 'group:' : $voPrefix . 'registry:';
      foreach($roles as $role) {
        $this->state['Attributes']['eduPersonEntitlement'][] =
          $this->coEntitlementProvisioningTarget['urn_namespace']          // URN namespace
          . ":group:registry:"         // URN namespace
          . urlencode($group['group_name'])      // VO
          . ":role=" . $role             // role
          . "#" . $this->urnAuthority; // AA FQDN
        // Enable legacy URN syntax for compatibility reasons?
        if($this->coEntitlementProvisioningTarget['urn_legacy']) {
          $this->state['Attributes']['eduPersonEntitlement'][] =
            $this->coEntitlementProvisioningTarget['urn_namespace']          // URN namespace
            . ':' . $this->urnAuthority  // AA FQDN
            . ':' . $role                // role
            . "@"                        // VO delimiter
            . urlencode($group['group_name']);     // VO
        }
      }
    }
  }

  private function constructRecursiveQuery($couId){
    $recursive_query =
      "WITH RECURSIVE cous_cte(id, name, parent_id, depth, path) AS ("
      . " SELECT cc.id, cc.name, cc.parent_id, 1::INT AS depth, cc.name::TEXT AS path, cc.id::TEXT AS path_id"
      . " FROM cm_cous AS cc"
      . " WHERE cc.parent_id IS NULL"
      . " UNION ALL"
      . " SELECT c.id, c.name, c.parent_id, p.depth + 1 AS depth,"
      . " (p.path || ':' || c.name::TEXT),"
      . " (p.path_id || ':' || c.id::TEXT)"
      . " FROM cous_cte AS p, cm_cous AS c"
      . " WHERE c.parent_id = p.id"
      . " )"
      . " SELECT * FROM cous_cte AS ccte where ccte.id=" . $couId;

    return $recursive_query;
  }

  /**
   * Returns nested COU path ready to use in an AARC compatible entitlement
   * @param array $cous
   * @param array $nested_cous_paths
   * @throws RuntimeException Failed to communicate with COmanage database
   * @uses SimpleSAML_Logger::debug
   * @uses SimpleSAML\Database::getInstance
   */

  private function getCouTreeStructure($obj, $cous) {
    $cous = $cous[0];
    foreach($cous as $cou) {
      if(empty($cou['group_name']) || empty($cou['cou_id'])) {
        continue;
      }
      // Strip the cou_id from the unnecessary characters
      //$queryParams = array(
      //  'cou_id' => array($cou['cou_id'], PDO::PARAM_INT),
      //);
      //$stmt = $db->read($recursive_query, $queryParams);
      $recursive_query = $this->constructRecursiveQuery($cou['cou_id']);
      $result = $obj->CoGroup->query($recursive_query);

      foreach($result as $row) {
        $row = $row[0];
        //            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if(strpos($row['path'], ':') !== false) {
          $this->nested_cous_paths += [
            $cou['cou_id'] => [
              'path'           => $row['path'],
              'path_id_list'   => explode(':', $row['path_id']),
              'path_full_list' => array_combine(
                explode(':', $row['path_id']), // keys
                explode(':', $row['path'])     // values
              ),
            ],
          ];
        }
      }
    }
    //SimpleSAML_Logger::debug("[attrauthcomanage] getCouTreeStructure: nested_cous_paths=" . var_export($nested_cous_paths, true));

    $obj->log(__METHOD__ . "::getCouTreeStructure: nested_cous_paths= => " . var_export($this->nested_cous_paths, true), LOG_DEBUG);
  }

  public function get_entitlements($obj) {
    // XXX Get all the memberships from the the CO for the user
    $co_memberships = SyncEntitlements::getMemberships($obj, 2, 1635);
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

    $obj->log(__METHOD__ . "::group_memberships => " . var_export($group_memberships, true), LOG_DEBUG);
    // XXX Construct the plain group Entitlements
    $this->groupEntitlemeAssemble($group_memberships, $obj->coId, $obj->voPrefix);

    // XXX Get the Nested COUs for the user
    $nested_cous = [];
    $this->getCouTreeStructure($obj, $co_memberships);

    return $group_memberships;
   

  }
}
