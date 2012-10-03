<?php

/**
 * Common hits report view.
 *
 * @category   ClearOS
 * @package    Proxy_Report
 * @subpackage Views
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('proxy_report');
$this->lang->load('reports');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

    $buttons = array(
        form_submit_update('submit-form'),
        anchor_custom('/app/proxy_report', lang('base_return_to_summary'))
    );


///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('proxy_report/' . $type . '/settings');
echo form_header(lang('reports_report_settings'));

echo field_dropdown('range', $ranges, $range, lang('reports_date_range'));
echo field_button_set($buttons);

echo form_footer();
echo form_close();

///////////////////////////////////////////////////////////////////////////////
// Chart
///////////////////////////////////////////////////////////////////////////////

echo chart_widget($title, "<div id='proxy_report_$type'></div>");

///////////////////////////////////////////////////////////////////////////////
// Data table
///////////////////////////////////////////////////////////////////////////////

// Anchors
//--------

$anchors = array();

// Headers
//--------

$headers = array(
    $key,
    lang('proxy_report_hits'),
    lang('proxy_report_size')
);

// Items
//------

foreach ($data as $key => $details) {
    $item['details'] = array(
        $key,
        $details['hits'],
        $details['size']
    );

    $items[] = $item;
}

// Data table
//-----------

echo summary_table(
    lang('reports_report_data'),
    $anchors,
    $headers,
    $items,
    array(
        'no_action' => TRUE,
        'sort-default-col' => 1,
        'sort-default-dir' => 'desc'
    )
);
