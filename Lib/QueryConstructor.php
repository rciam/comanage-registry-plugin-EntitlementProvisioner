<?php
class QueryConstructor {
  public static function getMembershipQuery($coId , $co_person_id){
    $query =  "SELECT"
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
    . " AND groups.co_id = " . $coId
    . " AND groups.status = 'A'"
    . " LEFT OUTER JOIN cm_cous AS cous ON groups.cou_id = cous.id"
    . " AND NOT cous.deleted"
    . " AND cous.cou_id IS NULL"
    . " LEFT OUTER JOIN cm_co_person_roles AS ROLE ON cous.id = role.cou_id"
    . " AND role.co_person_role_id IS NULL"
    . " AND role.status = 'A'"
    . " AND NOT role.deleted AND role.co_person_id = members.co_person_id"
    . " GROUP BY"
    . " groups.name";
    return $query;
  }

  public static function getRecursiveQuery($couId){
    $query =
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
    return $query;  
  }
}
?>