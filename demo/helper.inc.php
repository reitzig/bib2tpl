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

/**
 * Provides helping functions in order to keep clutter from the main file.
 *
 * @author Raphael Reitzig <code@verrech.net>
 * @version 2.0
 * @package bib2tpl
 * @license http://www.gnu.org/licenses/gpl.html GPLv3
 * @copyright © 2012, Raphael Reitzig
 */
class Helper
{

  /**
   * Copy of main class's options
   * @var array
   * @access private
   */
  private $options;

  /**
   * Constructor.
   *
   * @access public
   * @param array $options Options array with same semantics as main class.
   */
  function __construct(&$options=array())
  {
    $this->options = $options;
  }

  /**
   * Obtains a month number from the passed entry.
   *
   * @access private
   * @param array $entry An entry
   * @return string The passed entry's month number. <code>00</code> if
   *                the month could not be recognized.
   */
  private function e2mn(&$entry) {
    $month = empty($entry['month']) ? '' : $entry['month'];

    $result = '00';
    $month = strtolower($month);

    // This is gonna get ugly; other solutions?
    $pattern = '/^'.$month.'/';
    if ( preg_match('/^\d[\d]$/', $month) )
    {
      return strlen($month) == 1 ? '0'.$month : $month;
    }
    else
    {
      foreach ( $this->options['lang']['months'] as $number => $name )
      {
        if ( preg_match($pattern , $name) )
        {
          $result = $number;
          break;
        }
      }
    }

    return $result;
  }

  /**
   * Compares two group keys for the purpose of sorting.
   *
   * @access public
   * @param string $k1 group key one
   * @param string $k2 group key two
   * @return int integer (<,=,>) zero if k1 is (less than,equal,larger than) k2
   */
  function group_cmp($k1, $k2)
  {
    return  $this->options['order_groups'] !== 'desc'
          ? strcmp($k1, $k2)
          : -strcmp($k1, $k2);
  }

  /**
   * Compares two entries for the purpose of sorting.
   *
   * @access public
   * @param string $k1 entry key one
   * @param string $k2 entry key two
   * @return int integer (<,=,>) zero if entry[$k1] is
   *                     (less than,equal,larger than) entry[k2]
   */
  function entry_cmp($e1, $e2)
  {
    if ( $this->options['sort_by'] === 'DATE' )
    {
      $order = strcmp((!empty($e1['year']) ? $e1['year'] : '0000').$this->e2mn($e1),
                      (!empty($e2['year']) ? $e2['year'] : '0000').$this->e2mn($e2));
    }
    elseif ( $this->options['sort_by'] === 'author' ) {
      $order = strcmp($e1['sortauthor'], $e2['sortauthor']);
    }
    elseif ( $this->options['sort_by'] === 'firstauthor' ) {
      $order = strcmp($e1['author'][0]['sort'], $e2['author'][0]['sort']);
    }
    else
    {
      $order = strcmp((!empty($e1[$this->options['sort_by']]) ? $e1[$this->options['sort_by']] : ''),
                      (!empty($e2[$this->options['sort_by']]) ? $e2[$this->options['sort_by']] : ''));
    }

    if ( $this->options['order'] === 'desc' )
    {
      $order = -$order;
    }

    return $order;
  }

  /**
   * Counts array elements in the specified array at the specified level.
   * For depth<=1, lcount equals count.
   *
   * @access public
   * @param array $array Array to count
   * @param int $depth Counting depth. Default 1.
   * @return int Number of array elements in $array at nesting level $depth
   */
  static function lcount(&$array, $depth=1)
  {
    $sum = 0;
    $depth--;

    if ( $depth > 0 )
    {
      foreach ( $array as $elem )
      {
        $sum += is_array($elem) ? self::lcount($elem, $depth) : 0;
      }
    }
    else
    {
      foreach ( $array as $elem )
      {
        $sum += is_array($elem) ? 1 : 0;
      }
    }

    return $sum;
  }
}
?>
