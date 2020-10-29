# MitreIdProvisioner

MitreId Provisioner Plugin is used to create the entitlements of CO Person following the [AARC-G002](https://aarc-community.org/guidelines/aarc-g002/) specification and provision them to other systems.

## Installation

1. Run `git clone https://github.com/rciam/comanage-registry-plugin-MitreIdProvisioner.git /path/to/comanage/local/Plugin/MitreIdProvisioner`
2. Run `cd /path/to/comanage/app`
3. Run `su -c "Console/clearcache" ${APACHE_USER}` [COManage Reference](https://spaces.at.internet2.edu/display/COmanage/Installing+and+Enabling+Registry+Plugins)
4. Run `Console/cake schema create --file schema.php --path /path/to/comanage/local/Plugin/MitreIdProvisioner/Config/Schema`
5. 🍺

## Schema update

1. Run `Console/cake schema update --file schema.php --path /path/to/comanage/local/Plugin/MitreIdProvisioner/Config/Schema`
   - During updates database alternations, which refer to constraints, have to be deployed manually

## Configuration

After the installation, you have to configure the plugin before using it.

### MitreIdProvisioner Configuration

  * `enableVoWhitelist`: Optional, if disabled will create all entitlements otherwise will create only for those in voWhitelist field.
  * `voWhitelist`: Optional, an array of strings that contains VOs (COUs) for which the module will generate entitlements.
  * `voRoles`: Required, an array of default roles to be used for the composition of the entitlements.
  * `mergeEntitlements`: A boolean to indicate whether the redundant `eduPersonEntitlement` will be removed from the state. Defaults to `false`.
  * `urnNamespace`: Required, a string to use as the URN namespace of the generated `eduPersonEntitlement` values containing group membership and role information.
  * `urnAuthority`: Required, a string to use as the authority of the generated `eduPersonEntitlement` URN values containing group membership and role information.
  * `urnLegacy`: Optional, a boolean value for controlling whether to generate `eduPersonEntitlement` URN values using the legacy syntax. Defaults to `false`.
  * `voGroupPrefix`: Optional, defines a prefix for groups if any.
  * `entitlementFormat`: Required, defines the format of entitlements to be removed from other systems.
  * `identifierType`: Required, the type of the user identifier.

## License

Licensed under the Apache 2.0 license, for details see [LICENSE](https://github.com/rciam/comanage-registry-plugin-MitreIdProvisioner/blob/master/LICENSE).
