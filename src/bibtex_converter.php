<?php
/*
 * By Raphael Reitzig, 2012
 * version 2.0
 * code@verrech.net
 * http://lmazy.verrech.net
 */
?>
<?php
/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>
<?php

// Use the slightly modified BibTex parser from PEAR.
require_once('lib/PEAR5.php');
require_once('lib/PEAR.php');
require_once('lib/BibTex.php');

// Some stupid functions
require_once('helper.inc.php');

/**
 * This class provides methods to parse bibtex files and convert them to
 * other text-based formats using a template language. See
 *   {@link http://lmazy.verrech.net/bib2tpl/ lmazy.verrech.net/bib2tpl}
 * for documentation.
 *
 * @author Raphael Reitzig <code@verrech.net>
 * @version 2.0
 * @package bib2tpl
 * @license http://www.gnu.org/licenses/gpl.html GPLv3
 * @copyright © 2012, Raphael Reitzig
 */
class BibtexConverter {
  /**
   * BibTex parser
   *
   * @access private
   * @var Structures_BibTex
   */
  private static $parser;

  /**
   * Options array. May contain the following pairs:
   *   only  => array([$field => $regexp], ...)
   *   group => (none|firstauthor|entrytype|$field)
   *   order_groups => (asc|desc)
   *   sort_by => (DATE|$field)
   *   order => (asc|desc)
   *   lang => xy (where lang/xy.php exists)
   * @access private
   * @var array
   */
  private $options;

  /**
   * Callback to a function that takes a string (taken from a
   * BibTeX field) and clears it up for output.
   * @access private
   * @var callback
   */
  private $sanitise;

  /**
   * Helper object with support functions.
   * @access private
   * @var Helper
   */
  private $helper;

  /**
   * Constructor.
   *
   * @access public
   * @param array $options Options array. May contain the following pairs:
   *                       - only  => array([$field => $regexp], ...)
   *                       - group => (none|year|firstauthor|entrytype|$field)
   *                       - order_groups => (asc|desc)
   *                       - sort_by => (DATE|$field)
   *                       - order => (asc|desc)
   *                       - lang  => any string $s as long as proper lang/$s.php exists
   *                       For details see documentation.
   * @param callback $sanitise Callback to a function that takes a string (taken from a
   *                           BibTeX field) and clears it up for output. Default is the
   *                           identity function.
   */
  function __construct($options=array(), $sanitise=null) {
    // Default options
    $this->options = array(
      'only'  => array(),
      'group' => 'year',
      'order_groups' => 'desc',
      'sort_by' => 'DATE',
      'order' => 'desc',
      'lang' => 'en'
    );

    // lame replacement for non-constant default parameter
    if ( !empty($sanitise) ) {
      $this->sanitise = $sanitise;
    }
    else {
      $this->sanitise = create_function('$i', 'return $i;');
    }

    // Overwrite default options
    foreach ( $this->options as $key => $value ) {
      if ( !empty($options[$key]) ) {
        $this->options[$key] = $options[$key];
      }
    }

    /* Load translations.
     * We assume that the english language file is always there.
     */
    if ( is_readable(dirname(__FILE__).'/lang/'.$this->options['lang'].'.php') ) {
      require('lang/'.$this->options['lang'].'.php');
    }
    else {
      require('lang/en.php');
    }
    $this->options['lang'] = $translations;

    $this->helper = new Helper($this->options);
  }

  /**
   * Parses the specified BibTeX string into an array with entries of the form
   * $entrykey => $entry. The result can be used with BibtexConverter::convert.
   *
   * @access public
   * @param string $bibtex BibTeX code
   * @return array Array with data from passed BibTeX
   */
  static function parse(&$bibtex) {
    if ( !isset(self::$parser) ) {
      self::$parser = new Structures_BibTex(array('removeCurlyBraces' => false));
    }

    self::$parser->loadString($bibtex);
    $stat = self::$parser->parse();

    if ( PEAR::isError($stat) ) {
      return $stat;
    }

    $parsed = self::$parser->data;
    $result = array();
    foreach ( $parsed as &$entry ) {
      $result[$entry['entrykey']] = $entry;
    }

    return $result;
  }

