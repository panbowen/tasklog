<?php

# Load last config
$lastConfig = json_decode(query("SELECT last_config FROM users WHERE id=:id", [":id" => $_SESSION["id"]])[0]["last_config"], true);
# Show the task logs

# Default timeframe: from Sunday to Saturday
if (isset($_GET["start"])) {
  $start = date("Y-m-d", strtotime($_GET["start"]));
} else {
  $start = date("Y-m-d", strtotime("Sunday last week"));
}

if (isset($_GET["end"])) {
  $end = date("Y-m-d", strtotime($_GET["end"]));
} else {
  $end = date("Y-m-d", strtotime("Saturday this week"));
}

# Make project map
$rawProjects = query("SELECT DISTINCT projects.id AS id, projects.name AS name FROM logs JOIN projects ON logs.project_id = projects.id ORDER BY logs.date limit 12");

if (isset($_GET["project"])) {
  $selectedProject = query("SELECT projects.id AS id, projects.name AS name FROM projects WHERE projects.id=:id", [":id" => $_GET["project"]])[0];
  if (!array_search($selectedProject, $rawProjects)) {
    array_push($rawProjects, $selectedProject);
  }
}

$projectMap = [];
foreach ($rawProjects as $rawProject) {
  $projectMap[$rawProject["id"]] = $rawProject["name"];
}

# Make project list for <select>
$projects = [];
foreach ($rawProjects as $i => $rawProject) {
  $projects[$i] = ["id" => $rawProject["id"], "name" => $rawProject["name"]];

  if (isset($_GET["project"])) {
    if ($rawProject["id"] == $_GET["project"]) {
      $projects[$i]["selected"] = true;
    }
  }
  else {
    # Get last project selection
    if (isset($lastConfig["projectId"]) && $lastConfig["projectId"] == $rawProject["id"]) {
      $projects[$i]["selected"] = true;
    }
  }
}

# Make type list for <select>
$typeList = $config["log types"];
$types = [];
foreach ($typeList as $key => $value) {
  $type = ["name" => $key, "value" => $value];
  if (isset($lastConfig["type"]) && $lastConfig["type"] == $value) {
    $type["selected"] = true;
  }
  array_push($types, $type);
}

# Get logs

if (is("admin")) {
  $logs = query("SELECT CONCAT(users.first_name, ' ', users.last_name) AS creator, projects.name AS project, logs.type AS type, logs.description AS description, logs.hours AS hours, DATE_FORMAT(logs.date, '%b %e, %Y') AS date FROM logs JOIN users ON logs.created_by = users.id JOIN projects ON logs.project_id = projects.id WHERE logs.date >= :start AND logs.date <= :end ORDER BY logs.date DESC", [
    ":start" => $start,
    ":end" => $end
  ]);
}

else {
  $logs = query("SELECT projects.name AS project, logs.type AS type, logs.description AS description, logs.hours AS hours, DATE_FORMAT(logs.date, '%b %e, %Y') AS date FROM logs JOIN projects ON logs.project_id = projects.id WHERE logs.created_by=:created_by AND logs.date >= :start AND logs.date <= :end ORDER BY logs.date DESC", [
    ":created_by" => $_SESSION["id"],
    ":start" => $start,
    ":end" => $end
  ]);
}

# Get last hour setting
if (isset($lastConfig["hours"])) {
  $lastHours = $lastConfig["hours"];
} else {
  $lastHours = "";
}

render("index", [
  "title" => "Index",
  "projects" => $projects,
  "types" => $types,
  "date" => date("m/d/Y"),
  "hours" => $lastHours,
  "logs" => $logs,
  "start" => date("m/d/Y", strtotime($start)),
  "end" => date("m/d/Y", strtotime($end))
]);