<?php

class AppSchema extends CakeSchema {

  public $connection = 'default';

  public function before($event = array())
  {
    
    return true;
  }

  public function after($event = array())
  {
    if (isset($event['create'])) {
      switch ($event['create']) {
        case 'co_entitlement_provisioner_targets':
          $EntitlementProvisioner = ClassRegistry::init('EntitlementProvisioner.CoEntitlementProvisionerTarget');
          $EntitlementProvisioner->useDbConfig = $this->connection;
          // Add the constraints or any other initializations
          $EntitlementProvisioner->query("ALTER TABLE ONLY public.cm_co_entitlement_provisioner_targets ADD CONSTRAINT cm_co_entitlement_provisioner_targets_co_provisioning_target_id_fkey FOREIGN KEY (co_provisioning_target_id) REFERENCES public.cm_co_provisioning_targets(id)");
          break;
      }
    }
  }

  public $co_entitlement_provisioner_targets = array(
    'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
    'co_provisioning_target_id' => array('type' => 'integer', 'null' => false, 'length' => 10),
    'type' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 2),
    'hostname' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'port' => array('type' => 'integer', 'null' => true, 'default' => null),
    'username' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'password' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 256),
    'databas' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'persistent' => array('type' => 'boolean', 'null' => true, 'default' => null),
    'encoding' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'vo_roles' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 256),
    'merge_entitlements' => array('type' => 'boolean', 'null' => true, 'default' => null),
    'urn_namespace' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'urn_authority' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'urn_legacy' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'enable_vo_whitelist' => array('type' => 'boolean', 'null' => true, 'default' => null),
    'vo_whitelist' => array('type' => 'text', 'null' => true, 'default' => null, 'length' => 4000),
    'vo_group_prefix' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 4000),
    'entitlement_format' => array('type' => 'text', 'null' => true, 'default' => null, 'length' => 256),
    'identifier_type' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
    'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
    'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
    'deleted' => array('type' => 'boolean', 'null' => false, 'default' => false),
    'indexes' => array(
      'PRIMARY' => array('unique' => true, 'column' => 'id')
    ),
    'tableParameters' => array()
  );

}