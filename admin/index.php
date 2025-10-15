<?php
// This file serves as a redirect from /admin to admin-login.php
// Redirect to admin login page if someone accesses the admin directory directly
header("Location: ../admin-login.php");
exit;