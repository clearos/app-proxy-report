<?php

/**
 * Proxy report class.
 *
 * @category   Apps
 * @package    Proxy_Report
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/proxy_report/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\proxy_report;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('proxy_report');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Framework
//----------

use \clearos\framework\Config as Config;

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\web_proxy\Squid as Squid;
use \clearos\apps\reports_database\Report as Report;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('web_proxy/Squid');
clearos_load_library('reports_database/Report');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Proxy report class.
 *
 * @category   Apps
 * @package    Proxy_Report
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/proxy_report/
 */

class Proxy_Report extends Report
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // hard coded date statements are for use by Mysql, changing them will have undesirable results
    // the "visible" dates may be modified here
    const DATE_DAY = 'Y M d';
    const DATE_DAY_SHORT = 'd';
    const DATE_MON = 'M Y';
    const DATE_MON_SHORT = 'M y';
    const DATE_TIME = 'H:i:s';
    const GROUPBY_NONE = 0;
    const GROUPBY_IP = 1;
    const GROUPBY_USER = 2;
    const GROUPBY_HOST = 3;
    const GROUPBY_STATUS = 4;
    const CONSTANT_FILTER_CODE_ALL = -1;
    const CONSTANT_FILTER_CODE_ALL_BANNED = -2;
    const CONSTANT_FILTER_CODE_ALL_EXCEPTIONS = -3;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $usermode = NULL;
    protected $db = NULL;
    protected $GeoIP = NULL;
    protected $chartcolors = array( "4e627c", "ffdc30", "d2d23d", "3b5743", "ffc262");
    protected $httpcodes = array();
    protected $filtercodes = array();
    protected $title = '';
    protected $params = array();
    protected $month_names = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Proxy Report constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

