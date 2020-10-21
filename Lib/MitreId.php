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
   * @return void
   */
  public static function config($mitreId, $datasource, $table_name, $entitlement_format = NULL)
  {
    $mitreId->useDbConfig = $datasource->configKeyName;
    $mitreId->useTable = $table_name;
    $mitreId->entitlementFormat = $entitlement_format;
  }
  
  /**
   * getCurrentEntitlements
   *
   * @param  mixed $mitreId
   * @param  integer $user_id
   * @return void
   */
  public static function getCurrentEntitlements($mitreId, $user_id) {
    $current_entitlements = $mitreId->find('all', array('conditions' => array('MitreIdEntitlements.user_id' => $user_id)));
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
    $deleteEntitlements = array_diff($current_entitlements, $new_entitlements);
    //Remove only those from check-in
    if(!empty($mitreId->entitlementFormat)) {
      $deleteEntitlements  = preg_grep($mitreId->entitlementFormat, $deleteEntitlements);
    }
    CakeLog::write('debug', __METHOD__ . ':: entitlements to be deleted from MitreId' . var_export($deleteEntitlements, true), LOG_DEBUG);
    if(!empty($deleteEntitlements)) {
      //Delete
      $deleteEntitlementsParam = '(\'' . implode("','", $deleteEntitlements) . '\')';
      $mitreId->query('DELETE FROM user_edu_person_entitlement'
        . ' WHERE user_id=' . $user_id
        . ' AND edu_person_entitlement IN ' . $deleteEntitlementsParam);
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
    if(strpos($mitreId->entitlementFormat,"/") == 0) {
      $regex = explode('/', $mitreId->entitlementFormat)[1];
    }
    else {
      $regex = $mitreId->entitlementFormat;
    }
    
    $group = !empty($group_name) ? ":" . $group_name : "";
    // cou_name are already url_encoded
    $entitlement_regex = '^' . $urn_namespace . ":group:" . str_replace('+','\+', $cou_name) . $group . ":(.*)#" . $urn_authority;

    if($urn_legacy) {
      $entitlement_regex = '('. $entitlement_regex . ') | (^'.$urn_namespace . ":group:" . str_replace('+','\+', $cou_name) .'#'. $urn_authority . ')';
    }
    $query = 'DELETE FROM user_edu_person_entitlement'
    . ' WHERE edu_person_entitlement ~  \''. $entitlement_regex .'\' AND edu_person_entitlement ~ \'' .$regex. '\'';

    CakeLog::write('debug', __METHOD__ . ':: delete entitlements by cou: ' . $query, LOG_DEBUG);
    $mitreId->query($query);
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
    if(strpos($mitreId->entitlementFormat,"/") === 0)
      $regex = explode('/', $mitreId->entitlementFormat)[1];
    else
      $regex = $mitreId->entitlementFormat;
    
    $entitlement_regex = '^'.$urn_namespace.':group:'.$vo_group_prefix.':'. str_replace('+','\+', urlencode($group_name)) .'(.*)'; 
    if($urn_legacy) {
      $entitlement_regex = '('. $entitlement_regex . ') | (^'.$urn_namespace.':'.$urn_authority.':(.*)@'.urlencode($group_name).')';
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
      $entitlement_regex = '(' . $entitlement_regex . ') | (^' . $urn_namespace . ':' . $urn_authority . ':(.*)@' . str_replace('+','\+', urlencode($old_group_name)) . ')';
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
      $entitlement_regex = '('. $entitlement_regex . ') | (^'.$urn_namespace . ":group:" . str_replace('+','\+', $old_cou_name) .'#'. $urn_authority . ')';
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
    CakeLog::write('debug', __METHOD__ . ':: entitlements to be inserted to MitreId' . var_export($insertEntitlements, true), LOG_DEBUG);
    if(!empty($insertEntitlements)) {
      //Insert
      $insertEntitlementsParam = '';
      foreach ($insertEntitlements as $entitlement) {
        $insertEntitlementsParam .= '(' . $user_id . ',\'' . $entitlement . '\'),';
      }
      $mitreId->query('INSERT INTO user_edu_person_entitlement (user_id, edu_person_entitlement) VALUES ' . substr($insertEntitlementsParam, 0, -1));
    }
  }
}
