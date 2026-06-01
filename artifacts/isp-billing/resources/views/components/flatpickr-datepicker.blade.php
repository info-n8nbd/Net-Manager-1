<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.flatpickr-input[readonly] { background-color: #fff !important; cursor: pointer; }
.flatpickr-calendar {
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    width: 307px !important;
}
.flatpickr-calendar .flatpickr-months {
    padding: 4px 0;
}
.flatpickr-calendar .flatpickr-month {
    height: 34px;
}
.flatpickr-calendar .flatpickr-current-month {
    font-size: 13px;
    padding-top: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    width: 100%;
    left: 0;
}
.flatpickr-calendar .flatpickr-current-month .flatpickr-monthDropdown-months {
    font-size: 13px;
    appearance: auto;
    -webkit-appearance: auto;
    padding: 2px 4px;
    border: 1px solid #ddd;
    border-radius: 3px;
    background: #fff;
    cursor: pointer;
}
.flatpickr-calendar .flatpickr-current-month input.cur-year {
    font-size: 13px;
    border: 1px solid #ddd !important;
    border-radius: 3px;
    padding: 2px 4px;
    background: #fff;
    cursor: text;
    width: 70px;
}
.flatpickr-calendar .numInputWrapper {
    width: 75px;
}
.flatpickr-calendar .numInputWrapper span {
    display: block !important;
    opacity: 1 !important;
    width: 16px;
    border: 1px solid #ddd;
    background: #f8f9fa;
}
.flatpickr-calendar .numInputWrapper span:hover {
    background: #e9ecef;
}
.flatpickr-calendar .flatpickr-weekdays {
    height: 24px;
}
.flatpickr-calendar span.flatpickr-weekday {
    font-size: 11px;
}
.flatpickr-calendar .flatpickr-days {
    width: 307px !important;
}
.flatpickr-calendar .dayContainer {
    width: 307px !important;
    min-width: 307px !important;
    max-width: 307px !important;
}
.flatpickr-calendar .flatpickr-day {
    max-width: 40px;
    height: 32px;
    line-height: 32px;
    font-size: 12px;
}
.flatpickr-calendar .flatpickr-prev-month,
.flatpickr-calendar .flatpickr-next-month {
    padding: 4px 8px;
}
.flatpickr-calendar .flatpickr-prev-month svg,
.flatpickr-calendar .flatpickr-next-month svg {
    width: 12px;
    height: 12px;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function initFlatpickr(el, extraOpts) {
        var opts = {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: true,
            disableMobile: true
        };
        if (extraOpts) {
            for (var k in extraOpts) opts[k] = extraOpts[k];
        }
        flatpickr(el, opts);
    }

    document.querySelectorAll('input[type="date"]').forEach(function(el) {
        el.type = 'text';
        initFlatpickr(el);
    });

    document.querySelectorAll('.flatpickr-date').forEach(function(el) {
        var isDob = (el.name === 'date_of_birth' || el.name === 'dob');
        initFlatpickr(el, isDob ? { maxDate: 'today' } : {});
    });

    document.querySelectorAll('input[type="datetime-local"]').forEach(function(el) {
        el.type = 'text';
        flatpickr(el, {
            dateFormat: 'Y-m-d H:i',
            altInput: true,
            altFormat: 'd-m-Y h:i K',
            enableTime: true,
            allowInput: true,
            disableMobile: true,
            time_24hr: false
        });
    });
});
</script>