//        $this->usermode = (self::GetDefaultGrouping() == ProxyReport::GROUPBY_USER);

        $this->month_names = array(
            '1' => lang('base_month_january'),
            '2' => lang('base_month_february'),
            '3' => lang('base_month_march'),
            '4' => lang('base_month_april'),
            '5' => lang('base_month_may'),
            '6' => lang('base_month_june'),
            '7' => lang('base_month_july'),
            '8' => lang('base_month_august'),
            '9' => lang('base_month_september'),
            '10' => lang('base_month_october'),
            '11' => lang('base_month_november'),
            '12' => lang('base_month_december')
        );

        $this->filtercodes = array(
            '1' => lang('proxy_report_filter_code_1'),
            '100|101' => lang('proxy_report_filter_code_100'),
            '102' => lang('proxy_report_filter_code_102'),
            '200' => lang('proxy_report_filter_code_200'),
            '300|301|400|401' => lang('proxy_report_filter_code_300'),
            '402|403' => lang('proxy_report_filter_code_402'),
            '500' => lang('proxy_report_filter_code_500'),
            '501|503|504' => lang('proxy_report_filter_code_501'),
            '502' => lang('proxy_report_filter_code_502'),
            '505' => lang('proxy_report_filter_code_505'),
            '506' => lang('proxy_report_filter_code_506'),
            '507' => lang('proxy_report_filter_code_507'),
            '508' => lang('proxy_report_filter_code_508'),
            '509' => lang('proxy_report_filter_code_509'),
            '600' => lang('proxy_report_filter_code_600'),
            '601' => lang('proxy_report_filter_code_601'),
            '602|612' => lang('proxy_report_filter_code_602'),
            '603|613' => lang('proxy_report_filter_code_603'),
            '604|605' => lang('proxy_report_filter_code_604'),
            '606' => lang('proxy_report_filter_code_606'),
            '607' => lang('proxy_report_filter_code_607'),
            '608' => lang('proxy_report_filter_code_608'),
            '609' => lang('proxy_report_filter_code_609'),
            '610' => lang('proxy_report_filter_code_610'),
            '700' => lang('proxy_report_filter_code_700'),
            '701' => lang('proxy_report_filter_code_701'),
            '800' => lang('proxy_report_filter_code_800'),
            '900' => lang('proxy_report_filter_code_900'),
            '1000' => lang('proxy_report_filter_code_1000'),
            '1100' => lang('proxy_report_filter_code_1100')
        );

        $this->httpcodes = array(
            '100' => lang('proxy_report_code_100'),
            '101' => lang('proxy_report_code_101'),
            '200' => lang('proxy_report_code_200'),
            '201' => lang('proxy_report_code_201'),
            '202' => lang('proxy_report_code_202'),
            '203' => lang('proxy_report_code_203'),
            '204' => lang('proxy_report_code_204'),
            '205' => lang('proxy_report_code_205'),
            '206' => lang('proxy_report_code_206'),
            '300' => lang('proxy_report_code_300'),
            '301' => lang('proxy_report_code_301'),
            '302' => lang('proxy_report_code_302'),
            '303' => lang('proxy_report_code_303'),
            '304' => lang('proxy_report_code_304'),
            '305' => lang('proxy_report_code_305'),
            '306' => lang('proxy_report_code_306'),
            '307' => lang('proxy_report_code_307'),
            '400' => lang('proxy_report_code_400'),
            '401' => lang('proxy_report_code_401'),
            '403' => lang('proxy_report_code_403'),
            '404' => lang('proxy_report_code_404'),
            '405' => lang('proxy_report_code_405'),
            '406' => lang('proxy_report_code_406'),
            '407' => lang('proxy_report_code_407'),
            '408' => lang('proxy_report_code_408'),
            '409' => lang('proxy_report_code_409'),
            '410' => lang('proxy_report_code_410'),
            '411' => lang('proxy_report_code_411'),
            '412' => lang('proxy_report_code_412'),
            '413' => lang('proxy_report_code_413'),
            '414' => lang('proxy_report_code_414'),
            '415' => lang('proxy_report_code_415'),
            '416' => lang('proxy_report_code_416'),
            '417' => lang('proxy_report_code_417'),
            '500' => lang('proxy_report_code_500'),
            '501' => lang('proxy_report_code_501'),
            '502' => lang('proxy_report_code_502'),
            '503' => lang('proxy_report_code_503'),
            '504' => lang('proxy_report_code_504'),
            '505' => lang('proxy_report_code_505')
        );

        parent::__construct();
    }

    /**
     * Returns dashboard summary data.
     *
     * @return array dashboard summary data
     * @throws Engine_Exception
     */

    public function get_dashboard_data($date_range = 'all', $records = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Specify SQL
        //------------

        $sql['select'] = 'SUBSTRING(timestamp,1,10) AS days , COUNT(request) AS hits , SUM(bytes)/1024/1024 AS size';
        $sql['from'] = 'proxy';
        $sql['where'] = 'domain IS NOT NULL';
        $sql['group_by'] = 'days';
        $sql['order_by'] = 'days DESC';

        $entries = $this->_run_query($sql, $date_range, $records);

        // Get report data
        //----------------

        $report_data = array();

        foreach ($entries as $entry) {
            $report_data[$entry['days']]['hits'] = (int) $entry['hits'];
            $report_data[$entry['days']]['size'] = (int) $entry['size'];
        }

        return $report_data;
    }

    /**
     * Returns domain summary data.
     *
     * @return array domain summary data
     * @throws Engine_Exception
     */

    public function get_domain_data($date_range = 'all', $records = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $sql['select'] = 'domain, COUNT(request) AS hits, SUM(bytes)/1024/1024 AS size';
        $sql['from'] = 'proxy';
        $sql['where'] = 'domain IS NOT NULL';
        $sql['group_by'] = 'domain';
        $sql['order_by'] = 'hits DESC';

        $entries = $this->_run_query($sql, $date_range, $records);

        // Format report data
        //-------------------

        $report_data = array();

        foreach ($entries as $entry) {
            $report_data[$entry['domain']]['hits'] = (int) $entry['hits'];
            $report_data[$entry['domain']]['size'] = (int) $entry['size'];
        }

        return $report_data;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns default grouping.
     */

    static public function get_default_grouping()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $squid = new Squid();
            $isuserbased = $squid->GetAuthenticationState();
        } catch (Exception $e) {
            $isuserbased = FALSE;
        }

        if ($isuserbased)
            $groupby = ProxyReport::GROUPBY_USER;
        else
            $groupby = ProxyReport::GROUPBY_IP;

        return $groupby;
    }

    /**
     * Displays proxy report.
     *
     * @param array $showactions
     * @throws Engine_Exception (during development only)
     *
     * @return string HTML formatted report
     */

    public function get_full_report($showactions)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($showactions))
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARSE_ERROR.': '.__METHOD__.'('.__LINE__.')');

        $type = (isset($showactions['type'])) ? $showactions['type'] : 'default';

        if (array_key_exists($type,$showactions)) {
            $this->_SetParams($showactions[$type],$type);
        } else {
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARSE_ERROR.': '.__METHOD__.'('.__LINE__.')');
        }

        $types = $this->GetReportTypes();
        $reporttitle =  $types[$type];

        $title = array();
        $q = array(); // queue the reports

        switch ($type) {

        case 'summary':
            $q[] = 'SummaryReport';
            $title[] = str_replace($types[$type], REPORT_LANG_SUMMARY, $reporttitle);
            break;

        case 'useripsummary':
            $q[] = 'UserIpSummaryReport';
            $title[] = $reporttitle;
            break;

        case 'details':
            $q[] = 'UserIpDetailsReport';
            $title[] = $reporttitle;
            break;

        case 'domains':
            $q[] = 'DomainSummaryReport';
            $title[] = $reporttitle;
            break;

        case 'search':
            $q[] = 'Search';
            $title[] = $reporttitle;
            break;

        default:
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.': '.REPORT_LANG_TYPE.': '.__METHOD__.'('.__LINE__.')');
        }

        $report = '';

        while (($func = array_pop($q)) != NULL) {
            $this->title = array_pop($title);
            $func = array($this,$func);

            if (is_callable($func)) {
                try {
                    $qoutput = call_user_func($func);
                } catch (Exception $e) {
                    if (empty($this->params['date'])) {
                        $period = (isset($this->params['period'])) ? intval($this->params['period']) : 1;

                        switch ($period) {

                        case 2:
                            $date = date("Y");
                            break;

                        case 1:
                            $date = date("Y-m");
                            break;

                        default:
                            $date = date("Y-m-d");
                        }

                        $date = $this->_ParseDate($date); //array($range,$text,$dateparts);
                    }

                    if (strpos(clearos_exception_message($e),REPORT_LANG_NO_REPORT) !== FALSE) {
                        ob_start();
                        WebDialogInfo("{$this->title} - " . REPORT_LANG_NO_REPORT . " ({$date[1]})");
                        $qoutput = ob_get_clean();
                    } else {
                        throw $e;
                    }
                }
            } else {
                throw new Engine_Exception(end($func)." - ".LOCALE_LANG_ERRMSG_WEIRD.': '.__METHOD__.'('.__LINE__.')',COMMON_ERROR);
            }

            $report .= $qoutput;
        }

        echo $report;
    }

    /**
     * Parses Webform parameters
     *
     * @param array $userparams
     * @param string $type report type
     */

    private function _set_params($userparams, $type)
    {
        clearos_profile(__METHOD__, __LINE__);

        $params = array(
                      'date' => NULL,
                      'filter' => NULL,
                      'groupby' => self::GetDefaultGrouping(),
                      'limit' => 30,
                      'period' => 0,
                      'lastperiod' => 0,
                      'showlegend' => FALSE,
                      'start' => 0
                  );

        if (! is_array($userparams))
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_INVALID_TYPE.': $userparams :'.__METHOD__.' ('.__LINE__.')');

        foreach ($userparams as $key => $value) {
            switch ($key) {

            case 'date':

                if (! empty($value)) {
                    $params[$key] = $this->_ParseDate($value);
                }

                break;

            case 'daterange':

                if (is_array($value)) {
                    foreach ($value as $subkey => $subval) {
                        list(,,$params[$key][$subkey],$time) = $this->_ParseDate($subval);
                        $params[$key][$subkey]['time'] = $time;
                    }
                } else {
                    throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": $key :".__METHOD__.'('.__LINE__.')');
                }

                break;

            case 'md5':

                if (ctype_xdigit($value)) {
                    $params[$key] = $value;
                }

                break;

            case 'filter':
                $prefilter = $value;

                if (is_array($prefilter)) {
                    $validfields = array('request', 'domain', 'client', 'ip', 'rfc931', 'hostname');
                    $filter = array('html' => '', 'title' => array(), 'sql' => array());

                    foreach ($prefilter as $field => $value) {
                        if (empty($value))
                            continue;

                        if (in_array($field, $validfields)) {

                            // mysql_real_escape_string requires a database connection
                            if (! is_object($this->db))
                                $this->db = $this->_connect();

                            $value = mysql_real_escape_string($value);

                            $filter['title'][$field] = $value;

                            if ($field == 'client') {
                                $filter['sql'][$field] = sprintf("%u",ip2long($value));
                            } elseif ($field == 'request') {
                                $filter['sql'][$field] = '%'.$value.'%';
                            } else {
                                $filter['sql'][$field] = $value;
                            }

                            $filter['html'] .= "\n".'<input type="hidden" name="'.$type.'['.$field.']" value="'.$filter['sql'][$field].'">';
                        }
                    }

                    $params[$key] = $filter;
                }

                break;

            case 'groupby':

            case 'limit':

            case 'period':

            case 'lastperiod':

            case 'showlegend':

            case 'start':
                $params[$key] = intval($value);
                break;

            case 'status':

                if (array_key_exists($value, $this->_GetHttpCodeHash())) {
                    $params[$key] = $value;
                    break;
                }

            case 'filter_code':

                if (array_key_exists($value, $this->_GetFilterCodeHash())) {
                    $params[$key] = $value;
                    break;
                }

            default:
                throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": $key :".__METHOD__.'('.__LINE__.')');
            }
        }

        $this->params = $params;

    }

    /**
     * Returns User Ip Summary Report
     *
     * @param array $params
     *
     * @return string
     */

    public function user_ip_summary_report($params = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $date = NULL;
        $groupby = self::GetDefaultGrouping();
        $limit = 30;
        $period = 0;
        $lastperiod = 0;
        $showlegend = FALSE;
        $start = 0;

        extract($this->params, EXTR_IF_EXISTS);

        if (!is_null($params))
            extract($params, EXTR_IF_EXISTS);

        if ($period != $lastperiod)
            $start = 0;

        $params = compact('date', 'groupby' . 'limit', 'period', 'showlegend', 'start');

        if (empty($date)) {
            switch ($period) {

            case 1:
                $date = date("Y");
                break;

            case 2:
                $date = date("Y-m");
                break;

            case 3:
                $date = date("Y-m-d", time() - 86400);
                break;

            case 4:
                $date = date("Y-m-d");
                break;

            default:
                $date = NULL;
            }

            $params = compact('date', 'groupby' . 'limit', 'period', 'showlegend', 'start');
            if (isset($date))
                $date = $this->_ParseDate($date);
        }

        list($date,$title,$dateparts,) = $date;

        $groups = array(
                      ProxyReport::GROUPBY_IP => array('ip', 'rfc931'),
                      ProxyReport::GROUPBY_USER => array('rfc931', 'ip'),
                      ProxyReport::GROUPBY_HOST => array('hostname', 'rfc931')
                  );

        if ($groupby == ProxyReport::GROUPBY_STATUS)
            $groupby = $this->GetDefaultGrouping();

        $group = $groups[$groupby];
        $charttype ='stacked bar';

        // if (! $this->usermode) {
            array_pop($group);
            $charttype ='bar';
        // }

        $sql = $this->_build_select_query(array(
                                            'select' => array_merge($group,array('hits','mb')),
                                            'date' => $date,
                                            'groupby' => $group,
                                            'orderby' => array('hits'),
                                            'sort' => 'DESC')
                                       );
        $data = $this->_query($sql);

        if (! empty($data)) {
            // create chart
            $data->SetGroup($group);
            $data->SetLimit($start,$limit);
            $data->SetShowChartLegend($showlegend);

            try {
                $chart = $data->GetChart($charttype);
            } catch (Exception $e) {
                throw $e;
            }

            // set format for rows
            // TODO: class changed to nosort... until sorting is fixed.
            // if ($this->usermode) {
            //    $headerformat = '<tr><th class="nosort" align="left">%s</th><th class="nosort" align="left">%s</th><th class="nosort" align="right">%s</th><th class="nosort" align="right">%s</th></tr>';
            //    $legendformat = '<td align="left" nowrap="nowrap">%s</td><td align="left">%s</td><td align="right">%s</td><td align="right">%s</td>';
            // } else {
                $headerformat = '<tr><th class="nosort" align="left">%s</th><th class="nosort" align="right">%s</th><th class="nosort" align="right">%s</th></tr>';
                $legendformat = '<td align="left" nowrap="nowrap">%s</td><td align="right">%s</td><td align="right">%s</td>';
            //}

            // retrieve legend data
            $header = $data->GetRowHeader();
            $cols = count($header);
            $legenddata = $data->GetLegendData();
            $totals = $data->GetTotals();
            $colorize = '<font color="%s"><b>%s</b></font><br />';
            $colorcount = count($this->chartcolors);
            $chartkeys = $data->GetChartKeys();
            $keyfield = $group[0];
            $mb = FALSE;

            if (($hits = array_search(PROXYREPORT_LANG_HITS,$header)) !== FALSE) {
                $hits--;
                $mb = array_search(LOCALE_LANG_MEGABYTES,$header);
                $mb--;
            }

            // build legend rows
            foreach ($legenddata as $key => $rows) {
                $legendrow = array();
                $totalhits = 0;
                $totalmb = 0;
                $sort = array();
                foreach ($rows as $row) {
                    // if ($this->usermode) {
                    //    $color = $this->chartcolors[array_search($row[0],$chartkeys) % $colorcount];
                    // } else {
                        $color = $this->chartcolors[0];
                    // }

                    foreach ($row as $fieldkey =>$field) {
                    /*    if ($this->usermode) {
                            switch ($fieldkey) {

                            case $hits:
                                $totalhits += $field;
                                break;

                            case $mb:
                                $totalmb += $field;
                                $field = sprintf('%01.3f',$field);
                                break;

                            default:

                                if (isset($sort[$fieldkey])) {
                                    $sort[$fieldkey][] = $field;
                                } else {
                                    $sort[$fieldkey] = array($field);
                                }
                            }
                        } else {
                    */
                            if ($fieldkey === $mb) {
                                $field = sprintf('%01.3f',$field);
                            }
                    //    }

                        if (isset($legendrow[$fieldkey])) {
                            $legendrow[$fieldkey] .= sprintf($colorize,$color,$field);
                        } else {
                            $legendrow[$fieldkey] = sprintf($colorize,$color,$field);
                        }
                    }
                }

                /*
                if ($this->usermode) {
                    foreach (array_keys($sort) as $fieldkey) {
                        $field = $sort[$fieldkey];
                        sort($field);
                        $field = array_shift($field);
                        $legendrow[$fieldkey] = sprintf('<!--%s-->',$field).$legendrow[$fieldkey];
                    }

                    $legendrow[$hits] = sprintf('<!--%010s-->',$totalhits).$legendrow[$hits].$totalhits;
                    $legendrow[$mb] = sprintf('<!--%010s-->',$totalmb).$legendrow[$mb].sprintf("%01.3f",$totalmb);
                }
                */

                if (($keyfield == 'hostname')
                        || ($keyfield == 'ip')) {
                    if (ip2long($key) !== FALSE) {
                        $keyfield = 'client';
                    }
                }

                // array_unshift($legendrow, "<!--$key-->" . WebButton('details['.$keyfield.']',$key,''));
                array_unshift($legendrow, "<!--$key-->" . $key); // TODO
                $legend[] = vsprintf($legendformat,$legendrow);
            }

            //build legend header
            $end = $start + count($legend);
            $row = array();
            $row[] = '<b>'.REPORT_LANG_RESULTS.': '.($start + 1).' - '.$end.' / '.$totals['rows'];
            $row[] = '<p align="right"><b>'.PROXYREPORT_LANG_TOTAL.': &#160;&#160;&#160;'.$totals['hits'].' '.PROXYREPORT_LANG_HITS.' &#160;&#160;&#160;'.sprintf("%01.3f",$totals['mb']).' '.LOCALE_LANG_MEGABYTES.'</b>';

            $legendheader = implode('|',$row);

            // create the legend
            $rows = '
                    <tr><td colspan="'.$cols.'" align="center">
                    <table class="sortable" id="UserIpSummaryReport" width="100%">
                    '.vsprintf($headerformat,$header)."\n".
                    '<tr valign="top">'.implode("</tr>\n<tr valign=\"top\">",$legend).'</tr>
                    </table>
                    </td></tr>
                    ';
        }

        // build report options
        $groupoptions = array(
                            ProxyReport::GROUPBY_IP => NETWORK_LANG_IP,
                            ProxyReport::GROUPBY_USER => LOCALE_LANG_USERNAME,
                            ProxyReport::GROUPBY_HOST => NETWORK_LANG_HOSTNAME
                        );

        $periodoptions = array(
            LOCALE_LANG_ALL,
            PROXYREPORT_LANG_THIS_YEAR,
            PROXYREPORT_LANG_THIS_MONTH,
            PROXYREPORT_LANG_YESTERDAY,
            PROXYREPORT_LANG_TODAY
        );

        $reportoptions = "
            <tr>
                <td nowrap='nowrap' align='right'>" .  REPORT_LANG_REPORT_PERIOD . "</td>
                <td>" . WebDropDownHash('period', $period, $periodoptions) . "</td>
                <td>&#160; </td>
            </tr>
            <tr>
                <td nowrap='nowrap' align='right'>" .  PROXYREPORT_LANG_GROUPBY . "</td>
                <td>" . WebDropDownHash('groupby', $groupby, $groupoptions, '100') . "</td>
                <td>&#160; </td>
            </tr>
            <tr>
                <td><input type='hidden' name='type' value='useripsummary'></td>
                <td>" . WebButtonGenerate('') . "</td>
                <td>&#160; </td>
            </tr>
        ";

        if (empty($chart)) {
            $chart = "<span align='center'><br><br>" . REPORT_LANG_NO_STATS . "<br><br></span>";
            $legend = "";
        } else {
            $legend = WebChartLegend($title, $rows, $legendheader, '600');
        }

        // create Report
        ob_start();
        WebFormOpen($_SERVER['PHP_SELF']);
        WebTableOpen($this->title,'100%');
        echo "
            <tr>
                <td align='center'>
                    <table border='0' cellspacing='3' cellpadding='0' align='center'><tr><td>
                    $reportoptions
                    </td></tr></table>
                </td>
            </tr>
            <tr>
                <td align='center'>$chart</td>
            </tr>
            <tr>
                <td align='center'>$legend</td>
            </tr>
        ";
        WebTableClose('100%');
        WebFormClose();

        $report = ob_get_clean();

        return $report;
    }

    /**
     * Returns summary data.
     *
     * @param array $params
     *
     * @return ProxyReportDataObject
     */

    public function get_summary_data($params)
    {
        clearos_profile(__METHOD__, __LINE__);

/*
            $params = compact('date','filter','groupby'.'limit','period','showlegend','start');
       $params = compact('date', 'filter', 'limit', 'period', 'start');
*/

        $date = NULL;
        $filter = NULL;
        $limit = 30;

        $period = 0;
        $select = array('days', 'hits', 'mb');
        $groupkey = 'days';

        $sql = $this->_build_select_query(array(
                                            'select' => $select,
                                            'date' => $date,
                                            'groupby' => array('`'.$groupkey.'`'),
                                            'orderby' => array('`'.$groupkey.'`'),
                                            'sort' => 'DESC',
                                            'filter' => $filter['sql'],
                                            'limit' => ((is_null($date)) ? $limit : 0))
                                       );

print_r($sql);
/*
SELECT SQL_CACHE   SUBSTRING(`date_time`,1,10) AS `days` , COUNT(`request`) AS `hits` , SUM(`bytes`)/1024/1024 AS `mb`  
FROM `proxy`
GROUP BY `days`
ORDER BY `days` DESC
LIMIT 30;
*/


try {
    $dbh = new \PDO("mysql:host=127.0.0.1;port=3308;dbname=reports", "reports", "gqxPqcCkBtnj2ePbKtT7+RkqsrE=");

    $dbs = $dbh->prepare($sql);
    $dbs->execute();
    $rows = array();

    while ($row = $dbs->fetch()) {
        $rows[] = $row;
    }
} catch(\PDOException $e) {  
    echo $e->getMessage();  
}

// Close handle
$dbh = NULL;

return $rows;

        $data = $this->_query($sql);

        if (empty($data))
            return NULL;

        $data->SetGroup($groupkey);

        return $data;
    }
    /**
     * Returns summary data.
     *
     * @param array $params
     *
     * @return ProxyReportDataObject
     */

    public function get_summary_data_orig($params)
    {
        clearos_profile(__METHOD__, __LINE__);

/*
            $params = compact('date','filter','groupby'.'limit','period','showlegend','start');
       $params = compact('date', 'filter', 'limit', 'period', 'start');
*/

        $date = NULL;
        $filter = NULL;
        $limit = 30;
        $period = 0;

        extract($params, EXTR_IF_EXISTS);

        if (! is_null($date)) {
            switch (count($date[2])) {

            case 3:
                $period = 0;
                $date = $date[0];
                break;

            case 2:
                $period = 1;
                $date = $date[0];
                break;

            default:
                $period = 0;
                $date = NULL;
            }
        }

        switch ($period) {

        case 2:
            $select = array('years','hits','mb');
            $groupkey = 'years';
            break;

        case 1:
            $select = array('months','hits','mb');
            $groupkey = 'months';
            break;

        default:
            $period = 0;
            $select = array('days','hits','mb');
            $groupkey = 'days';
        }

        $sql = $this->_build_select_query(array(
                                            'select' => $select,
                                            'date' => $date,
                                            'groupby' => array('`'.$groupkey.'`'),
                                            'orderby' => array('`'.$groupkey.'`'),
                                            'sort' => 'DESC',
                                            'filter' => $filter['sql'],
                                            'limit' => ((is_null($date)) ? $limit : 0))
                                       );

        $data = $this->_query($sql);

        if (empty($data))
            return NULL;

        $data->SetGroup($groupkey);

        return $data;
    }

    /**
     * Returns Summary Report.
     *
     * @param array $params report parameters
     *
     * @return string
     */

    public function summary_report($params = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $date = NULL; // is only set when called by UserIpDetails
        $filter = NULL; // is only set when called by UserIpDetails
        $type = 'summary'; // is only set when called by UserIpDetails
        $limit = 30;
        $period = 0;
        $start = 0;

        extract($this->params, EXTR_IF_EXISTS);

        if (!is_null($params))
            extract($params, EXTR_IF_EXISTS);

        $params = compact('date', 'filter', 'limit', 'period', 'start');

        // TODO: period is a generic/global parameter, so this is our hack
        // to remove an invalid period:

        $periodoptions = array(
            PROXYREPORT_LANG_DAILY_SUMMARY,
            PROXYREPORT_LANG_MONTHLY_SUMMARY
        );

        $title = PROXYREPORT_LANG_HITS;

        $data = $this->GetSummaryData($params);

        if (! empty($data)) {
            $data->SetLimit($start, $limit);
            $data->ParseChartData(FALSE);

            if (is_null($date)) {
                $data->SetLimit($start, $limit);
                $data->SetChartKeys(array($title));
                $title = NULL;
            } else {
                if (! is_array($date)) {
                    list($date,$title,,) = $this->_ParseDate($date);
                } else {
                    list($date,$title,,) = $date;
                    $data->SetChartKeys(array($title));
                }
            }

            // create chart
            $chart = $data->GetChart('column');

            // retrieve legend data
            $header = $data->GetRowHeader();
            $legenddata = $data->GetLegendData();

            $headerformat = "
                <tr>
                    <th class='sort' align='center'>%s</th>
                    <th class='sort' align='right'>%s</th>
                    <th class='sort' align='right'>%s</th>
                </tr>
            ";

            $legendformat = "
                <td align='center' nowrap='nowrap'>%s</td>
                <td align='right'>%s</td>
                <td align='right'>%s</td>
            ";

            // build legend array
            $hits = array_search(PROXYREPORT_LANG_HITS, $header);
            $hits--;
            $mb = array_search(LOCALE_LANG_MEGABYTES, $header);
            $mb--;
            $legend = array();
            $totalhits = 0;
            $totalmb = 0;

            foreach ($legenddata as $key => $rows) {
                $legendrow = array();
                foreach ( $rows as $row) {
                    foreach ($row as $fieldkey =>$field) {
                        if ($fieldkey === $hits) {
                            $totalhits += $field;
                        }

                        if ($fieldkey === $mb) {
                            $totalmb += $field;
                            $field = sprintf('%01.3f',$field);
                        }

                        $legendrow[$fieldkey] = $field;
                    }
                }

                $legendrow[] = ""; //WebButtonView("date[{$key}]");
                list(,$key,,) = $this->_ParseDate($key);
                array_unshift($legendrow,$key);
                $legend[] = vsprintf($legendformat, $legendrow);
            }

            $row = array();
            $row[] = '<p align="right"><b>' . PROXYREPORT_LANG_TOTAL . '</b></p>';
            $row[] = '<b>' . $totalhits . '</b>';
            $row[] = '<b>' . sprintf("%01.3f", $totalmb) . '</b>';
            $row[] = '&#160;';

            $legend[] = vsprintf($legendformat, $row);

            // create the legend
            $rows = "
                <tr>
                    <td align='center'>
                        <table class='sortable' id='SummaryReport' width='100%'>" .
                            vsprintf($headerformat, $header) . "
                            <tr valign='top'>" . implode("</tr>\n<tr valign='top'>", $legend) . "</tr>
                        </table>
                    </td>
                </tr>
            ";
        }

        $reportperiod = "
            <tr>
                <td colspan='100' nowrap='nowrap' align='center'>" .
                    REPORT_LANG_REPORT_PERIOD . " " .
                    WebDropDownHash('period', $period, $periodoptions) . " " .
                    WebButtonGo('') . " <br><br>" .
                    "<input type='hidden' name='type' value='$type'>
                </td>
            </tr>
        ";


        if (empty($chart)) {
            $chart = "<span align='center'><br><br>" . REPORT_LANG_NO_STATS . "<br><br></span>";
            $legend = "";
        } else {
            $legend = WebChartLegend($title, $rows, '', '600');
        }

        // create report
        ob_start();

        WebFormOpen();
        WebTableOpen($this->title, '100%');
        echo "
            $reportperiod
            <tr>
                <td align='center'>" . $chart . "</td>
            </tr>
            <tr>
                <td align='center'>" . $legend . "</td>
            </tr>
        ";
        WebTableClose('100%');
        WebFormClose();

        $report = ob_get_clean();

        return $report;
    }

    /**
     * Returns User IP Details Report
     *
     *
     * @return string
     */

    public function user_ip_details_report()
    {
        clearos_profile(__METHOD__, __LINE__);

        $date = NULL;
        $filter = NULL;
        $groupby = ProxyReport::GROUPBY_STATUS;
        $limit = 30;
        $period = 0;
        $showlegend = FALSE;
        $start = 0;
        extract($this->params,EXTR_IF_EXISTS);

        $params = compact('date','filter','groupby'.'limit','period','showlegend','start');

        if (empty($date)) {
            switch ($period) {

            case 1:
                $date = date("Y");
                break;

            case 2:
                $date = date("Y-m");
                break;

            case 3:
                $date = date("Y-m-d", time() - 86400);
                break;

            case 4:
                $date = date("Y-m-d");
                break;

            default:
                $date = NULL;
            }

            $params = compact('date','filter','groupby'.'limit','period','showlegend','start');
            if (isset($date))
                $date = $this->_ParseDate($date);
        }

        list($date,$title,$dateparts,) = $date;

        $groups = array(
                      ProxyReport::GROUPBY_IP => array('domain','ip','rfc931','status'),
                      ProxyReport::GROUPBY_USER => array('domain','rfc931','ip','status'),
                      ProxyReport::GROUPBY_HOST => array('domain','hostname','rfc931','status'),
                      ProxyReport::GROUPBY_STATUS => array('domain','status','rfc931','ip')
                  );
        $group = $groups[$groupby];

        if (! $this->usermode) {
            if ($groupby == ProxyReport::GROUPBY_USER) {
                $groupby = ProxyReport::GROUPBY_STATUS;
                $group = $groups[$groupby];
            }

            unset($group[array_search('rfc931',$group)]);
            $group = array_values($group);//reindex
        }

        $chart = '';
        $charttype = 'stacked bar';

        if (isset($filter['sql']['domain'])) {
            $charttype = 'column';

            if (is_array($date)) {
                $data = $this->GetSummaryData($params);
                $data->ParseChartData(FALSE);
                $data->SetChartKeys(array($title));
                $chart = $data->GetChart($charttype);
            }

            array_shift($group);
            $select = array_merge(array('unixtimestamp'),$group);

            if (end($select) == 'status') {
                $select[] = 'status'; # TODO clean up
            } else {
                // keep status and reason togather
                $tmp = array();
                $s = '';

                while (($s != 'status')&&(count($select)>0)) {
                    array_push($tmp,array_pop($select));
                    $s = end($tmp);
                }

                array_push($select,array_pop($tmp));
                array_push($select,'filter_code');

                while (count($tmp)>0) {
                    array_push($select,array_pop($tmp));
                }
            }

            $orderby = array('unixtimestamp');
            $group = NULL;
        } else {
            $select = array_merge($group,array('hits','mb'));
            $orderby = array('hits');
        }

        $sql = $this->_build_select_query(array(
                                            'select' => $select,
                                            'date' => $date,
                                            'groupby' => $group,
                                            'orderby' => $orderby,
                                            'sort' => 'DESC',
                                            'limit' => '50',
                                            'filter' => $filter['sql'])
                                       );
        $data = $this->_query($sql);

        if (isset($data)) {
            $data->SetShowChartLegend($showlegend);
            $data->SetLimit($start,$limit);

            if (isset($filter['sql']['domain'])) {
                $data->SetGroup(array('hours'));
                $data->SetFields(array_combine($select,array_fill(0,count($select),0)));
            } else {
                $data->SetGroup($group);
            }

            try {
                $chart .= $data->GetChart($charttype);
            } catch (Exception $e) {
                throw $e;
            }

            // format legend rows
            $header = $data->GetRowHeader();
            $cols = count($header);
            $headerformat = '';
            $legendformat = '';

            foreach ($header as $col) {
                switch ($col) {

                case PROXYREPORT_LANG_HITS:

                case LOCALE_LANG_MEGABYTES:
                    $class = 'sort';
                    $align = 'right';
                    break;

                default:
                    $class = 'sort';
                    $align = 'left';
                }

                $headerformat .= "<th class=\"$class\" align=\"$align\">%s</th>";
                $legendformat .= "<td align=\"$align\">%s</td>";
            }

            $headerformat = '<tr>'.$headerformat.'</tr>';

            //get legend data
            $legend = array();

            if (isset($filter['sql']['domain'])) {
                $legenddata = $data->GetRows();
                $start = 0;
            } else {
                $legenddata = $data->GetLegendData();
                $totals = $data->GetTotals();
                $colorize = '<font color="%s"><b>%s</b></font><br />';
                $colorcount = count($this->chartcolors);
                $chartkeys = $data->GetChartKeys();
                $hits = array_search(PROXYREPORT_LANG_HITS,$header);
                $hits--;
                $mb = array_search(LOCALE_LANG_MEGABYTES,$header);
                $mb--;
            }

            // build legend array
            foreach ($legenddata as $key => $rows) {
                $legendrow = array();

                if (isset($filter['sql']['domain'])) {
                    array_pop($rows);
                    $legendrow = $rows;
                    $legendrow['unixtimestamp'] = date(ProxyReport::DATE_DAY.' / '.ProxyReport::DATE_TIME,$legendrow['unixtimestamp']);
                    $limit = 0;
                } else {
                    $totalhits = 0;
                    $totalmb = 0;
                    $sort = array();
                    foreach ($rows as $row) {
                        $color = $this->chartcolors[array_search($row[0],$chartkeys) % $colorcount];
                        foreach ($row as $fieldkey =>$field) {
                            switch ($fieldkey) {

                            case $hits:
                                $totalhits += $field;
                                break;

                            case $mb:
                                $totalmb += $field;
                                $field = sprintf('%01.3f',$field);
                                break;

                            default:

                                if (isset($sort[$fieldkey])) {
                                    $sort[$fieldkey][] = $field;
                                } else {
                                    $sort[$fieldkey] = array($field);
                                }
                            }

                            if (isset($legendrow[$fieldkey])) {
                                $legendrow[$fieldkey] .= sprintf($colorize,$color,$field);
                            } else {
                                $legendrow[$fieldkey] = sprintf($colorize,$color,$field);
                            }
                        }
                    }

                    foreach (array_keys($sort) as $fieldkey) {
                        $field = $sort[$fieldkey];
                        sort($field);
                        $field = array_shift($field);
                        $legendrow[$fieldkey] = sprintf('<!--%s-->',$field).$legendrow[$fieldkey];
                    }

                    $legendrow[$hits] = sprintf('<!--%010s-->',$totalhits).$legendrow[$hits].$totalhits;
                    $legendrow[$mb] = sprintf('<!--%010s-->',$totalmb).$legendrow[$mb].sprintf("%01.3f",$totalmb);
                    array_unshift($legendrow,$this->_LookupDomain($key,'details[domain]'));
                }

                $legend[] = vsprintf($legendformat,$legendrow);
            }

            //build legend header
            $max = (isset($totals)) ? $totals['rows'] : count($legend);
            $end = $start + count($legend);
            $row = array();
            $row[0] = '<b>'.REPORT_LANG_RESULTS.': '.($start + 1).' - '.$end.' / '.$max;
            $row[] = (isset($totals))?'<p align="right"><b>'.PROXYREPORT_LANG_TOTAL.': &#160;&#160;&#160;'.$totals['hits'].' '.PROXYREPORT_LANG_HITS.' &#160;&#160;&#160;'.sprintf("%01.3f",$totals['mb']).' '.LOCALE_LANG_MEGABYTES.'</b>':'&#160;';
            $legendheader = implode('|',$row);

            //Create the legend
            $rows = '
                <tr><td colspan="'.$cols.'" align="center">
                <table class="sortable" id="UserIpDetailsReport" width="100%">
                '.vsprintf($headerformat,$header)."\n".
                '<tr valign="top">'.implode("</tr>\n<tr valign=\"top\">",$legend).'</tr>
                </table>
                </td></tr>
                ';

            //build report options
            $back = '';

            if (isset($filter['sql'])) {
                if (isset($filter['sql']['domain'])) {
                    unset($filter['sql']['domain']);
                    $back = '<input type="hidden" name="filter" value="'.urlencode(serialize($filter['sql'])).'">';
                }
            }
        }

        $groupoptions = array(
                            ProxyReport::GROUPBY_IP => NETWORK_LANG_IP,
                            ProxyReport::GROUPBY_USER => LOCALE_LANG_USERNAME,
                            ProxyReport::GROUPBY_HOST => NETWORK_LANG_HOSTNAME,
                            ProxyReport::GROUPBY_STATUS => LOCALE_LANG_STATUS
                        );

        if (!$this->usermode) {
            unset($groupoptions[ProxyReport::GROUPBY_USER]);
        }

        $periodoptions = array(
            LOCALE_LANG_ALL,
            PROXYREPORT_LANG_THIS_YEAR,
            PROXYREPORT_LANG_THIS_MONTH,
            PROXYREPORT_LANG_YESTERDAY,
            PROXYREPORT_LANG_TODAY
        );

        $reportoptions = "
            <tr>
                <td nowrap='nowrap' align='right'>" .  REPORT_LANG_REPORT_PERIOD . "</td>
                <td>" . WebDropDownHash('period', $period, $periodoptions) . "</td>
                <td>&#160; </td>
            </tr>
            <tr>
                <td nowrap='nowrap' align='right'>" .  PROXYREPORT_LANG_GROUPBY . "</td>
                <td>" . WebDropDownHash('groupby', $groupby, $groupoptions, '100') . "</td>
                <td>&#160; </td>
            </tr>
            <tr>
                <td><input type='hidden' name='type' value='details'></td>
                <td>" . WebButtonGenerate('') . "</td>
                <td>&#160; </td>
            </tr>
        ";

        if (empty($chart)) {
            $chart = "<span align='center'><br><br>" . REPORT_LANG_NO_STATS . "<br><br></span>";
            $legend = "";
        } else {
            $legend = WebChartLegend($title, $rows, $legendheader, '600');
        }

        //Create Report
        $filter = (is_null($filter)) ? '' : ' ('.implode(',',$filter['title']).')';
        ob_start();
        WebFormOpen($_SERVER['PHP_SELF']);
        WebTableOpen($this->title.$filter,'100%');
        echo "
            <tr>
                <td align='center'>
                    <table border='0' cellspacing='3' cellpadding='0' align='center'><tr><td>
                    $reportoptions
                    </td></tr></table>
                </td>
            </tr>
            <tr>
                <td align='center'>$chart</td>
            </tr>
            <tr>
                <td align='center'>$legend</td>
            </tr>
        ";
        WebTableClose('100%');
        WebFormClose();

        $report = ob_get_clean();

        return $report;
    }

    /**
     * Returns Domain Summary Report
     *
     *
     * @return string
     */

    public function domain_summary_report()
    {
        clearos_profile(__METHOD__, __LINE__);

        $date = NULL;
        $limit = 100;
        $period = 2;
        $lastperiod = 0;
        $start = 0;
        extract($this->params, EXTR_IF_EXISTS);
        $title = NULL;

        // TODO: should be done in SetParams?
        if (isset($_POST['limit']) && preg_match("/^\d+$/", $_POST['limit'])) {
            $limit = $_POST['limit'];
            $this->params['limit'] = $limit;
        }

        if ($period != $lastperiod)
            $start = 0;

        if ($period != $lastperiod)
            $date = NULL;

        if (is_array($date))
            list($date,$title,$dateparts,) = $date;

        switch ($period) {

        case 1:

            if (is_null($date)) {
                $year = date("Y");
                list($date,$title,,) = $this->_ParseDate($year);
            } else {
                $year = $dateparts[0];
            }

            $max = 500;
            break;

        case 2:

            if (is_null($date)) {
                $month = date("Y-m");
                list($date,$title,,) = $this->_ParseDate($month);
            } else {
                $month = $dateparts[0];
            }

            $max = 0;
            break;

        case 3:

            if (is_null($date)) {
                $day = date("Y-m-d", time() - 86400);
                list($date,$title,,) = $this->_ParseDate($day);
            } else {
                $day = $dateparts[0];
            }

            $max = 0;
            break;

        case 4:

            if (is_null($date)) {
                $day = date("Y-m-d");
                list($date,$title,,) = $this->_ParseDate($day);
            } else {
                $day = $dateparts[0];
            }

            $max = 0;
            break;

        default:
            $max = 500;
        }

        $sql = array();
        $sql[] = 'CREATE TEMPORARY TABLE `hits` '.
                 $this->_build_select_query(array(
                                              'select' => array('domain', 'hits', 'mb'),
                                              'date' => $date,
                                              'groupby' => array('domain'),
                                              'orderby' => array('hits'),
                                              'sort' => 'DESC'));

        $sql[] = 'CREATE TEMPORARY TABLE `cachehits` '.
                 $this->_build_select_query(array(
                                              'select' => array('domain', 'hits', 'mb'),
                                              'date' => $date,
                                              'groupby' => array('domain'),
                                              'orderby' => array('hits'),
                                              'sort' => 'DESC',
                                              'filter'=> array('cache_code' => '%HIT%')));

        if ($max > 0) {
            $sql[] = 'SELECT `hits`.`domain`,`hits`.`hits`,`hits`.`mb`,coalesce(`cachehits`.`hits`, 0) AS `cachehits`,coalesce(`cachehits`.`mb`,0) AS `cachemb`,`hits`.`hits`-coalesce(`cachehits`.`hits`, 0) AS `diff` FROM `hits` LEFT JOIN `cachehits` ON `hits`.`domain`=`cachehits`.`domain` LIMIT '.$max.';';
        } else {
            $sql[] = 'SELECT `hits`.`domain`,`hits`.`hits`,`hits`.`mb`,coalesce(`cachehits`.`hits`, 0) AS `cachehits`,coalesce(`cachehits`.`mb`,0) AS `cachemb`,`hits`.`hits`-coalesce(`cachehits`.`hits`, 0) AS `diff` FROM `hits` LEFT JOIN `cachehits` ON `hits`.`domain`=`cachehits`.`domain`;';
        }

        $results = $this->_query($sql,TRUE);

        // get totals, but do NOT cache results.
        // The md5 is the same, but the temp tables are different.
        $qry = 'SELECT COUNT(`hits`.`domain`) AS `rows`,SUM(`hits`.`hits`) AS `hits`,SUM(`hits`.`mb`) AS `mb`, SUM(coalesce(`cachehits`.`hits`, 0)) AS `cachehits`,SUM(coalesce(`cachehits`.`mb`, 0)) AS `cachemb` FROM `hits` LEFT JOIN `cachehits` ON `hits`.`domain`=`cachehits`.`domain` ;';

        $totals = current($this->_query($qry,TRUE,FALSE));

        if ($totals['rows'] > 0) {

            // set page limit
            $results = array_slice($results,$start,$limit);
            $data = new ProxyReportDataObject($results);
            $data->SetGroup(array('domain'));
            $data->ParseChartData(TRUE);

            // create stacked chartdata
            $domains = array_map('ExtractDomains',$results);
            array_unshift($domains,'');
            $cached = array_map('ExtractCacheHits',$results);
            array_unshift($cached,PROXYREPORT_LANG_CACHE_HIT);
            $diff = array_map('ExtractDiff',$results);
            array_unshift($diff,PROXYREPORT_LANG_CACHE_MISS);
            $chartdata = array($domains,$diff,$cached);
            $data->SetChartData($chartdata);
            $chart = $data->GetChart('stacked bar');

            //build the legend
            $header= $data->GetRowHeader();
            $header[] = PROXYREPORT_LANG_CACHE_HIT;
            $header[] = PROXYREPORT_LANG_CACHE_MB;
            $cols = count($header);
            // TODO: changed class to nosort -- fix sorting.
            $headerformat = '<th class="sort" align="left">%s</th><th class="sort" align="right">%s</th><th class="sort" align="right">%s</th><th class="nosort" align="right">%s</th><th class="nosort" align="right">%s</th>';
            $legendformat = '<td>%s</td><td align="right">%s</td><td align="right">%s</td><td align="right">%s</td><td align="right">%s</td>';

            // build legend array
            $legend = array();

            foreach ($results as $row) {
                $row['domain'] = $this->_LookupDomain($row['domain']);
                $row['cachehits'] .= ' ('.sprintf("%01.1f%%",($row['cachehits']/$row['hits'])*100).')';
                $row['cachemb'] = sprintf("%01.3f",$row['cachemb']).' ('.sprintf("%01.1f%%",($row['cachemb']/$row['mb'])*100).')';
                $row['mb'] = sprintf("%01.3f",$row['mb']);
                unset($row['diff']);
                $legend[] = vsprintf($legendformat,$row);
            }

            // build legend header
            $end = $start + count($legend);
            $row = array();

            if (($max > 0)&&($max < $totals['rows'])) {
                $row[] = '<b>'.REPORT_LANG_NUMBER_OF_RECORDS.': '.$max.' : '.($start + 1).' - '.$end;
            } else {
                $row[] = '<b>'.REPORT_LANG_RESULTS.': '.($start + 1).' - '.$end.' / '.$totals['rows'];
            }

            $row[] = '<p align="right"><b>'.PROXYREPORT_LANG_TOTAL.' : '.$totals['cachehits'].' / '.$totals['hits'].' ('.sprintf("%01.1f%%",($totals['cachehits']/$totals['hits'])*100).') '.PROXYREPORT_LANG_HITS.'</b></p>';
            $row[] = '<p align="right"><b>'.sprintf("%01.3f",$totals['cachemb']).' / '.sprintf("%01.3f",$totals['mb']).' ('.sprintf("%01.1f%%",($totals['cachemb']/$totals['mb'])*100).') '.LOCALE_LANG_MEGABYTES.'</b></p>';
            $legendheader = implode('|',$row);

            // create the legend
            $rows = '
                    <tr><td colspan="'.$cols.'" align="center">
                    <table class="sortable" id="DomainSummaryReport" width="100%">
                    '.vsprintf($headerformat,$header)."\n".
                    '<tr valign="top">'.implode("</tr>\n<tr valign=\"top\">",$legend).'</tr>
                    </table>
                    </td></tr>
                    ';
        }

        // build report options
        $periodoptions = array(
            LOCALE_LANG_ALL,
            PROXYREPORT_LANG_THIS_YEAR,
            PROXYREPORT_LANG_THIS_MONTH,
            PROXYREPORT_LANG_YESTERDAY,
            PROXYREPORT_LANG_TODAY
        );

        // testing revealed that with more than ~70 results IE had problems rendering the graph.
        $msie = '/msie\s(5|6)\.?[0-9]*.*(win)/i';

        if (!isset($_SERVER['HTTP_USER_AGENT']) || !preg_match($msie, $_SERVER['HTTP_USER_AGENT'])) {
            $range = range(10,100,10);
        } else {
            $range = range(10,50,10);
        }

        if (empty($chart)) {
            $chart = "<span align='center'><br><br>" . REPORT_LANG_NO_STATS . "<br><br></span>";
            $legend = "";
        } else {
            $legend = WebChartLegend($title, $rows, $legendheader, '600');
        }

        $reportoptions = "
            <tr>
                <td nowrap='nowrap' align='right'>" .  REPORT_LANG_REPORT_PERIOD . "</td>
                <td>" . WebDropDownHash('period', $period, $periodoptions) . "</td>
                <td>&#160; </td>
            </tr>
            <tr>
                <td nowrap='nowrap' align='right'>" .  REPORT_LANG_RESULTS . "</td>
                <td>" . WebDropDownArray('limit', $limit, $range) . "</td>
                <td>&#160; </td>
            </tr>
            <tr>
                <td><input type='hidden' name='type' value='domains'></td>
                <td>" . WebButtonGenerate('') . "</td>
                <td>&#160; </td>
            </tr>
        ";

        // create Report
        ob_start();

        WebFormOpen();
        WebTableOpen($this->title, '100%');
        echo "
            <tr>
                <td align='center'>
                    <table border='0' cellspacing='3' cellpadding='0' align='center'><tr><td>
                    $reportoptions
                    </td></tr></table>
                </td>
            </tr>
            <tr>
                <td align='center'>$chart</td>
            </tr>
            <tr>
                <td align='center'>$legend</td>
            </tr>
        ";
        WebTableClose('100%');
        WebFormClose();

        $report = ob_get_clean();

        return $report;
    }

    /**
     * Returns ad-hoc report.
     *
     *
     * @return string
     */

    public function search()
    {
        clearos_profile(__METHOD__, __LINE__);

        $daterange = NULL;
        $filter = NULL;
        $filter_code = self::CONSTANT_FILTER_CODE_ALL;
        $status = '0';
        $start = 0;
        $md5 = 0;
        $title = '';
        extract($this->params, EXTR_IF_EXISTS);
        $limit = 300;

        if (is_array($daterange)){
            if ($daterange[1]['time'] < $daterange[0]['time']){
                $startdate = $daterange[1];
                $enddate = $daterange[0];
                $endtime = $daterange[0]['time'];
            }else{
                $startdate = $daterange[0];
                $enddate = $daterange[1];
                $endtime = $daterange[1]['time'];
            }
        }else{
            $startdate = date("Y-m-d");
            list(,,$startdate,$endtime) = $this->_ParseDate($startdate);
            $enddate = $startdate;
        }

        $sqlday = array(
                'start' => $startdate[0],
                'end' => $enddate[0]
        );
        // TODO: quick workaround.  No queries should be done on page fresh page load
        $no_filter_specified = FALSE;

        if (is_null($filter)) {
            $no_filter_specified = TRUE;
            $filter = array(
                          'html' => '',
                          'title' => array('ip' => '', 'rfc931' => '', 'request' => ''),
                          'sql' => array()
                      );
        }

        if (! isset($filter['title']['ip']))
            $filter['title']['ip'] = '';

        if (! isset($filter['title']['rfc931']))
            $filter['title']['rfc931'] = '';

        if (! isset($filter['title']['request']))
            $filter['title']['request'] = '';

        $allmonths = array(
            '01' => LOCALE_LANG_MONTH_1,
            '02' => LOCALE_LANG_MONTH_2,
            '03' => LOCALE_LANG_MONTH_3,
            '04' => LOCALE_LANG_MONTH_4,
            '05' => LOCALE_LANG_MONTH_5,
            '06' => LOCALE_LANG_MONTH_6,
            '07' => LOCALE_LANG_MONTH_7,
            '08' => LOCALE_LANG_MONTH_8,
            '09' => LOCALE_LANG_MONTH_9,
            '10' => LOCALE_LANG_MONTH_10,
            '11' => LOCALE_LANG_MONTH_11,
            '12' => LOCALE_LANG_MONTH_12,
        );

        $alldays = array(
            '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
            '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
            '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'
        );

        $allyears = $this->_GetYearsHash();

        $startoptions = WebDropDownArray('search[startdate][year]', $startdate[1], $allyears) .
                        WebDropDownHash('search[startdate][mon]', $startdate[2], $allmonths) .
                        WebDropDownArray('search[startdate][day]', $startdate[3], $alldays);

        $endoptions = WebDropDownArray('search[enddate][year]', $enddate[1], $allyears) .
                        WebDropDownHash('search[enddate][mon]', $enddate[2], $allmonths) .
                        WebDropDownArray('search[enddate][day]', $enddate[3], $alldays);

        $httpstatushash = $this->_GetHttpCodeHash();
        $httpstatusoptions = WebDropDownHash('search[status]', $status, $httpstatushash);
        if (isset($status) && ($status != 0))
            $filter['sql']['status'] = $status;

        $filterstatushash = $this->_GetFilterCodeHash();
        $filterstatusoptions = WebDropDownHash('search[filter_code]', $filter_code, $filterstatushash);

        switch ($filter_code){
        case self::CONSTANT_FILTER_CODE_ALL:
            $mycustomsql = "";
            break;
        case self::CONSTANT_FILTER_CODE_ALL_BANNED:
            $mycustomsql = " AND `filter_code` > 0 AND (`filter_code` < 600 OR `filter_code` >= 700) ";
            break;
        case self::CONSTANT_FILTER_CODE_ALL_EXCEPTIONS:
            $mycustomsql = " AND `filter_code` >= 600 AND `filter_code` < 700 ";
            break;
        default :
            $codes = explode('|', $filter_code);
            $codesql = array();
            foreach ($codes as $code)
                $codesql[] = "(`filter_code` = '$code')";

            $mycustomsql = " AND (" . implode(" or ", $codesql) . ") ";
        }


        $sqlday['end'] = date("Y-m-d", $endtime + 86400); // date >=start AND < end, so add a day

        if ($no_filter_specified) {
            $max = 0;
        } else  {
            $sql = $this->_build_select_query(array(
                                                'select' => array('COUNT(*) AS rows'),
                                                'date' => $sqlday,
                                                'filter' => $filter['sql'],
                                            ), $mycustomsql);
            $max = $this->_query($sql,TRUE);
            $max = $max[0]['rows'];
        }

        if ($max > 0) {
            if ($md5 != md5($sql)) {
                $start = 0;
                $md5 = md5($sql);
            }

            $select = array('unixtimestamp', 'ip', 'rfc931', 'request', 'status', 'bytes', 'filter_code');
            $header = array(
                LOCALE_LANG_DATE.' / '.LOCALE_LANG_TIME,
                NETWORK_LANG_IP,
                LOCALE_LANG_USERNAME,
                'URL',
                LOCALE_LANG_STATUS,
                LOCALE_LANG_BYTES,
                PROXYREPORT_LANG_FILTER_CODE
            );

            $headcount = count($header);
            $legendformat ='';
            $headerformat = '';

            for($i=0; $i<$headcount; $i++) {
                $headerformat .= '<th class="sort" align="left">%s</th>';
                $legendformat .= '<td nowrap>%s</td>';
            }

            $sql = $this->_build_select_query(array(
                                                'select' => $select,
                                                'date' => $sqlday,
                                                'filter' => $filter['sql'],
                                                'limit' => array($start,300)
                                            ), $mycustomsql);
            $results = $this->_query($sql, TRUE);

            $rows = array();

            foreach ($results as $row) {
                $row['unixtimestamp'] = date(self::DATE_DAY ." / ".self::DATE_TIME,$row['unixtimestamp']);
                $row['ip'] = '<!--'.ip2long($row['ip']).'-->'.$row['ip'];

                // trim overly long urls
                if (strlen($row['request']) > 50)
                    $row['request'] = substr($row['request'], 0, 50);

                $row['filter_code'] = $this->_GetFilterStatusText($row['filter_code']);

                $rows[] = vsprintf($legendformat, $row);
            }

            // build legend header
            $end = $start + count($rows);
            $legendheader = '<b>'.REPORT_LANG_RESULTS.': '.($start + 1).' - '.$end.' / '.$max;

            // create the legend
            $rows = '
                    <tr><td colspan="'.$headcount.'" align="center">
                    <table class="sortable" id="AdhocReport" width="100%">
                    '.vsprintf($headerformat,$header)."\n".
                    '<tr valign="top">'.implode("</tr>\n<tr valign=\"top\">",$rows).'</tr>
                    </table>
                    </td></tr>
                    ';
        } else {
            $rows ='<tr><td colspan="7" align="center">' . REPORT_LANG_EMPTY_REPORT . '</td></tr>';
            $legendheader = '';
        }

        ob_start();
        WebFormOpen();
        WebTableOpen($this->title, '100%');
        echo '
            <tr>
                <td>' . LOCALE_LANG_START . '</td>
                <td>' . $startoptions . '</td>
            </tr>
            <tr>
                <td>' . LOCALE_LANG_END . '</td>
                <td>' . $endoptions . '</td>
            </tr>
            <tr>
                <td>' . NETWORK_LANG_IP . '</td>
                <td><input type="text" name="search[ip]" value="' . $filter['title']['ip']. '"></td>
            </tr>
            <tr>
                <td>' . LOCALE_LANG_USERNAME . '</td>
                <td><input type="text" name="search[rfc931]" value="' . $filter['title']['rfc931'] . '"></td>
            </tr>
            <tr>
                <td>' . 'URL' . '</td>
                <td><input type="text" name="search[request]" value="' . $filter['title']['request'] . '"></td>
            </tr>
            <tr>
                <td>' . PROXYREPORT_LANG_HTTP_CODE . '</td>
                <td>' . $httpstatusoptions . '</td>
            </tr>
            <tr>
                <td>' . PROXYREPORT_LANG_FILTER_CODE . '</td>
                <td>' . $filterstatusoptions . '</td>
            </tr>
            <tr>
                <td>&#160; </td>
                <td>' . WebButtonGenerate('dosearch') . '</td>
            </tr>
            <tr>
                <td colspan="10">' . WebChartLegend($title, $rows, $legendheader, '100%') . '</td>
            </tr>
            <tr>
                <td colspan="10" align="center">
                <input type="hidden" name="md5" value="' . $md5 . '">
                ' . $this->_ReportNavButtons($start, $limit, $max) . '
                </td>
            </tr>
        ';
        WebTableClose('100%');
        WebFormClose();

        $report = ob_get_clean();

        return $report;
    }

    /**
     * Retrieves a "country of origin" flag for the domain from GeoIP.
     *
     * If the domain is an ip and the Whois class is installed,
     * the OrgName will also be retrieved to better identify the domain
     *
     * @param string $domain the domain to look up
     * @param string $buttonname (optional) if included, returns a WebButton(bottonname,domain,flag).
     *
     * @return string
     */
    private function _lookup_domain($domain,$buttonname=NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_object($this->GeoIP)) {
            $this->GeoIP = new GeoIP();
        }

        $GeoIP = $this->GeoIP;
        $loc ='';
        $domain = preg_replace('/:[\d]+$/','',$domain); //remove any trailing port numbers
        $ip_long = ip2long($domain);

        if ( $ip_long !== FALSE) {
            $loc = gethostbyaddr($domain);

            if ($loc == $domain) {
                $loc ='';
            }

            if ((!
                    (( ($ip_long >= ip2long("10.0.0.0")) && ($ip_long <= ip2long("10.255.255.255")) ) ||
                     ( ($ip_long >= ip2long("172.16.0.0")) && ($ip_long <= ip2long("172.31.255.255")) ) ||
                     ( ($ip_long >= ip2long("192.168.0.0")) && ($ip_long <= ip2long("192.168.255.255")) )))
                    && (file_exists(COMMON_CORE_DIR.'/api/Whois.class.php')
                       )) {
                require_once(COMMON_CORE_DIR.'/api/Whois.class.php');
                $whois = new Net_Whois();

                if (empty($loc)) {
                    $whoisdata = explode("\n",$whois->query($domain));
                } else {
                    $whoisdata = explode("\n",$whois->query($loc));
                }

                $orgname = array();

                if (preg_match('/^OrgName\:(.*)$/',$whoisdata[1],$orgname)) {
                    $loc = (empty($loc)) ? end($orgname) : "$loc<br/>".end($orgname);
                }
            } else {
                $loc .=" : " . PROXYREPORT_LANG_PRIVATE_IP;
            }

            // $loc .= " : " . $GeoIP->GetLocation($domain);
        }

        // The img tag for the flag causes wonky sort results, so a "comment" tweak was added to sorttables.js
        // Now, by adding the domain as a comment here, the columns will sort as expected.

        if (is_null($buttonname)) {
            $domain = "\n<!--$domain-->\n" . $GeoIP->GetFlag($domain) . '&#160;' . $domain . $loc;
        } else {
            $domain = "\n<!--$domain-->\n" . WebButton($buttonname, $domain, $GeoIP->GetFlag($domain)) . $loc;
        }

        return $domain;
    }

    /**
     * Parses/Validates date input
     * Returns an array of date info
     *
     * @param string $date YYYY,YYYY-MM, OR YYYY-MM-DD
     *
     * @return array
     */
    private function _parse_date($date)
    {
        clearos_profile(__METHOD__, __LINE__);

        $valid_date = '/^([\d]{4})-([\d]{2})-([\d]{2})$/';
        $valid_month = '/^([\d]{4})-([\d]{2})$/';
        $valid_year = '/^([\d]{4})$/';

        $dateparts = array();

        $text = '';

        if (strlen($date)==5) {
            $date = FixNumericKey($date);
        }

        // The date format can also cause wonky sort results.
        // Adding the unformatted date as a comment here fixes the issue.
        if (preg_match($valid_date,$date,$dateparts)) {
            list(,$year,$mon,$day) = array_map('intval',$dateparts);
            $time = mktime(0,0,0,$mon,$day,$year);

            //reverse sanity check
            //check for a date like 2006-02-31 which is invalid, but parses correctly
            if (date("Y-m-d",$time)!= $date){
                throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": ".LOCALE_LANG_DATE.': '.$date);
            }

            if ($date == date("Y-m-d")) {
                $text = "<!--$date-->".PROXYREPORT_LANG_TODAY;
            }

            elseif ($date == date("Y-m-d", time() - 86400)) {
                $text = "<!--$date-->".PROXYREPORT_LANG_YESTERDAY;
            }
            else {
                $text = "<!--$date-->".date(ProxyReport::DATE_DAY,$time);
            }

            $range = $date;
        }

        elseif (preg_match($valid_month,$date,$dateparts)) {
            // adjust to a range
            list(,$year,$mon) = array_map('intval',$dateparts);
            $next = str_pad($mon + 1,2,'0',STR_PAD_LEFT);
            $range = array("$year-$mon-01","$year-$next-01");
            $time = mktime(0,0,0,$mon,1,$year);
            $text = date(ProxyReport::DATE_MON,$time);
        }

        elseif (preg_match($valid_year,$date,$dateparts)) {
            // adjust to a range
            list(,$year) = $dateparts;
            $next = $year + 1;
            $range = array("$year-01-01","$next-01-01");
            $time = mktime(0,0,0,1,1,$year);
            $text = $year;
        }
        else {
            $debug = array_shift(debug_backtrace());
            $line = $debug['line'];
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": ".LOCALE_LANG_DATE.': '.__METHOD__."($line)");
        }

        return array($range,$text,$dateparts,$time);
    }


    /**
     * Builds a "Select" query.
     * Data should be validated/escaped BEFORE passing to this function!!!
     *
     * _build_select_query(
     * <code>
     * array{
     *      'select' => (array) field names (should be delimited)
     *      'date' => (string|array) exa.  '2006-05-01' or array('2006-05-01','2006-06-01')
     *      'status' => (int)
     *      'filter' => (array) field names for filtering. exa. array(fieldname=>value)
     *      'group' => (array) field names for the GROUP BY clause
     *      'order' => (array) field names for the ORDER BY clause
     *      'sort' => (string) one of ASC|DESC; defaults to ASC
     *      'limit' => (int|array) sql limit value(s)
     *)
     * </code>
     *)
     *
     * @return string
     */

    // TODO: method is not documented and hard to follow.
    // As a temporary measure, the "extrawhere" parameter was added for
    // the Search method in order to add the following:
    // "WHERE ((filter_code = 300) or (filter_code = 301) or ...))

    private function _build_select_query($params, $extrawhere = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $days = ' SUBSTRING(`date_time`,1,10) AS `days` ';
        $hits = ' COUNT(`request`) AS `hits` ';
        $hours = ' SUBSTRING(`date_time`,12,2) AS `hours` ';
        $ip = ' INET_NTOA(`client`) AS `ip` ';
        $mb = ' SUM(`bytes`)/1024/1024 AS `mb` ';
        $months = ' SUBSTRING(`date_time`,1,7) AS `months` ';
        $sql_limit = ' LIMIT %d , %d ';
        $status_equals = '`status` = \'%s\' ';
        $unixtimestamp = ' UNIX_TIMESTAMP(`date_time`) as `unixtimestamp` ';
        $where_date_equals = ' WHERE substring(`date_time`,1,10) = \'%s\' ';
        $where_date_isbetween = ' WHERE `date_time` >=\'%s\' AND `date_time` < \'%s\' ';
        $years = ' SUBSTRING(`date_time`,1,4) AS `years` ';

        if (isset($params['select'])) {
            $fields = $params['select'];
            $date = isset($params['date']) ? $params['date'] : '';
            $status = isset($params['status']) ? $params['status'] : 0;
            $filter = isset($params['filter']) ? $params['filter'] : '';
            $group = isset($params['groupby']) ? $params['groupby'] : '';
            $order = isset($params['orderby']) ? $params['orderby'] : '';
            $sort = isset($params['sort']) ? $params['sort'] : 'ASC';
            $limit = isset($params['limit']) ? $params['limit'] : 0;
        } else {
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.': $params : '.__METHOD__.'('.__LINE__.')');
        }

        if (is_array($fields)) {
            // add some "magic", so unknown hostnames can be mapped to ip

            if (in_array('hostname',$fields)) {
                if (! in_array('ip',$fields)) {
                    $fields[] = 'ip';
                }
            }

            $join = '';
            foreach ($fields as $key => $field) {
                switch ($field) {

                case 'days':
                    $fields[$key] = $days;
                    break;

                case 'hits':
                    $fields[$key] = $hits;
                    break;

                case 'hostname':
                    $join = "JOIN `hostnames` ON (`hostnames`.`ip` = `proxy`.`client`)";
                    break;

                case 'hours':
                    $fields[$key] = $hours;
                    break;

                case 'ip':
                    $fields[$key] = $ip;
                    break;

                case 'mb':
                    $fields[$key] = $mb;
                    break;

                case 'months':
                    $fields[$key] = $months;
                    break;

                case 'unixtimestamp':
                    $fields[$key] = $unixtimestamp;
                    break;

                case 'years':
                    $fields[$key] = $years;
                    break;

                default:
                    $fields[$key] = preg_replace('/^(\w+)$/',"`\${1}`",$field);//add `quotes`
                }
            }

            $fields = ' '.implode(',',$fields).' ';
        } else {
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_INVALID_TYPE.': $fields : '.__METHOD__.'('.__LINE__.')');
        }

        $where = '';

        if (! empty($date)) {
            if (is_array($date)) {
                $where .= vsprintf($where_date_isbetween,$date);
            } else {
                $where .= sprintf($where_date_equals,$date);
            }
        }

        if ($status > 0) {
            if (empty($where)) {
                $where = " WHERE ";
            } else {
                $where .=" AND ";
            }

            $where .= sprintf($status_equals,$status);
        }

        if (is_array($filter)) {
            foreach ($filter as $field => $value) {
                if (empty($where)) {
                    $where = " WHERE ";
                } else {
                    $where .=" AND ";
                }

                if ($field == 'ip') {
                    $where .= "INET_NTOA(`client`) LIKE '$value%'";
                } else {
                    if (strpos($value,'%') !== FALSE) {
                        $where .= "$field LIKE '$value'";
                    } else {
                        $where .= "$field = '$value'";
                    }
                }
            }
        }

        if (is_array($group)) {
            // more hostname "magic"

            if (in_array('hostname',$group)) {
                $group[array_search('hostname',$group)] = 'ip';
            }

            $group = preg_replace('/^(\w+)$/',"`\${1}`",$group);
            $groupby = 'GROUP BY '.implode(",",$group);
        }

        if (is_array($order)) {
            $order = preg_replace('/^(\w+)$/',"`\${1}`",$order);
            $orderby = 'ORDER BY '.implode(",",$order);
        }

        if (! empty($limit)) {
            if (is_array($limit)) {
                $limit = vsprintf($sql_limit,$limit);
            } else {
                $limit ="LIMIT {$limit}";
            }
        }

        $sql = "SELECT SQL_CACHE "
               . $fields
               . "\nFROM `proxy`"
               . ((empty($join)) ? '' : "\n$join")
               . ((empty($where)) ? '' : "\n$where")
               . $extrawhere
               . ((empty($groupby)) ? '' : "\n$groupby")
               . ((empty($orderby)) ? '' : "\n$orderby $sort")
               . ((empty($limit)) ? '' : "\n$limit")
               . ';'
               ;

        // echo "$sql<br>";

        return $sql;
    }

    /**
     * Performs SQL query
     *
     * @param string $sql
     * @param boolean $getall if TRUE, function returns returns raw data
     * @param boolean $cache toggles use of cache for query data
     *
     * @return ProxyReportDataObject or array if $getall=TRUE
     */

    private function _query($sql,$getall=FALSE,$cache=TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $results = NULL;

        if (is_array($sql)) {
            $md5 = '';
            foreach ($sql as $qry) {
                $md5 .= md5($qry);
            }

            $md5 = md5($md5.session_id());
        } else {
            $md5 = md5($sql.session_id());
        }

        if (($cache) && (file_exists(COMMON_CORE_DIR.'/common/Cache.class.php'))) {
            require_once(COMMON_CORE_DIR.'/common/Cache.class.php');
            $DataCache = new DataCache();
            $DataCache->setStore(COMMON_TEMP_DIR."/");
            $DataCache->Clean();
            $results = $DataCache->Get(__CLASS__,$md5);
        } else {
            $cache = FALSE;
        }

        if (is_null($results)) {
            $max = 0;
        } else {
            if (is_array($results)) {
                $max = count(array_filter($results));
            } else {
                $max = 0;
            }
        }

        if ($max == 0) {
            if (! is_object($this->db)) {
                $this->db = $this->_connect();

                if (! is_object($this->db)) {
                    throw new Engine_Exception(PROXYREPORT_LANG_DB_ERROR.' :'.__METHOD__.'('.__LINE__.')',COMMON_ERROR);
                }
            }

            $db =& $this->db;

            if (is_array($sql)) {
                foreach ($sql as $qry) {
                    $getall = TRUE;
                    $results = $db->getAll($qry);
                    // Always check that the result is not an error

                    if (PEAR::isError($results)) {
                        throw new Engine_Exception($results->getMessage()." : ".__METHOD__.'('.__LINE__.")<br /><br />".nl2br(print_r($sql,TRUE)),COMMON_ERROR);
                    }
                }
            } else {
                $res =& $db->query($sql);
                // Always check that the result is not an error

                if (PEAR::isError($res)) {
                    throw new Engine_Exception($res->getMessage()." : ".__METHOD__.'('.__LINE__.")<br /><br />".nl2br($sql),COMMON_ERROR);
                }

                $max = $res->numRows();
                $results = array();

                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ASSOC))) {
                    // let the tweaks begin

                    if (array_key_exists('hostname',$row)) {
                        if ($row['hostname'] == "UNK") { // map unknown hosts to ip
                            $row['hostname'] = $row['ip'];
                        }
                    }

                    if (array_key_exists('status',$row)) { // map status codes to text translations
                        $row['status'] = $this->httpcodes[$row['status']];
                    }

                    if (array_key_exists('unixtimestamp',$row)) { // if there is a timestamp, add hours to the results
                        $row = array_merge($row,array('hours' => date("H",$row['unixtimestamp'])));
                    }

                    if (array_key_exists('mb',$row)) {
                        if ($row['mb'] == 0) {
                            // fudge mb slightly to keep array_filter from zapping it
                            // its rounded by sprintf, so the fudge won't show
                            $row['mb'] = 0.000001;
                        }
                    }

                    $results[] = $row;
                }

                // Always check that the result is not an error
                if (PEAR::isError($res)) {
                    throw new Engine_Exception($res->getMessage()." : ".__METHOD__.'('.__LINE__.")<br />SQL=$sql;",COMMON_ERROR);
                }
            }
        }

        if (($cache) && (! $getall))
            $DataCache->Put(__CLASS__, $md5,120,$results);

        if ($getall)
            return $results;

        if ($max > 0) {
            $data = new ProxyReportDataObject($results,$md5);
        } else {
            $data = NULL;
        }

        return $data;
    }

    /**
     * Returns report years as hash
     *
     *
     * @return array
     */
    private function _get_years_hash()
    {
        if (! is_object($this->db)) {
            $this->db = $this->_connect();

            if (! is_object($this->db))
                throw new Engine_Exception(PROXYREPORT_LANG_DB_ERROR.' :'.__METHOD__.'('.__LINE__.')',COMMON_ERROR);
        }

        $db = $this->db;
        $sql = 'SELECT SQL_CACHE DISTINCT SUBSTRING( `date_time` , 1, 4 ) AS `years` FROM `proxy` ORDER BY `years`';
        $years = $db->getAll($sql);

        if (PEAR::isError($years)) {
            throw new Engine_Exception($years->getMessage()." : ".__METHOD__.'('.__LINE__.")<br />SQL=$sql;",COMMON_ERROR);
        }

        $years = array_map('ExtractYears',$years);
        $years = array_combine($years,$years);
        return $years;
    }

    /**
     * Returns report months as hash
     *
     *
     * @return array
     */
    private function _get_months_hash()
    {
        if (! is_object($this->db)) {
            $this->db = $this->_connect();

            if (! is_object($this->db))
                throw new Engine_Exception(PROXYREPORT_LANG_DB_ERROR.' :'.__METHOD__.'('.__LINE__.')',COMMON_ERROR);
        }

        $db = $this->db;
        $sql = 'SELECT SQL_CACHE DISTINCT SUBSTRING( `date_time` , 1, 7 ) AS `months` FROM `proxy` ORDER BY `months`';
        $months = $db->getAll($sql);

        if (PEAR::isError($months)) {
            throw new Engine_Exception($months->getMessage()." : ".__METHOD__.'('.__LINE__.")<br />SQL=$sql;",COMMON_ERROR);
        }

        $months = array_flip(array_map('ExtractMonths',$months));
        foreach (array_keys($months) as $month) {
            $months[$month] = ShortMon($month);
        }

        return $months;
    }

    /**
     * Returns report days availible for a specific month
     *
     * @param string $month YYYY-MM
     *
     * @return array
     */
    private function _get_days_hash($month)
    {
        if (! is_object($this->db)) {
            $this->db = $this->_connect();

            if (! is_object($this->db)) {
                throw new Engine_Exception(PROXYREPORT_LANG_DB_ERROR.' :'.__METHOD__.'('.__LINE__.')',COMMON_ERROR);
            }
        }

        $db = $this->db;
        $sql = 'SELECT SQL_CACHE DISTINCT SUBSTRING( `date_time` , 1, 10 ) AS `day` FROM `proxy` WHERE `date_time` LIKE \'%'.$month.'%\' ORDER BY `day` ';
        //print "<br/>$sql<br/>";

        $days = $db->getAll($sql);

        if (PEAR::isError($days)) {
            throw new Engine_Exception($days->getMessage()." : ".__METHOD__.'('.__LINE__.")<br />SQL=$sql;",COMMON_ERROR);
        }

        $days = array_flip(array_map('ExtractDay',$days));
        foreach (array_keys($days) as $day) {
            $days[$day] = ShortDay($day);
        }

        return $days;
    }

    /**
     * Returns text for given status code.
     *
     * @param int $status status code
     *
     * @return array
     */

    private function _get_filter_status_text($status)
    {
        foreach ($this->filtercodes as $codelist => $message) {
            $codearray = explode('|', $codelist);
            if (in_array($status, $codearray))
                return $message;
        }

        return " &#160; ";
    }

    /**
     * Returns hash of valid content filter status codes.
     *
     *
     * @return array
     */

    private function _get_filter_code_hash()
    {
        $filtercodes = array();
        $messages = array();

        foreach ($this->filtercodes as $code => $message)
            $messages[$message] = $code;

        ksort($messages);

        $filtercodes[self::CONSTANT_FILTER_CODE_ALL] = "--- " . LOCALE_LANG_ALL . " ---";
        $filtercodes[self::CONSTANT_FILTER_CODE_ALL_BANNED] = PROXYREPORT_LANG_FILTER_CODE_ALL_BANNED;
        $filtercodes[self::CONSTANT_FILTER_CODE_ALL_EXCEPTIONS] = PROXYREPORT_LANG_FILTER_CODE_ALL_EXCEPTIONS;

        foreach ($messages as $message => $code) {
            $filtercodes[$code] = $message;
        }

        return $filtercodes;
    }

    /**
     * Returns hash of valid HTTP status codes.
     *
     *
     * @return array
     */

    private function _get_http_code_hash()
    {
        $httpcodes = array();

        foreach ($this->httpcodes as $code => $description) {
            $httpcodes[$code] = "$code - $description";
        }

        $httpcodes[0] = "--- " . LOCALE_LANG_ALL . " ---";

        ksort($httpcodes);

        return $httpcodes;
    }
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Proxy Report Data Object.
 *
 * Contains methods to manipulate data for use by PHP/SWF
 *
 * @category   Apps
 * @package    
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps//
 * @copyright  2006-2011 ClearFoundation
 */

