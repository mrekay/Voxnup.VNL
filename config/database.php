<?php

$db = new Database();

$CurrentUser = new User();

/* AUTO LOGIN */

//Session::set("user_id",1);

/* AUTO LOGIN END */


/* "Beni hatırla" çerezi + oturum */
UserModule::CheckCookieAuth();
UserModule::SetCurrentUserInfo();
UserModule::CheckUser();

/* Not: burada UserFriends::ReloadFriends() çağrılıyordu. Öyle bir sınıf
   hiç olmadı — VNL'in türetildiği eski uygulamadan kalmış bir satırdı ve
   girişli her istekte fatal veriyordu. VNL 3.0'da kaldırıldı. */

