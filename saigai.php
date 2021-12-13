<?php

//気象庁から高頻度フィードを取得、釧路地方気象台からの発表を取得
//高頻度フィードから見つからなければ長期フィードから取得
//見つかったデータをテキストファイルに追記する
//最新のデータとアップ用のテキストデータの時刻を比較して違った場合にchatworkに投稿

date_default_timezone_set ('Asia/Tokyo'); 
$time = date('Y-m-d_His');

list($out,$s) = data_get("http://www.data.jma.go.jp/developer/xml/feed/extra.xml");
if($s == 'no') {
  list($out,$s) = data_get("http://www.data.jma.go.jp/developer/xml/feed/extra_l.xml");
}

$file_name = "saigai.txt";
if (file_exists("$file_name")) {
  $a = fopen("$file_name", "a");
  @fwrite($a, $out);
  @fwrite($a, "----\n");
  fclose($a);
} else {
  $out .= "----\n";
  file_put_contents("$file_name", $out);    
}

echo $out;

$cmd = "python up.py";
echo exec($cmd);

//////////////////////////////////////////////////////////////////////////////////////////
function data_get($url) {
  #$url = "http://www.data.jma.go.jp/developer/xml/feed/extra.xml";
  $source = file_get_contents($url);
  $xml = simplexml_load_string($source);
  $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
  $json_value = json_decode($json);

  $out = '';
  $num = count($json_value->{'entry'});

  $s = 'no';
  for($i = 0; $num > $i; $i++) {
    $name = $json_value->{'entry'}[$i]->{'author'}->{'name'};
    $title = $json_value->{'entry'}[$i]->{'title'};
    if(strpos($name,'釧路') !== false and strpos($title,'警報・注意報') !== false){
      $t = new DateTime($json_value->{'entry'}[$i]->{'updated'});
      $t->setTimeZone(new DateTimeZone('Asia/Tokyo'));
      $out .= $t->format('Y-m-d H:i:s');
      $out .= "【発表】\n"; 
      $out .= $json_value->{'entry'}[$i]->{'title'} . "\n";
      $out .= $json_value->{'entry'}[$i]->{'author'}->{'name'} . "\n";
      $out .= $json_value->{'entry'}[$i]->{'content'} . "\n";
      $out .= "https://www.jma.go.jp/bosai/#pattern=forecast&area_type=offices&area_code=014100\n";
      $s = 'yes';
      $out .= "\n";
      $out .= xml_get($json_value->{'entry'}[$i]->{'id'});
      if($s == 'yes') {
        break;
      }
    }  
  }
  return [$out, $s];
}

function xml_get($url) {
  $source = file_get_contents($url);
  $xml = simplexml_load_string($source);
  $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
  $json_value = json_decode($json);
  $out = '';
  
  if(isset($json_value->{'Head'}->{'Headline'}->{'Information'}[0]->{'Item'})){
    #echo "aaa";
  } else {
    #echo "nasi\n";
    return;
    #exit('プログラムを終了します');
  }

  $type = gettype($json_value->{'Body'}->{'Warning'});
  if($type == "array") {
    $w_num = count($json_value->{'Body'}->{'Warning'});
    $item_type = gettype($json_value->{'Body'}->{'Warning'}[0]->{'Item'});
    if($item_type == "array") {
      $item_num = count($json_value->{'Body'}->{'Warning'}[0]->{'Item'});
      for($i = 0; $item_num > $i; $i++) {
        $out .= "■" . $json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Area'}->{'Name'} . "\n";
        $kind_type = gettype($json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'});
        if($kind_type == "array") {
          $kind_num = count($json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'});
          for($x = 0; $kind_num > $x; $x++) {
            $out .= $json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'}[$x]->{'Name'};
            $out .= "【" . $json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'}[$x]->{'Status'} . "】\n";
          }
        } else {
          if(isset($json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'}->{'Name'})) {
            $out .= $json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'}->{'Name'};
            $out .= "【" . $json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'}->{'Status'} . "】\n";
          } else {
            $out .= "【" . $json_value->{'Body'}->{'Warning'}[0]->{'Item'}[$i]->{'Kind'}->{'Status'} . "】\n";
          }
          
        }
      }
    } else {
      $out .= "■" . $json_value->{'Body'}->{'Warning'}[0]->{'Item'}->{'Area'}->{'Name'} . "\n";
      $kind_type = gettype($json_value->{'Body'}->{'Warning'}[0]->{'Item'}->{'Kind'});
      if($kind_type == "array") {
        $kind_num = count($json_value->{'Body'}->{'Warning'}[0]->{'Item'}->{'Kind'});
        for($x = 0; $kind_num > $x; $x++) {
          $out .= $json_value->{'Body'}->{'Warning'}[0]->{'Item'}->{'Kind'}[$x]->{'Name'};
          $out .= "【" . $json_value->{'Body'}->{'Warning'}[0]->{'Item'}->{'Kind'}[$x]->{'Status'} . "】\n";
        }
      }
    }
  }
 
  return $out;
}
