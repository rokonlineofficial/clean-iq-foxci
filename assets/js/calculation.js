/**
 * Cleaning Booking Calculator - live quote.
 * Prices come from includes/pricing-config.json (window.CBC_PRICING), in integer cents,
 * exactly the same file the server uses to re-calculate the total on submit.
 */
(function (root) {
    'use strict';

    function toInt(value) {
        var n = parseInt(value, 10);
        return isNaN(n) || n < 0 ? 0 : n;
    }

    function calculateCents(cfg, s) {
        var total = 0;
        var svc = s.service && cfg.services[s.service] ? cfg.services[s.service] : null;
        var sqft = toInt(s.sqft);

        // Base: sqft x service rate (minimum base below the sqft threshold)
        if (svc && sqft > 0) {
            var base = sqft * svc.rate_cents;
            if (sqft < cfg.min_base.below_sqft) {
                base = Math.max(base, cfg.min_base.cents);
            }
            total += base;
        }

        total += toInt(s.bathrooms) * cfg.bathroom_cents;
        total += toInt(s.halfBaths) * cfg.half_bath_cents;
        total += toInt(s.kitchens) * cfg.kitchen_cents;

        var included = svc ? svc.included : [];
        var seen = {};
        (s.extras || []).forEach(function (name) {
            if (seen[name] || cfg.extras[name] === undefined) { return; }
            seen[name] = true;
            if (included.indexOf(name) === -1) {
                total += cfg.extras[name];
            }
        });

        if (s.laundry && cfg.laundry[s.laundry] !== undefined) {
            total += cfg.laundry[s.laundry];
        }

        return total;
    }

    function formatMoney(cents) {
        var c = Math.max(0, Math.round(cents));
        var dollars = Math.floor(c / 100);
        var rest = String(c % 100);
        if (rest.length < 2) { rest = '0' + rest; }
        return '$' + String(dollars).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '.' + rest;
    }

    // Exposed for testing / debugging.
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { calculateCents: calculateCents, formatMoney: formatMoney };
    } else {
        root.CBCCalc = { calculateCents: calculateCents, formatMoney: formatMoney };
    }

    if (typeof document === 'undefined') { return; }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('cbc-quote-calculator-form');
        var cfg = root.CBC_PRICING;
        if (!form || !cfg) { return; }

        var totalDisplay = document.getElementById('cbc-total-amount-display');
        var submitBtn = form.querySelector('.cbc-submit-button');

        function val(name) {
            var el = form.querySelector('[name="' + name + '"]');
            return el ? el.value : '';
        }

        function readState() {
            var extras = [];
            form.querySelectorAll('input[name="extras[]"]:checked').forEach(function (cb) {
                extras.push(cb.value);
            });
            var laundryEl = form.querySelector('input[name="laundry_service"]:checked');
            return {
                service: val('service_type'),
                sqft: val('sqft'),
                bathrooms: val('bathrooms'),
                halfBaths: val('half_baths'),
                kitchens: val('kitchens'),
                extras: extras,
                laundry: laundryEl ? laundryEl.value : ''
            };
        }

        function updateIncludedTags(service) {
            var included = cfg.services[service] ? cfg.services[service].included : [];
            form.querySelectorAll('.cbc-checkbox-item').forEach(function (label) {
                var input = label.querySelector('input[type="checkbox"]');
                var tag = label.querySelector('.cbc-included-tag');
                if (input && tag) {
                    tag.hidden = included.indexOf(input.value) === -1;
                }
            });
        }

        function update() {
            var state = readState();
            updateIncludedTags(state.service);
            if (totalDisplay) {
                totalDisplay.textContent = formatMoney(calculateCents(cfg, state));
            }
        }

        // Restore the customer's entries after a server-side validation error.
        function restoreOld() {
            var raw = form.getAttribute('data-cbc-old');
            if (!raw) { return; }
            var old;
            try { old = JSON.parse(raw); } catch (e) { return; }
            Object.keys(old).forEach(function (key) {
                var value = old[key];
                if (key === 'extras') {
                    form.querySelectorAll('input[name="extras[]"]').forEach(function (cb) {
                        cb.checked = Array.isArray(value) && value.indexOf(cb.value) !== -1;
                    });
                    return;
                }
                if (key === 'laundry_service') {
                    form.querySelectorAll('input[name="laundry_service"]').forEach(function (r) {
                        r.checked = (r.value === value);
                    });
                    return;
                }
                var el = form.querySelector('[name="' + key + '"]');
                if (el && el.type !== 'checkbox') { el.value = value; }
            });
        }

        // Date picker: no past dates (server re-checks).
        var dateInput = form.querySelector('[name="appointment_date"]');
        if (dateInput) {
            var now = new Date();
            var mm = String(now.getMonth() + 1).padStart(2, '0');
            var dd = String(now.getDate()).padStart(2, '0');
            dateInput.min = now.getFullYear() + '-' + mm + '-' + dd;
        }

        form.addEventListener('input', update);
        form.addEventListener('change', update);

        // Prevent double submissions.
        form.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
            }
        });
        window.addEventListener('pageshow', function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
            }
        });

        restoreOld();
        update();
    });
})(typeof window !== 'undefined' ? window : this);
