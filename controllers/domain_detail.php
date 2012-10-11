<?php

/**
 * Domain detail report controller.
 *
 * @category   Apps
 * @package    Proxy_Report
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/proxy_report/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require clearos_app_base('reports') . '/controllers/report_controller.php';

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Domain detail report controller.
 *
 * @category   Apps
 * @package    Proxy_Report
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/proxy_report/
 */

class Domain_Detail extends Report_Controller
{
    /**
     * Default report.
     *
     * @return view
     */

    function index($domain)
    {
        $this->_report('dashboard', $domain);
    }

    /**
     * Full report.
     *
     * @return view
     */

    function full()
    {
        $this->_report('full', $domain);
    }

    /**
     * Generic report method.
     *
     * @param string $type report type
     *
     * @return view
     */

    function _report($type, $domain)
    {
        // Load dependencies
        //------------------

        $this->lang->load('base');
        $this->lang->load('proxy_report');
        $this->load->library('proxy_report/Proxy_Report');

        // Handle range widget
        //--------------------

        parent::_handle_range();

        $data['range'] = $this->session->userdata('report_sr');
        $data['ranges'] = self::_get_summary_ranges();

$this->session->set_userdata('report_detail', $domain);

        // Define report parameters
        //-------------------------

        $data['app'] = 'proxy_report';
        $data['report'] = 'domain_detail';
        $data['title'] = lang('proxy_report_domain_details');
        $data['headers'] = array(
            lang('base_date'),
            lang('proxy_report_hits'),
            lang('proxy_report_size')
        );

        // Load views
        //-----------

        $options['javascript'] = array(clearos_app_htdocs('reports') . '/reports.js.php');
    
        if ($type === 'dashboard') {
            $view = 'reports/dashboard_report';
        } else {
            $view = 'reports/full_report';
            $options['type'] = MY_Page::TYPE_REPORT;
        }

        $this->page->view_form($view, $data, lang('proxy_report_domains'), $options);
    }

    /**
     * Report data.
     *
     * @return JSON report data
     */

    function get_data($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load dependencies
        //------------------

        $this->load->library('proxy_report/Proxy_Report');

        // Load data
        //----------

        try {
            $data = $this->proxy_report->get_domain_detail_data(
                $this->session->userdata('report_sr'),
                10,
                $this->session->userdata('report_detail')
            );
        } catch (Exception $e) {
            echo json_encode(array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }

        // Show data
        //----------

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($data);
    }
}
