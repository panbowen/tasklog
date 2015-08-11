<?php

query("INSERT INTO logs (type, created_by, project_id, description, date, hours, created_at) VALUES (:type, :created_by, :project_id, :description, :date, :hours, :created_at)", [
  ":type" => $_POST["type"],
  ":created_by" => $_SESSION["id"],
  ":project_id" => $_POST["project"],
  ":description" => $_POST["description"],
  ":date" => date("Y-m-d",strtotime($_POST["date"])),
  ":hours" => $_POST["hours"],
  ":created_at" => date("Y-m-d H:i:s")
]);

$lastConfig = json_decode(query("SELECT last_config FROM users WHERE id=:id", [":id" => $_SESSION["id"]])[0]["last_config"], true);
$lastConfig["projectId"] = $_POST["project"];
$lastConfig["hours"] = $_POST["hours"];
$lastConfig["type"] = $_POST["type"];

query("UPDATE users SET last_config=:last_config WHERE id=:id", [
  ":last_config" => json_encode($lastConfig),
  ":id" => $_SESSION["id"]
]);

redirect("index");