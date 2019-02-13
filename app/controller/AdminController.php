<?php

use Metzli\Encoder\Encoder;
use Metzli\Renderer\PngRenderer;

class AdminController
{
    public function login()
    {
        $view = new View();    
        $view->render('login', [
            "message" => ""
        ]);
    }

    public function registration()
    {
        $view = new View();
        $view->render('registration',[
            "message" => ""
        ]);
    }

    public function profil()
    {
        $view = new View();
        $view->render('profil',[
            "message" => ""
        ]);
    }

    public function register()
    {
        $db = Db::connect();
        $statement = $db->prepare("select id from user where email=:email");
        $statement->bindValue('email', Request::post("email"));
        $statement->execute();
        if($statement->fetchColumn() > 0) {
            $view = new View();
            return $view->render('registration',["message"=>"This email already is in use, please insert different email."]);
        } else {
            $code = Encoder::encode(Request::post("firstname").Request::post("lastname").Request::post("email"));
            $renderer = new PngRenderer();
            $renderer->render($code);
            $picName = rand(5000,1000000);
            rename(BP.'images/image.png',BP . 'images/' . $picName . '.png');
            $db = Db::connect();
            $statement = $db->prepare("insert into user (firstname,lastname,email,pass,picture) values (:firstname,:lastname,:email,:pass,:picture)");
            $statement->bindValue('firstname', trim(Request::post("firstname")));
            $statement->bindValue('lastname', trim(Request::post("lastname")));
            $statement->bindValue('email', trim(Request::post("email")));
            $statement->bindValue('pass', password_hash(Request::post("pass"),PASSWORD_DEFAULT));
            $statement->bindValue('picture', $picName . '.png');
            $statement->execute();

            Session::getInstance()->logout();
            $view = new View();
            $view->render('login',["message"=>""]);
        }
    }

    public function updateUser()
    {
        if(empty(trim(Request::post('firstname'))) && empty(trim(Request::post('lastname'))) &&
            empty(trim(Request::post('email'))) && empty(trim(Request::post('newpass'))) &&
            empty(trim(Request::post('newpassconf'))) && $_FILES["file"]["size"] ===null) {

            $view = new View();
            return $view->render('profil',["message"=>"Please insert something."]);
        }

        $db = Db::connect();
        $db->beginTransaction();

        if(isset($_POST['firstname']) && !empty($_POST['firstname'])) {
            $statement = $db->prepare("Update user set firstname=:firstname Where id=:id");
            $statement->bindValue('firstname',  trim($_POST['firstname']));
            $statement->bindValue('id', Session::getInstance()->getUser()->id);
            $statement->execute();
        }

        if(isset($_POST['lastname']) && !empty($_POST['lastname'])) {
            $statement = $db->prepare("Update user set lastname=:lastname Where id=:id");
            $statement->bindValue('lastname',  trim($_POST['lastname']));
            $statement->bindValue('id', Session::getInstance()->getUser()->id);
            $statement->execute();
        }

        if(isset($_POST['email']) && !empty($_POST['email'])) {
            $statement = $db->prepare("select id from user where email=:email");
            $statement->bindValue('email', $_POST['email']);
            $statement->execute();
            if($statement->fetchColumn() > 0) {
                $db->rollBack();
                $view = new View();
                return $view->render('profil',["message"=>"This email already is in use, please insert different email."]);
            } else {
                $statement = $db->prepare("Update user set email=:email Where id=:id");
                $statement->bindValue('email',  trim($_POST['email']));
                $statement->bindValue('id', Session::getInstance()->getUser()->id);
                $statement->execute();
            }
        }

        if((isset($_POST['newpass']) && !empty($_POST['newpass'])) && (isset($_POST['newpassconf']) && empty($_POST['newpassconf']))) {
            $db->rollBack();
            $view = new View();
            return $view->render('profil',["message"=>"Please insert both new password and confirm new password."]);

        } elseif((isset($_POST['newpass']) && empty($_POST['newpass'])) && (isset($_POST['newpassconf']) && !empty($_POST['newpassconf']))) {
            $db->rollBack();
            $view = new View();
            return $view->render('profil',["message"=>"Please insert both new password and confirm new password."]);

        } elseif(isset($_POST['newpass']) && !empty($_POST['newpass']) && isset($_POST['newpassconf']) && !empty($_POST['newpassconf'])) {
            if($_POST['newpass'] !== $_POST['newpassconf']) {
                $db->rollBack();
                $view = new View();
                return $view->render('profil',["message"=>"New password and confirm new password must be identical!"]);
            } elseif($_POST['newpass'] === $_POST['newpassconf']) {
                $statement = $db->prepare("Update user set pass=:pass Where id=:id");
                $statement->bindValue('pass',  password_hash(Request::post("newpass"),PASSWORD_DEFAULT));
                $statement->bindValue('id', Session::getInstance()->getUser()->id);
                $statement->execute();
            }
        }

        if($_FILES["file"]["size"]>0) {
            $pictureName = str_replace(' ','_',$_FILES["file"]["name"]);
            if(move_uploaded_file($_FILES["file"]["tmp_name"], BP . "images/" . $pictureName)) {
                $statement = $db->prepare("Update user set picture=:picture Where id=:id");
                $statement->bindValue('picture', $pictureName);
                $statement->bindValue('id', Session::getInstance()->getUser()->id);
                $statement->execute();
            } else {
                $db->rollBack();
                $view = new View();
                return $view->render('profil',["message"=>"Something wrong with picture upload"]);
            }
        }

        $db->commit();

        $statement = $db->prepare("select id, firstname, lastname, email, picture from user where id=:id");
        $statement->bindValue('id', Session::getInstance()->getUser()->id);
        $statement->execute();

        $user = $statement->fetch();
        Session::getInstance()->login($user);

        $view = new View();
        $view->render('profil',["message"=>"Ažurirano."]);
    }

