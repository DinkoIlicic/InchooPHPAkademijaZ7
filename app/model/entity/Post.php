<?php

class Post
{
    private $id;

    private $content;

    private $user;

    private $date;

    private $likes;

    private $dislikes;

    private $comments;

    private $tags;

    private $userid;

    public function __construct($id, $content, $user,$date, $likes, $dislikes, $comments, $tags, $userid)
    {
        $this->setId($id);
        $this->setContent($content);
        $this->setUser($user);
        $this->setDate($date);
        $this->setLikes($likes);
        $this->setDislikes($dislikes);
        $this->setComments($comments);
        $this->setUserid($userid);
        $this->setTags($tags);
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

    public static function allXXXXXX()
    {

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

        }

        return $list;
    }
    public static function all()
    {

        $list = [];
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date,
        d.id as commentid, d.content as commentcontent ,
        concat(e.firstname, ' ', e.lastname) as commentuser,
        d.date as commentdate,
        count(distinct c.id) as likes,
        count(distinct f.id) as dislikes
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
        left join comment d on a.id=d.post
        left join user e on d.user=e.id
        left join dislike f on a.id=f.post
        where a.date > ADDDATE(now(), INTERVAL -7 DAY) 
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date ,
        d.id , d.content  ,
        concat(e.firstname, ' ', e.lastname) ,d.date
        having count(distinct f.id)<5
        order by a.date desc limit 100");
        $statement->execute();
        //todo završiti
        $komentari=[];
        $postid=0;
        foreach ($statement->fetchAll() as $post) {
            //nema komentare - morao sam u upiti ići s left join
            if($post->commentid==null){
                $list[] = new Post($post->id, $post->content, $post->user,$post->date,$post->likes,$post->dislikes,[] ,null, 0);
                continue;
            }
            //prvi rezultat ili promjena posta
            if($postid===0 || $postid!==$post->id){
                if($postid!==0){
                    $p->setComments($komentari);
                    $list[]=$p;
                }
                $postid=$post->id;
                $p = new Post($post->id, $post->content, $post->user,$post->date,$post->likes,$post->dislikes,[],null ,0);
                $komentari=[];
            }
            $k=new stdClass();
            $k->id = $post->commentid;
            $k->content = $post->commentcontent;
            $k->user = $post->commentuser;
            $k->date = $post->commentdate;
            $komentari[] = $k;

        }

        return $list;
    }

    public static function countDislike($id)
    {
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("select count(distinct id) as commentdislikes from dislike where comment=:id");
        $statement->bindValue('id', $id);
        $statement->execute();
        $counts = $statement->fetch();
        return $counts;
    }


    public static function find($id)
    {
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date, a.user as userid, 
        count(distinct c.id) as likes, count(distinct d.id) as dislikes
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
        left join dislike d on a.id=d.post
        where a.id=:id");
        $statement->bindValue('id', $id);
        $statement->execute();
        $post = $statement->fetch();

        $statement = $db->prepare("select
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date 
        from 
        comment a inner join user b on a.user=b.id 
        where a.post=:id ");
        $statement->bindValue('id', $id);
        $statement->execute();
        $comments = $statement->fetchAll();

        $statement = $db->prepare("select
        id, name
        from 
        tag 
        where post=:id ");
        $statement->bindValue('id', $id);
        $statement->execute();
        $tags = $statement->fetchAll();

        return new Post($post->id, $post->content, $post->user, $post->date,$post->likes, $post->dislikes, $comments, $tags, $post->userid);
    }
}