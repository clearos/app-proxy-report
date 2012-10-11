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
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\reports_database\Database_Report as Database_Report;
use \clearos\apps\web_proxy\Squid as Squid;

clearos_load_library('base/Engine');
clearos_load_library('reports_database/Database_Report');
clearos_load_library('web_proxy/Squid');

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

class Proxy_Report extends Database_Report
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $http_codes = array();
    protected $filter_codes = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Proxy Report constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->filter_codes = array(
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

        $this->http_codes = array(
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
     * Returns domain summary data.
     *
     * @return array domain summary data
     * @throws Engine_Exception
     */

    public function get_domain_data($date_range = 'today', $records = NULL)
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
        $report_data['header'] = array(lang('network_domain'), lang('proxy_report_hits'), lang('proxy_report_size'));
        $report_data['type'] = array('string', 'int', 'int');

        foreach ($entries as $entry) 
            $report_data['data'][] = array($entry['domain'], (int) $entry['hits'], (int) $entry['size']);
            // FIXME $report_data['data'][] = array("<a href='/bob'>" . $entry['domain'] . "</a>", (int) $entry['hits'], (int) $entry['size']);

        return $report_data;
    }

    /**
     * Returns domain detail data.
     *
     * @return array domain summary data
     * @throws Engine_Exception
     */

    public function get_domain_detail_data($domain, $range = Report::RANGE_TODAY, $timespan = Report::INTERVAL_HOURLY)
    {
        clearos_profile(__METHOD__, __LINE__);

// FIXME
$timespan = 'daily';
$domain = 'www.google.ca';
$domain = 'www.facebook.com';

        // Get report data
        //----------------

        $sql['select'] = 'COUNT(request) AS hits, SUM(bytes)/1024 AS size';
        $sql['from'] = 'proxy';
        $sql['where'] = 'domain="' . $domain . '"';
        $sql['group_by'] = 'timespan';
        $sql['order_by'] = 'timespan';

        $entries = $this->_run_query($sql, $range, $timespan);

        // Format report data
        //-------------------

        $report_data = array();
        $report_data['header'] = array(lang('base_date'), lang('proxy_report_hits'), lang('proxy_report_size'));
        $report_data['type'] = array('date', 'int', 'int');

        foreach ($entries as $entry) 
            $report_data['data'][] = array($entry['timespan'], (int) $entry['hits'], (int) $entry['size']);

        return $report_data;
    }

    /**
     * Returns IP summary data.
     *
     * @return array ip summary data
     * @throws Engine_Exception
     */

    public function get_ip_data($date_range = 'today', $records = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $sql['select'] = 'ip, COUNT(request) AS hits, SUM(bytes)/1024/1024 AS size';
        $sql['from'] = 'proxy';
        $sql['where'] = 'domain IS NOT NULL';
        $sql['group_by'] = 'ip';
        $sql['order_by'] = 'hits DESC';

        $entries = $this->_run_query($sql, $date_range, $records);

        // Format report data
        //-------------------

        $report_data = array();
        $report_data['header'] = array(lang('network_ip'), lang('proxy_report_hits'), lang('proxy_report_size'));
        $report_data['type'] = array('ip', 'int', 'int');

        foreach ($entries as $entry)
            $report_data['data'][] = array($entry['ip'], (int) $entry['hits'], (int) $entry['size']);

        return $report_data;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
}