    public function delete($post)
    {
        $db = Db::connect();
        $db->beginTransaction();

        $statement = $db->prepare("delete from tag where post=:post");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("delete from comment where post=:post");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("delete from likes where post=:post");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("delete from post where id=:post");
        $statement->bindValue('post', $post);
    
        $statement->execute();

        $db->commit();
        
        $this->index();
    }

    public function comment($post)
    {
        $db = Db::connect();
        $statement = $db->prepare("insert into comment (post,user, content) values (:post,:user,:content)");
        $statement->bindValue('post', $post);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->bindValue('content', Request::post("content"));
        $statement->execute();
        
        $view = new View();

        $view->render('view', [
            "post" => Post::find($post)
        ]);
    }

    public function tag($post) {
        $db = Db::connect();
        $statement = $db->prepare("insert into tag (post, name) values (:post,:name)");
        $statement->bindValue('post', $post);
        $statement->bindValue('name', Request::post("name"));
        $statement->execute();

        $view = new View();

        $view->render('view', [
            "post" => Post::find($post)
        ]);
    }


    public function like($post)
    {
        $db = Db::connect();
        $statement = $db->prepare("insert into likes (post,user) values (:post,:user)");
        $statement->bindValue('post', $post);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->execute();
        
        $this->index();
    }


    public function authorize()
    {
//ne dostaju kontrole
        $db = Db::connect();
        $statement = $db->prepare("select id, firstname, lastname, email, pass, picture from user where email=:email");
        $statement->bindValue('email', Request::post("email"));
        $statement->execute();


        if($statement->rowCount()>0){
            $user = $statement->fetch();
            if(password_verify(Request::post("password"), $user->pass)){
              
                unset($user->pass);
                
                Session::getInstance()->login($user);

                $this->index();
            }else{
                $view = new View();
                $view->render('login',["message"=>"Neispravna kombinacija korisničko ime i lozinka"]);
            }
        }else{
            $view = new View();
            $view->render('login',["message"=>"Neispravan email"]);
        }
    }



    public function logout()
    {
        Session::getInstance()->logout();
        $this->index();
    }

    public function json()
    {
        $posts = Post::all();
       //print_r($posts);
        echo json_encode($posts);
    }

    public function index()
    {
        $posts = Post::all();
        $view = new View();
        $view->render('index', [
            "posts" => $posts
        ]);
    }

    function bulkinsert()
    {
        $db = Db::connect();
        for($i=0;$i<1000;$i++){

            $statement = $db->prepare("insert into post (content,user) values ('DDDD $i',1)");
            $statement->execute();

            $id = $db->lastInsertId();

            for($j=0;$j<10;$j++){

                $statement = $db->prepare("insert into comment (content,user,post) values ('CCCCC $i',1,$id)");
                $statement->execute();
            }
        }
    }
}