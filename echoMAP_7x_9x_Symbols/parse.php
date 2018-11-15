<? echo "#include <idc.idc>\n"; ?>

static SetName_(addr, name) {
  auto n, i;

  n=name;
  if (MakeNameEx(addr, n, SN_NOWARN) == 0) {
    i = 1;
    while (MakeNameEx(addr, n + "_" + ltoa(i, 10), SN_NOWARN) == 0) i++;
  }
}

static main(void) {


<?
  $strs = file('Unknown.bin');

  while (list ($line_num, $line) = each ($strs)) {
    $line = trim($line);

    if (preg_match('/^0x([^,]+),(.+),(.+) Code,(.+)$/i', $line, $mathces)) {
      list($dummy, $addr, $name, $type, $size) = $mathces;
      $addr = hexdec($addr);

      if ($addr < 0xA0000000) {
        if ($type == "Thumb") $addr--;
        echo("MakeUnknown($addr, $size, DOUNK_EXPAND); ");

        if ($name != '__switch$$') {
          if ($type == "Thumb") {
            echo "SetReg($addr, \"T\", 1); ";
          } else {
            echo "SetReg($addr, \"T\", 0); ";
          }

          echo "SetName_($addr, \"$name\"); ";
          echo "MakeFunction($addr, BADADDR);";
        }
        echo "\n";
      }
    }
  }

/*
  $strs = array_reverse($strs);
  while (list ($line_num, $line) = each ($strs)) {
    $line = trim($line);

    if (preg_match('/^0x([^,]+),(.+),(.+) Code,(.+)$/i', $line, $mathces)) {
      list($dummy, $addr, $name, $type, $comm) = $mathces;
      $addr = hexdec($addr);

      if ($addr < 0xA0000000) {
        if ($type == "Thumb") {
          $addr--;
        }

        echo "MakeFunction($addr, BADADDR); ";
        echo "SetFunctionCmt($addr, \"$comm\", 0);\n";
      }
    }
  }
*/
?>

}
