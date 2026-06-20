<?php
    error_reporting(E_ERROR);
    require_once __DIR__ . '/auth_guard.php';

    $page_title = "Investment Report";
    include("header.php");
    include_once("db_connect.php");

    // $search_query = "select month, excepted, actual from monthly_investment";
//
    $db = new db();
//
//     $rs = $db->query($search_query);
//     if (!$rs) {
//         // TODO: handle error
//         echo "Sorry, unable to genrate result at this time. Please try later";
//     }
//
//     $series_data = '';
//     $series_data2 = '';
//
//     $series_values = array();
//     $series_values2 = array();
//     $avg_amount_data_array = array();
//
//     $previous_amount = 360000;
//     $total_amount = 0;
//     $i = 0;
//     while ($rs_row = mysqli_fetch_object($rs)) {
//         if ($i == 0) {
//             $starts_from = strtotime($rs_row->month);
//             list($y, $m, $d) = explode(',', date('Y, m, d', $starts_from));
//             $m -= 1;
//             $starts_from_str = 'Date.UTC(' . $y . ', ' . $m . ', ' . $d . ')';
//         }
//
//         $i++;
//         $montly_added_amount = $rs_row->actual - $previous_amount;
//         $previous_amount = $rs_row->actual;
//
//         $series_values['Actual'][] = "[".strtotime($rs_row->month . " + 2 day")."000, $rs_row->actual]";
//         $series_values['Excepted'][] = "[".strtotime($rs_row->month . " + 2 day")."000, $rs_row->excepted]";
//         $series_values2['Added'][] = "[".strtotime($rs_row->month . " + 2 day")."000, $montly_added_amount]";
//
//         $total_amount += $montly_added_amount;
//         $avg_amount = $total_amount/$i;
//         $avg_amount_data_array[] = $avg_amount;
//     }
//
//     /*
//     for ($a = 0; $a < $i; $a++) {
//         $avg_amount_data_array[] = $avg_amount;
//     }
//     */
//     $avg_amount_str = "[". implode(',', $avg_amount_data_array) ."]";
//
//     foreach ($series_values as $series=>$svalues) {
//         $series_data .= "{";
//         $series_data .= "name: '$series Investment',";
//         $series_data .= "data: [" . implode(',', $svalues) . "]";
//         $series_data .= "},";
//     }
//
//     foreach ($series_values2 as $series=>$svalues) {
//         $series_data2 .= "{ type: 'column',";
//         $series_data2 .= "name: '$series Investment',";
//         $series_data2 .= "data: [" . implode(',', $svalues) . "]";
//         $series_data2 .= "},";
//     }

    // showing Userwise and schme wise total investments
    $userwise_data_query = "SELECT u.name as name, sum(deposite_amount) as deposite_amount FROM `accounts` a INNER JOIN acc_users u on u.id = a.name where u.is_active = 1 and a.is_active = 1 group by a.name";

    $schemewise_data_query = "SELECT s.scheme_name as scheme_name, sum(deposite_amount) as deposite_amount FROM `accounts` a INNER JOIN deposite_schemes s on s.id = a.deposite_scheme where s.is_active = 1 and a.is_active = 1 group by a.deposite_scheme";

    $userwise_data_rs = $db->query($userwise_data_query);
    $schemewise_data_rs = $db->query($schemewise_data_query);

    $userwise_data_result = "";
    while($rs_row = mysqli_fetch_object($userwise_data_rs)) {
        $username = explode(" ", $rs_row->name);
        $username_short = $username[0] . " " . $username[2];
        $userwise_data_result .=  "{name: '$username_short', y: $rs_row->deposite_amount, drilldown: '$rs_row->scheme_name'},";
    }

    $schemewise_data_result = "";
    while($rs_row = mysqli_fetch_object($schemewise_data_rs)) {
        $username = explode(" ", $rs_row->name);
        $username_short = $username[0] . " " . $username[2];
        $schemewise_data_result .=  "{name: '$rs_row->scheme_name', y: $rs_row->deposite_amount, drilldown: '$username_short'},";
    }
    
    
    $get_result_rs = $db->query("SELECT name, sum(deposite_amount) as deposite_amount FROM scheme_wise_details_active group by name");
    $userwise_drilldown_agg = "";
    $userwise_drilldown_details = "";
    while($rs_row = mysqli_fetch_object($get_result_rs)) {
        $userwise_drilldown_agg .= "{name: '$rs_row->name', y: $rs_row->deposite_amount, drilldown: '$rs_row->name'},";
        
        $get_result_rs2 = $db->select("SELECT scheme_name, deposite_amount FROM scheme_wise_details_active where name = ?", "s", [$rs_row->name]) ;
        $userwise_drilldown_details .= "{name: '$rs_row->name', id: '$rs_row->name', data:[";
        while($rs_row2 = mysqli_fetch_object($get_result_rs2)) {
            $userwise_drilldown_details .= "['$rs_row2->scheme_name', $rs_row2->deposite_amount],";
        }
        $userwise_drilldown_details .= "]},";
    }
    