class ProxyReportDataObject extends Engine
{

    protected $chartcolors = array();
    protected $chartdata = array();
    protected $chartkeys = array();
    protected $fields = array();
    protected $group = NULL;
    protected $labels = array();
    protected $legenddata = array();
    protected $max = 0;
    protected $md5 = NULL;
    protected $rowheader = array();
    protected $rows = array();
    protected $showchartlegend = FALSE;
    protected $slice = array();
    protected $total = array();

    public function __construct($rows=NULL,$md5=NULL)
    {
        clearos_profile(__METHOD__, __LINE__);


        $this->Reset();

        if (! is_null($rows)) {
            $this->rows = $rows;
        }

        if (! is_null($md5)) {
            $this->rows = $rows;
        }
    }

    /**
     * Sets the object's vars to their initial stat
     *
     */
    public function reset()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->chartcolors = array( "4e627c", "ffdc30", "d2d23d", "3b5743", "ffc262");
        $this->chartdata = array();
        $this->chartkeys = array();
        $this->fields = array();
        $this->group = NULL;
        $this->labels = array(
                            'cachehits' => PROXYREPORT_LANG_HITS,
                            'cachemb' => LOCALE_LANG_MEGABYTES,
                            'date' => LOCALE_LANG_DATE,
                            'days' => LOCALE_LANG_DAYS,
                            'diff' => 'DIFF',
                            'domain' => NETWORK_LANG_DOMAIN,
                            'filter_code' => PROXYREPORT_LANG_CODE,
                            'filter_detail' => PROXYREPORT_LANG_REASON,
                            'hits' => PROXYREPORT_LANG_HITS,
                            'hostname'=> NETWORK_LANG_HOSTNAME,
                            'hours' => LOCALE_LANG_HOURS,
                            'ip' => NETWORK_LANG_IP,
                            'mb' => LOCALE_LANG_MEGABYTES,
                            'months' => LOCALE_LANG_MONTHS,
                            'rfc931' => LOCALE_LANG_USERNAME,
                            'status' => LOCALE_LANG_STATUS,
                            'unixtimestamp'=> LOCALE_LANG_DATE.' / '.LOCALE_LANG_TIME,
                            'years' => PROXYREPORT_LANG_YEARS
                        );

