<?php

session_start();
# Init router
if ( !isset($_GET["to"]) ) {
  $controller = "index";
}
else {
  $controller = trim($_GET["to"], "./");
}

# Load config
$config = parse_ini_file('./config.ini', true);
$strings = parse_ini_file('./I18n.ini', true)[$config['general']['locale']];

foreach (explode(",", $config["general"]["roles"]) as $code => $name ) {
  $config["roles"][$name] = $code + 1;
}

foreach (explode(",", $config["general"]["log_types"]) as $log_type ) {
  $config["log types"][$log_type] = $log_type;
}

$renderArgs = [];
if (isset($_GET["__args"])) {
  $renderArgs = json_decode($_GET["__args"], true);
}
# Init PDO
$db = new PDO("{$config['database']['driver']}:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']}", $config['database']['username'], $config['database']['password']);

$db -> setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

# Init Mustache
require './libs/Mustache/Autoloader.php';

Mustache_Autoloader::register();


# Redirect helper
function redirect($controller, $args=null) {
  if ($args !== null) {
    $json = json_encode($args);
    header("Location: index.php?to={$controller}&__args={$json}");
  }
  else {
    header("Location: index.php?to={$controller}");
  }
  exit();
}

# Query helper
function query($queryString, $params = []) {
  $config = parse_ini_file('./config.ini', true);
  $db = new PDO("{$config['database']['driver']}:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']}", $config['database']['username'], $config['database']['password']);
  $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  try {
    $query = $db -> prepare($queryString);
    $query -> execute($params);
    return $query -> fetchAll();
  }
  catch (Exception $e) {
    echo $e;
  }
}


# Log helper
function dump($string) {
  echo "<pre>";
  print_r($string);
  echo "</pre>";
}


# Render helper
# In template, {{controller}} means the current controller
# {{currentUser}} has the info of current user
function mustache($viewPath, $values = []) {
  global $controller;
  global $renderArgs;
  global $strings;
  $mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views'),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
    'strict_callables' => true
  ]);
  $tpl = $mustache -> loadTemplate($viewPath);

  $values["controller:{$controller}?"] = true;
  $values["controller"] = $controller;

  foreach (["firstName", "lastName", "roles", "id", "email"] as $i) {
    if (isset($_SESSION[$i])) {
      $values["currentUser:{$i}"] = $_SESSION[$i];
    }
  }

  if (isset($_SESSION["id"])) {
    $reports = reports("SELECT SUM(hours) FROM logs WHERE created_by = :created_by AND date >= :start AND date <= :end", $_SESSION["id"], ":created_by", ":start", ":end");
    foreach (["weekly", "monthly", "quarterly", "annually"] as $j) {
      $values["currentUser:{$j}"] = $reports[$j];
    }

    $thisWeek = query("SELECT SUM(hours) FROM logs WHERE created_by = :created_by AND date >= :start AND date <= :end", [
      ":created_by" => $_SESSION["id"],
      ":start" => date("Y-m-d", strtotime("last Sunday")),
      ":end" => date("Y-m-d", strtotime("this Saturday"))
    ])[0][0];

    if ($thisWeek === null) {
      $thisWeek = 0;
    }

    $values["currentUser:thisWeek"] = $thisWeek;
  }

  # Roles identifier for template
  foreach (roles() as $role) {
    $values["currentUser:{$role}?"] = true;
  }

  foreach ($renderArgs as $key => $value) {
    if (isset($strings[$value])) {
      $value = $strings[$value];
    }
    $values["args:{$key}"] = $value;
  }

  return $tpl -> render($values);
}

function render($viewPath, $values = []) {
  echo mustache($viewPath, $values);
}


# Pagination helper
function paginate($currentPage, $total) {
  global $config;
  $totalPages = ceil($total / $config["general"]["per_page"]);
  $pages = [];

  if ($currentPage < 1 || $currentPage > $totalPages) {
    return [];
  }

  # Left arrow
  array_push($pages, ["name" => "&laquo;", "value" => $currentPage - 1]);

  # Left "..."
  if ($currentPage >= 5) {
    array_push($pages, ["name" => 1, "value" => 1]);
    array_push($pages, ["name" => "...", "value" => null, "disabled?" => true]);
  }

  # Pages
  for ($i = $currentPage - 3; $i <= $currentPage + 3; $i++) {
    if ($i >= 1 && $i <= $totalPages) {
      $page = ["name" => $i, "value" => $i];
      if ($i == $currentPage) {
        $page["active?"] = true;
        $page["value"] = null;
      }
      array_push($pages, $page);
    }
  }
  
  # Right "..."
  if ($currentPage <= $totalPages - 4) {
    array_push($pages, ["name" => "...", "value" => null, "disabled?" => true]);
    array_push($pages, ["name" => $totalPages, "value" => $totalPages]);
  }

  # Right arrow
  array_push($pages, ["name" => "&raquo;", "value" => $currentPage + 1]);

  # Disable arrows when current page is first or last
  if ($currentPage == 1) {
    $pages[0]["disabled?"] = true;
    $pages[0]["value"] = null;
  }
  if ($currentPage == $totalPages) {
    end($pages);
    $pages[key($pages)]["disabled?"] = true;
    $pages[key($pages)]["value"] = null;
  }

  return $pages;
}


