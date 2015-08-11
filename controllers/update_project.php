<?php

$project = $_POST["project"];

$warnings = validate($project, [
  "name" => "required"
]);

warn($warnings);

try{
  query("UPDATE projects SET name=:name, description=:description, bill_to_name=:bill_to_name, bill_to_address=:bill_to_address, bill_to_department=:bill_to_department, bill_to_campus=:bill_to_campus, updated_at=:updated_at, updated_by=:updated_by WHERE id=:id", [
    ":name" => $project["name"],
    ":description" => $project["description"],
    ":bill_to_name" => $project["bill_to_name"],
    ":bill_to_address" => $project["bill_to_address"],
    ":bill_to_department" => $project["bill_to_department"],
    ":bill_to_campus" => $project["bill_to_campus"],
    ":updated_by" => $_SESSION["id"],
    ":updated_at" => date("Y-m-d H:i:s"),
    ":id" => $_GET["id"]
  ]);
  output(["status" => "success"]);
  exit();
}
catch (Exception $e) {
  output(["status" => "fail", "exceptions" => $e]);
  exit();
}