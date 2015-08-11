<?php
require_once './libs/password_hashing.php';
$user = $_POST["user"];

  $warnings = validate($user, [
  "first_name" => "required",
  "last_name" => "required",
  "email" => ["required", "email"],
  "roles" => ["required", "array"],
  "password" => "required"
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

  $password = password_hash($user["password"], PASSWORD_DEFAULT);
  $created_at = date("Y-m-d H:i:s");
  $created_by = $_SESSION["id"];

  $createUser = $db -> prepare("INSERT INTO users (first_name, last_name, email, password, roles, created_by, created_at, updated_by, updated_at)
    VALUES (:firstName, :lastName, :email, :password, :roles, :created_by, :created_at, :updated_by, :updated_at)");

  # If user with the same email exists but is removed, then revive the user with the new info
  $findExistingUser = $db -> prepare("SELECT id FROM users WHERE email=:email LIMIT 1");
  $reviveUser = $db -> prepare("UPDATE users SET first_name=:firstName, last_name=:lastName, password=:password, roles=:roles, updated_at=:updated_at, updated_by=:updated_by, status='active' WHERE id=:id");

  try{

    $existingUser = query("SELECT id FROM users WHERE email=:email LIMIT 1", [":email" => $email]);

    if (isset($existingUser["id"])) {

      query("UPDATE users SET first_name=:firstName, last_name=:lastName, password=:password, roles=:roles, updated_at=:updated_at, updated_by=:updated_by, status='active' WHERE id=:id", [
        ":id" => $existingUser["id"],
        ":firstName" => $firstName,
        ":lastName" => $lastName,
        ":password" => $password,
        ":roles" => $roles,
        ":updated_by" => $created_by,
        ":updated_at" => $created_at
      ]);

      output(["status" => "success"]);
      exit();

    } else{

      query("INSERT INTO users (first_name, last_name, email, password, roles, created_by, created_at, updated_by, updated_at) VALUES (:firstName, :lastName, :email, :password, :roles, :created_by, :created_at, :updated_by, :updated_at)", [
        ":firstName" => $firstName,
        ":lastName" => $lastName,
        ":email" => $email,
        ":password" => $password,
        ":roles" => $roles,
        ":created_by" => $created_by,
        ":created_at" => $created_at,
        ":updated_by" => $created_by,
        ":updated_at" => $created_at
        ]);
      output(["status" => "success"]);
      exit();
    }
  }
  catch (Exception $e) {
    output(["status" => "fail", "exceptions" => $e]);
  exit();
  }