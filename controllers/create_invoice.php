<?php

$existingLogs = query("SELECT logs.id FROM logs WHERE logs.project_id=:project_id AND logs.date>=:start_date AND logs.date<=:end_date", [
  ":project_id" => $_POST["project"],
  ":start_date" => date("Y-m-d", strtotime($_POST["startDate"])),
  ":end_date" => date("Y-m-d", strtotime($_POST["endDate"]))
]);

if (count($existingLogs) == 0) {
  redirect("invoices", ["warnings" => "no_logs"]);
}

query("INSERT INTO invoices (created_by, project_id, description, start_date, end_date, rate, created_at) VALUES (:created_by, :project_id, :description, :start_date, :end_date, :rate, :created_at)", [
  ":created_by" => $_SESSION["id"],
  ":project_id" => $_POST["project"],
  ":description" => $_POST["description"],
  ":start_date" => date("Y-m-d", strtotime($_POST["startDate"])),
  ":end_date" => date("Y-m-d", strtotime($_POST["endDate"])),
  ":rate" => $_POST["rate"],
  ":created_at" => date("Y-m-d H:i:s")
]);

$lastConfig = json_decode(query("SELECT last_config FROM users WHERE id=:id", [":id" => $_SESSION["id"]])[0]["last_config"], true);
$lastConfig["invoiceRate"] = $_POST["rate"];

query("UPDATE users SET last_config=:last_config WHERE id=:id", [
  ":last_config" => json_encode($lastConfig),
  ":id" => $_SESSION["id"]
]);

redirect("invoices");