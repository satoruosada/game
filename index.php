<?php
ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

//恐竜達格納用
$kyoryus = array();
//性別クラス
class Sex{
  const MAN = 1;
  const WOMAN = 2;
  const OKAMA = 3;
}
//抽象クラス（生き物クラス）
abstract class Creature{
  protected $name;
  protected $hp;
  protected $attackMin;
  protected $attackMax;
  abstract public function sayCry();
  public function setName($str){
    $this->name = $str;
  }
  public function getName(){
    return $this->name;
  }
  public function setHp($num){
    $this->hp = $num;
  }
  public function getHp(){
    return $this->hp;
  }
  public function attack($targetObj){
    $attackPoint = mt_rand($this->attackMin, $this->attackMax);
    if(!mt_rand(0,9)){//10分の1の確率でクリティカル
      $attackPoint = $attackPoint * 1.5;
      $attackPoint = (int)$attackPoint;
      History::set($this->getName().'のクリティカルヒット！！');
    }
    $targetObj->setHp($targetObj->getHp()-$attackPoint);
    History::set($attackPoint.'ポイントのダーメージ！！');
  }
}
//人クラス
class Human extends Creature{
  protected $sex;
  public function __construct($name, $sex, $hp, $attackMin, $attackMax){
    $this->name = $name;
    $this->sex = $sex;
    $this->hp = $hp;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  public function setSex($num){
    $this->sex = $num;
  }
  public function getSex(){
    return $this->sex;
  }
  public function sayCry(){
    History::set($this->name.'が叫ぶ！');
    switch($this->sex){
      case Sex::MAN :
        History::set('ぐはぁっ！');
        break;
      case Sex::WOMAN :
        History::set('きゃっ！');
        break;
      case Sex::OKAMA :
        History::set('もっと！♡');
        break;
    }
  }
}
//恐竜クラス
class Kyoryu extends Creature{
  //プロパティ
  protected $img;
  //コンストラクタ
  public function __construct($name, $hp, $img, $attackMin, $attackMax){
    $this->name = $name;
    $this->hp = $hp;
    $this->img = $img;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  //ゲッター
  public function getImg(){
    return $this->img;
  }
  public function sayCry(){
    History::set($this->name.'が叫ぶ！');
    History::set('はうっ！');
  }
}
//魔法を使える恐竜クラス
class MagicKyoryu extends Kyoryu{
 private $magicAttack;
 function __construct($name, $hp, $img, $attackMin, $attackMax, $magicAttack){
   parent::__construct($name, $hp, $img, $attackMin, $attackMax);
   $this->magicAttack = $magicAttack;
 }
 public function getMagicAttack(){
   return $this->magicAttack;
 }
 public function attack($targetObj){
   if(!mt_rand(0,4)){ //5分の1の確率で魔法攻撃
     History::set($this->name.'の魔法攻撃！！');
     $targetObj->setHp($targetObj->getHp() - $this->magicAttack);
     History::set($this->magicAttack.'ポイントのダメージを受けた！');
   }else{
     parent::attack($targetObj);
   }
 }
}
interface HistoryInterface{
  public function set($str);
  public function clear();
}
// 履歴管理クラス（インスタンス化して複数に増殖させる必要性がないクラスなので、staticにする）
class History implements HistoryInterface{
  public function set($str){
    // セッションhistoryが作られてなければ作る
    if(empty($_SESSION['history'])) $_SESSION['history'] = '';
    // 文字列をセッションhistoryへ格納
    $_SESSION['history'] .= $str.'<br>';
  }
  public function clear(){
    unset($_SESSION['history']);
  }
}

//インスタンス生成
$human = new Human('勇者見習い', Sex::OKAMA, 500, 40, 120);
$kyoryus[] = new Kyoryu('ステゴサウルス',100,'img/ステゴサウルス.jpeg',20,40);
$kyoryus[] = new MagicKyoryu('スピノサウルス',300,'img/スピノサウルス.jpeg',20,60,mt_rand(500, 100));
$kyoryus[] = new Kyoryu('ティアノサウルス',200,'img/ティラノサウルス.jpeg',30,50);
$kyoryus[] = new Kyoryu('ディプロドクス',300,'img/ディプロドクス.jpeg',20,30);
$kyoryus[] = new MagicKyoryu('トリケラトプス',300,'img/トリケラトプス.jpeg',20,40,mt_rand(60, 120));
$kyoryus[] = new Kyoryu('プテラノドン',200,'img/プテラノドン.jpeg',20,30);

function createKyoryu(){
  global $kyoryus;
  $kyoryu = $kyoryus[mt_rand(0,5)];
  History::set($kyoryu->getName().'が現れた！');
  $_SESSION['kyoryu'] = $kyoryu;
}
function createHuman(){
  global $human;
  $_SESSION['human'] = $human;
}

function init(){
  History::clear();
  History::set('初期化します！');
  $_SESSION['knockDownCount'] = 0;
  createHuman();
  createKyoryu();
}
function gameOver(){
  $_SESSION = array();
}

//1,post送信されていた場合
if(!empty($_POST)){
  $attackFlg = (!empty($_POST['attack'])) ? true : false;
  $escapeFlg = (!empty($_POST['escape'])) ? true : false;
  $startFlg = (!empty($_POST['start'])) ? true : false;
  error_log('POSTされた！');

  if($startFlg){
    History::set('ゲームスタート！');
    init();
  }else{
    //攻撃するを押した場合
    if($attackFlg){

      //恐竜に攻撃を与える
      History::set($_SESSION['human']->getName().'の攻撃！！');
      $_SESSION['human']->attack($_SESSION['kyoryu']);
      $_SESSION['kyoryu']->sayCry();

      //モンスターが攻撃する
      History::set($_SESSION['kyoryu']->getName().'の攻撃！');
      $_SESSION['kyoryu']->attack($_SESSION['human']);
      $_SESSION['human']->sayCry();

      //自分のHPが０以下になったら、ゲームオーバー
      if($_SESSION['human']->getHp() <= 0){
        gameOver();
      }else{
        // hpが0以下になったら、別のモンスターを出現させる
        if($_SESSION['kyoryu']->getHp() <= 0){
          History::set($_SESSION['kyoryu']->getName().'を倒した！');
          createKyoryu();
          $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
        }
       }
     }elseif($escapeFlg){//逃げるを押した場合
       History::set('逃げた！');
       createKyoryu();
     }else{//かわすを押した場合
       History::set('恐竜の攻撃をかわした！');
     }
   }
   $_POST = array();
 }

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHPオブジェクトGAME</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">