?>

<script type="text/javascript">
    /**
    javascript function for formating numbers (indian style like, 10,00,000)
    */
    function thousandSeparator(numValue) {
        // convert value to string first
        var numValue = numValue.toString()
        nStr = numValue.replace(/,/g,'');
        nStr += '';
        x = nStr.split('.');
        x1 = x[0];
        x2 = x.length > 1 ? '.' + x[1].slice(0,2) : '';

        var rgx = /(\d+)(\d{3})/;

        while (rgx.test(x1)) {
           x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return  x1+x2;
    }

    $(function () {
    var chart;
    $(document).ready(function() {
        // chart1 = new Highcharts.Chart({
//             chart: {
//                 renderTo: 'result_chart',
//                 type: 'spline',
//                 zoomType: 'x',
//                 animation: true,
//                 // width: 625,
//                 height: 600,
//             },
//             title: {
//                 text: null,
//             },
//             xAxis: [{
//                 type: 'datetime',
//                 title: {text: "Month"},
//             }],
//             yAxis: {
//                 title: {
//                     text: 'Rupees (In Thousands)'
//                 },
//                 // min: 0,
//                 startOnTick: false,
//                 showFirstLable: false,
//                 lineWidth: 1,
//                 gridLineWidth: 1,
//                 gridLineColor: "#DDDDDD",
//                 gridLineDashStyle: 'dot',
//             },
//             plotOptions: {
//                 spline: {
//                     marker: {
//                         enabled: false,
//                         states: {
//                             hover: {
//                                 enabled: true,
//                                 radius: 5
//                             }
//                         }
//                     },
//                     shadow: false,
//                     states: {
//                         hover: {
//                             lineWidth: 2
//                         }
//                     },
//                     animation: false
//                 }
//             },
//             tooltip: {
//                 /*formatter: function() {
//                         return '<b>'+ this.series.name +'</b><br/>'+
//                         Highcharts.dateFormat('%b, %Y', this.x) +': '+ thousandSeparator(this.y) +' Rs';
//                 },*/
//                 //crosshairs: false,
//                 crosshairs: [true, true],
//                 shared: true
//             },
//             credits: {
//                 text:""
//             },
//            series: [<?php // echo $series_data ?>]
//         });

        // chart2 = new Highcharts.Chart({
//             chart: {
//                 renderTo: 'result_chart2',
//                 //type: 'column',
//                 zoomType: 'x',
//                 animation: true,
//                 // width: 100,
//                 height: 600
//             },
//             title: {
//                 text: null,
//             },
//             xAxis: [{
//                 type: 'datetime',
//                 title: {text: "Month"},
//             }],
//             yAxis: {
//                 title: {
//                     text: 'Rupees (In Thousands)'
//                 },
//                 // min: 0,
//                 startOnTick: false,
//                 showFirstLable: false,
//                 lineWidth: 1,
//                 gridLineWidth: 1,
//                 gridLineColor: "#DDDDDD",
//                 gridLineDashStyle: 'dot',
//                 //max: 250000,
//             },
//             tooltip: {
//                 formatter: function() {
//                         return '<b>'+ this.series.name +'</b><br/>'+
//                         Highcharts.dateFormat('%b, %Y', this.x) +': '+ thousandSeparator(this.y) +' Rs';
//                 },
//                 crosshairs: [true, true]
//             },
//             plotOptions: {
//                 line: {
//                     marker: {
//                         enabled: false,
//                         states: {
//                             hover: {
//                                 enabled: true,
//                                 radius: 5
//                             }
//                         }
//                     },
//                     shadow: false,
//                     states: {
//                         hover: {
//                             lineWidth: 2
//                         }
//                     },
//                     animation: false
//                 }
//             },
//             credits: {
//                 text:""
//             },
//             series: [
//                 <?php echo $series_data2 ?>
//                 {
//                     type: 'spline',
//                     name: 'Average',
//                     pointInterval: 24 * 3600 * 1000*30.5,
//                     pointStart: <?php echo $starts_from_str ?>,
//                     data: <?php echo $avg_amount_str ?>,
//                     tooltip: {
//                         formatter: function() {
//                             return '<b>'+ this.series.name +'</b><br/>' + thousandSeparator(this.y) +' Rs';
//                         },
//                     },
//                     marker: {
//                         enabled: false
//                     }
//                 },
//             ]
//         });
    
        Highcharts.setOptions({
        lang: {
            thousandsSep: ','
        }
    });
        
        chart3 = new Highcharts.Chart({
            chart: {
                renderTo: 'result_chart3',
                type: 'pie',
                zoomType: 'x',
                animation: true
                // width: 100,
                // height: 300
            },
            title: {
                text: null,
            },
            lang: {
                    thousandsSep: ','
            },
            tooltip: {
                pointFormat: '{point.percentage:.2f} %<br/>{point.y:,.0f} Rs</b>',
                percentageDecimals: 2
            },
            plotOptions: {
                series: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false,
                    },
                    showInLegend: false
                }
            },
            credits: {
                text:""
            },
            series: [
                {
                    type: "pie",
                    name: 'Users',
                    colorByPoint: true,
                    data: [<?php echo $userwise_drilldown_agg ?>],
                },
            ],
            drilldown: {
                series: [
                    <?php echo $userwise_drilldown_details ?>
                ]
            }
        });
    
        chart4 = new Highcharts.Chart({
            chart: {
                renderTo: 'result_chart4',
                zoomType: 'x',
                animation: true
                //height: 300
            },
            title: {
                text: null,
            },
            lang: {
                    thousandsSep: ','
            },
            tooltip: {
                pointFormat: '{point.percentage:.2f} %<br/>{point.y:,.0f} Rs</b>',
                percentageDecimals: 2
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false,
                    },
                    showInLegend: true
                }
            },
            credits: {
                text:""
            },
            series: [{
                    type: 'pie',
                    name: 'Schemewise Details',
                    data: [<?php echo $schemewise_data_result ?>],
            }]
        });
    });
});

</script>
<h2>Monthly Investment Analysis Report</h2>
<div id="charts">
<!-- <div id="result_chart">
    <em>Rendering result, please wait!!!</em>
</div>
<div id="result_chart2">
    <em>Rendering result, please wait!!!</em>
</div> -->
<div id="result_chart_group">
<div id="result_chart3">
    <em>Rendering result, please wait!!!</em>
</div>
<div id="result_chart4">
    <em>Rendering result, please wait!!!</em>
</div>
</div> <!--  END of div result_chart_group -->
<div style="clear:both"></div>
</div>

<?php
    include("footer.php");
?>
