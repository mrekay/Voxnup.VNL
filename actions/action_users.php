<?php

use ActionGlobal\Auth;

class action_users
{

    public function getList($req)
    {
        Auth::CheckLogin();
        $page_length = intval($req->post('length'));
        $page_start = $req->post('start');

        $columns = $req->post('columns');
        $orders = $req->post('order');

        $order_by = "InsertDate";
        $order = "Desc";
        if (!empty($orders)) {
            $order_by = $columns[intval($orders[0]['column'])]['name'];
            $order = $orders[0]['dir'];
        }


        $extras = [
            'begin' => $page_start,
            'limit' => intval($page_length),
            'search' => '',
            'order' => $order_by,
            'order_by' => $order
        ];
        $filters = array();
        foreach ($columns as $col) {
            if (isset($col['search']['value']) && $col['search']['value'] != null)
                $filters[$col['name']] = $col['search']['value'];
        }

        $ulist = User::SelectList($filters, $extras)->get();
        $filter_count = User::SelectList($filters, null)->count();
        $total_count = User::SelectList(null, null)->count();

        $data = [];
        $i = $page_start + 1;

        foreach ($ulist as $u) {


            $data[] =
                array(
                    "id" => $u->Id,
                    "no" => '<span class="ps-10">' . $i . '</span>  <input type="hidden" class="row_id" value="' . $u->Id . '">',
                    "Username" => $u->Username,
                    "Name" => $u->Name,
                    "Surname" => $u->Surname,
                    "Email" => $u->Email,
                    "AuthenticationCode" => $u->AuthenticationCode,
                    "InsertDate" => date("Y-m-d H:i:s", strtotime($u->InsertDate)),
                    "UpdateDate" => empty($u->UpdateDate) ? '-' : date("Y-m-d H:i:s", strtotime($u->UpdateDate)),

                );
            $i++;


        }


        $jsondata = array(
            "recordsTotal" => $total_count,
            "recordsFiltered" => $filter_count,
            "data" => $data
        );
        returnSuccessDataOnly($jsondata);

    }

    public function searchPersonnel($req)
    {
        Auth::CheckLogin();

        $query = $req->post('query');

        $users = User::SelectList(["Fullname" => $query], ['limit' => 10, 'order' => 'Id', 'order_by' => 'asc'])->get();
        $returnData = [];
        foreach ($users as $u) {
            $returnData[] = [
                'Id' => $u->Id,
                'Username' => $u->Username,
                'Fullname' => $u->Name . ' ' . $u->Surname,
                'Name' => $u->Name,
                'Surname' => $u->Surname,
            ];
        }

        returnSuccessDataOnly(["data" => $returnData]);
    }

    public function deleteUser($req){
        Auth::CheckLogin();
        $password = $req->post('password');
        $user_id = $req->post('user_id');
        DefaultValidator::validateEmpty($password, "Parola");
        $currentUser = Auth::User();
        if(EncryptionModule::PasswordEncrypt($password) == $currentUser->Password){
            User::where('Id', $user_id)->update(['DeleteUser' => $currentUser->Id]);
            if (User::where('Id',$user_id)->delete()){
                returnSuccessMessage("Kullanıcı silindi.");
            }else{
                returnErrorMessage("Kullanıcı silinemedi.");
            }
        }else{
            returnErrorMessage("Parola doğrulanamadı.");
        }
    }

    public function getPersonelDetails($req)
    {
        Auth::CheckLogin();
        $user_id = $req->post('uID');

        DefaultValidator::validateEmpty($user_id, "Kullanıcı");

        $user = User::SelectList(['id' => $user_id], [])->first();
        $user_permissions = UserPermissions::SelectList(['UserId' => $user->Id], [])->get();

        returnSuccessDataOnly(["user" => $user, "permissions" => $user_permissions]);
    }

    public function save($req)
    {
        Auth::CheckLogin();
        $user_id = $req->post('user_id');
        $username = $req->post('username');
        $password = $req->post('password');
        $first_name = $req->post('first_name');
        $last_name = $req->post('last_name');
        $email = $req->post('email');
        $erp_user = $req->post('erp_user');

        $selectedPermissions = $req->post('permissions');
        $definitionPermissions = $req->post('usercontrol_permissions') ?? [];


        DefaultValidator::validateEmpty($username, "Kullanıcı Adı");
        DefaultValidator::validateEmpty($first_name, "Adı");
        DefaultValidator::validateEmpty($last_name, "Soyadı");
        DefaultValidator::validateEmpty($email, "Email");

        if (empty($user_id) && empty($password)) returnErrorMessage("Parola alanı boş olamaz");

        $user = new User();
        if ($user_id > 0) {
            $user = User::Select(["id" => $user_id]);
        }

        $user->Username = $username;
        if (!empty($password)) $user->Password = EncryptionModule::PasswordEncrypt($password);
        $user->Name = $first_name;
        $user->Surname = $last_name;
        $user->Email = $email;
        if (empty($user_id)) {
            $user->UserReferenceId = empty($erp_user) ? '00000000-0000-0000-0000-000000000000' : $erp_user;
            $user->InsertDate = date("Y-m-d H:i:s");
            $user->InsertUser = Auth::User()->Id;
            $user->AuthenticationCode = Database::raw("NEWID()");
        }

        $user_save_status = $user->save();

        /* eğer yeni kullanıcı oluşturuluyorsa onun için tüm yetkileri oluştur */
        if (empty($user_id) || $user_id <= 0) {
            $allPermissions = Permission::SelectList([], [])->get();

            foreach ($allPermissions as $perm) {
                $userperm = new UserPermissions();
                $userperm->Insert([
                    "UserId" => $user->Id,
                    "PermissionId" => $perm->Id,
                    "InsertDate" => date("Y-m-d H:i:s"),
                    "InsertUser" => Auth::User()->Id,
                    "CanView" => 0,
                    "CanEdit" => 0,
                    "CanInsert" => 0,
                    "CanDelete" => 0,
                ]);


            }
        }

        /* tüm yetkileri sıfırla */
        UserPermissions::where('UserId', $user->Id)->update([
            'CanView' => 0,
            'CanInsert' => 0,
            'CanEdit' => 0,
            'CanDelete' => 0,
        ]);

        /* ilgili kullanıcı yetkilerini 1 yap */
        if (!empty($selectedPermissions)) {
            UserPermissions::where('UserId', $user->Id)
                ->whereIn('PermissionId', $selectedPermissions)
                ->update(['CanView' => 1]);
        }

        foreach ($definitionPermissions as $permId => $actions) {
            UserPermissions::where('UserId', $user->Id)
                ->where('PermissionId', $permId)
                ->update([
                    'CanView' => isset($actions['CanView']) ? 1 : 0,
                    'CanInsert' => isset($actions['CanInsert']) ? 1 : 0,
                    'CanEdit' => isset($actions['CanEdit']) ? 1 : 0,
                    'CanDelete' => isset($actions['CanDelete']) ? 1 : 0,
                ]);
        }

        if ($user_save_status) {
            returnSuccessMessage("Kullanıcı kayıt edildi");
        } else {
            returnErrorMessage("Kullanıcı kayıt edilemedi.");
        }


    }


}