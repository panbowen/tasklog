<?php

if (isset($_GET["page"])) {
  $page = $_GET["page"];
} else {
  $page = 1;
}

$projects = query("SELECT * FROM projects WHERE status='active' ORDER BY name ASC");

foreach ($projects as $i => $project) {
  $id = $project["id"];

  $reports = reports("SELECT SUM(hours) FROM logs WHERE project_id = :project_id AND date >= :start AND date <= :end", $id, ":project_id", ":start", ":end");
  $projects[$i]["weekly"] = $reports["weekly"];
  $projects[$i]["monthly"] = $reports["monthly"];
  $projects[$i]["quarterly"] = $reports["quarterly"];
  $projects[$i]["annually"] = $reports["annually"];
  
}

render("projects", [
  "projects?" => true,
  "projects" => $projects,
  "title" => "Project management"
]);