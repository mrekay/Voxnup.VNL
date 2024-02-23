<?php

$db = new Database();

$CurrentUser = new User();

/* AUTO LOGIN */

//Session::set("user_id",1);

/* AUTO LOGIN END */

UserModule::SetCurrentUserInfo();
UserModule::CheckUser();
