<?php
  require('../src/bibtex_converter.php');

  $conv = new BibtexConverter(array(
      'only'  => array(),
      'group' => 'entrytype',
      'order' => 'asc',
      'lang' => 'de'
    ));

  if ( empty($argv[1]) || empty($argv[2]) ) {
    echo 'Usage: php test.php bibtex.bib template.tpl';
  }
  else {
    echo $conv->convert(file_get_contents($argv[1]), file_get_contents($argv[2]));
  }
?>