  /**
   * Parses the given BibTeX string and applies its data to the passed template string.
   * If $bibtex is an array (which has to be parsed by BibtexConverter::parse)
   * parsing is skipped.
   *
   * @access public
   * @param string|array $bibtex BibTeX code or parsed array
   * @param string       $template template code
   * @param array  $replacementKeys An array with entries of the form $entrykey => $newKey.
   *                                If an entrykey occurrs here, it will be replaced by
   *                                its correspoding newKey in the output.
   * @return string|PEAR_Error Result string or PEAR_Error on failure
   */
  function convert($bibtex, &$template, &$replacementKeys=array()) {
    // If there are no grouping tags, disable grouping.
    if ( preg_match('/@\{group@/s', $template) + preg_match('/@\}group@/s', $template) < 2 ) {
      $groupingDisabled = $this->options['group'];
      $this->options['group'] = 'none';
    }

    // If grouping is off, remove grouping tags.
    if ( $this->options['group'] === 'none' ) {
      $template = preg_replace(array('/@\{group@/s', '/@\}group@/s'), '', $template);
    }

    // Parse if necessary
    if ( is_array($bibtex) ) {
      $data = $bibtex;
    }
    else {
      $data = self::parse($bibtex);
    }

    $data   = $this->filter($data, $replacementKeys);
    $data   = $this->group($data);
    $data   = $this->sort($data);
    $result = $this->translate($data, $template);

    /* If grouping was disabled because of the template, restore the former
     * setting for future calls. */
    if ( !empty($groupingDisabled) ) {
      $this->options['group'] = $groupingDisabled;
    }

    return $result;
  }

  /**
   * This function filters data from the specified array that should
   * not be shown. Filter criteria are specified at object creation.
   *
   * Furthermore, entries whose entrytype is not translated in the specified
   * language file are put into a distinct group.
   *
   * @access private
   * @param array data Unfiltered data, that is array of entries
   * @param replacementKeys An array with entries of the form $entrykey => $newKey.
   *                        If an entrykey occurrs here, it will be replaced by
   *                        its correspoding newKey in the output.
   * @return array Filtered data as array of entries
   */
  private function filter(&$data, &$replacementKeys=array()) {
    $result = array();

    $id = 0;
    foreach ( $data as $entry ) {
      // Some additions/corrections
      if ( empty($this->options['lang']['entrytypes'][$entry['entrytype']]) ) {
        $entry['entrytype'] = $this->options['lang']['entrytypes']['unknown'];
      }

      // Check wether this entry should be included
      $keep = true;
      foreach ( $this->options['only'] as $field => $regexp ) {
        if ( !empty($entry[$field]) ) {
          $val =   $field === 'author'
                 ? $entry['niceauthor']
                 : $entry[$field];

          $keep = $keep && preg_match('/'.$regexp.'/i', $val);
        }
        else {
          /* If the considered field does not even exist, consider this a fail.
           * That enables to use $field => '.*' as existence check. */
          $keep = false;
        }
      }

      if ( $keep === true ) {
        if ( !empty($replacementKeys[$entry['entrykey']]) ) {
          $entry['entrykey'] = $replacementKeys[$entry['entrykey']];
        }

        $result[] = $entry;
      }
    }

    return $result;
  }

  /**
   * This function groups the passed entries according to the criteria
   * passed at object creation.
   *
   * @access private
   * @param array data An array of entries
   * @return array An array of arrays of entries
   */
  private function group(&$data) {
    $result = array();

    if ( $this->options['group'] !== 'none' ) {
      foreach ( $data as $entry ) {
        if ( !empty($entry[$this->options['group']]) || $this->options['group'] === 'firstauthor' ) {
          if ( $this->options['group'] === 'firstauthor' ) {
            $target = $entry['author'][0]['nice'];
          }
          elseif ( $this->options['group'] === 'author' ) {
            $target = $entry['niceauthor'];
          }
          else {
            $target =  $entry[$this->options['group']];
          }
        }
        else {
          $target = $this->options['lang']['rest'];
        }

        if ( empty($result[$target]) ) {
          $result[$target] = array();
        }

        $result[$target][] = $entry;
      }
    }
    else {
      $result[$this->options['lang']['all']] = $data;
    }

    return $result;
  }

  /**
   * This function sorts the passed group of entries and the individual
   * groups if there are any.
   *
   * @access private
   * @param array data An array of arrays of entries
   * @return array A sorted array of sorted arrays of entries
   */
  private function sort($data) {
    // Sort groups if there are any
    if ( $this->options['group'] !== 'none' ) {
      uksort($data, array($this->helper, 'group_cmp'));
    }

    // Sort individual groups
    foreach ( $data as &$group ) {
      uasort($group, array($this->helper, 'entry_cmp'));
    }

    return $data;
  }

  /**
   * This function inserts the specified data into the specified template.
   * For template syntax see class documentation or examples.
   *
   * @access private
   * @param array data An array of arrays of entries
   * @param string template The used template
   * @return string The data represented in terms of the template
   */
  private function translate(&$data, &$template) {
    $result = $template;

    // Replace global values
    $result = preg_replace(array('/@globalcount@/', '/@globalgroupcount@/'),
                           array(Helper::lcount($data, 2), count($data)),
                           $result);

    if ( $this->options['group'] !== 'none' ) {
      $pattern = '/@\{group@(.*?)@\}group@/s';

      // Extract group templates
      $group_tpl = array();
      preg_match($pattern, $result, $group_tpl);

      // For all occurrences of an group template
      while ( !empty($group_tpl) ) {
        // Translate all groups
        $groups = '';
        $id = 0;
        foreach ( $data as $groupkey => $group ) {
          $groups .= $this->translate_group($groupkey, $id++, $group, $group_tpl[1]);
        }

        $result = preg_replace($pattern, $groups, $result, 1);
        preg_match($pattern, $result, $group_tpl);
      }

      return $result;
    }
    else {
      $groups = '';
      foreach ( $data as $groupkey => $group ) { // loop will only be run once
        $groups .= $this->translate_group($groupkey, 0, $group, $template);
      }
      return $groups;
    }
  }

