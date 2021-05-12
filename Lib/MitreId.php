<?php
App::uses('CakeLog', 'Log');
/**
 * This class is quering the MITREid Connect Database
 *
 * 
 */
class MitreId
{

  //public static $entitlementFormat = '/(^urn:mace:egi.eu:(.*)#aai.egi.eu$)|(^urn:mace:egi.eu:aai.egi.eu:(.*))/i';
  
  /**
   * config
   *
   * @param  mixed $mitreId
   * @param  mixed $datasource
   * @param  string $table_name
   * @param  array $coProvisioningTargetData
   * @return void
   */
  public static function config($mitreId, $datasource, $table_name, $coProvisioningTargetData = NULL, $user_profile = NULL)
  {
    $mitreId->useDbConfig = $datasource->configKeyName;
    $mitreId->useTable = $table_name;
    $mitreId->userProfile = $user_profile;
    if(!is_null($coProvisioningTargetData)) {
      foreach($coProvisioningTargetData as $key => $value) {
        if(!in_array($key, array('id', 'deleted', 'created', 'modified', 'co_provisioning_target_id'))) {
          $key = lcfirst(Inflector::camelize($key));
          $mitreId->$key = $value;
        }
      }
    }
  }
  
  /**
   * getCurrentEntitlements
   *
   * @param  mixed $mitreId
   * @param  integer $user_id
   * @return array
   */
  public static function getCurrentEntitlements($mitreId, $user_id) {
    $current_entitlements = $mitreId->find('all', array('conditions' => array('MitreIdEntitlements.user_id' => $user_id)));
    if(empty($current_entitlements)) {
        return array();
    }
    $current_entitlements = Hash::extract($current_entitlements, '{n}.MitreIdEntitlements.edu_person_entitlement');
    return $current_entitlements;
  }
  
  /**
   * deleteOldEntitlements
   *
   * @param  mixed $mitreId
   * @param  integer $user_id
   * @param  array $current_entitlements
   * @param  array $new_entitlements
   * @return void
   */
  public static function deleteOldEntitlements($mitreId, $user_id, $current_entitlements, $new_entitlements) {
    $deleteEntitlements_white = array();
    $deleteEntitlements_format = array();

    // Find the candidate Entitlements
    $deleteEntitlements = array_diff($current_entitlements, $new_entitlements);
    // There is nothing to delete
    if(empty($deleteEntitlements)) {
        return;
    }
    //Remove the ones matching the Entitlement Format regex
    if(!empty($mitreId->entitlementFormat)) {
      $deleteEntitlements_format  = preg_grep($mitreId->entitlementFormat, $deleteEntitlements);
    }
    // Remove the ones constructed from the VO Whitelist
    if($mitreId->entitlementFormatIncludeVowht
       && !empty($mitreId->voWhitelist)) {
        $vowhite_list = explode(",", $mitreId->voWhitelist);
        foreach ($vowhite_list as $vo_name) {
            $whitelist_regex = "/" . $mitreId->urnNamespace . ":group:" . $vo_name . ":(.*)#" . $mitreId->urnAuthority . "/i";
            $deleteEntitlements_tmp  = preg_grep($whitelist_regex, $deleteEntitlements);
            $deleteEntitlements_white = array_merge($deleteEntitlements_white, $deleteEntitlements_tmp);
        }
    }
    // Calculate the final list of entitlements to be deleted
    $deleteEntitlements = array_merge($deleteEntitlements_white, $deleteEntitlements_format);
    CakeLog::write('debug', __METHOD__ . ':: entitlements to be deleted from MitreId: ' . print_r($deleteEntitlements, true), LOG_DEBUG);
    if(!empty($deleteEntitlements)) {
      //Delete
      $deleteEntitlementsParam = '(\'' . implode("','", $deleteEntitlements) . '\')';
      $mitreId->query('DELETE FROM user_edu_person_entitlement'
        . ' WHERE user_id=' . $user_id
        . ' AND edu_person_entitlement IN ' . $deleteEntitlementsParam);

      if($mitreId->rciamExternalEntitlements) {
        // Import the ones from third parties
        MitreId::insertRciamSyncVomsEntitlements($mitreId, $user_id);
      }
    }
  }
  
