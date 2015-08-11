<?php

$projectId = $_GET["id"];

try{
  query("UPDATE projects SET status='removed', updated_at=:updated_at, updated_by=:updated_by WHERE id=:id", [
    ":id" => $projectId,
    ":updated_at" => date("Y-m-d H:i:s"),
    ":updated_by" => $_SESSION["id"]
  ]);
  output(["status" => "success"]);
  exit();
} catch (Exception $e) {
  output(["status" => "fail", "exceptions" => $e]);
  exit();
}