<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  require 'basic_protobuf.php';

  if (!isset($_GET['id'])) die('Required parameter missing.');
  $part_numbers = array('006-B' . $_GET['id'] . '-00');

  // 1. Generating the request to Garmin Express server.

  $main_template = file_get_contents('RequestTemplate.txt');
  $updatefile_template = file_get_contents('RequestUpdateFileTemplate.txt');

  $update_files = '';
  foreach ($part_numbers as $part_number)
    $update_files .= str_replace('%part_number%', chop($part_number), $updatefile_template) . "\n";

  $device_xml = str_replace('%part_number%', chop($part_numbers[0]), $main_template);
  $device_xml = str_replace('%update_files%', $update_files, $device_xml);

  $request_data =
    write_string(1, 
      write_string(1, 'express') .
      write_string(2, 'en_US') .
      write_string(3, 'Windows') .
      write_string(4, '601 Service Pack 1')
    ) .
    write_string(2, $device_xml);


  // 2. POSTing the data to the update server.

  $ch=curl_init();

  curl_setopt($ch, CURLOPT_URL, 'http://omt.garmin.com/Rce/ProtobufApi/SoftwareUpdateService/GetAllUnitSoftwareUpdates');
  curl_setopt($ch, CURLOPT_USERAGENT, 'Garmin Core Service Win - 5.7.0.2');
  curl_setopt($ch, CURLOPT_HTTPHEADER,
    array(
      'Garmin-Client-Name: CoreService',
      'Garmin-Client-Version: 5.7.0.2',
      'X-garmin-client-id: EXPRESS',
      'Garmin-Client-Platform: windows',
      'Garmin-Client-Platform-Version: 601',
      'Garmin-Client-Platform-Version-Revision: 1',
      'Content-Type: application/octet-stream'
    )
  );
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);

  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $answer = curl_exec($ch);


  echo "Information from Garmin Express server:<br>\r\n";

  $info = curl_getinfo($ch);
  if ($info['http_code'] == '200') {
    $records = read_message($answer);
    if (is_array($records["1"])) {
      $records = $records["1"];
    } else {
      $records = array($records["1"]);
    }
    $update_list = array();

    foreach ($records as $record) {
      $fields = read_message($record);
      if (isset($fields[15]) && strcasecmp($fields[15], 'Firmware')) {
        $fields[6] = read_message($fields[6]);

        if (!is_array($fields[1]))
          $fields[1] = array($fields[1]);

        $changes = str_replace("\xE2\x80\xA2", "\x95", join('<br>', $fields[1]));

        echo(join('<br>', array($fields[2], $changes, $fields[8], $fields[9], make_url($fields[6][2]), $fields[6][3], $fields[6][4])));
      }
    }
  } else {
    echo $info['http_code'];
    echo '<pre>'; print_r($info); echo '</pre>';
  }


  // 5. Generating the request to WebUpdater server.
  $main_template = file_get_contents('RequestTemplate_WebUpdater.txt');
  $updatefile_template = file_get_contents('RequestUpdateFileTemplate_WebUpdater.txt');

  $update_files = '';
  foreach ($part_numbers as $part_number)
    $update_files .= str_replace('%part_number%', chop($part_number), $updatefile_template) . "\n";

  $request_data = str_replace('%part_number%', chop($part_numbers[0]), $main_template);
  $request_data = str_replace('%update_files%', $update_files, $request_data);


  // 6. POSTing the data to the update server.
  $ch=curl_init();

  curl_setopt($ch, CURLOPT_URL, 'https://www.garmin.com/support/WUSoftwareUpdate.jsp');
  curl_setopt($ch, CURLOPT_USERAGENT, 'Undefined agent');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);

  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $answer = curl_exec($ch);


  echo "<br><br>Information from Garmin Update server:<br>\r\n";

  $info = curl_getinfo($ch);
  if ($info['http_code'] == '200') {
    $xml = new SimpleXMLElement($answer);
    foreach ($xml as $record) {
      echo (string)$record->Update->Description . "<br>";

      $changes = urldecode((string)$record->Update->ChangeDescription);
      echo $changes . "<br>";
      printf("%u.%02u<br>", (int)$record->Update->Version->VersionMajor, (int)$record->Update->Version->VersionMinor);
      echo make_url((string)$record->Update->AdditionalInfo) . "<br>";
      echo make_url((string)$record->Update->UpdateFile->Location) . "<br>";
      echo (string)$record->Update->UpdateFile->MD5Sum . "<br>";
      echo (string)$record->Update->UpdateFile->Size . "<br>";
    }
  } else {
    echo $info['http_code'] . '<br>';
    print_r($info);
    echo '<br>' . $answer;
  }

  function make_url($str) {
    return '<a href="' .$str. '">' .$str. '</a>';
  }
?>