    /**
   * renameEntitlementsByCou
   *
   * @param  mixed $mitreId
   * @param  mixed $old_group_name
   * @param  mixed $new_group_name
   * @param  mixed $urn_namespace
   * @param  mixed $urn_legacy
   * @param  mixed $urn_authority
   * @param  mixed $vo_group_prefix
   * @return void
   */
  public static function deleteEntitlementsByCou($mitreId, $cou_name,  $urn_namespace, $urn_legacy, $urn_authority) {
    if(!empty($mitreId->entitlementFormat)
       && strpos($mitreId->entitlementFormat,"/") == 0) {
      $regex = explode('/', $mitreId->entitlementFormat)[1];
    } else {
      $regex = $mitreId->entitlementFormat;
    }
    
    $group = !empty($group_name) ? ":" . $group_name : "";
    // cou_name are already url_encoded
    $entitlement_regex = '^' . $urn_namespace . ":group:" . str_replace('+','\+', $cou_name) . $group . ":(.*)#" . $urn_authority;

    if($urn_legacy) {
      $entitlement_regex = '('. $entitlement_regex . ')|(^'.$urn_namespace . ":group:" . str_replace('+','\+', $cou_name) .'#'. $urn_authority . ')';
    }
    $query = 'DELETE FROM user_edu_person_entitlement'
    . ' WHERE edu_person_entitlement ~  \''. $entitlement_regex .'\' AND edu_person_entitlement ~ \'' .$regex. '\'';

    CakeLog::write('debug', __METHOD__ . ':: delete entitlements by cou: ' . $query, LOG_DEBUG);
    $mitreId->query($query);

    if($mitreId->rciamExternalEntitlements) {
      // Import the ones from third parties
      MitreId::insertRciamSyncVomsEntitlements($mitreId, $user_id);
    }
  }
   
  /**
   * deleteEntitlementsByGroup
   *
   * @param  mixed $mitreId
   * @param  mixed $group_name
   * @param  mixed $urn_namespace
   * @param  mixed $urn_legacy
   * @param  mixed $urn_authority
   * @param  mixed $vo_group_prefix
   * @return void
   */
  public static function deleteEntitlementsByGroup($mitreId, $group_name, $urn_namespace, $urn_legacy, $urn_authority, $vo_group_prefix) {
    if(!empty($mitreId->entitlementFormat)
       && strpos($mitreId->entitlementFormat,"/") === 0) {
      $regex = explode('/', $mitreId->entitlementFormat)[1];
    } else {
      $regex = $mitreId->entitlementFormat;
    }
    
    $entitlement_regex = '^'.$urn_namespace.':group:'.$vo_group_prefix.':'. str_replace('+','\+', urlencode($group_name)) .'(.*)'; 
    if($urn_legacy) {
      $entitlement_regex = '('. $entitlement_regex . ')|(^'.$urn_namespace.':'.$urn_authority.':(.*)@'.urlencode($group_name).')';
    }
    $query = 'DELETE FROM user_edu_person_entitlement'
    . ' WHERE edu_person_entitlement ~  \''. $entitlement_regex .'\' AND edu_person_entitlement ~ \'' .$regex. '\'';

    CakeLog::write('debug', __METHOD__ . ':: delete entitlements by group: ' . $query, LOG_DEBUG);

   $mitreId->query($query);
  }

   
  /**
   * renamentitlementsByGroup
   *
   * @param  mixed $mitreId
   * @param  mixed $old_group_name
   * @param  mixed $new_group_name
   * @param  mixed $urn_namespace
   * @param  mixed $urn_legacy
   * @param  mixed $urn_authority
   * @param  mixed $vo_group_prefix
   * @return void
   */
  public static function renameEntitlementsByGroup($mitreId, $old_group_name, $new_group_name,  $urn_namespace, $urn_legacy, $urn_authority, $vo_group_prefix) {
    if(strpos($mitreId->entitlementFormat,"/") == 0) {
      $regex = explode('/', $mitreId->entitlementFormat)[1];
    }
    else {
      $regex = $mitreId->entitlementFormat;
    }
    $entitlement_regex = '^' . $urn_namespace . ':group:' . $vo_group_prefix . ':' . str_replace('+','\+', urlencode($old_group_name)) . '(.*)';
    if($urn_legacy) {
      $entitlement_regex = '(' . $entitlement_regex . ')|(^' . $urn_namespace . ':' . $urn_authority . ':(.*)@' . str_replace('+','\+', urlencode($old_group_name)) . ')';
    }
    $query = 'UPDATE user_edu_person_entitlement SET edu_person_entitlement = REPLACE(edu_person_entitlement, \'' . urlencode($old_group_name) . '\',\'' . urlencode($new_group_name) . '\')'
    . ' WHERE edu_person_entitlement ~  \'' . $entitlement_regex . '\' AND edu_person_entitlement ~ \'' . $regex . '\'';
    CakeLog::write('debug', __METHOD__ . ':: rename entitlements by group: ' . $query, LOG_DEBUG);
    $mitreId->query($query);
  }

