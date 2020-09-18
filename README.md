# EntitlementProvisioner

Entitlement Provisioner Plugin is used to create the entitlements of CO Person following the [AARC-G002](https://aarc-community.org/guidelines/aarc-g002/) specification and synchronise them with other systems like MitreId.

## Installation

1. Run `git clone https://github.com/rciam/comanage-registry-plugin-EntitlementProvisioner.git /path/to/comanage/local/Plugin/EntitlementProvisioner`
2. Run `cd /path/to/comanage/app`
3. Run `su -c "Console/clearcache" ${APACHE_USER}` [COManage Reference](https://spaces.at.internet2.edu/display/COmanage/Installing+and+Enabling+Registry+Plugins)
4. Run `Console/cake schema create --file schema.php --path /path/to/comanage/local/Plugin/EntitlementProvisioner/Config/Schema`
5. 🍺

## Schema update

1. Run `Console/cake schema update --file schema.php --path /path/to/comanage/local/Plugin/EntitlementProvisioner/Config/Schema`
   - During updates database alternations, which refer to constraints, have to be deployed manually

## Configuration

After the installation, you have to configure the plugin before using it.

### EntitlementProvisioner Configuration

  * `blacklist`: Optional, an array of strings that contains the SPs that the module will skip to process.
  * `voWhitelist`: Optional, an array of strings that contains VOs (COUs) for which the module will generate entitlements.
  * `communityIdps`: Optional, an array of strings that contains the Entity Ids of trusted communities.
  * `urnNamespace`: Required, a string to use as the URN namespace of the generated `eduPersonEntitlement` values containing group membership and role information.
  * `voRoles`: Required, an array of default roles to be used for the composition of the entitlements.
  * `urnAuthority`: Required, a string to use as the authority of the generated `eduPersonEntitlement` URN values containing group membership and role information.
  * `registryUrls`: Required, an array of COmanage endpoints representing standard Enrollment Flow types. All the four endpoints are mandatory.
  * `urnLegacy`: Optional, a boolean value for controlling whether to generate `eduPersonEntitlement` URN values using the legacy syntax. Defaults to `false`.
  * `mergeEntitlements`: A boolean to idicate whether the redundant `eduPersonEntitlement` will be removed from the state. Defaults to `false`.

## License

Licensed under the Apache 2.0 license, for details see [LICENSE](https://github.com/rciam/comanage-registry-plugin-EntitlementProvisioner/blob/master/LICENSE).
