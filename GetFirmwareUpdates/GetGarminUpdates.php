<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  require 'basic_protobuf.php';


  // 1. Generating the request to Garmin Express server.

  $part_numbers = file('RequestPartNumbers.txt');
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


  $info = curl_getinfo($ch);
  if ($info['http_code'] != '200') die();
  

  // 3. Parsing the response.

  $records = read_message($answer);
  $records = $records["1"];
  $update_list = array();

  foreach ($records as $record) {
    $fields = read_message($record);
    if (isset($fields[15]) && strcasecmp($fields[15], 'Firmware')) {
      $fields[6] = read_message($fields[6]);

      $update_list[$fields[2]] = array($fields[2], $fields[8], $fields[9], $fields[6][2], $fields[6][3], $fields[6][4]);
    }
  }
  uksort($update_list, "strcasecmp");


  // 5. Generating the request to WebUpdater server.

  $main_template = file_get_contents('RequestTemplate_WebUpdater.txt');
  $updatefile_template = file_get_contents('RequestUpdateFileTemplate_WebUpdater.txt');

  $update_files = '';
  foreach ($part_numbers as $part_number)
    //if (chop($part_number) != '006-B2442-00')
      $update_files .= str_replace('%part_number%', chop($part_number), $updatefile_template) . "\n";

  $request_data = str_replace('%part_number%', chop($part_numbers[0]), $main_template);
  $request_data = str_replace('%update_files%', $update_files, $request_data);


  // 6. POSTing the data to the update server.

  $ch=curl_init();

  curl_setopt($ch, CURLOPT_URL, 'http://www.garmin.com/support/WUSoftwareUpdate.jsp');
  curl_setopt($ch, CURLOPT_USERAGENT, 'Undefined agent');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);

  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $answer = curl_exec($ch);


  $info = curl_getinfo($ch);
  if ($info['http_code'] == '200' && substr($answer, 0, 5) == '<?xml') {
    // 7. Parsing the response.
    $xml = new SimpleXMLElement($answer);
    foreach ($xml as $record) {
      $partno = (string)$record->RequestedPartNumber;

      $update_list_wu[$partno][1] = (string)$record->Update->Version->BuildType;
      if (isset($record->Update->Version->VersionMajor) && isset($record->Update->Version->VersionMinor))
        $update_list_wu[$partno][2] = sprintf("%u.%02u", (int)$record->Update->Version->VersionMajor, (int)$record->Update->Version->VersionMinor);
      $update_list_wu[$partno][3] = (string)$record->Update->AdditionalInfo;
      $update_list_wu[$partno][4] = (string)$record->Update->UpdateFile->Location;
      $update_list_wu[$partno][5] = (string)$record->Update->UpdateFile->Size;
      $update_list_wu[$partno][6] = (string)$record->Update->UpdateFile->MD5Sum;
    }
  }


  // 8. Writing the results.

  $line_template = file_get_contents('LatestGarminFirmwaresLineTemplate.txt');
  $update_lines = '';
  foreach ($update_list as $item) {
    $line = $line_template;
    $line = str_replace('%model%',       $item[0], $line);
    $line = str_replace('%part_number%', $item[1], $line);
    $line = str_replace('%version%',     $item[2], $line);
    $line = str_replace('%URL%',         $item[3], $line);
    $line = str_replace('%MD5%',         $item[4], $line);
    $line = str_replace('%size%',        $item[5] >> 10, $line);

    if (isset($update_list_wu))
      $item_wu = $update_list_wu[$item[1]];
    else
      $item_wu = array('', '', '', '', '', '', '', '');

    $line = str_replace('%build_type_wu%',  $item_wu[1], $line);
    $line = str_replace('%version_wu%',     $item_wu[2], $line);
    $line = str_replace('%details_URL_wu%', $item_wu[3], $line);
    $line = str_replace('%URL_wu%',         $item_wu[4], $line);
    $line = str_replace('%size_wu%',        $item_wu[5], $line);
    $line = str_replace('%MD5_wu%',         $item_wu[6], $line);

    $line = str_replace('%visible_wu_1%', ($item[2] == $item_wu[2])? "none": "", $line);
    $line = str_replace('%visible_wu_2%', ($item[3] == $item_wu[4])? "none": "", $line);

    $update_lines .= $line;
  }

  $result = str_replace('%update_lines%', $update_lines, file_get_contents('LatestGarminFirmwaresTemplate.txt'));

  if (file_exists('../generated/LatestGarminFirmwares.html'))
    copy('../generated/LatestGarminFirmwares.html', '../generated/LatestGarminFirmwares_prev.html');


  $file = fopen('../generated/LatestGarminFirmwares.html', 'w');
  fputs($file, $result);
  fclose($file);

  //echo $result;
  //echo "<br><br>";
  require('compare.php');
?>