  /**
   * renameEntitlementsByCou
   *
   * @param  mixed $mitreId
   * @param  mixed $old_group_name
   * @param  mixed $new_group_name
   * @param  mixed $urn_namespace
   * @param  mixed $urn_legacy
   * @param  mixed $urn_authority
   * @param  mixed $vo_group_prefix
   * @return void
   */
  public static function renameEntitlementsByCou($mitreId, $old_cou_name, $new_cou_name,  $urn_namespace, $urn_legacy, $urn_authority) {
    if(strpos($mitreId->entitlementFormat,"/") == 0) {
      $regex = explode('/', $mitreId->entitlementFormat)[1];
    }
    else {
      $regex = $mitreId->entitlementFormat;
    }
    
    $group = !empty($group_name) ? ":" . $group_name : "";
    // old_cou_name and new_cou_name are already url_encoded
    $entitlement_regex = '^' . $urn_namespace . ":group:" . str_replace('+','\+', $old_cou_name) . $group . ":(.*)#" . $urn_authority;

    if($urn_legacy) {
      $entitlement_regex = '('. $entitlement_regex . ')|(^'.$urn_namespace . ":group:" . str_replace('+','\+', $old_cou_name) .'#'. $urn_authority . ')';
    }
    $query = 'UPDATE user_edu_person_entitlement SET edu_person_entitlement = REPLACE(edu_person_entitlement, \''. $old_cou_name .'\',\''. $new_cou_name .'\') '
    . 'WHERE edu_person_entitlement ~  \''. $entitlement_regex .'\' AND edu_person_entitlement ~ \'' .$regex. '\'';
    CakeLog::write('debug', __METHOD__ . ':: rename entitlements by cou: ' . $query, LOG_DEBUG);
    $mitreId->query($query);
  }

  /**
   * deleteAllEntitlements
   *
   * @param  mixed $mitreId
   * @param  integer $user_id
   * @return void
   */
  public static function deleteAllEntitlements($mitreId, $user_id) {
    
    $query = 'DELETE FROM user_edu_person_entitlement'
    . ' WHERE user_id=' . $user_id;
    CakeLog::write('debug', __METHOD__ . ':: delete all entitlements from mitreid for user :' . $user_id . 'with query' . $query, LOG_DEBUG);
    $mitreId->query($query);
  }
  
