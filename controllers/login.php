<?php

require_once './libs/password_hashing.php';
# When GET then show the login page
# When POST then do the login

switch ($_SERVER['REQUEST_METHOD']) {

  case 'GET':
    render('login');
    break;

  case 'POST':
    login();
    break;
}

function login() {
  global $db;
  global $strings;

  $users = query("SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1", [
    ":email" => $_POST["email"]
  ]);
  
  if ( sizeof($users) == 0 ) {
    render("login", ["error" => $strings["user_doesnt_exist"]]);
  } else if ($users[0]["status"] != "active") {
    render("login", ["error" => $strings["user_is_not_active"]]);
  } else if ( $_POST["password"] != $users[0]["password"] ){
    render("login", ["error" => $strings["invalid_password"]]);
  }
  else {
    $user = $users[0];
    session_start();
    $_SESSION["id"] = $user["id"];
    $_SESSION["firstName"] = $user["first_name"];
    $_SESSION["lastName"] = $user["last_name"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["roles"] = $user["roles"];
    redirect("index");
  }

}
