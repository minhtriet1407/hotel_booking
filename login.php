<?php
// Redirect old procedural login to new MVC login
header("Location: public/index.php?url=auth/login");
exit();
