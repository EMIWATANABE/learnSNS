<?php

session_start();
require('dbconnect.php');
require('function.php');

const CONTENT_PER_PAGE = 5;

    if (!isset($_SESSION['id'])) {
        header('Location: signin.php');
        exit();
    }

$signin_user = get_user($dbh, $_SESSION['id']);

//初期化
$errors = array();

//何ページ目を開いているか取得する
if (isset($_GET['page'])){
  $page = $_GET['page'];
}else{
$page = 1;
}

//-1などのページ数として不正な数値を渡された場合の対策
$page = max($page,1);

//ヒットしたレコードの数を取得するSQL


//取得したページ数を1ページあたりに表示する件数で割って何ページが最後になるか取得
$last_page = get_last_page($dbh);

//最後のページより大きい値を渡された場合の対策
$page = min($page,$last_page);

$start = ($page - 1) * CONTENT_PER_PAGE;

//ユーザーが投稿ボタンを押したら発動
if (!empty($_POST)){

    //バリデーション
    $feed = $_POST['feed'];//投稿データ

//投稿の空チェック
    if ($feed != ''){
        //投稿処理
        create_feed($dbh,$feed,$signin_user['id']);

        header('Location: timeline.php');
        exit();
    }else{
        $errors['feed'] = 'blank';
    }
}
 if (isset($_GET['search_word'])) {
        $sql = 'SELECT `f`.*, `u`.`name`, `u`.`img_name` FROM `feeds` AS `f` LEFT JOIN `users` AS `u` ON `f`.`user_id`=`u`.`id` WHERE f.feed LIKE "%"? "%" ORDER BY `created` DESC LIMIT '. CONTENT_PER_PAGE .' OFFSET ' . $start;
        $data = [$_GET['search_word']];
    } else {
    //LEFT JOINで全件取得
    $sql = 'SELECT`f`.*,`u`.`name`,`u`.`img_name` FROM `feeds` AS `f` LEFT JOIN `users` AS `u` ON `f`.`user_id` = `u`.`id` ORDER BY `created` DESC LIMIT '. CONTENT_PER_PAGE .' OFFSET ' . $start;
    $data = array();
  }
    $stmt = $dbh->prepare($sql);
    $stmt->execute($data);

    //表示用の配列を初期化
    $feeds = array();

    while (true){
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($record == false){
        break;
    }

    //いいね済みかどうかの確認
$record["is_liked"] = is_liked($dbh,$signin_user["id"],$record["id"]);


    //いいねの件数
    $record["like_cnt"] = count_like($dbh,$record["id"]);

    $feeds[] = $record;
    }

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Learn SNS</title>
  <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.css">
  <link rel="stylesheet" type="text/css" href="assets/font-awesome/css/font-awesome.css">
  <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body style="margin-top: 60px; background: lightyellow;">
  <div class="navbar navbar-default navbar-fixed-top">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse1" aria-expanded="false">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="#">Learn SNS</a>
      </div>
      <div class="collapse navbar-collapse" id="navbar-collapse1">
        <ul class="nav navbar-nav">
          <li class="active"><a href="#">タイムライン</a></li>
          <li><a href="#">ユーザー一覧</a></li>
        </ul>
        <form method="GET" action="" class="navbar-form navbar-left" role="search">
          <div class="form-group">
            <input type="text" name="search_word" class="form-control" placeholder="投稿を検索">
          </div>
          <button type="submit" class="btn btn-default">検索</button>
        </form>
        <ul class="nav navbar-nav navbar-right">
          <li class="dropdown">
            <span hidden id="signin-user"><?php echo $signin_user['id']; ?></span>

            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><img src="user_profile_img/<?php echo $signin_user['img_name']; ?>" width="18" class="img-circle"> <?php echo $signin_user['name']; ?><span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a href="#">マイページ</a></li>
              <li><a href="signout.php">サインアウト</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="row">
      <div class="col-xs-3">
        <ul class="nav nav-pills nav-stacked">
          <li class="active"><a href="timeline.php?feed_select=news">新着順</a></li>
          <li><a href="timeline.php?feed_select=likes">いいね！済み</a></li>
          <!-- <li><a href="timeline.php?feed_select=follows">フォロー</a></li> -->
        </ul>
      </div>
      <div class="col-xs-9">
        <div class="feed_form box8">
          <form method="POST" action="">
            <div class="form-group">
              <textarea name="feed" class="form-control" rows="3" placeholder="Happy Hacking!" style="font-size: 24px;"></textarea><br>
              <?php if (isset($errors['feed']) && $errors['feed'] == 'blank') { ?><p class = "alert alert-danger" >投稿データを入力してください</p>
          <?php } ?>
            </div>
            <input type="submit" value="投稿する" class="btn btn-warning">
          </form>
        </div>
        <?php foreach($feeds as $feed){ ?>
          <div class="box8">
            <div class="row">
              <div class="col-xs-1">
                <img src="user_profile_img/<?php echo $feed['img_name']; ?>" width="40">
              </div>
              <div class="col-xs-11">
                <?php echo $feed['name']; ?><br>
                <a href="#" style="color: #7F7F7F;"><?php echo $feed['created']; ?></a>
              </div>
            </div>
            <div class="row feed_content">
              <br>
              <div class="col-xs-12">
              <div class="col-xs-10" style="background:#fff; padding: 10px; height: 5em; border-radius: 10px; border: 1px solid #fdd35c;">
                <span style="font-size: 24px;"><?php echo $feed['feed']; ?></span>
              </div>
              </div>
            </div>
            <br>
            <div class="row feed_sub">
              <div class="col-xs-12">
                <span hidden class="feed-id"><?= $feed["id"] ?></span>
                <?php if ($feed['is_liked']): ?>
                <button class="btn btn-defalut btn-xs js-unlike">
                  <i class="fa fa-thunbs-up" aria-hidden="true"></i>
                  <span>いいねを取り消す</span>
                </button>
                <?php else: ?>
                    <button class="btn btn-default btn-xs js-like">
                        <i class="fa fa-thumbs-up" aria-hidden="true"></i>
                        <span>いいね!</span>
                    </button>
                  <?php endif; ?>
                    <span>いいね数 : </span>
                    <span class="like_count"><?= $feed['like_cnt'] ?></span>
                <span class="comment_count">コメント数 : 9</span>
                <?php if($feed["user_id"] == $_SESSION["id"]): ?>
                  <a href="edit.php?feed_id=<?php echo $feed["id"] ?>"class="btn btn-default btn-xs"><font color="limegreen">編集</font></a>
                  <a onclick="return confirm('ほんとに消す？');" href="delete.php?feed_id=<?php echo $feed["id"] ?>" class="btn btn-default btn-xs"><font color="indianred">削除</font></a>
                <?php endif; ?>
              </div>
            </div>
          </div>
      <?php } ?>
        <div aria-label="Page navigation">
          <ul class="pager">
            <?php if ($page == 1): ?>
            <li class="previous disabled"><a><span aria-hidden="true">&larr;</span>Newer</a></li>
            <?php else: ?>
              <li class="previous"><a href="timeline.php?page=<?= $page - 1; ?>"><span aria-hidden="true">&larr;</span> Newer</a></li>
            <?php endif; ?>

            <?php if ($page == $last_page): ?>

              <il class="next disabled"><a>Older <span aria-hidden="true">&rarr;</span></a></il>

              <?php else: ?>
                <li class="next"><a href="timeline.php?page=<?= $page + 1; ?>">Older<span aria-hidden="true">&rarr;</span></a></li>
                <?php endif; ?>
                          </ul>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/js/jquery-3.1.1.js"></script>
  <script src="assets/js/jquery-migrate-1.4.1.js"></script>
  <script src="assets/js/bootstrap.js"></script>
   <script src="assets/js/app.js"></script>
</body>
</html>
