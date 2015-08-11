<?php

# Load last config
$lastConfig = json_decode(query("SELECT last_config FROM users WHERE id=:id", [":id" => $_SESSION["id"]])[0]["last_config"], true);


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
    if (isset($lastConfig["invoiceProjectId"]) && $lastConfig["invoiceProjectId"] == $rawProject["id"]) {
      $projects[$i]["selected"] = true;
    }
  }
}

$startDate = date("m/d/Y");
$rate = null;

if (isset($lastConfig["invoiceRate"])) {
  $rate = $lastConfig["invoiceRate"];
}

if (isset($_GET["project"])) {
  $lastCycle = query("SELECT end_date, rate FROM invoices WHERE project_id=:project_id ORDER BY end_date DESC LIMIT 1", [":project_id" => $_GET["project"]]);
  if (isset($lastCycle[0])) {
    $startDate = date("m/d/Y", strtotime("{$lastCycle[0]["end_date"]} +1 day"));
    $rate = $lastCycle[0]["rate"];
  }
}

$invoices = query("
  SELECT invoices.id AS id, DATE_FORMAT(invoices.start_date, '%b %e, %Y') AS startDate,
    DATE_FORMAT(invoices.end_date, '%b %e, %Y') AS endDate,
    projects.name AS project,
    invoices.description AS description,
    SUM(logs.hours) * invoices.rate AS total
  FROM invoices
    JOIN logs ON logs.date >= invoices.start_date AND logs.date <= invoices.end_date AND logs.project_id=invoices.project_id
    JOIN projects ON invoices.project_id = projects.id
  WHERE invoices.end_date >= :start AND invoices.end_date <= :end
  GROUP BY invoices.id
  ORDER BY invoices.end_date DESC", [
  ":start" => $start,
  ":end" => $end
]);

render("invoices", [
  "title" => "Invoice management",
  "projects" => $projects,
  "start" => date("m/d/Y", strtotime($start)),
  "end" => date("m/d/Y", strtotime($end)),
  "rate" => $rate,
  "startDate" => $startDate,
  "invoices" => $invoices,
  "endDate" => date("m/d/Y")
]);