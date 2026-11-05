/**
 * CMS Dashboard JS
 * Made by vzy
 */

document.addEventListener('DOMContentLoaded', function () {
    // User menu toggle (click support for mobile)
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            this.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            userMenu.classList.remove('open');
        });
    }

    // Sidebar toggle mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.style.display = window.innerWidth <= 992 ? 'inline-flex' : 'none';
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
        window.addEventListener('resize', function () {
            sidebarToggle.style.display = window.innerWidth <= 992 ? 'inline-flex' : 'none';
            if (window.innerWidth > 992) sidebar.classList.remove('open');
        });
    }

    // Posts dropdown in the sidebar
    document.querySelectorAll('.nav-dropdown-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const dropdown = this.closest('.nav-dropdown');
            const open = dropdown.classList.toggle('open');
            this.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    // Auto-hide flash alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.4s, transform 0.4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Yakin ingin menghapus?')) {
                e.preventDefault();
            }
        });
    });

    // Tabs
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = this.dataset.tab;
            const parent = this.closest('.card, .modal-body, body');
            parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const content = document.getElementById(target);
            if (content) content.classList.add('active');
        });
    });

    // Slug auto-generate
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function () {
            if (!slugInput.dataset.manual) {
                slugInput.value = slugify(this.value);
            }
        });
        slugInput.addEventListener('input', function () {
            this.dataset.manual = '1';
        });
    }

    // Rich text editor for posts
    const editor = document.getElementById('postContentEditor');
    const contentInput = document.getElementById('postContent');
    const editorForm = editor ? editor.closest('form') : null;
    if (editor && contentInput) {
        document.querySelectorAll('.editor-toolbar [data-command]').forEach(function (control) {
            control.addEventListener('click', function () {
                editor.focus();
                if (this.dataset.command === 'createLink') {
                    const url = prompt('Masukkan URL link:');
                    if (url) document.execCommand('createLink', false, url);
                } else {
                    document.execCommand(this.dataset.command, false, this.tagName === 'SELECT' ? this.value : null);
                }
            });
        });

        const uploadPostImage = function (file, callback) {
            const csrf = document.querySelector('input[name="csrf_token"]');
            const data = new FormData();
            data.append('action', 'upload_post_image');
            data.append('csrf_token', csrf ? csrf.value : '');
            data.append('image', file);
            fetch('posts.php', { method: 'POST', body: data })
                .then(response => response.json())
                .then(result => result.success ? callback(result) : alert(result.message || 'Upload gagal.'))
                .catch(() => alert('Upload gagal. Periksa koneksi atau ukuran file.'));
        };

        const editorImageButton = document.querySelector('.editor-upload-image');
        if (editorImageButton) {
            editorImageButton.addEventListener('click', function () {
                const input = document.createElement('input');
                input.type = 'file'; input.accept = 'image/*';
                input.addEventListener('change', function () {
                    if (this.files[0]) uploadPostImage(this.files[0], function (result) {
                        editor.focus();
                        document.execCommand('insertHTML', false, '<img src="' + result.url + '" alt="">');
                    });
                });
                input.click();
            });
        }

        const insertShareButton = document.querySelector('.editor-insert-share');
        if (insertShareButton) {
            insertShareButton.addEventListener('click', function () {
                editor.focus();
                document.execCommand('insertHTML', false, '<p>[social_share]</p>');
            });
        }

        const featuredButton = document.getElementById('uploadFeaturedImage');
        const featuredFile = document.getElementById('featuredImageFile');
        const featuredInput = document.getElementById('featuredImage');
        if (featuredButton && featuredFile && featuredInput) {
            featuredButton.addEventListener('click', () => featuredFile.click());
            featuredFile.addEventListener('change', function () {
                if (this.files[0]) uploadPostImage(this.files[0], result => { featuredInput.value = result.path; });
            });
        }
        if (editorForm) editorForm.addEventListener('submit', () => { contentInput.value = editor.innerHTML; });
    }

    // Modal helpers
    window.openModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('active');
    };
    window.closeModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('active');
    };
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

    // Dashboard clock and weather widget
    const clock = document.getElementById('dashboardClock');
    const dateLabel = document.getElementById('dashboardDate');
    if (clock && dateLabel) {
        const dateFormatter = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
        const timeFormatter = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        const updateDateTime = function () {
            const now = new Date();
            clock.textContent = timeFormatter.format(now);
            dateLabel.textContent = dateFormatter.format(now);
        };
        updateDateTime();
        setInterval(updateDateTime, 1000);
    }

    const weatherTemp = document.getElementById('weatherTemp');
    const weatherCondition = document.getElementById('weatherCondition');
    const weatherLocation = document.getElementById('weatherLocation');
    const weatherIcon = document.getElementById('weatherIcon');
    if (weatherTemp && weatherCondition && weatherLocation) {
        const weatherCodes = {
            0: ['Cerah', 'fa-sun'], 1: ['Cerah berawan', 'fa-cloud-sun'], 2: ['Berawan sebagian', 'fa-cloud-sun'],
            3: ['Berawan', 'fa-cloud'], 45: ['Berkabut', 'fa-smog'], 48: ['Berkabut', 'fa-smog'],
            51: ['Gerimis ringan', 'fa-cloud-rain'], 53: ['Gerimis', 'fa-cloud-rain'], 55: ['Gerimis lebat', 'fa-cloud-rain'],
            61: ['Hujan ringan', 'fa-cloud-showers-heavy'], 63: ['Hujan', 'fa-cloud-showers-heavy'], 65: ['Hujan lebat', 'fa-cloud-showers-heavy'],
            80: ['Hujan lokal', 'fa-cloud-rain'], 81: ['Hujan lokal', 'fa-cloud-rain'], 82: ['Hujan deras', 'fa-cloud-showers-heavy'],
            95: ['Badai petir', 'fa-cloud-bolt'], 96: ['Badai petir', 'fa-cloud-bolt'], 99: ['Badai petir', 'fa-cloud-bolt']
        };
        const loadWeather = function (latitude, longitude, label) {
            const url = 'https://api.open-meteo.com/v1/forecast?latitude=' + latitude + '&longitude=' + longitude + '&current=temperature_2m,weather_code&timezone=auto';
            fetch(url).then(response => response.json()).then(data => {
                const current = data.current;
                const detail = weatherCodes[current.weather_code] || ['Kondisi tidak diketahui', 'fa-cloud'];
                weatherTemp.textContent = Math.round(current.temperature_2m) + '°C';
                weatherCondition.textContent = detail[0];
                weatherLocation.textContent = label;
                if (weatherIcon) weatherIcon.innerHTML = '<i class="fas ' + detail[1] + '"></i>';
            }).catch(() => { weatherCondition.textContent = 'Cuaca tidak tersedia'; });
        };
        const fallback = function () { loadWeather(-6.2088, 106.8456, 'Jakarta, Indonesia'); };
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                position => loadWeather(position.coords.latitude, position.coords.longitude, 'Lokasi Anda'),
                fallback,
                { timeout: 4000 }
            );
        } else fallback();
    }
});

function slugify(text) {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-');
}
