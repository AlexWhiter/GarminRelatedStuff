<?php 
  function read_varint(&$str) {
    $res = 0;
    $multiplier = 1;

    do {
      if ($str === '') break;

      $val = ord(substr($str, 0, 1)); $str = substr($str, 1);

      $res += ($val & 0x7F) * $multiplier;
      $multiplier <<= 7;
    } while ($val & 0x80);

    return $res;
  }

  function read_message($str) {
    $res = array();

    while ($str) {
      $id = read_varint($str);
      $wiretype = $id & 7;
      $id >>= 3;

      if ($wiretype == 0) {
        // Varint
        $val = read_varint($str);
      } elseif ($wiretype == 1) {
        // 64-bit. Skip it.
        $str = substr($str, 8);
      } elseif ($wiretype == 2) {
        $len = read_varint($str);
        $val = substr($str, 0, $len);
        $str = substr($str, $len);
      } elseif ($wiretype == 5) {
        // int32
        list($val) = unpack("V", $str);
        $str = substr($str, 4);
      } else {
        die("Unsupported wiretype $wiretype\n");
      }

      if (isset($res[$id])) {
        if (!is_array($res[$id]))
          $res[$id] = array($res[$id]);
        array_push($res[$id], $val);
      } else
        $res[$id] = $val;
    }

    if ((count($res) == 1) && is_array($res[1]))
      $res = $res[1];

    return $res;
  }


  function pack_varint($val) {
    $res = '';
    while ($val) {
      $mod = $val % 0x80;
      $val >>= 7;

      if ($val) $mod |= 0x80;

      $res .= pack('c', $mod);
    }
    return $res;
  }

  function pack_int32($val) {
    return pack('V', $val);
  }

  function pack_string($str) {
    return pack_varint(strlen($str)) . $str;
  }


  function write_varint($id, $val) {
    $id <<= 3;
    return pack_varint($id) . pack_varint($val);
  }

  function write_int32($id, $val) {
    $id <<= 3;
    $id |= 5;
    return pack_varint($id) . pack_int32($val);
  }

  function write_string($id, $val) {
    $id <<= 3;
    $id |= 2;
    return pack_varint($id) . pack_string($val);
  }
?>
