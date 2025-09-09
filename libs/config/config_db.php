<?php

$db = new Database();

$CurrentUser = new User();

/* AUTO LOGIN */

//Session::set("user_id",1);

/* AUTO LOGIN END */


/* COOKIE ILE OTURUM AÇMA*/
UserModule::CheckCookieAuth();
UserModule::SetCurrentUserInfo();
UserModule::CheckUser();

if ($CurrentUser->id > 0)
UserFriends::ReloadFriends($CurrentUser->id);

