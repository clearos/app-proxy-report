<?php

/**
 * Proxy report class.
 *
 * @category   apps
 * @package    proxy-report
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2013 ClearFoundation
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

clearos_load_language('base');
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
 * @category   apps
 * @package    proxy-report
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/proxy_report/
 */

class Proxy_Report extends Database_Report
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const DEFAULT_DB_CACHE_TIME = 1200;

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
     * Returns traffic data.
     *
     * @param string $range range information
     *
     * @return array traffic summary data
     * @throws Engine_Exception
     */

    public function get_traffic_data($range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($range === 'today') || ($range === 'yesterday')) {
            $timespan = 'DATE_FORMAT(timestamp, \'%Y-%m-%d %H:00:00\') as timestamp ';
            $group_by = 'DATE(timestamp), HOUR(timestamp)';
        } else {
            $timespan = 'DATE_FORMAT(timestamp, \'%Y-%m-%d\') as timestamp ';
            $group_by = 'DATE(timestamp)';
        }

        // Create temporary tables
        //------------------------

        $create_options['range'] = $range;

        $sql['table'] = 'hits';
        $sql['select'] = 'COUNT(`request`) AS `hits`, SUM(`bytes`)/1024/1024 AS `size`, COUNT(DISTINCT ip) as ips, ' . $timespan . ' ';
        $sql['from'] = 'proxy';
        $sql['where'] = 'base_domain IS NOT NULL';
        $sql['group_by'] = $group_by;

        $this->_create_temporary_table('proxy', $sql, $create_options);

        $sql = array();
        $sql['table'] = 'cache_hits';
        $sql['select'] = 'COUNT(`request`) AS `hits`, SUM(`bytes`)/1024/1024 AS `size`, ' . $timespan . ' ';
        $sql['from'] = 'proxy';
        $sql['where'] = 'cache_code LIKE \'%HIT%\'';
        $sql['group_by'] = $group_by;

        $this->_create_temporary_table('proxy', $sql, $create_options);

        $sql = array();
        $sql['table'] = 'warning_hits';
        $sql['select'] = 'COUNT(`request`) AS `hits`, SUM(`bytes`)/1024/1024 AS `size`, ' . $timespan . ' ';
        $sql['from'] = 'proxy';
        $sql['where'] = 'filter_code = 1100';
        $sql['group_by'] = $group_by;

        $this->_create_temporary_table('proxy', $sql, $create_options);

        // Get report data
        //----------------

        $sql = array();
        $sql['select'] = 'hits.timestamp, hits.hits, hits.size, hits.ips, ' .
            'warning_hits.hits as warning_hits, ' .
            'cache_hits.hits AS cache_hits, ' .
            '((cache_hits.hits*100)/hits.hits) AS hits_percent';
        $sql['from'] = 'hits';
        $sql['joins'] = 'LEFT JOIN cache_hits ON hits.timestamp=cache_hits.timestamp LEFT JOIN warning_hits ON hits.timestamp=warning_hits.timestamp';

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('traffic');

        foreach ($entries as $entry) {
            $report_data['data'][] = array(
                $entry['timestamp'],
                (int) $entry['size'],
                (int) $entry['hits'],
                (int) $entry['cache_hits'],
                (int) $entry['hits_percent'],
                (int) $entry['warning_hits'],
                (int) $entry['ips']
            );
        }

        return $report_data;
    }
    /**
     * Returns sites summary data.
     * 
     * @param string $range range information
     *
     * @return array sites summary data
     * @throws Engine_Exception
     */

    public function get_sites_data($range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $sql['select'] = 'base_domain, COUNT(request) AS hits, SUM(bytes)/1024/1024 AS size, ' .
            'SUM(filter_malware) AS malware, SUM(filter_block) AS block, SUM(filter_blacklist) AS blacklist';
        $sql['from'] = 'proxy';
        $sql['where'] = 'base_domain IS NOT NULL';
        $sql['group_by'] = 'base_domain';
        $sql['order_by'] = 'size DESC';

        $options['range'] = $range;

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('top_sites');

        foreach ($entries as $entry) {
            $report_data['data'][] = array(
                $entry['base_domain'],
                (int) $entry['size'],
                (int) $entry['hits'],
                (int) $entry['malware'],
                (int) $entry['block'],
                (int) $entry['blacklist']
            );
        }

        return $report_data;
    }

    /**
     * Returns IP summary data.
     *
     * @param string $range range information
     *
     * @return array IP summary data
     * @throws Engine_Exception
     */

    public function get_ip_data($range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $sql['select'] = 'ip, COUNT(request) AS hits, SUM(bytes)/1024/1024 AS size, ' .
            'SUM(filter_malware) AS malware, SUM(filter_block) AS block, SUM(filter_blacklist) AS blacklist';
        $sql['from'] = 'proxy';
        $sql['where'] = 'base_domain IS NOT NULL';
        $sql['group_by'] = 'ip';
        $sql['order_by'] = 'size DESC';

        $options['range'] = $range;

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('top_ips');

        foreach ($entries as $entry) {
            $report_data['data'][] = array(
                $entry['ip'],
                (int) $entry['size'],
                (int) $entry['hits'],
                (int) $entry['malware'],
                (int) $entry['block'],
                (int) $entry['blacklist']
            );
        }

        return $report_data;
    }

    /**
     * Returns IP detail data.
     *
     * @param string $ip    IP address
     * @param string $range range information
     *
     * @return array IP summary data
     * @throws Engine_Exception
     */

    public function get_ip_details_data($ip, $range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $ip = sprintf("%u", ip2long($ip)); // TODO: not IPv6 friendly

        $sql['select'] = 'base_domain, COUNT(request) AS hits, SUM(bytes)/1024/1024 AS size, ' .
            'SUM(filter_malware) AS malware, SUM(filter_block) AS block, SUM(filter_blacklist) AS blacklist';
        $sql['from'] = 'proxy';
        $sql['where'] = 'ip = \'' . $ip . '\'';
        $sql['group_by'] = 'base_domain';
        $sql['order_by'] = 'size DESC';

        $options['range'] = $range;

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('ips');

        foreach ($entries as $entry) {
            $report_data['data'][] = array(
                $entry['base_domain'],
                (int) $entry['size'],
                (int) $entry['hits'],
                (int) $entry['malware'],
                (int) $entry['block'],
                (int) $entry['blacklist']
            );
        }

        return $report_data;
    }

    /**
     * Returns IP summary data.
     *
     * @param string $range range information
     *
     * @return array IP summary data
     * @throws Engine_Exception
     */

    public function get_user_data($range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $sql['select'] = 'username, COUNT(request) AS hits, SUM(bytes)/1024/1024 AS size, ' .
            'SUM(filter_malware) AS malware, SUM(filter_block) AS block, SUM(filter_blacklist) AS blacklist';
        $sql['from'] = 'proxy';
        $sql['where'] = 'base_domain IS NOT NULL';
        $sql['group_by'] = 'username';
        $sql['order_by'] = 'size DESC';

        $options['range'] = $range;

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('top_users');

        foreach ($entries as $entry) {
            $report_data['data'][] = array(
                $entry['username'],
                (int) $entry['size'],
                (int) $entry['hits'],
                (int) $entry['malware'],
                (int) $entry['block'],
                (int) $entry['blacklist']
            );
        }

        return $report_data;
    }

    /**
     * Returns user detail data.
     *
     * @param string $username username
     * @param string $range    range information
     *
     * @return array user summary data
     * @throws Engine_Exception
     */

    public function get_user_details_data($username, $range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $sql['select'] = 'base_domain, COUNT(request) AS hits, SUM(bytes)/1024/1024 AS size, ' .
            'SUM(filter_malware) AS malware, SUM(filter_block) AS block, SUM(filter_blacklist) AS blacklist';
        $sql['from'] = 'proxy';
        $sql['where'] = 'username = \'' . $username . '\'';
        $sql['group_by'] = 'base_domain';
        $sql['order_by'] = 'size DESC';

        $options['range'] = $range;

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('users');

        foreach ($entries as $entry) {
            $report_data['data'][] = array(
                $entry['base_domain'],
                (int) $entry['size'],
                (int) $entry['hits'],
                (int) $entry['malware'],
                (int) $entry['block'],
                (int) $entry['blacklist']
            );
        }

        return $report_data;
    }

    /**
     * Returns malware detail data.
     *
     * @param string $range range information
     *
     * @return array malware summary data
     * @throws Engine_Exception
     */

    public function get_malware_details_data($range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        $sql['select'] = 'ip, username, base_domain, filter_detail, request, bytes, timestamp';
        $sql['from'] = 'proxy';
        $sql['where'] = 'filter_malware = 1';
        $sql['order_by'] = 'timestamp ASC';

        $options['range'] = $range;

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('malware_details');

        foreach ($entries as $entry)
            $report_data['data'][] = array(
                $entry['timestamp'],
                (int) $entry['bytes'],
                $entry['ip'],
                $entry['username'],
                $entry['filter_detail'],
                $entry['base_domain']
            );

        return $report_data;
    }

    /**
     * Returns warning data.
     *
     * @param string $range range information
     *
     * @return array warning summary data
     * @throws Engine_Exception
     */

    public function get_warning_data($range = 'today')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get report data
        //----------------

        if (($range === 'today') || ($range === 'yesterday')) {
            $timespan = 'DATE_FORMAT(timestamp, \'%Y-%m-%d %H:00:00\') as timestamp ';
            $group_by = 'DATE(timestamp), HOUR(timestamp)';
        } else {
            $timespan = 'DATE_FORMAT(timestamp, \'%Y-%m-%d\') as timestamp ';
            $group_by = 'DATE(timestamp)';
        }

        $sql['select'] = "SUM(filter_malware) AS malware, SUM(filter_block) AS block, SUM(filter_blacklist) AS blacklist, $timespan ";
        $sql['from'] = 'proxy';
        $sql['where'] = '(filter_malware = 1) || (filter_block = 1) || (filter_blacklist = 1)';
        $sql['group_by'] = $group_by;
        $sql['order_by'] = 'timestamp DESC';

        $options['range'] = $range;

        $entries = $this->_run_query('proxy', $sql, $options);

        // Format report data
        //-------------------

        $report_data = $this->_get_data_info('warnings');

        foreach ($entries as $entry) {
            $report_data['data'][] = array(
                $entry['timestamp'],
                (int) $entry['malware'],
                (int) $entry['block'],
                (int) $entry['blacklist']
            );
        }

        return $report_data;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Report engine definition.
     *
     * @return array report definition
     */
    
    protected function _get_definition()
    {
        // Traffic Summary
        //----------------

        $reports['traffic'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_traffic_summary'),
            'api_data' => 'get_traffic_data',
            'chart_type' => 'timeline',
            'sort_column' => 0,
            'format' => array(
                'series_label' => lang('base_megabytes'),
                'baseline_format' => 'timestamp'
            ),
            'headers' => array(
                lang('base_date'),
                lang('proxy_report_size'),
                lang('proxy_report_hits'),
                lang('proxy_report_cache_hits'),
                lang('proxy_report_cache_percentage'),
                lang('proxy_report_warnings'),
                lang('proxy_report_ips')
            ),
            'types' => array(
                'timestamp',
                'int',
                'int',
                'int',
                'int',
                'int',
                'int'
            ),
        );

        // Warning Summary
        //----------------

        $reports['warnings'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_warning_summary'),
            'api_data' => 'get_warning_data',
            'chart_type' => 'bar',
            'sort_column' => 0,
            'format' => array(
                'series_label' => lang('proxy_report_incidents'),
                'baseline_format' => 'timestamp'
            ),
            'headers' => array(
                lang('base_date'),
                lang('proxy_report_malware'),
                lang('proxy_report_blocked'),
                lang('proxy_report_blacklist')
            ),
            'types' => array(
                'timestamp',
                'int',
                'int',
                'int'
            ),
        );

        // Top Users
        //----------

        $reports['top_users'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_top_users'),
            'api_data' => 'get_user_data',
            'chart_type' => 'pie',
            'format' => array(
                'baseline_data_points' => 10,
            ),
            'headers' => array(
                lang('base_username'),
                lang('proxy_report_size'),
                lang('proxy_report_hits'),
                lang('proxy_report_malware'),
                lang('proxy_report_blocked'),
                lang('proxy_report_blacklist')
            ),
            'types' => array(
                'string',
                'int',
                'int',
                'int',
                'int',
                'int'
            ),
            'detail' => array(
                '/app/proxy_report/users/index/',
                NULL,
                NULL 
            ),
        );

        // Top IPs
        //--------

        $reports['top_ips'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_top_ips'),
            'api_data' => 'get_ip_data',
            'chart_type' => 'pie',
            'format' => array(
                'baseline_data_points' => 10,
            ),
            'headers' => array(
                lang('network_ip'),
                lang('proxy_report_size'),
                lang('proxy_report_hits'),
                lang('proxy_report_malware'),
                lang('proxy_report_blocked'),
                lang('proxy_report_blacklist')
            ),
            'types' => array(
                'ip',
                'int',
                'int',
                'int',
                'int',
                'int'
            ),
            'detail' => array(
                '/app/proxy_report/ips/index/',
                NULL,
                NULL 
            ),
        );

        // Top Sites
        //----------

        $reports['top_sites'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_top_sites'),
            'api_data' => 'get_sites_data',
            'chart_type' => 'horizontal_bar',
            'format' => array(
                'series_label' => lang('base_megabytes'),
                'series_format' => '%d',
                'baseline_data_points' => 10,
            ),
            'headers' => array(
                lang('proxy_report_site'),
                lang('proxy_report_size'),
                lang('proxy_report_hits'),
                lang('proxy_report_malware'),
                lang('proxy_report_blocked'),
                lang('proxy_report_blacklist')
            ),
            'types' => array(
                'string',
                'int',
                'int',
                'int',
                'int',
                'int'
            ),
        );

        // Malware Details
        //----------------

        $reports['malware_details'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_malware_details'),
            'api_data' => 'get_malware_details_data',
            'chart_type' => 'bar',
            'format' => array(
                'baseline_data_points' => 10,
            ),
            'headers' => array(
                lang('base_date'),
                lang('proxy_report_bytes'),
                lang('network_ip'),
                lang('base_username'),
                lang('proxy_report_malware'),
                lang('proxy_report_site')
            ),
            'types' => array(
                'timestamp',
                'int',
                'ip',
                'string',
                'string',
                'string'
            ),
            'chart_series' => array(
                NULL,
                TRUE,
                FALSE,
                FALSE,
                FALSE,
                FALSE
            ),
        );

        // IP Detail
        //----------

        $reports['ips'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_ip_summary'),
            'api_data' => 'get_ip_details_data',
            'chart_type' => 'bar',
            'is_detail' => TRUE,
            'format' => array(
                'series_label' => lang('proxy_report_size'),
                'baseline_data_points' => 10,
            ),
            'headers' => array(
                lang('proxy_report_site'),
                lang('proxy_report_size'),
                lang('proxy_report_hits'),
                lang('proxy_report_malware'),
                lang('proxy_report_blocked'),
                lang('proxy_report_blacklist')
            ),
            'types' => array(
                'string',
                'int',
                'int',
                'int',
                'int',
                'int'
            ),
        );

        // User Detail
        //------------

        $reports['users'] = array(
            'app' => 'proxy_report',
            'title' => lang('proxy_report_user_summary'),
            'api_data' => 'get_user_details_data',
            'chart_type' => 'bar',
            'is_detail' => TRUE,
            'format' => array(
                'series_label' => lang('proxy_report_size'),
                'baseline_data_points' => 10,
            ),
            'headers' => array(
                lang('proxy_report_site'),
                lang('proxy_report_size'),
                lang('proxy_report_hits'),
                lang('proxy_report_malware'),
                lang('proxy_report_blocked'),
                lang('proxy_report_blacklist')
            ),
            'types' => array(
                'string',
                'int',
                'int',
                'int',
                'int',
                'int'
            ),
        );

        // Done
        //-----

        return $reports;
    }
}