        $this->legenddata = array();
        $this->max = 0;
        $this->md5 = NULL;
        $this->rowheader = array();
        $this->rows = array();
        $this->showchartlegend = FALSE;
        $this->slice = array();
    }

    /**
     * Add a "row" to the objects data array
     *
     * @param array $row
     */
    public function add_row($row)
    {
        clearos_profile(__METHOD__, __LINE__);

        $row = array_map('htmlentities',$row); // just a security precaution

        $this->rows[] = array_values($row);
    }

    /**
     * Set grouping order.
     *
     * @see ParseChartData
     *
     * @param mixed $group (array|boolean)
     */

    public function set_group($group)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($group))
            $group = array($group);

        $this->group = $group;
    }

    /**
     * Set legend fields
     *
     * @see ParseChartData
     *
     * @param array $fields keys are the fieldnames and the value is boolean. boolean toggles use as data for the graph
     */
    public function set_fields($fields)
    {
        clearos_profile(__METHOD__, __LINE__);

        foreach (array_keys($fields) as $field) {
            if (! array_key_exists($field,$this->labels)) {
                //oops.... update $this->labels with this field.  :p
                throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": $field : ".__METHOD__.'('.__LINE__.')');
            }
        }

        $this->fields = $fields;
    }

    /**
     * Limits data returns
     * cf. array_slice for how to set $start and $limit.
     *
     * @param int $start
     * @param int $limit
     * @param int $max
     */
    public function set_limit($start=0,$limit=100,$max=0)
    {
        $this->slice = array($start,$limit);
        $this->max = $max;
    }

    /**
     * Toggle visibility of chart legend
     *
     * @param boolean $showlegend if TRUE, the chart legend will be visible
     */
    public function set_show_chart_legend($showlegend)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->showchartlegend = $showlegend;
    }

    /**
     * Allows chartkeys to be overidden.
     * Will have no effect unless ParseChartData is called first.
     *
     * @param array $chartkeys
     */
    public function set_chart_keys($chartkeys)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($chartkeys)) {
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_INVALID_TYPE.': $chartkeys : '.__METHOD__.'('.__LINE__.')');
        }

        //running thru the data anyway, might as well validate it too
        //no single index should be empty
        $ndx = 0;

        foreach ($this->chartdata as $data) {
            if ($ndx > 0) {
                if (array_key_exists($ndx - 1,$chartkeys)) {
                    $this->chartdata[$ndx][0] = strip_tags($chartkeys[$ndx - 1]);
                }
            }

            if (count(array_filter($data,'strlen')) == intval($this->showchartlegend)) {// chartkeys don't count

                if (($ndx == 0)&&(count($data) == 2)) { //unless where at the the first index ;)
                    $ndx++;
                } else {
                    throw new Validation_Exception(PROXYREPORT_LANG_CHARTDATA_INVALID);
                }
            }

            $ndx++;
        }

        $this->chartkeys = $chartkeys;

    }

    /**
     * Sets and validates chart data
     *
     * @param array $chartdata
     */
    public function set_chart_data($chartdata)
    {
        clearos_profile(__METHOD__, __LINE__);

        //validate the chartdata before adding it
        //no single index should be empty
        $ndx = 0;

        foreach ($chartdata as $data) {
            if (count(array_filter($data)) == intval($this->showchartlegend)) {// chartkeys don't count

                if (($ndx == 0)&&(count($data) == 2)) { //unless where at the the first index ;)
                    $ndx++;
                } else {
                    throw new Validation_Exception(PROXYREPORT_LANG_CHARTDATA_INVALID);
                }
            }

            $chartdata[$ndx] = array_map('FixNumericKey',$chartdata[$ndx]);

            if (!is_array($chartdata[$ndx])) {
                unset($chartdata[$ndx]);
            }

            $ndx++;
        }

        $this->chartdata = $chartdata;

    }

    /**
     * Returns a list of validated legend fields and sets legend header.
     *
     *
     * @return array
     */
    public function get_fields()
    {
        clearos_profile(__METHOD__, __LINE__);

        $fields = $this->fields;
        $group = $this->group;

        if (is_array($group)) {
            $group = array_combine($group,array_fill(0,count($group),0));
            $fields = array_merge($group,$fields);
        }

        // tweak fields
        if (! ((isset($fields['unixtimestamp']))||(isset($fields['hours']))))
            $fields = array_merge($fields,array('hits' => 1,'mb' => 0));

        // verify the requested fields
        $row = current($this->rows);

        if (count($row) == 0)
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_NO_ENTRIES.': $rows : '.__METHOD__.'('.__LINE__.')');

        foreach (array_keys($fields) as $field) {
            if (array_key_exists($field, $row)) {
                // update row headers
                if ($field != 'hours') {
                    if (! in_array($this->labels[$field], $this->rowheader))
                        $this->rowheader[] = $this->labels[$field];
                }
            } else {
                throw new Validation_Exception(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": $field : ".__METHOD__.'('.__LINE__.')');
            }
        }

        return $fields;
    }

    /**
     * Returns an embedded link to PHP/SWF.
     * If needed, data will be reformatted to meet PHP/SWF requirements.
     *
     * @param string $type  cf. php/swf for valid types
     * @param int $width (optional|600)
     * @param int $height (optional|0/100) if 0, an attempt will be made to calculate the correct height based on type
     * @param string $url (optional|'')
     *
     * @return string embedded link to php/swf
     */

    public function get_chart($type,$width=600,$height=0,$url='')
    {
        clearos_profile(__METHOD__, __LINE__);

        if (count($this->chartdata) == 0) {
            $stacked = (strpos($type,'stacked') !== FALSE);
            $this->ParseChartData($stacked);
        }

        //tweak chart data
        $chartdata = $this->chartdata;

        $count = count($chartdata);

        if ((strpos($type,"pie") === FALSE)|(strpos($type,"stacked") === FALSE)) {
            for($i=0;$i<$count;$i++) {
                $pad = array_shift($chartdata[$i]);//remove the first element, normally its a chart label
                $chartdata[$i] = array_reverse($chartdata[$i]); //fix appearance, reverse the data, so it "matches" the legend
                array_unshift($chartdata[$i],$pad); //replace the first element
            }
        }

        if (strpos($type,"column") !== FALSE) {
            if (count($chartdata[0]) >32) { // max for a month + 1
                //space labels out for readability
                foreach ($chartdata[0] as $key => $value) {
                    $chartdata[0][$key] = (($key % 7)==0) ? $value : '';
                }
            }
        }

        if ($height == 0) {
            if (strpos($type,"bar") !== FALSE) {
                $height = 20*count($chartdata[0]);
            } else {
                $height = 250;
            }
        }

        //buffer the chart so it can be returned
        ob_start();

        WebChart('',$type,$width,$height,$chartdata,$this->chartcolors,"#FEFEFE",FALSE,$url);

        return ob_get_clean();

    }

    /**
     * Returns chart data
     *
     *
     * @return array
     */
    public function get_chart_data()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->chartdata;
    }

    /**
     * Reorganize the data into array formatted for use by PHP/SWF
     * and creates its legend
     *
     *
     * @return void
     */
    public function parse_chart_data($stacked=TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $fields = $this->GetFields();
        $fields = array_keys($fields,1);
        $group = $this->group;

        switch (count($group)) {

        case 5 :

        case 4 :
            $group = array_slice($group,0,3);

        case 3 :

        case 2 :
            break;

        case 1 :
            $stacked = FALSE;
            break;

        default:
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_NO_ENTRIES.': $group : '.__METHOD__.'('.__LINE__.')');
        }

        $groupkey = $group[0];

        $legenddata = $this->GroupBy($this->rows,$group,$stacked); // yummmm.... recursion
        $this->total['hits'] = $legenddata['hits'];
        $this->total['mb'] = $legenddata['mb'];
        unset($legenddata[$groupkey]);
        unset($legenddata['hits']);
        unset($legenddata['mb']);
        //sort the newly grouped data

        switch ($groupkey) {

        case 'days':

        case 'months':

        case 'hours':
            break;

        default:
            $this->_SortBySubkey($legenddata,'hits',FALSE);
        }

        if ($this->max > 0) {
            $legenddata = array_slice($legenddata,0,$this->max);
        }

        $max = $this->total['rows'] = count($legenddata);
        $slice = $this->slice;

        if (count($slice)>1) {
            if ($slice[0]>$max) {
                $slice[0] = 0;
            }

            $legenddata = array_slice($legenddata,$slice[0],$slice[1]);
        }

        $legendkeys = array_keys($legenddata);
        $chartdata = array();

        if ($stacked) {
            $chartkeys = array();
            foreach ($legenddata as $values) {
                if (isset($values[$group[1]])) {
                    if (is_array($values[$group[1]])) {
                        $chartkeys = array_merge($chartkeys,$values[$group[1]]);
                    } else {
                        $chartkeys[] = $values[$group[1]];
                    }
                }
            }

            $chartkeys = array_values(array_unique($chartkeys));
            foreach ($legenddata as $values) {
                foreach ($chartkeys as $grp) {
                    if (! isset($chartdata[$grp])) {
                        if ($this->showchartlegend) {
                            $chartdata[$grp] = array($grp);
                        } else {
                            $chartdata[$grp] = array('');
                        }
                    }

                    if (isset($values[$grp]['hits'])) {
                        $chartdata[$grp][] = $values[$grp]['hits'];
                    } else {
                        $chartdata[$grp][] = 0;
                    }
                }
            }
        } else {
            $chartkeys = $legendkeys;
            foreach ($legenddata as $values) {
                if (! isset($chartdata[$groupkey])) {
                    if ($this->showchartlegend) {
                        $chartdata[$groupkey] = array($groupkey);
                    } else {
                        $chartdata[$groupkey] = array('');
                    }
                }

                if (isset($values['hits'])) {
                    $chartdata[$groupkey][] = $values['hits'];
                } else {
                    $chartdata[$groupkey][] = 0;
                }
            }
        }

        //store legend
        $this->legenddata = array_filter($legenddata);

        // pad chardata and legendkeys if required
        switch ($groupkey) {

        case 'days':

        case 'months':

        case 'hours':
            $this->_Pad($chartdata,$legendkeys,$groupkey);
            $chartkeys = $legendkeys;
            $this->total['hits'] = count($legendkeys);
        }

        // store keys
        $this->chartkeys = $chartkeys;

        // format chartdata for PHP/SWF
        array_unshift($legendkeys,'');
        array_unshift($chartdata,$legendkeys);
        $chartdata = array_values($chartdata);

        $this->SetChartData($chartdata);
    }

    /**
     * Returns Chart Keys
     *
     *
     * @return array
     */
    public function get_chart_keys()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->chartkeys;
    }

    /**
     * Returns row headers
     *
     *
     * @return array
     */
    public function get_row_header()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array_unique($this->rowheader);
    }

    /**
     * Returns the legend data formatted into an array of grouped rows
     *
     *
     * @return array
     */
    public function get_legend_data()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (count($this->legenddata) == 0)
            throw new Validation_Exception(LOCALE_LANG_ERRMSG_NO_ENTRIES.': $legenddata : '.__METHOD__.' ('.__LINE__.')');

        $groups = array_slice($this->group,0,3);

        if (count($groups)>1) {
            array_shift($groups);
            $legenddata = $this->FlattenGroups($this->legenddata,$groups); //yummm... recursion
            foreach ($legenddata as $key => $rows) {
                $legenddata[$key]= array();
                foreach ($rows as $subkey => $row) {
                    if (is_array($row)) {
                        foreach ($row as  $subrow) {
                            $legenddata[$key][] = array_merge(array($subkey),explode('|',$subrow));
                        }
                    } else {
                        $legenddata[$key][] = explode('|',$row);
                    }
                }
            }
        } else {
            $legenddata = array();
            foreach ($this->legenddata as $key => $values) {
                $legenddata[$key] = array(array_values($values));
            }
        }

        return $legenddata;
    }

    /**
     * Returns the object's raw (unproccessed) data
     *
     *
     * @return array
     */
    public function get_rows()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->rows;
    }

    /**
     * Returns the number of rows of raw data
     *
     *
     * @return int
     */
    public function get_count()
    {
        clearos_profile(__METHOD__, __LINE__);

        return count($this->rows);
    }

    /**
     * Returns legend data summary info
     *
     *
     * @return int
     */
    public function get_totals()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->total;
    }

    /**
     * Reindexes an associative array using groupkeys as the new indexes.
     *
     * @param array $array
     * @param array $groupkeys keys to "promote" to indexes
     *
     * @return array
     */
    static public function group_by($array,$groupkeys=NULL,$group=FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $groupkey = array_shift($groupkeys);

        if (is_null($groupkey)) {
            $hits = 0;
            $mb = 0;
            foreach ($array as $value) {
                if (is_array($value)) {
                    if (isset($value['hits'])) {
                        $hits += $value['hits'];
                    } else {//count values
                        $hits += 1;
                    }

                    if (isset($value['mb'])) {
                        $mb += $value['mb'];
                    }
                }
            }

            if ($group) {
                return $array = array_merge($array,array('hits'=> $hits,'mb'=>$mb));
            } else {
                return array('hits'=> $hits,'mb'=>$mb);
            }
        }

        $results = array();
        foreach ($array as $row) {
            if (! isset($row[$groupkey])) {
                continue;
            }

            $key = $row[$groupkey];
            unset($row[$groupkey]);

            if (is_numeric($key)) {
                // numeric keys don't play well w/array_merge, so "fix" it
                $key .= "_";
            }

            if (is_array($key)) {
                continue;
            } else {
                $results[$key][] = $row;
            }
        }

        foreach ($results as $key => $rows) {
            $results = array_merge($results,array($key => self::GroupBy($rows,$groupkeys,$group)));
            $results = array_merge_recursive($results,array($groupkey => $key));
        }

        $hits = 0;
        $mb = 0;
        foreach ($results as $value) {
            if (is_array($value)) {
                if (isset($value['hits'])) {
                    $hits += $value['hits'];
                }

                if (isset($value['mb'])) {
                    $mb += $value['mb'];
                }
            }
        }

        return array_merge($results,array('hits'=> $hits,'mb'=>$mb));
    }

    /**
     * Flattens grouped data into an array of delimited lines
     *
     * @param array $array
     * @param array $groupkeys
     *
     * @return array
     */
    static public function flatten_groups($array,$groupkeys=NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $groupkey = array_shift($groupkeys);

        if (is_null($groupkey)) {
            if (is_array($array)) {
                $results = array();
                foreach ($array as $key => $value) {
                    if (is_array($value)) {
                        $val = current($value);

                        while ($val !== FALSE) {
                            if (is_array($val)) {
                                $results[] = $key.'|'.implode("|",$val);
                            }

                            $val = next($value);
                        }
                    }
                }

                return array_filter($results);
            }

            return NULL;
        }

        if (is_array($array)) {
            $results = array();
            foreach ($array as $key => $rows) {
                $results[$key] = self::FlattenGroups($rows,$groupkeys);
            }

            return array_filter($results);
        }

        return NULL;
    }

    /**
     * Diagnostic Data Dump
     *
     * @param mixed $fields list of fields to dump.  Can be listed individually or as an array.
     *
     * @return void
     */
    public function dump($fields=NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($fields)) {
            $fields = func_get_args();

            if (empty($fields)) {
                $fields = array('default');
            }
        }

        print("<div align='left'><pre>");
        foreach ($fields as $field) {
            switch (strtolower($field)) {

            case 'all':
                print_r($this);
                break;

            case 'headers':
                print("\nHEADERS:");
                print_r($this->rowheader);
                break;

            case 'legenddata':
                print("\nLEGENDDATA:");
                print_r($this->legenddata);
                break;

            case 'rows':
                print("\nROWS:");
                print_r($this->rows);
                break;

            case 'chartdata':
                print("\nCHARTDATA:");
                print_r($this->chartdata);
                break;

            case 'chartkeys':
                print("\nCHARTKEYS:");
                print_r($this->chartkeys);
                break;

            default:
                print("\nHEADERS:");
                print_r($this->rowheader);
                print("\nROWS:");
                print_r($this->rows);
            }
        }

        print("</pre></div>");
    }

    /**
     * Pad Chartdata.
     * If keys are non-consectutive, then missing keys are added and their value set to zero
     *
     * @param array $values chartdata with chartkey
     * @param array $keys chartkey
     * @param string $padtype one of day|month|hour
     *
     * @return void
     */
    private function _pad(&$values,&$keys,$padtype)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($padtype == 'days')||($padtype == 'months')) {
            $start = min($keys);
            $stop = max($keys);
            $parts = explode("-",$stop);
            $c = count($parts);

            if ($c == 2) {
                $parts[] = 1;
                $stop = strtotime(implode("-",array_reverse($parts)));
                $parts = explode("-",$start);
                $parts[] = 1;
                $date = strtotime(implode("-",array_reverse($parts)));
            } else {
                $stop = strtotime(implode("-",array_reverse($parts)));
                $parts = explode("-",$start);
                $date = strtotime(implode("-",array_reverse($parts)));
            }

            $values = current($values);
            $label = array_shift($values);
            $data = array_combine($keys,$values);
            $pad = array();

            if ($c == 2) {
                while ($date <= $stop) {
                    $pad[] = date("Y-m",$date);
                    $date += date("t",$date) * 86400;
                }
            } else {
                while ($date <= $stop) {
                    $pad[] = date("Y-m-d",$date);
                    $date += 86400;
                }
            }

            $pad = array_flip($pad);
            $keys = array_keys($pad);
            foreach ($keys as $key) {
                if (array_key_exists($key,$data)) {
                    $pad[$key] = $data[$key];
                } else {
                    $pad[$key] = 0;
                    //update the legendata too
                    $fields = array_fill(0,2,0);
                    $this->legenddata = array_merge($this->legenddata,array($key => $fields));
                    krsort($this->legenddata);
                }
            }

            if ($c == 2) {
                $keys = array_map('ShortMon',$keys);
            } else {
                $keys = array_map('ShortDay',$keys);
            }

            $keys = array_reverse($keys);
            $pad = array_reverse($pad);

            if (count($this->slice)>1) {
                $slice = $this->slice;
                $keys = array_slice($keys,$slice[0],$slice[1]);
                $pad = array_slice($pad,$slice[0],$slice[1]);
                $this->legenddata = array_slice($this->legenddata,$slice[0],$slice[1]);
            }

            array_unshift($pad,$label);
            $values = array(array_values($pad));
        } else {

            if ($padtype == 'hours') {
                if (count($values)==1) {
                    $values = current($values);
                }

                $label = array_shift($values);
                $label = LOCALE_LANG_HOURS;
                $data = array_combine($keys,$values);
                $values = array();
                $keys = array();

                for ($h=0;$h<24;$h++) {
                    $hh = str_pad($h,2,'0',STR_PAD_LEFT).'_';
                    $keys[]=$hh;

                    if (array_key_exists($hh,$data)) {
                        $values[] = $data[$hh];
                    } else {
                        $values[] = 0;
                    }
                }

                $keys = array_reverse($keys);
                $values = array_reverse($values);
                array_unshift($values,$label);
                $values = array($values);
            }
        }
    }

    /**
     * Uses strnatcasecmp to sort an array based upon values of a specific subkey.
     *
     * @param array $array
     * @param mixed $subkey string if sorting an associative array, otherwise numeric.
     * @param boolean $asc toggle ASC/DESC sortting. Defaults to ASC (TRUE)
     */
    private function _sort_by_subkey(&$array, $subkey, $asc = TRUE)
    {
/*
FIXME: deprecated PHP stuff in here
        if (is_numeric($subkey)) {
            if ($asc) {
                uasort(&$array, create_function('$a,$b','return strnatcasecmp($a['.$subkey.'], $b['.$subkey.']);'));
            } else {
                uasort(&$array, create_function('$a,$b','return strnatcasecmp($b['.$subkey.'], $a['.$subkey.']);'));
            }
        } else {
            if ($asc) {
                uasort(&$array, create_function('$a,$b','return strnatcasecmp($a["'.$subkey.'"], $b["'.$subkey.'"]);'));
            } else {
                uasort(&$array, create_function('$a,$b','return strnatcasecmp($b["'.$subkey.'"], $a["'.$subkey.'"]);'));
            }

        }
*/
    }

}

