(function () {
    'use strict';

    function loadCalendar() {
        var el = document.getElementById('bs_advanced_calendar');
        if (!el) return;

        var url = el.getAttribute('data-events-url');
        var params = new URLSearchParams();
        var location = document.getElementById('bs_scheduler_location');
        var staff = document.getElementById('bs_scheduler_staff');
        if (location && location.value) params.append('business_location_id', location.value);
        if (staff && staff.value) params.append('staff_id', staff.value);

        el.innerHTML = '<div class="bs-calendar-loading">Loading appointments...</div>';
        fetch(url + '?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.json(); })
            .then(function (events) {
                if (!events.length) {
                    el.innerHTML = '<div class="bs-calendar-empty">No appointments found for the selected filters.</div>';
                    return;
                }
                var html = '<div class="bs-calendar-list">';
                events.forEach(function (ev) {
                    html += '<a class="bs-calendar-item bs-status-' + (ev.status || 'booked') + '" href="' + ev.url + '">' +
                        '<strong>' + ev.title + '</strong><span>' + ev.start + '</span></a>';
                });
                html += '</div>';
                el.innerHTML = html;
            })
            .catch(function () {
                el.innerHTML = '<div class="bs-calendar-error">Unable to load appointments.</div>';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadCalendar();
        var refresh = document.getElementById('bs_scheduler_refresh');
        if (refresh) refresh.addEventListener('click', loadCalendar);
    });
})();
