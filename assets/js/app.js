// Jobhunt front end behaviour. Uses Alpine.js (menu, apply form) and Axios (requests).
// 1. Save and unsave jobs without reloading (Axios, talks to save_job.php)
// 2. Auto-submit filters on the jobs page
// 3. Apply form component for Alpine.js (see applyForm below)
// 4. Count-up numbers on the home page
// 5. Small toast messages

document.addEventListener('DOMContentLoaded', function () {
    initSaveButtons();
    initAutoSubmit();
    initCountUp();
    initConfirmButtons();
});

/* ---------- Toast ---------- */

function showToast(message) {
    var region = document.getElementById('toast-region');
    if (!region) return;
    var toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    region.appendChild(toast);
    setTimeout(function () {
        toast.classList.add('leaving');
        setTimeout(function () { toast.remove(); }, 260);
    }, 2400);
}

/* ---------- Save jobs ---------- */

function initSaveButtons() {
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-save]');
        if (!button) return;

        event.preventDefault();
        var jobId = button.getAttribute('data-save');
        var body = new FormData();
        body.append('job_id', jobId);

        button.disabled = true;

        axios.post('save_job.php', body, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (response) { return response.data; })
            .then(function (data) {
                if (!data.success) {
                    showToast(data.error || 'Could not save this job.');
                    return;
                }

                // Update every save button for this job (card and detail page).
                document.querySelectorAll('[data-save="' + jobId + '"]').forEach(function (b) {
                    b.classList.toggle('is-saved', data.saved);
                    b.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
                    b.setAttribute('aria-label', data.saved ? 'Remove from saved jobs' : 'Save this job');
                    b.classList.remove('pop');
                    void b.offsetWidth;
                    b.classList.add('pop');
                });

                var pill = document.getElementById('saved-count');
                if (pill) {
                    pill.textContent = data.count;
                    pill.classList.remove('bump');
                    void pill.offsetWidth;
                    pill.classList.add('bump');
                }

                showToast(data.saved ? 'Job saved' : 'Removed from saved jobs');

                // On the saved page, remove the card after unsaving.
                if (!data.saved && document.querySelector('[data-saved-page]')) {
                    var card = button.closest('.job-card');
                    if (card) {
                        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(20px)';
                        setTimeout(function () {
                            card.remove();
                            if (!document.querySelector('.job-card')) {
                                window.location.reload();
                            }
                        }, 260);
                    }
                }
            })
            .catch(function () {
                showToast('Something went wrong. Please try again.');
            })
            .finally(function () {
                button.disabled = false;
            });
    });
}

/* ---------- Filters ---------- */

function initAutoSubmit() {
    document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
        form.querySelectorAll('select, input[type="radio"]').forEach(function (field) {
            field.addEventListener('change', function () { form.submit(); });
        });
    });

    document.querySelectorAll('select[data-sort]').forEach(function (select) {
        select.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('sort', select.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    });
}

/* ---------- Apply form (Alpine.js component, requests sent with Axios) ---------- */

function applyForm() {
    return {
        sending: false,
        sent: false,
        errors: [],
        company: '',
        submit: function (event) {
            var self = this;
            self.errors = [];
            self.sending = true;
            axios.post(event.target.action, new FormData(event.target), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(function (response) {
                    var data = response.data;
                    if (data.success) {
                        self.sent = true;
                        self.company = data.company;
                        showToast('Application sent');
                    } else {
                        self.errors = data.errors || ['Something went wrong.'];
                    }
                })
                .catch(function () {
                    self.errors = ['Could not send your application. Check your connection and try again.'];
                })
                .finally(function () { self.sending = false; });
        }
    };
}

/* ---------- Count-up numbers ---------- */

function initCountUp() {
    var numbers = document.querySelectorAll('[data-count]');
    if (!numbers.length) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    numbers.forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target) || target <= 0) return;
        var start = null;
        var duration = 900;

        function step(timestamp) {
            if (start === null) start = timestamp;
            var progress = Math.min((timestamp - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased);
            if (progress < 1) requestAnimationFrame(step);
        }
        el.textContent = '0';
        requestAnimationFrame(step);
    });
}

/* ---------- Confirm before risky actions (admin) ---------- */

function initConfirmButtons() {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });
}
