<?php
$dataPath = __DIR__ . '/data';
if(!file_exists($dataPath)) {
  mkdir($dataPath, 0777, true);
}
$json = json_decode(file_get_contents('http://ptx.transportdata.tw/MOTC/v2/Bus/RealTimeByFrequency/City/Tainan?$format=json'), true);
if(isset($json[0])) {
  $file = $dataPath . '/' . date('YmdHis', strtotime($json[0]['UpdateTime'])) . '.json';
  if(!file_exists($file)) {
    file_put_contents($file, json_encode($json));
  }
}
