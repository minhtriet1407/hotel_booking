<?php
// Redirect old procedural register to new MVC register
header("Location: public/index.php?url=auth/register");
exit();