# List roles
function listRoles() {
  global $config;
  $roles = [];
  foreach ($config["roles"] as $name => $code) {
    array_push($roles, ["code" => $code, "name" => $name]);
  }
  return $roles;
}

# Role translator
function roles($roleCode = null) {
  if (!isset($roleCode) && isset($_SESSION["roles"])) {
    $roleCode = $_SESSION["roles"];
  }
  if (!is_int($roleCode)) {
    return [];
  }

  $roles = listRoles();

  $roleList = [];

  foreach ($roles as $role) {
    if ($roleCode & $role["code"]) {
      array_push($roleList, $role["name"]);
    }
  }

  return $roleList;
}

# Judge role
function is($roles) {
  $currentUserRoles = roles();

  # When input is string
  if (is_string($roles)) {
    if ( in_array($roles, $currentUserRoles) ) {
      return true;
    }
    else {
      return false;
    }
  }
  # Else, return false
  else {
    return false;
  }
}

# Authentication
# Must have all role tags
function auth($roles = []) {

  # Check is logged in?
  if(!isset($_SESSION["roles"])) {
    redirect("login");
  }

  if ($roles == []) {
    return true;
  }

  global $config;
  $roleCode = 0;

  if (is_array($roles)) {
    foreach ($roles as $role) {
      $code = (int) $config["roles"][$role] - 1;
      $roleCode = $roleCode + pow(2, $code);
    }
  }
  else if (is_string($roles)) {
    $roleCode = (int) $config["roles"][$role] - 1;
  }

  if ($_SESSION["roles"] & $roleCode) {
    return true;
  }
  else {
    exit();
  }
}

# Response json
function output($array) {
  header('Content-Type: application/json');
  echo json_encode($array);
}


# Validator
function validate($obj, $conditions) {
  $warnings = [];
  foreach ($conditions as $field => $condition) {
    if (!is_array($condition)) {
      $condition = [$condition];
    }
    foreach ($condition as $validator) {
      switch ($validator) {
        case "required":
          if (!isset($obj[$field]) || $obj[$field] == null) {
            array_push($warnings, [$field => "validation_required"]);
          }
          break 2;
        case "email":
          if (!filter_var($obj[$field], FILTER_VALIDATE_EMAIL)) {
            array_push($warnings, [$field => "validation_email"]);
          }
          break;
        case "array":
          if (!is_array($obj[$field])) {
            array_push($warnings, [$field => "validation_array"]);
          }
          break;
      }
    }
  }
  return $warnings;
}

# Output warning
function warn($warnings) {
  global $strings;
  if (count($warnings) > 0) {
    $outputWarnings = [];
    foreach ($warnings as $warningArray) {
      foreach ($warningArray as $field => $warning) {
        array_push($outputWarnings, "{$field}: {$strings[$warning]}");
      }
    }
    output(["status" => "fail", "exceptions" => $outputWarnings]);
    exit();
  }
}

# Report helper
function reports($countHourQuery="", $id, $idField, $startField="start", $endField="end") {
  $result = [];

  # Weekly report
  $result["weekly"] = query($countHourQuery, [
    "{$idField}" => $id,
    "{$startField}" => date("Y-m-d", strtotime("Sunday last week -7 days")),
    "{$endField}" => date("Y-m-d", strtotime("Saturday this week -7 days"))
  ])[0][0];

  # Monthly report
  $result["monthly"] = query($countHourQuery, [
    "{$idField}" => $id,
    "{$startField}" => date("Y-m-d", strtotime("first day of last month")),
    "{$endField}" => date("Y-m-d", strtotime("last day of last month"))
  ])[0][0];

  # Quarterly report
  $monthOfTheQuarter = (int) date("n", strtotime("first day of -3 month"));
  $yearOfTheQuarter = (int) date("Y", strtotime("first day of -3 months"));
  $quarter = ceil($monthOfTheQuarter / 3);
  $quarterStartMonth = ($quarter - 1) * 3 + 1;
  $quarterEndMonth = ($quarter - 1) * 3 + 3;
  $result["quarterly"] = query($countHourQuery, [
    "{$idField}" => $id,
    "{$startField}" => date("Y-m-d", strtotime("{$yearOfTheQuarter}-{$quarterStartMonth}-1")),
    "{$endField}" => date("Y-m-d", strtotime("{$yearOfTheQuarter}-{$quarterEndMonth}-1"))
  ])[0][0];

  $lastYear = (int) date("Y", strtotime("today")) - 1;
  # Annually report
  $result["annually"] = query($countHourQuery, [
    "{$idField}" => $id,
    "{$startField}" => date("Y-m-d", strtotime("{$lastYear}-01-01")),
    "{$endField}" => date("Y-m-d", strtotime("{$lastYear}-12-31"))
  ])[0][0];

  foreach (["weekly", "monthly", "quarterly", "annually"] as $key){
    if (!isset($result[$key])) {
      $result[$key] = 0;
    }
  };
  
  return $result;
}

# Routing
try{
  if (isset($config["policy"][$controller])) {
    if ($config["policy"][$controller] == "*") {

      auth();
    }
    else {   

      auth(explode(",", $config["policy"][$controller]));
    }
  }

  require "./controllers/{$controller}.php";
}
catch (Exception $e) {
  exit();
}