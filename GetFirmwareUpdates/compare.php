<?php
  require("htmldiff/html_diff.php");

  $html1 = file('../generated/LatestGarminFirmwares_prev.html'); array_shift($html1); $html1 = join('', $html1);
  $html2 = file('../generated/LatestGarminFirmwares.html');      array_shift($html2); $html2 = join('', $html2);

  echo html_diff($html1, $html2);
?>