  /**
   * This function translates one entry group
   *
   * @access private
   * @param string key The rendered group's key
   * @param int id A unique ID for this group
   * @param array data Array of entries in this group
   * @param string template The group part of the template
   * @return string String representing the passed group wrt template
   */
  private function translate_group($key, $id, &$data, $template) {
    $result = $template;

    // Replace group values
    if ( $this->options['group'] === 'entrytype' ) {
      $key = $this->options['lang']['entrytypes'][$key];
    }
    $result = preg_replace(array('/@groupkey@/', '/@groupid@/', '/@groupcount@/'),
                           array($key, $id, count($data)),
                           $result);

    $pattern = '/@\{entry@(.*?)@\}entry@/s';

    // Extract entry templates
    $entry_tpl = array();
    preg_match($pattern, $result, $entry_tpl);

    // For all occurrences of an entry template
    while ( !empty($entry_tpl) ) {
      // Translate all entries
      $entries = '';
      foreach ( $data as $entry ) {
        $entries .= $this->translate_entry($entry, $entry_tpl[1]);
      }

      $result = preg_replace($pattern, $entries, $result, 1);
      preg_match($pattern, $result, $entry_tpl);
    }

    return $result;
  }

  /**
   * This function translates one entry
   *
   * @access private
   * @param array entry Array of fields
   * @param string template The entry part of the template
   * @return string String representing the passed entry wrt template
   */
  private function translate_entry(&$entry, $template) {
    $result = $template;

    // Resolve all conditions
    $result = $this->resolve_conditions($entry, $result);

    // Replace all possible unconditional fields
    $patterns = array();
    $replacements = array();

    foreach ( $entry as $key => $value ) {
      if ( $key === 'author' ) {
        $value = $entry['niceauthor'];
      }

      $patterns []= '/@'.$key.'@/';

      if ( $key === 'bibtex' ) {
        $replacements []= $value;
      }
      else {
        $replacements []= call_user_func($this->sanitise, $value);
      }
    }

    return preg_replace($patterns, $replacements, $result);
  }

  /**
   * This function eliminates conditions in template parts.
   *
   * @access private
   * @param array entry Entry with respect to which conditions are to be
   *                    solved.
   * @param string template The entry part of the template.
   * @return string Template string without conditions.
   */
  private function resolve_conditions(&$entry, &$string) {
    $pattern = '/@\?(\w+)(?:(<=|>=|==|!=|~)(.*?))?@(.*?)(?:@:\1@(.*?))?@;\1@/s';
    /* There are two possibilities for mode: existential or value check
     * Then, there can be an else part or not.
     *          Existential       Value Check      RegExp
     * Group 1  field             field            \w+
     * Group 2  then              operator         .*?  /  <=|>=|==|!=|~
     * Group 3  [else]            value            .*?
     * Group 4   ---              then             .*?
     * Group 5   ---              [else]           .*?
     */

    $match = array();

    /* Would like to do
     *    preg_match_all($pattern, $string, $matches);
     * to get all matches at once but that results in Segmentation
     * fault. Therefore iteratively:
     */
    while ( preg_match($pattern, $string, $match) )
    {
      $resolved = '';

      $evalcond = !empty($entry[$match[1]]);
      $then = count($match) > 3 ? 4 : 2;
      $else = count($match) > 3 ? 5 : 3;

      if ( $evalcond && count($match) > 3 ) {
        if ( $match[2] === '==' ) {
          $evalcond = $entry[$match[1]] === $match[3];
        }
        elseif ( $match[2] === '!=' ) {
          $evalcond = $entry[$match[1]] !== $match[3];
        }
        elseif ( $match[2] === '<=' ) {
          $evalcond =    is_numeric($entry[$match[1]])
                      && is_numeric($match[3])
                      && (int)$entry[$match[1]] <= (int)$match[3];
        }
        elseif ( $match[2] === '>=' ) {
          $evalcond =    is_numeric($entry[$match[1]])
                      && is_numeric($match[3])
                      && (int)$entry[$match[1]] >= (int)$match[3];
        }
        elseif ( $match[2] === '~' ) {
          $evalcond = preg_match('/'.$match[3].'/', $entry[$match[1]]) > 0;
        }
      }

      if ( $evalcond )
      {
        $resolved = $match[$then];
      }
      elseif ( !empty($match[$else]) )
      {
        $resolved = $match[$else];
      }

      // Recurse to cope with nested conditions
      $resolved = $this->resolve_conditions($entry, $resolved);

      $string = str_replace($match[0], $resolved, $string);
    }

    return $string;
  }
}

?>
