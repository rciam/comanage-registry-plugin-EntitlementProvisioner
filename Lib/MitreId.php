<?php
App::uses('CakeLog', 'Log');
/**
 * This class is quering the MITREid Connect Database
 *
 * 
 */
class MitreId
{

  public static $entitlementFormat = '/(^urn:mace:egi.eu:(.*)#aai.egi.eu$)|(^urn:mace:egi.eu:aai.egi.eu:(.*))/i';

  public static function config($mitreId, $datasource, $table_name)
  {
    $mitreId->useDbConfig = $datasource->configKeyName;
    $mitreId->useTable = $table_name;
  }

  public static function getCurrentEntitlements($mitreId, $user_id) {
    $current_entitlements = $mitreId->find('all', array('conditions' => array('MitreIdEntitlements.user_id' => $user_id)));
    $current_entitlements = Hash::extract($current_entitlements, '{n}.MitreIdEntitlements.edu_person_entitlement');
    return $current_entitlements;
  }

  public static function deleteOldEntitlements($mitreId, $user_id, $current_entitlements, $new_entitlements) {
    $deleteEntitlements = array_diff($current_entitlements, $new_entitlements);
    //Remove only those from check-in
    $deleteEntitlements  = preg_grep(MitreId::$entitlementFormat, $deleteEntitlements);
    if(!empty($deleteEntitlements)) {
      //Delete
      $deleteEntitlementsParam = '(\'' . implode("','", $deleteEntitlements) . '\')';
      $mitreId->query('DELETE FROM user_edu_person_entitlement'
        . ' WHERE user_id=' . $user_id
        . ' AND edu_person_entitlement IN ' . $deleteEntitlementsParam);
    }
  }

  public static function insertNewEntitlements($mitreId, $user_id, $current_entitlements, $new_entitlements) {
    $insertEntitlements = array_diff($new_entitlements, $current_entitlements);
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
