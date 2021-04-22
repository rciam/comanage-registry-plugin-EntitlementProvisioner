<?php


class MitreIdProvisionerDBDriverTypeEnum
{
    const Mysql     = 'MY';
    const Postgres  = 'PG';
    const type      = array(
        'MY' => 'Mysql',
        'PG' => 'Postgres'
    );
}

class MitreIdProvisionerDBPortsEnum
{
  const Mysql     = '3306';
  const Postgres  = '5432';
  const port      = array(
    'MY' => '3306',
    'PG' => '5432'
  );
}

class MitreIdProvisionerDBEncodingTypeEnum
{
    const utf_8      = 'utf8';
    const iso_8859_7 = 'iso_8859_7'; // Latin/ Greek
    const latin1     = 'latin1'; // Western European
    const latin2     = 'latin2'; // Central European
    const latin3     = 'latin3'; // South European
    const latin4     = 'latin4'; // North European

    const type       = array(
        'utf8'       => 'utf8',
        'iso_8859_7' => 'iso_8859_7',
        'latin1'     => 'latin1',
        'latin2'     => 'latin2',
        'latin3'     => 'latin3',
        'latin4'     => 'latin4'
    );
}

class MitreIdProvisionerDateTruncEnum
{
    const daily     = 'day';
    const weekly    = 'week';
    const monthly   = 'month';
    const yearly    = 'year';

    const type      = array(
        'daily'     => 'day',
        'weekly'    => 'week',
        'monthly'   => 'month',
        'yearly'    => 'year',
    );
}

class MitreIdProvisionerDateEnum
{
    const daily     = 'daily';
    const weekly    = 'weekly';
    const monthly   = 'monthly';
    const yearly    = 'yearly';

    const type      = array(
        'weekly'    => 'weekly',
        'monthly'   => 'monthly',
        'yearly'    => 'yearly',
    );
}

class MitreIdProvisionerIdentifierEnum
{
  const Badge              = 'badge';
  const Enterprise         = 'enterprise';
  const ePPN               = 'eppn';
  const ePTID              = 'eptid';
  const ePUID              = 'epuid';
  const Mail               = 'mail';
  const National           = 'national';
  const Network            = 'network';
  const OpenID             = 'openid';
  const ORCID              = 'orcid';
  const ProvisioningTarget = 'provisioningtarget';
  const Reference          = 'reference';
  const SORID              = 'sorid';
  const UID                = 'uid';

  const type = array(
    MitreIdProvisionerIdentifierEnum::Badge => 'badge',
    MitreIdProvisionerIdentifierEnum::Enterprise => 'enterprise',
    MitreIdProvisionerIdentifierEnum::ePPN => 'eppn',
    MitreIdProvisionerIdentifierEnum::ePTID => 'eptid',
    MitreIdProvisionerIdentifierEnum::ePUID => 'epuid',
    MitreIdProvisionerIdentifierEnum::Mail => 'mail',
    MitreIdProvisionerIdentifierEnum::National => 'national',
    MitreIdProvisionerIdentifierEnum::Network => 'network',
    MitreIdProvisionerIdentifierEnum::OpenID => 'openid',
    MitreIdProvisionerIdentifierEnum::ORCID => ' orcid',
    MitreIdProvisionerIdentifierEnum::ProvisioningTarget => 'provisioningtarget',
    MitreIdProvisionerIdentifierEnum::Reference => 'reference',
    MitreIdProvisionerIdentifierEnum::SORID => 'sorid',
    MitreIdProvisionerIdentifierEnum::UID => 'uid'
  );
}

class MitreIdProvisionerRciamSyncVomsCfg {
  const VoBlackList = array(
    'vo.elixir-europe.org'
  );
  const UserIdAttribute   = 'distinguishedName';
  const TableName         = 'voms_members';
}
