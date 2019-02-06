<?php

class Post
{
    private $id;

    private $content;

    private $user;

    private $date;

    private $likes;

    private $comments;

    private $userid;

    public function __construct($id, $content, $user,$date, $likes,$comments,$userid)
    {
        $this->setId($id);
        $this->setContent($content);
        $this->setUser($user);
        $this->setDate($date);
        $this->setLikes($likes);
        $this->setComments($comments);
        $this->setUserid($userid);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __call($name, $arguments)
    {
        $function = substr($name, 0, 3);
        if ($function === 'set') {
            $this->__set(strtolower(substr($name, 3)), $arguments[0]);
            return $this;
        } else if ($function === 'get') {
            return $this->__get(strtolower(substr($name, 3)));
        }

        return $this;
    }

    public static function all()
    {

       // $time=microtime(true);

        $list = [];
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date, 
        count(c.id) as likes
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
        where a.date > ADDDATE(now(), INTERVAL -7 DAY) 
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date 
        order by a.date desc limit 10");
        $statement->execute();
        foreach ($statement->fetchAll() as $post) {

            $statement = $db->prepare("select a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date from comment a inner join user b on a.user=b.id where a.post=:id ");
            $statement->bindValue('id', $post->id);
            $statement->execute();
            $comments = $statement->fetchAll();

            $list[] = new Post($post->id, $post->content, $post->user,$post->date,$post->likes,$comments,0);
           // $list[] = $post;
        }
         //   $time2 = microtime(true);
       // echo $time2-$time;

        return $list;
    }


    public static function allinone()
    {

        $time=microtime(true);
        $list = [];
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date,
        d.id as commentid, d.content as commentcontent ,
        concat(e.firstname, ' ', e.lastname) as commentuser,
        count(c.id) as likes
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
        inner join comment d on a.id=d.post
        inner join user e on d.user=e.id
        where a.date > ADDDATE(now(), INTERVAL -7 DAY) 
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date ,
        d.id , d.content  ,
        concat(e.firstname, ' ', e.lastname) 
        order by a.date desc limit 100");
        $statement->execute();
        //todo završiti
        foreach ($statement->fetchAll() as $post) {



            $list[] = new Post($post->id, $post->content, $post->user,$post->date,$post->likes,[] ,0);
        }
        $time2 = microtime(true);
        echo $time2-$time;

        return $list;
    }


    public static function find($id)
    {
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date, a.user as userid, count(c.id) as likes
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
         where a.id=:id");
        $statement->bindValue('id', $id);
        $statement->execute();
        $post = $statement->fetch();

        $statement = $db->prepare("select a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date from comment a inner join user b on a.user=b.id where a.post=:id ");
        $statement->bindValue('id', $id);
        $statement->execute();
        $comments = $statement->fetchAll();

        return new Post($post->id, $post->content, $post->user, $post->date,$post->likes, $comments,$post->userid);
    }
}