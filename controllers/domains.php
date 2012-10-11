<?php

/**
 * Domains report controller.
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

require_once clearos_app_base('reports') . '/controllers/report_controller.php';

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Domains report controller.
 *
 * @category   Apps
 * @package    Proxy_Report
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/proxy_report/
 */

class Domains extends Report_Controller
{
    /**
     * Constructor.
     */

    function __construct()
    {
        // Load translations
        //------------------

        $this->lang->load('proxy_report');

        // App coordinates
        //----------------

        $report['app'] = 'proxy_report';
        $report['library'] = 'Proxy_Report';
        $report['report'] = 'domains';
        $report['method'] = 'get_domain_data';

        // Translations
        //-------------

        $report['title'] = lang('proxy_report_domain_summary');
        $report['headers'] = array(
            lang('proxy_report_domain'),
            lang('proxy_report_hits'),
            lang('proxy_report_size')
        );

        // Load report
        //------------

        parent::__construct($report);
    }
}
