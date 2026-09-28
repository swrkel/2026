(function () {
    'use strict';

    var SVG_NS = 'http://www.w3.org/2000/svg';
    var nextGaugeId = 1;

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function polar(cx, cy, radius, angleDegrees) {
        var radians = angleDegrees * Math.PI / 180;
        return {
            x: cx + radius * Math.cos(radians),
            y: cy + radius * Math.sin(radians)
        };
    }

    /*
     * Google Gauge's dial runs from about 135deg (0, lower-left) through
     * 270deg (top) to 405deg/45deg (100, lower-right). Convert a tank percentage to that
     * visual angle while keeping the data itself unchanged.
     */
    function valueAngle(value) {
        return 135 + (clamp(value, 0, 100) * 2.7);
    }

    function arcPath(cx, cy, radius, startAngle, endAngle) {
        var start = polar(cx, cy, radius, startAngle);
        var end = polar(cx, cy, radius, endAngle);
        var sweep = Math.abs(endAngle - startAngle);
        var largeArc = sweep > 180 ? 1 : 0;

        return [
            'M', start.x.toFixed(3), start.y.toFixed(3),
            'A', radius, radius, 0, largeArc, 1, end.x.toFixed(3), end.y.toFixed(3)
        ].join(' ');
    }

    function createSvgElement(name, attributes, text) {
        var node = document.createElementNS(SVG_NS, name);
        Object.keys(attributes || {}).forEach(function (key) {
            node.setAttribute(key, attributes[key]);
        });
        if (typeof text !== 'undefined') {
            node.textContent = text;
        }
        return node;
    }

    function appendStop(gradient, offset, colour, opacity) {
        gradient.appendChild(createSvgElement('stop', {
            offset: offset,
            'stop-color': colour,
            'stop-opacity': typeof opacity === 'undefined' ? 1 : opacity
        }));
    }

    function addDefs(svg, id) {
        var defs = createSvgElement('defs');

        var bezel = createSvgElement('linearGradient', {
            id: 'pgd-bezel-' + id,
            x1: '0%', y1: '0%', x2: '100%', y2: '100%'
        });
        appendStop(bezel, '0%', '#f8f8f8');
        appendStop(bezel, '24%', '#cfcfcf');
        appendStop(bezel, '50%', '#eeeeee');
        appendStop(bezel, '78%', '#bdbdbd');
        appendStop(bezel, '100%', '#ececec');
        defs.appendChild(bezel);

        var face = createSvgElement('radialGradient', {
            id: 'pgd-face-' + id,
            cx: '48%', cy: '42%', r: '67%'
        });
        appendStop(face, '0%', '#ffffff');
        appendStop(face, '78%', '#fafafa');
        appendStop(face, '100%', '#eeeeee');
        defs.appendChild(face);

        var hub = createSvgElement('radialGradient', {
            id: 'pgd-hub-' + id,
            cx: '38%', cy: '30%', r: '70%'
        });
        appendStop(hub, '0%', '#62a1f4');
        appendStop(hub, '100%', '#3f7fd9');
        defs.appendChild(hub);

        var shadow = createSvgElement('filter', {
            id: 'pgd-shadow-' + id,
            x: '-20%', y: '-20%', width: '140%', height: '140%'
        });
        shadow.appendChild(createSvgElement('feDropShadow', {
            dx: '0', dy: '1.4', stdDeviation: '1.5',
            'flood-color': '#000000', 'flood-opacity': '0.20'
        }));
        defs.appendChild(shadow);

        svg.appendChild(defs);
    }

    function addDialBase(svg, id, cx, cy) {
        svg.appendChild(createSvgElement('circle', {
            cx: cx, cy: cy, r: 124,
            fill: 'url(#pgd-bezel-' + id + ')',
            stroke: '#5a5a5a', 'stroke-width': 1.6,
            filter: 'url(#pgd-shadow-' + id + ')'
        }));

        svg.appendChild(createSvgElement('circle', {
            cx: cx, cy: cy, r: 112,
            fill: '#ededed', stroke: '#c5c5c5', 'stroke-width': 2
        }));

        svg.appendChild(createSvgElement('circle', {
            cx: cx, cy: cy, r: 103,
            fill: 'url(#pgd-face-' + id + ')',
            stroke: '#ffffff', 'stroke-width': 1.5
        }));
    }

    function addColouredRanges(svg, cx, cy) {
        var rangeRadius = 91;
        var width = 24;

        svg.appendChild(createSvgElement('path', {
            d: arcPath(cx, cy, rangeRadius, valueAngle(0), valueAngle(35)),
            fill: 'none', stroke: '#e83b0b', 'stroke-width': width,
            'stroke-linecap': 'butt'
        }));

        svg.appendChild(createSvgElement('path', {
            d: arcPath(cx, cy, rangeRadius, valueAngle(35), valueAngle(70)),
            fill: 'none', stroke: '#ff9900', 'stroke-width': width,
            'stroke-linecap': 'butt'
        }));
    }

    function addTicks(svg, cx, cy) {
        var totalTicks = 20; // 5 minor intervals between each 25-point quarter.

        for (var i = 0; i <= totalTicks; i += 1) {
            var value = i * 5;
            var angle = valueAngle(value);
            var isMajor = value % 25 === 0;
            var startRadius = isMajor ? 78 : 83;
            var endRadius = 99;
            var start = polar(cx, cy, startRadius, angle);
            var end = polar(cx, cy, endRadius, angle);

            svg.appendChild(createSvgElement('line', {
                x1: start.x.toFixed(2), y1: start.y.toFixed(2),
                x2: end.x.toFixed(2), y2: end.y.toFixed(2),
                stroke: isMajor ? '#2f2f2f' : '#707070',
                'stroke-width': isMajor ? 3 : 1.4
            }));
        }
    }

    function addScaleLabels(svg, cx, cy) {
        var zero = polar(cx, cy, 75, valueAngle(0));
        var hundred = polar(cx, cy, 75, valueAngle(100));

        svg.appendChild(createSvgElement('text', {
            x: (zero.x + 9).toFixed(2), y: (zero.y - 2).toFixed(2),
            fill: '#252525', 'font-size': 15, 'font-family': 'Arial, sans-serif',
            'text-anchor': 'middle'
        }, '0'));

        svg.appendChild(createSvgElement('text', {
            x: (hundred.x - 10).toFixed(2), y: (hundred.y - 2).toFixed(2),
            fill: '#252525', 'font-size': 15, 'font-family': 'Arial, sans-serif',
            'text-anchor': 'middle'
        }, '100'));
    }

    function addNeedle(svg, id, cx, cy, value) {
        var angle = valueAngle(value);
        var radians = angle * Math.PI / 180;
        var tip = polar(cx, cy, 92, angle);
        var back = polar(cx, cy, 18, angle + 180);
        var perpendicularX = -Math.sin(radians);
        var perpendicularY = Math.cos(radians);
        var halfWidth = 4.2;

        var p1 = {
            x: back.x + perpendicularX * halfWidth,
            y: back.y + perpendicularY * halfWidth
        };
        var p2 = {
            x: tip.x,
            y: tip.y
        };
        var p3 = {
            x: back.x - perpendicularX * halfWidth,
            y: back.y - perpendicularY * halfWidth
        };

        svg.appendChild(createSvgElement('polygon', {
            points: [
                p1.x.toFixed(2) + ',' + p1.y.toFixed(2),
                p2.x.toFixed(2) + ',' + p2.y.toFixed(2),
                p3.x.toFixed(2) + ',' + p3.y.toFixed(2)
            ].join(' '),
            fill: '#e76545', stroke: '#b84129', 'stroke-width': 1.6,
            'stroke-linejoin': 'round',
            filter: 'url(#pgd-shadow-' + id + ')'
        }));

        svg.appendChild(createSvgElement('circle', {
            cx: cx, cy: cy, r: 16.5,
            fill: 'url(#pgd-hub-' + id + ')',
            stroke: '#777777', 'stroke-width': 1.5
        }));
    }

    function addValue(svg, cx, cy, value) {
        svg.appendChild(createSvgElement('text', {
            x: cx, y: cy + 94,
            fill: '#111111', 'font-size': 27, 'font-family': 'Arial, sans-serif',
            'font-weight': '400', 'text-anchor': 'middle'
        }, String(Math.round(value))));
    }

    function renderGauge(gauge) {
        var value = parseFloat(gauge.getAttribute('data-value'));
        if (!Number.isFinite(value)) {
            value = 0;
        }
        value = clamp(value, 0, 100);

        var id = nextGaugeId++;
        var cx = 160;
        var cy = 132;
        var svg = createSvgElement('svg', {
            viewBox: '0 0 320 285',
            'aria-hidden': 'true',
            focusable: 'false'
        });

        addDefs(svg, id);
        addDialBase(svg, id, cx, cy);
        addColouredRanges(svg, cx, cy);
        addTicks(svg, cx, cy);
        addScaleLabels(svg, cx, cy);
        addNeedle(svg, id, cx, cy, value);
        addValue(svg, cx, cy, value);

        gauge.appendChild(svg);
        gauge.classList.add('is-ready');
    }

    function init() {
        var gauges = document.querySelectorAll('.pgd-gauge');
        Array.prototype.forEach.call(gauges, renderGauge);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
