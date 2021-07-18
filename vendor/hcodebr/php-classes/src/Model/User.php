<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Mailer;
use \Hcode\Model;

class User extends Model
{
    const SESSION = "User";
    public static function login($login, $password)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));

        if (count($results) === 0) {
            throw new \Exception("USUARIO INEXISTENTE OU SENHA INVALIDA");
        }

        $data = $results[0];

        if (password_verify($password, $data["despassword"])) {
            $user = new User();
            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();
        } else {
            throw new \Exception("USUARIO INEXISTENTE OU SENHA INVALIDA");
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION]) || !$_SESSION[User::SESSION] ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0 || !(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
        ) {
            header("Location: /admin/login");
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = null;
    }

    public static function listAll()
    {
        $sql = new SqL();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.idperson");
        return $results;
    }

    public static function getforgot($email)
    {

        $sql = new SQL();

        $result = $sql->select("
        SELECT *
        FROM tb_persons a
        INNER JOIN tb_users b USING(idperson)
        WHERE a.desemail = :email;
        ", array(
            ":email" => $email
        ));

        if (count($result) == 0) {
            throw new \Exception("Error Processing Request");
        } else {
            $data = $result[0];

            $result2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                "iduser" => $data["iduser"],
                "desip" => $_SERVER["REMOTE_ADDR"]
            ));
        }

        if (count($result2) == 0) {
            throw new \Exception("Error Processing Request");
        } else {
            $dataRecovery = $result2[0];
            $code = $dataRecovery["idrecovery"];
            $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
            $mailer = new Mailer(
                $data["desemail"],
                $data["desperson"],
                "redefinir senha",
                "forgot",
                array(
                    "name" => $data["desperson"],
                    "link" => $link
                )
            );
            $mailer->send();
        }

        return $data;
    }

    public static function validForgotDecrypt($code)
    {
        $sql = new Sql();
        $result = $sql->select(
            "
        SELECT *
        FROM tb_userspasswordsrecoveries a
        INNER JOIN tb_users b USING(iduser)
        INNER JOIN tb_persons c USING(idperson)
        WHERE 
        a.idrecovery = :idrecovery
        AND
        a.dtrecovery IS NULL
        AND
        DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()",
            array(":idrecovery" => $code)
        );
        if (count($result) == 0) {
            throw new \Exception("Error Processing Request");
        } else {
            return $result[0];
        }
    }

    public static function setFogot($id)
    {

        $sql = new Sql();

        $sql->query(
            "UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :id",
            array(":id" => $id)
        );
    }

    public static function setPassword($password,$id)
    {
        $sql = new Sql();
        $sql->query(
            "UPDATE tb_users SET despassword = :pass WHERE iduser = :id",
            array(
                ":pass" => $password,
                ":id" => $id
            )
        );
    }
    public function get($iduser)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser" => $iduser
        ));
        $this->setData($results[0]);
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin(),
        ));

        $this->setData($results[0]);
    }

    public function update()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, 
                                :desemail, :nrphone, :inadmin)", array(
            "iduser" => $this->getiduser(),
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin(),
        ));
        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser()
        ));
    }
}