  /**
   * insertNewEntitlements
   *
   * @param  mixed $mitreId
   * @param  integer $user_id
   * @param  array $current_entitlements
   * @param  array $new_entitlements
   * @return void
   */
  public static function insertNewEntitlements($mitreId, $user_id, $current_entitlements, $new_entitlements) {
    $insertEntitlements = array_diff($new_entitlements, $current_entitlements);
    CakeLog::write('debug', __METHOD__ . ':: entitlements to be inserted to MitreId' . print_r($insertEntitlements, true), LOG_DEBUG);
    if(!empty($insertEntitlements)) {
      //Insert
      $insertEntitlementsParam = '';
      foreach ($insertEntitlements as $entitlement) {
        $insertEntitlementsParam .= '(' . $user_id . ',\'' . $entitlement . '\'),';
      }
      $mitreId->query('INSERT INTO user_edu_person_entitlement (user_id, edu_person_entitlement) VALUES ' . substr($insertEntitlementsParam, 0, -1));
    }
    if($mitreId->rciamExternalEntitlements) {
      // Import the ones from third parties
      MitreId::insertRciamSyncVomsEntitlements($mitreId, $user_id);
    }
  }

  /**
   * @param $mitreId
   * @param $mitre_user_id
   */
  public static function insertRciamSyncVomsEntitlements($mitreId, $mitre_user_id)
  {
    // Fetch users Certificates
    if(empty($mitreId->userProfile['Cert'])) {
      return;
    }
    // Link to my table
    $mdl_name = Inflector::camelize(MitreIdProvisionerRciamSyncVomsCfg::TableName);
    // XXX Since this table uses the useDbConfig = "default" i can not use
    // CAKEPHP's embeded PDO because it will always add the prefix cm_
    $RciamModel = ClassRegistry::init($mdl_name);
    // Get configuration
    $vo_roles = !empty($mitreId->voRoles) ? explode(",", $mitreId->voRoles) : array();

    $entitlements = array();
    $user_id_entries = Hash::extract($mitreId->userProfile['Cert'], '{n}.Cert.subject');
    // Construct the entitlements
    foreach ($user_id_entries as $userId) {
      $blacklist = '(\'' . implode("','", MitreIdProvisionerRciamSyncVomsCfg::VoBlackList) . '\')';
      $vo_query =
        "select t.vo_id"
        . " from " MitreIdProvisionerRciamSyncVomsCfg::TableName . " t"
        . " where t.subject='" . $userId . "'"
        . " and t.vo_id IS NOT NULL"
        . " and t.vo_id NOT IN " . $blacklist;
      $vos = $RciamModel->query($vo_query);
      // Remove the unnecessary levels
      $vo_names = Hash::extract($vos, '{n}.{n}.vo_id');
      foreach($vo_names as $name) {
        foreach ($vo_roles as $role) {
          $entitlement =
            $mitreId->urnNamespace                 // URN namespace
            . ":group:" . urlencode($name) . ":"   // VO
            . "role=" . $role                      // role
            . "#" . $mitreId->urnAuthority;        // AA FQDN TODO
          $entitlements[] = $entitlement;
        }
      }
    }

    // Push the entitlements
    if (count($entitlements) > 0) {
      $insertEntitlementsParam = '';
      foreach ($entitlements as $ent_insert) {
        $insertEntitlementsParam .= '(' . $mitre_user_id . ',\'' . $ent_insert . '\'),';
      }
      if(!empty($insertEntitlementsParam)) {
        $insert_query =
          'INSERT INTO user_edu_person_entitlement (user_id, edu_person_entitlement) VALUES '
          . substr($insertEntitlementsParam, 0, -1)
          . ' ON CONFLICT ON CONSTRAINT user_id_eduperson_entitlement_unique DO NOTHING';
        // Push everything into the database
        CakeLog::write('debug', __METHOD__ . ':: Insert third party entitlements: ' . $insert_query, LOG_DEBUG);
        $mitreId->query($insert_query);
      }
    }

  }

}
