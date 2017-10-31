<?php
/*
PlateNumb - 車牌號碼
RouteUID - 路線唯一識別代碼
Direction - 去返程 = ['0: 去程', '1: 返程']
BusPosition - 車輛位置經度
DutyStatus - 勤務狀態 = ['0: 正常', '1: 開始', '2: 結束']
BusStatus - 行車狀況 = ['0: 正常', '1: 車禍', '2: 故障', '3: 塞車', '4: 緊急求援', '5: 加油', '90: 不明', '91: 去回不明', '98: 偏移路線', '99: 非營運狀態', '100: 客滿', '101: 包車出租', '255: 未知']
GPSTime - 車機時間
*/
require __DIR__ . '/vendor/autoload.php';
use Location\Coordinate;
use Location\Distance\Vincenty;

$calculator = new Vincenty();

$pool = array();
foreach(glob(__DIR__ . '/data/*.json') AS $jsonFile) {
  $json = json_decode(file_get_contents($jsonFile), true);
  foreach($json AS $item) {
    if(!isset($item['PlateNumb'])) {
      continue;
    }
    if($item['DutyStatus'] == 1 && $item['BusStatus'] == 0) {
      $t = strtotime($item['GPSTime']);
      $day = date('Ymd', $t);
      if(!isset($pool[$item['PlateNumb']])) {
        $pool[$item['PlateNumb']] = array();
      }
      if(!isset($pool[$item['PlateNumb']][$day])) {
        $pool[$item['PlateNumb']][$day] = array();
      }
      $pool[$item['PlateNumb']][$day][$t] = array(
        $item['RouteName']['Zh_tw'],
        $item['Direction'],
        $item['BusPosition']['PositionLat'],
        $item['BusPosition']['PositionLon'],
      );
    }
  }
}

$fh = fopen(__DIR__ . '/report.csv', 'w');
fputcsv($fh, array('車牌', '日期', '路線', '0:去|1:返', '移動距離', '開始時間', '結束時間', '行駛時間', '點位數量'));
foreach($pool AS $PlateNumb => $data1) {
  foreach($data1 AS $day => $data2) {
    ksort($data2);
    $currentRoute = '';
    $currentDirection = -1;
    $routeData = false;
    foreach($data2 AS $t => $data3) {
      if($currentRoute != $data3[0] || $currentDirection != $data3[1]) {
        if(false !== $routeData) {
          $routeData['time'] = round(($routeData['timeEnd'] - $routeData['timeBegin']) / 3600, 2);
          $routeData['timeBegin'] = date('YmdHis', $routeData['timeBegin']);
          $routeData['timeEnd'] = date('YmdHis', $routeData['timeEnd']);
          $routeData['distance'] = round($routeData['distance'] / 1000, 2);
          fputcsv($fh, array($PlateNumb, $day, $routeData['name'], $routeData['direction'], $routeData['distance'], $routeData['timeBegin'], $routeData['timeEnd'], $routeData['time'], $routeData['countPoints']));
        }
        $routeData = array(
          'name' => $data3[0],
          'point' => false,
          'distance' => 0.0,
          'timeBegin' => 0,
          'timeEnd' => 0,
          'time' => 0,
          'direction' => $data3[1],
          'countPoints' => 0,
        );
        $currentRoute = $data3[0];
        $currentDirection = $data3[1];
      }

      if(false === $routeData['point']) {
        $routeData['point'] = new Coordinate($data3[2], $data3[3]);
        $routeData['timeBegin'] = $routeData['timeEnd'] = $t;
      } else {
        $p = new Coordinate($data3[2], $data3[3]);
        $routeData['distance'] += $calculator->getDistance($routeData['point'], $p);
        $routeData['point'] = $p;
        $routeData['timeEnd'] = $t;
      }
      ++$routeData['countPoints'];
    }
    $routeData['time'] = round(($routeData['timeEnd'] - $routeData['timeBegin']) / 3600, 2);
    $routeData['timeBegin'] = date('YmdHis', $routeData['timeBegin']);
    $routeData['timeEnd'] = date('YmdHis', $routeData['timeEnd']);
    $routeData['distance'] = round($routeData['distance'] / 1000, 2);
    fputcsv($fh, array($PlateNumb, $day, $routeData['name'], $routeData['direction'], $routeData['distance'], $routeData['timeBegin'], $routeData['timeEnd'], $routeData['time'], $routeData['countPoints']));
  }
}
