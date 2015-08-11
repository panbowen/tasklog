<?php

require_once './libs/password_hashing.php';
$user = $_POST["user"];

  $warnings = validate($user, [
  "first_name" => "required",
  "last_name" => "required",
  "email" => ["required", "email"],
  "roles" => ["required", "array"]
  ]);

  if ($user["password"] !== $user["confirm_password"]) {
    array_push($warnings, ["password" => "password_mismatch"]);
  }

  warn($warnings);

  $firstName = $user["first_name"];
  $lastName = $user["last_name"];
  $email = $user["email"];

  $roles = 0;
  foreach ($user["roles"] as $role) {
    $roles = $roles + pow(2, $role - 1);
  }

  $updated_at = date("Y-m-d H:i:s");
  $updated_by = $_SESSION["id"];

  $userInfo = [
    ":firstName" => $firstName,
    ":lastName" => $lastName,
    ":email" => $email,
    ":roles" => $roles,
    ":updated_by" => $updated_by,
    ":updated_at" => $updated_at,
    ":id" => $_GET["id"]
  ];

  if ($user["password"] != null) {
    $updateUser = "UPDATE users SET first_name=:firstName, last_name=:lastName, email=:email, roles=:roles, password=:password, updated_at=:updated_at, updated_by=:updated_by WHERE id=:id";
    $userInfo[":password"] = password_hash($user["password"], PASSWORD_DEFAULT);
  } else {
    $updateUser = "UPDATE users SET first_name=:firstName, last_name=:lastName, email=:email, roles=:roles, updated_at=:updated_at, updated_by=:updated_by WHERE id=:id";
  }

  try{
    query($updateUser, $userInfo);
    output(["status" => "success"]);
    exit();
  }
  catch (Exception $e) {
    output(["status" => "fail", "exceptions" => $e]);
    exit();
  }