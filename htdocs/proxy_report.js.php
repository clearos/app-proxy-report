<?php

/**
 * Proxy and filter report javascript helper.
 *
 * @category   Apps
 * @package    Proxy_Report
 * @subpackage Javascript
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
// FIXME: remove "received" and "delivered"?

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
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

    // Translations
    //-------------

    lang_received = '<?php echo lang("proxy_report_received"); ?>';
    lang_delivered = '<?php echo lang("proxy_report_delivered"); ?>';
    lang_forwarded = '<?php echo lang("proxy_report_forwarded"); ?>';
    lang_deferred = '<?php echo lang("proxy_report_deferred"); ?>';
    lang_bounced = '<?php echo lang("proxy_report_bounced"); ?>';
    lang_rejected = '<?php echo lang("proxy_report_rejected"); ?>';
    lang_held = '<?php echo lang("proxy_report_held"); ?>';
    lang_discarded = '<?php echo lang("proxy_report_discarded"); ?>';

    // Events
    //-------

    $('#range').click(function(){
        generate_report('senders', $('#range').val());
    });

    // Main
    //-----

    if ($('#proxy_report_dashboard').length != 0) 
        generate_dashboard_report();

    if ($('#proxy_report_domains').length != 0) 
        generate_report('domains', 'today');

    if ($('#proxy_report_recipients').length != 0) 
        generate_report('recipients', 'today');
});

/**
 * Ajax call for standard report.
 */

function generate_report(type, range) {
    $.ajax({
        url: '/app/proxy_report/' + type + '/get_data/' + range + '/10',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            create_pie_chart(type, payload);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(generate_report, 3000);
        }
    });
}

/**
 * Ajax call for dashboard report.
 */

function generate_dashboard_report() {
    $.ajax({
        url: '/app/proxy_report/dashboard/get_data',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            create_pie_chart(payload);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(generate_dashboard_report, 3000);
        }
    });
}

/**
 * Generates standard report.
 */

function create_chart(type, payload) {
    var data = new Array();
    var chart_id = 'proxy_report_' + type;

    for (var item_info in payload) {
        key = item_info;
        value = payload[key];
        data.push([key, value]);
    }

    var chart = jQuery.jqplot (chart_id, [data],
    {
        legend: { show: true, location: 'e' },
        seriesDefaults: {
            renderer: jQuery.jqplot.PieRenderer,
            shadow: true,
            rendererOptions: {
                showDataLabels: true,
                sliceMargin: 8,
                dataLabels: 'value'
            }
        },
        grid: {
            gridLineColor: 'transparent',
            background: 'transparent',
            borderColor: 'transparent',
            shadow: false
        }
    });

    chart.redraw();
}

/**
 * Generates dashboard report.
 */

function create_pie_chart(type, payload) {

    data = Array();
    hits = Array();
    size = Array();

    var chart_id = 'proxy_report_' + type;

    for (var day_info in payload) {
        if (payload.hasOwnProperty(day_info)) {
            hits.push([payload[day_info].hits, day_info]);
        }
    }

    var chart = jQuery.jqplot (chart_id, [hits],
    {
        animate: !$.jqplot.use_excanvas,
        seriesDefaults: {
            renderer: jQuery.jqplot.BarRenderer,
            rendererOptions: {
                barDirection: 'horizontal'
            },
            pointLabels: { show: true, location: 'e', edgeTolerance: -15 },
        },
        axes: {
            yaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
            }
        }
    });

    chart.redraw();
}

// vim: ts=4 syntax=javascript
