<?php

if (isset($_GET["page"])) {
  $page = $_GET["page"];
} else {
  $page = 1;
}

$userCount = query("SELECT COUNT(id) FROM users WHERE status='active'")[0][0];

$users = query(" SELECT id, first_name, last_name, email, roles, created_by, updated_by, created_at, updated_at FROM users WHERE status='active' ORDER BY last_name ASC, id ASC");

$countHourQuery = "SELECT SUM(hours) FROM logs WHERE created_by = :created_by AND date >= :start AND date <= :end";

foreach($users as $i => $user) {
  $id = $user["id"];

  $roles = [];
  $roleBits = array_reverse(str_split(decbin($user["roles"])));
  foreach($roleBits as $j => $roleBit) {
    if ($roleBit == "1") {
      array_push($roles, $j+1);
    }
  }

  $users[$i]["roles"] = json_encode($roles);

  $reports = reports("SELECT SUM(hours) FROM logs WHERE created_by = :created_by AND date >= :start AND date <= :end", $id, ":created_by", ":start", ":end");
  $users[$i]["weekly"] = $reports["weekly"];
  $users[$i]["monthly"] = $reports["monthly"];
  $users[$i]["quarterly"] = $reports["quarterly"];
  $users[$i]["annually"] = $reports["annually"];

}

if (count($users) > 0) {
  render("users", [
    "users?" => true,
    "roles" => listRoles(), 
    "users" => $users,
    "title" => "User management"
  ]);
} else {
  render("users");
}