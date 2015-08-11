<?php

foreach ($_SESSION as $key => $value) {
  unset($_SESSION[$key]);
}
foreach ($_COOKIE as $key => $value) {
  unset($_COOKIE[$key]);
}

redirect("login");
