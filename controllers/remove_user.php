<?php

$userId = $_GET["id"];
$currentUser = $_SESSION["id"];

if ($userId == $currentUser) {
  output(["status" => "fail", "exceptions" => [$strings["cant_remove_self"]]]);
  exit();
}

try{
  query("UPDATE users SET status='removed', updated_at=:updated_at, updated_by=:updated_by WHERE id=:id", [
    ":id" => $userId,
    ":updated_at" => date("Y-m-d H:i:s"),
    ":updated_by" => $currentUser
  ]);
  output(["status" => "success"]);
  exit();
} catch (Exception $e) {
  output(["status" => "fail", "exceptions" => $e]);
  exit();
}