///////////////////////////////////////////////////////////////////////////////
// C A L L B A C K   F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////
/**
 * Callback Functions for ProxyReports and ProxyReportDataObject.
 *
 * @category   Apps
 * @package    
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps//
 * @copyright  2006-2011 ClearFoundation
 */
/**
 * @access private
 */
function ShortDay($date)
{
    list($year,$mon,$day) = explode("-",$date);
    return date(ProxyReport::DATE_DAY_SHORT,mktime(0,0,0,$mon,$day,$year));
}

/**
 * @access private
 */
function ShortMon($date)
{
    list($year,$mon) = explode("-",$date);
    return date(ProxyReport::DATE_MON_SHORT,mktime(0,0,0,$mon,1,$year));
}

/**
 * @access private
 */
function FixNumericKey($v)
{
    return preg_replace('/_$/','',$v);
}

/**
 * @access private
 */
function ExtractDay($v)
{
    return $v['day'];
}

/**
 * @access private
 */
function ExtractMonths($v)
{
    return $v['months'];
}

/**
 * @access private
 */
function ExtractYears($v)
{
    return $v['years'];
}

/**
 * @access private
 */
function ExtractCacheHits($v)
{
    return $v['cachehits'];
}

/**
 * @access private
 */
function ExtractDiff($v)
{
    return $v['diff'];
}

/**
 * @access private
 */
function ExtractDomains($v)
{
    return $v['domain'];
}

