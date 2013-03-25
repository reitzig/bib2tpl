<?php

if ( empty($_POST) ) { ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>bib2tpl live demo</title>
    <meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
  </head>

  <body>
    <h3>Demo of bib2tpl</h3>
    <form action="" target="view" method="post">
      <table width="400">
        <tr>
          <td>Template:</td><td><select name="template">
            <option value="xhtml_simple">Simple XHTML</option>
            <option value="bibtex_simple">Simple BibTeX</option>
            <option value="dokuwiki_simple">Simple DokuWiki Markup</option>
            <option value="mediawiki_simple">Simple MediaWiki Markup</option>
            <option value="xhtml_fancy">Fancy XHTML</option>
          </select></td>
        </tr>
        <tr>
          <td>Only by author:</td><td><input name="only_author" type="text" value="" /><br />
              <small>Try <code>knuth|cormen</code></small></td>
        </tr>
        <tr>
          <td>Only types:</td><td><input name="only_entrytype" type="text" value="" /><br />
              <small>Try <code>journal</code></small></td>
        </tr>
        <tr>
          <td>Group by:</td><td><input name="group" type="text" value="" /><br />
          <small>Try <code>none</code>, <code>year</code>, <code>firstauthor</code> or <code>entrytype</code>.</small></td>
        </tr>
        <tr>
          <td>Order Groups:</td><td><select name="order_groups">
            <option value="desc">Descending</option>
            <option value="asc">Ascending</option>
          </select></td>
        </tr>
        <tr>
          <td>Sort entries by:</td><td><input name="sort_by" type="text" value="" /><br />
          <small>Try <code>firstauthor</code>, <code>title</code> or <code>DATE</code>.</small></td>
        </tr>
        <tr>
          <td>Order entries:</td><td><select name="order">
            <option value="desc">Descending</option>
            <option value="asc">Ascending</option>
          </select></td>
        </tr>
        <tr>
          <td>Language:</td><td><select name="lang">
            <option value="en">English</option>
            <option value="de">Deutsch</option>
          </select></td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr>
          <td colspan="2" align="center"><input type="submit" value="Convert live" /></td>
        </tr>
      </table>
    </form>
    <p>Find bib2tpl <a href="http://lmazy.verrech.net/bib2tpl/">here</a>.</p>

    <hr />

    <iframe name="view" width="750px" height="550px" scrolling="auto">
      Please make a selection in the form above.
    </iframe>

  </body>

</html>

<?php
}
else {
  require('bibtex_converter.safe.php');

  function sanitise($string) {
    return preg_replace(array('/\{|\}/',"/\\'e/",'/\\&/'), array('','é','&'), $string);
  }

  $parser = new BibtexConverter(array(
    'only' => array('author' => $_POST['only_author'], 'entrytype' => $_POST['only_entrytype']),
    'group' => $_POST['group'],
    'order_groups' => $_POST['order_groups'],
    'sort_by' => $_POST['sort_by'],
    'order' => $_POST['order'],
    'lang' => $_POST['lang']), 'sanitise');

  $result = $parser->convert(file_get_contents('demo.safe.bib'), file_get_contents($_POST['template'].'.safe.tpl'));

  if ( !preg_match('/^xhtml/', $_POST['template']) ) {
    $result = '<textarea cols="100" rows="100">'.$result.'</textarea>';
  }

  echo $result;
}

?>
