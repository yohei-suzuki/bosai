<?php
//気象庁から高頻度フィードを取得、釧路地方気象台からの発表を取得
//高頻度フィードから見つからなければ長期フィードから取得
//見つかったデータをテキストファイルに追記する
//最新のデータとアップ用のテキストデータの時刻を比較して違った場合にchatworkに投稿

date_default_timezone_set ('Asia/Tokyo'); 

$info1 = data_get("http://www.data.jma.go.jp/developer/xml/feed/eqvol.xml");
$info2 = data_get("http://www.data.jma.go.jp/developer/xml/feed/eqvol_l.xml");
$info = array_merge($info1, $info2);

$out = area_get($info);
echo $out;

if(!empty($out)) {
  $file_name = "jisin.txt";
  if (file_exists($file_name)) {
    $a = fopen($file_name, "a");
    @fwrite($a, $out);
    @fwrite($a, "----\n");
    fclose($a);
  } else {
    $out .= "----\n";
    file_put_contents($file_name, $out);    
  }
  $cmd = "python up_j.py";
  echo exec($cmd);  
}

#####################################################################################
function data_get($url) {
  echo $url ."\n";
  $out = '';
  $info = array();
  $source = file_get_contents($url);
  $xml = simplexml_load_string($source);
  $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
  $json_value = json_decode($json);

  $num = count($json_value->{'entry'});
  echo $num . "\n";
  $s = 'no';
  for($i = 0; $num > $i; $i++) {
    $title = $json_value->{'entry'}[$i]->{'title'};
    if(strpos($title,'震度に関する情報') !== false){
      $info[$i] = '';
      $t = new DateTime($json_value->{'entry'}[$i]->{'updated'});
      $t->setTimeZone(new DateTimeZone('Asia/Tokyo'));
      $info[$i] .= $t->format('Y-m-d H:i:s');
      $info[$i] .= "【発表】\n"; 
      $info[$i] .= $json_value->{'entry'}[$i]->{'content'} . "\n";
      $info[$i] .= xml_get($json_value->{'entry'}[$i]->{'id'});
    }
  }
  return $info;
}

function area_get($info) {
  $out = '';
  $areas = array("釧路市", "厚岸町", "浜中町", "標茶町", "弟子屈町", "鶴居", "白糠町", "根室市", "別海町", "中標津", "標津町", "羅臼町");
  foreach($info as $i) {
    foreach($areas as $area) {
      if(strpos($i,$area) !== false){
        #echo $area . " #############################\n";
        #echo $i;
        $out .= $i;
        break;
      }
    }
  }
  return $out;
}

function xml_get($url) {
  $source = file_get_contents($url);
  $xml = simplexml_load_string($source);
  $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
  $json_value = json_decode($json);

  $out = '';

  if(!isset($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'})){
    return;
  }

  $type_pref = gettype($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'});
  $s = 'yes';
  if('array' == $type_pref){
    $num = count($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'});
    for ($i = 0; $num > $i; $i++) {
      if(strpos($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Name'}, "北海道") !== false) {
        $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Name'} . "\n";
        $type_area = gettype($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'});
        if('array' == $type_area){
          $area_num = count($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'});
          for($x = 0; $area_num > $x; $x++) {
            $type_city = gettype($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}[$x]->{'City'});
            if('array' == $type_city){
              $city_num = count($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}[$x]->{'City'});
              for($z = 0; $city_num > $z; $z++) {
                $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}[$x]->{'City'}[$z]->{'Name'} . " ";
                $out .= "震度" . $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}[$x]->{'City'}[$z]->{'MaxInt'} . "\n";
              }
            } else {
              $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}[$x]->{'City'}->{'Name'} . " ";
              $out .= "震度" . $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}[$x]->{'City'}->{'MaxInt'} . "\n";
            }
          }
        } else {
          $type_city = gettype($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}->{'City'});
          if('array' == $type_city){
            for($z = 0; $city_num > $z; $z++) {
              $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}->{'City'}[$z]->{'Name'} . " ";
              $out .= "震度" . $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}->{'City'}[$z]->{'MaxInt'} . "\n";
            }
          } else {
            $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}->{'City'}->{'Name'} . " ";
            $out .= "震度" . $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}[$i]->{'Area'}->{'City'}->{'MaxInt'} . "\n";
          }
        }
      } else {
        $out .= "no\n";
      }
    }
  } else {
    if(strpos($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Name'}, "北海道") !== false) {
      $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Name'} . "\n"; 
      $type_area = gettype($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'});
      if('array' == $type_area){
        $num_area = count($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'});
        for ($i = 0; $num_area > $i; $i++) {
          $type_city = gettype($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'}[$i]->{'City'});
          if('array' == $type_city){
            $num_city = count($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'}[$i]->{'City'});
            for ($x = 0; $num_city > $x; $x++) {
              $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'}[$i]->{'City'}[$x]->{'Name'} . " ";
              $out .= "震度 " . $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'}[$i]->{'City'}[$x]->{'MaxInt'} . "\n";
            }
          } else {
            $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'}[$i]->{'City'}->{'Name'} . " ";
            $out .= "震度 " . $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'}[$i]->{'City'}->{'MaxInt'} . "\n";
          }
        }
      } else {
        $type_area = gettype($json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Area'});
        if('array' == $type_area){

        } else {
          $out .= $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'Name'} . " ";
          $out .= "震度 " . $json_value->{'Body'}->{'Intensity'}->{'Observation'}->{'Pref'}->{'MaxInt'} . "\n";
        }
      }
    } else {
      $out .= "no\n";
    }
    $out .= "https://www.jma.go.jp/bosai/#pattern=earthquake_volcano&area_type=offices&area_code=014100\n";
  }

  return $out;
}
