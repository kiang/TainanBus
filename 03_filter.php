<?php
/*
Array
(
    [0] => 車牌
    [1] => 日期
    [2] => 路線
    [3] => 0:去|1:返
    [4] => 移動距離
    [5] => 開始時間
    [6] => 結束時間
    [7] => 行駛時間
    [8] => 點位數量
)

車牌, 日期, 第一班時間, 末班時間, 期間, 車輛行駛時間, 車輛行駛距離
*/

$fh = fopen(__DIR__ . '/report.csv', 'r');
fgetcsv($fh, 2048);
$result = array();
while($line = fgetcsv($fh, 2048)) {
  if($line[1] == '20171016' || $line[1] == '20171025') {
    continue;
  }
  if(!isset($result[$line[0]])) {
    $result[$line[0]] = array();
  }
  if(!isset($result[$line[0]][$line[1]])) {
    $result[$line[0]][$line[1]] = array(
      'begin' => 0,
      'end' => 0,
      'duration' => 0.0,
      'moving_time' => 0.0,
      'moving_distance' => 0.0,
    );
  }
  if($result[$line[0]][$line[1]]['begin'] == 0 || $line[5] < $result[$line[0]][$line[1]]['begin']) {
    $result[$line[0]][$line[1]]['begin'] = $line[5];
  }
  if($line[6] > $result[$line[0]][$line[1]]['end']) {
    $result[$line[0]][$line[1]]['end'] = $line[5];
    $result[$line[0]][$line[1]]['duration'] = toUnixTime($result[$line[0]][$line[1]]['end']) - toUnixTime($result[$line[0]][$line[1]]['begin']);
  }
  $result[$line[0]][$line[1]]['moving_time'] += $line[7];
  $result[$line[0]][$line[1]]['moving_distance'] += $line[4];
}

$fh = fopen(__DIR__ . '/report03.csv', 'w');
fputcsv($fh, array('車牌', '日期', '開始時間', '結束時間', '間隔時間(小時)', '移動時間(小時)', '移動距離(公里)'));
foreach($result AS $plate => $data1) {
  foreach($data1 AS $day => $data2) {
    $data2['duration'] = round($data2['duration'] / 3600, 2);
    $data2['begin'] = substr($data2['begin'], 8, 2) . ':' . substr($data2['begin'], 10, 2);
    $data2['end'] = substr($data2['end'], 8, 2) . ':' . substr($data2['end'], 10, 2);
    fputcsv($fh, array($plate, $day, $data2['begin'], $data2['end'], $data2['duration'], $data2['moving_time'], $data2['moving_distance']));
  }
}

function toUnixTime($v) {
  return mktime(substr($v, 8, 2),substr($v, 10, 2),substr($v, 12, 2),substr($v, 4, 2),substr($v, 6, 2),substr($v, 0, 4));
}