  <header class="site-width">
    <div class="head">
      <ul class="menu">
        <li>ジュラシック○ーク</li>
        <li>MENU
          <ul class="sub">
            <li><a href="https://www.kyouryu.info/popularity_ranking2018.php">恐竜の種類</a></li>
            <li><a href="https://www.dinosaur.pref.fukui.jp/">おすすめ恐竜博物館</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </header>

    <div class="main">
     <h1>恐竜とバトル！！</h1>
      <div class="main2">
       <?php if(empty($_SESSION)){ ?>
        <h2>GAME START ?</h2>
        <form method="post">
          <input type="submit" name="start" value="▶︎ゲームスタート">
        </form>

        <?php }else{ ?>
        <h2><?php echo $_SESSION['kyoryu']->getName().'が現れた！！'; ?></h2>
        <div>
          <img src="<?php echo $_SESSION['kyoryu']->getImg(); ?>">
        </div>
        <p class="center">恐竜のHP:<?php echo $_SESSION['kyoryu']->getHp(); ?></p>
        <p class="center">倒した恐竜数:<?php echo $_SESSION['knockDownCount']; ?></p>
        <p class="center">自分の残りHP:<?php echo $_SESSION['human']->getHp(); ?></p>
        <form method="post">
          <input type="submit" name="attack" value="▶︎鉄砲で撃つ">
          <input type="submit" name="dodge" value="▶︎攻撃をかわす">
          <input type="submit" name="escape" value="▶︎逃げる">
          <input type="submit" name="start" value="▶︎ゲームリスタート">
        </form>

     <?php } ?>
         <div class="comment">
           <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
         </div>

       　</div>
     　</div>
   </div>
  <!--jquery-->
  <script
      src="https://code.jquery.com/jquery-3.4.1.min.js"
      integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
      crossorigin="anonymous"></script>
  <script src="app.js"></script>
 </body>
</html>
