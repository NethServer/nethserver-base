
// Dashboard.js

var series = [];
var old_values = [];
var refresh_time = 2;
var refresh_alerts_time = 5;


$(document).ready(function () {
    initTimeout();
    initAlertsTimeout();

    $('#disk_chart').jqChart({
        title: { text: '' },
        border: { lineWidth: 0 },
        legend: { title: '' },
        //background: background,
        series: [
            {
                type: 'pie',
                labels: {
                    stringFormat: '%.1f%%',
                    valueType: 'percentage',
                    font: '15px sans-serif',
                    fillStyle: 'white'
                },
                data: [['system', 5],
                       ['ibay', 35],
                       ['home', 22],
                       ['free', 38]]
            }
        ]
    });
});

function refreshNetworkTrafficChart() {
    $('#network_chart').jqChart({
        series: [
            {
                type: 'line',
                data: series['net_rx'],
                markers: { size: 5 },
                title: 'Rx'
            },
            {
                type: 'line',
                data: series['net_tx'],
                markers: { size: 5 },
                title: 'Tx'
            }
        ],
        border: {
            visible: false,
            lineWidth: 0,
        },
        legend: {
            visible: true,
            location: 'top',
            margin: 5,
            background: '#eee',
        },
        axes: [
            {
                location: 'left',
                lineWidth: 0,
                strokeStyle: 'red',
                labels: { stringFormat: '%2g Kb/s' }
            }
        ]
    });
}

function refreshMonitorNetworkTrafficChart() {
    $('#monitor_network_chart').jqChart({
        series: [
            {
                type: 'line',
                data: series['net_rx'],
                markers: { size: 5 },
                title: 'Rx'
            },
            {
                type: 'line',
                data: series['net_tx'],
                markers: { size: 5 },
                title: 'Tx'
            }
        ],
        border: {
            visible: false,
            lineWidth: 0,
        },
        legend: {
            visible: true,
            location: 'top',
            margin: 5,
            background: '#eee',
        },
        axes: [
            {
                location: 'left',
                lineWidth: 0,
                strokeStyle: 'red',
                labels: { stringFormat: '%2g Kb/s' }
            }
        ]
    });
}

function initTimeout() {
    setTimeout("notifyTimeout()", refresh_time*1000);
}

function initAlertsTimeout() {
    setTimeout("notifyAlertsTimeout()", refresh_alerts_time*1000);
}

function notifyTimeout() {
    updateSerieData();
    initTimeout();
}

function notifyAlertsTimeout() {
    updateAlerts();
    //    initAlertsTimeout();
}

function updateSerieData() {
    // make Ajax call
    $.ajax({
        url : 'Status/Dashboard/getNetworkTraffic',
        dataType : 'json',
        data : {},
        success : function(res) {
            var myres = res[0][1];
            
            updateSerieDiff('net_rx', parseFloat(myres['netstats']['eth0']['rx_bytes'])/refresh_time, 25);
            updateSerieDiff('net_tx', parseFloat(myres['netstats']['eth0']['tx_bytes'])/refresh_time, 25);

            // refresh charts
            
            refreshNetworkTrafficChart();
            refreshMonitorNetworkTrafficChart();
        }
    });
}

function updateSerieDiff(label, val, window_len) {
    old_values[label] = old_values[label] ? old_values[label] : val;
    if (!series[label]) series[label] = [];
    series[label].push([new Date(), (val-old_values[label])/1024.0]);
    if (series[label].length > window_len) series[label].shift();
    old_values[label] = val;
}

function updateAlerts() {
    $.ajax({
        url : 'Status/Dashboard/getActiveAlerts',
        dataType : 'json',
        data : {},
        success : function(res) {
            var myres = res[0][1];
            var alerts = myres['alerts'];

            var html = '';
            for(i = 0; i < alerts.length; i++) {
                html += '<tr>';
                html += '<td>' + alerts[i]['label'] + '</td>';
                html += '<td>' + alerts[i]['code'] + '</td>';
                html += '<td>' + alerts[i]['priority'] + '</td>';
                html += '<td>' + alerts[i]['modified'] + '</td>';
                html += '</tr>';
            }

            $('#active_alerts table tbody').get(0).innerHTML = html;
        }
    });
}
