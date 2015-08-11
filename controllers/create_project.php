<?php

$project = $_POST["project"];

$warnings = validate($project, [
  "name" => "required",
]);

warn($warnings);

$name = $project["name"];
$description = $project["description"];

$created_at = date("Y-m-d H:i:s");
$created_by = $_SESSION["id"];

try{
  query("INSERT INTO projects (name, description, bill_to_name, bill_to_address, bill_to_department, bill_to_campus, created_by, created_at, updated_by, updated_at) VALUES (:name, :description, :bill_to_name, :bill_to_address, :bill_to_department, :bill_to_campus, :created_by, :created_at, :updated_by, :updated_at)", [
    ":name" => $name,
    ":description" => $description,
    ":bill_to_name" => $project["bill_to_name"],
    ":bill_to_address" => $project["bill_to_address"],
    ":bill_to_department" => $project["bill_to_department"],
    ":bill_to_campus" => $project["bill_to_campus"],
    ":created_by" => $created_by,
    ":created_at" => $created_at,
    ":updated_by" => $created_by,
    ":updated_at" => $created_at
  ]);
  output(["status" => "success"]);
  exit();
}
catch (Exception $e) {
  output(["status" => "fail", "exceptions" => $e]);
  exit();
}