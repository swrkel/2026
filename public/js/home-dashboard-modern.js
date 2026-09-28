(function ($, Chart) {
    'use strict';

    if (!$ || !Chart || !window.HomeDashboardConfig) {
        return;
    }

    var config = window.HomeDashboardConfig;
    var permissions = config.permissions || {};
    var chartInstances = {};
    var activeRequest = null;

    var currentMetricColors = [
        ['#8b3fe0', '#5126b7'],
        ['#48c85a', '#07868d'],
        ['#ff9a26', '#f05b00'],
        ['#ff5b48', '#c9142c']
    ];

    var previousMetricColors = [
        ['#8b3fe0', '#5126b7'],
        ['#48c85a', '#07868d'],
        ['#2f90ff', '#1e63e9'],
        ['#ff5b48', '#c9142c']
    ];

    var paymentColors = ['#7d2bd1', '#35b657', '#ff8a19', '#ee2737'];
    var comparisonLabels = ['Purchases', 'Sales', 'Stocks', 'Expenses'];
    var paymentLabels = ['Cash', 'Credit cards', 'Credit sales', 'Shortages'];

    function toNumber(value) {
        var number = Number(value);
        return Number.isFinite(number) ? number : 0;
    }

    function safeArray(values, expectedLength) {
        var output = Array.isArray(values) ? values.map(toNumber) : [];
        while (output.length < expectedLength) {
            output.push(0);
        }
        return output.slice(0, expectedLength);
    }

    function formatAmount(value) {
        return toNumber(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function destroyChart(key) {
        if (chartInstances[key]) {
            chartInstances[key].destroy();
            chartInstances[key] = null;
        }
    }

    function setChartState(name, message, stateClass) {
        var $state = $('[data-chart-state="' + name + '"]');
        if (!$state.length) {
            return;
        }

        $state.removeClass('is-loading is-restricted is-empty');
        if (!message) {
            $state.attr('hidden', true).text('');
            return;
        }

        $state.addClass(stateClass || '').removeAttr('hidden').text(message);
    }

    function setAllChartStates(message, stateClass) {
        ['previous-bar', 'current-bar', 'current-payment', 'previous-payment'].forEach(function (name) {
            setChartState(name, message, stateClass);
        });
    }

    function createBarGradients(context, canvas, palette) {
        return (palette || currentMetricColors).map(function (colors) {
            var gradient = context.createLinearGradient(0, 0, 0, canvas.clientHeight || 260);
            gradient.addColorStop(0, colors[0]);
            gradient.addColorStop(1, colors[1]);
            return gradient;
        });
    }

    function renderBarChart(key, canvasId, values, allowed, scaleSuffix, palette) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) {
            return;
        }

        destroyChart(key);

        if (!allowed) {
            setChartState(key, (config.labels && config.labels.restricted) || 'Restricted', 'is-restricted');
            return;
        }

        setChartState(key, '', '');
        var context = canvas.getContext('2d');
        var chartValues = safeArray(values, comparisonLabels.length);
        var gradients = createBarGradients(context, canvas, palette);

        chartInstances[key] = new Chart(context, {
            type: 'bar',
            data: {
                labels: comparisonLabels,
                datasets: [{
                    label: 'Amount',
                    data: chartValues,
                    backgroundColor: gradients,
                    borderColor: (palette || currentMetricColors).map(function (colors) { return colors[1]; }),
                    borderWidth: 1,
                    maxBarThickness: 72
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 550,
                    easing: 'easeOutQuart'
                },
                layout: {
                    padding: {
                        left: 8,
                        right: 8,
                        top: 4,
                        bottom: 0
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                return comparisonLabels[context.dataIndex] + ': ' + formatAmount(context.parsed.y) + (scaleSuffix || '');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#202b48',
                            font: {
                                weight: 'bold'
                            },
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e3e8f0'
                        },
                        ticks: {
                            color: '#35415e',
                            callback: function (value) {
                                return toNumber(value).toLocaleString('en-US');
                            }
                        }
                    }
                }
            }
        });
    }

    function renderPaymentChart(key, canvasId, values, allowed) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) {
            return;
        }

        destroyChart(key);

        if (!allowed) {
            setChartState(key, (config.labels && config.labels.restricted) || 'Restricted', 'is-restricted');
            return;
        }

        setChartState(key, '', '');
        var chartValues = safeArray(values, paymentLabels.length);
        var total = chartValues.reduce(function (sum, value) {
            return sum + Math.abs(value);
        }, 0);

        if (total === 0) {
            chartValues = [0, 0, 0, 0];
        }

        chartInstances[key] = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: chartValues,
                    backgroundColor: paymentColors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverBorderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '52%',
                animation: {
                    duration: 600,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 18,
                            padding: 16,
                            color: '#202b48',
                            usePointStyle: false
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return (context.label || '') + ': ' + formatAmount(context.parsed);
                            }
                        }
                    }
                }
            }
        });
    }

    function readComparison(data) {
        if (data.comparison && Array.isArray(data.comparison.current) && Array.isArray(data.comparison.previous)) {
            return {
                current: safeArray(data.comparison.current, comparisonLabels.length),
                previous: safeArray(data.comparison.previous, comparisonLabels.length)
            };
        }

        var rows = [];
        try {
            rows = typeof data.layered === 'string' ? JSON.parse(data.layered) : (data.layered || []);
        } catch (error) {
            rows = [];
        }

        rows = rows.slice(0, comparisonLabels.length);
        return {
            current: safeArray(rows.map(function (row) { return row.This; }), comparisonLabels.length),
            previous: safeArray(rows.map(function (row) { return row.Last; }), comparisonLabels.length)
        };
    }

    function updateSummary(data) {
        if (!permissions.summary) {
            return;
        }

        $('#purchases').text(data.purchases || '0.00');
        $('#sales').text(data.sales || '0.00');
        $('#stocks').text(data.stocks || '0.00');
        $('#expenses').text(data.expenses || '0.00');
        $('#credit_given').text(data.credit_given || '0.00');
        $('#credit_received').text(data.credit_received || '0.00');
    }

    function setSummaryLoading() {
        if (!permissions.summary) {
            return;
        }
        $('.home-summary-value').html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
    }

    function updateTitles(data) {
        var currentTitle = data.current_title || $('#period_1 option:selected').text() || 'Current Period';
        var previousTitle = data.previous_title || 'Previous Period';

        $('#current-period-chart-title').text(currentTitle);
        $('#previous-period-chart-title').text(previousTitle);
        $('#current-payment-chart-title').text('Payment Methods (' + currentTitle + ')');
        $('#previous-payment-chart-title').text('Payment Methods (' + previousTitle + ')');
        $('#home-dashboard-date-label').text(data.date_label || '');
    }

    function renderDashboard(data) {
        var comparison = readComparison(data);
        var scaleSuffix = data.chart_scale_suffix || '';

        updateSummary(data);
        updateTitles(data);

        renderBarChart('previous-bar', 'home_previous_bar_chart', comparison.previous, permissions.previousGraph, scaleSuffix, previousMetricColors);
        renderBarChart('current-bar', 'home_current_bar_chart', comparison.current, permissions.currentGraph, scaleSuffix, currentMetricColors);
        renderPaymentChart('current-payment', 'home_current_payment_chart', data.pie_curr || data.pie_chart, permissions.currentPayments);
        renderPaymentChart('previous-payment', 'home_previous_payment_chart', data.pie_prev, permissions.previousPayments);
    }

    function showLoadError(xhr) {
        var message = (config.labels && config.labels.loadError) || 'Unable to load dashboard data.';
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }

        $('#home-dashboard-error').text(message).removeAttr('hidden');
        setAllChartStates(message, 'is-empty');
    }

    function loadDashboard() {
        var period = $('#period_1').val() || 'today';

        if (activeRequest && activeRequest.readyState !== 4) {
            activeRequest.abort();
        }

        $('#home-dashboard-error').attr('hidden', true).text('');
        setSummaryLoading();
        setAllChartStates('Loading dashboard data…', 'is-loading');

        activeRequest = $.ajax({
            url: config.dataUrl,
            method: 'GET',
            dataType: 'json',
            data: {
                filter: period,
                type: $('#category_filter').val(),
                location_id: $('#location_id').val()
            }
        }).done(function (data) {
            renderDashboard(data || {});
        }).fail(function (xhr, status) {
            if (status !== 'abort') {
                showLoadError(xhr);
            }
        });
    }

    $(function () {
        $('#period_1').val($('#period_1').val() || 'today');
        $(document)
            .off('change.homeDashboard', '#category_filter, #period_1, #location_id')
            .on('change.homeDashboard', '#category_filter, #period_1, #location_id', loadDashboard);

        loadDashboard();
    });
})(window.jQuery, window.Chart);
