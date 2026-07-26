/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Video Oynatıcı
 *  Tarayıcının varsayılan <video controls> arayüzü yerine
 *  kullanılan özel oynatıcı. Stiller: assets/css/vnl-player.css
 *
 *      var el = VNLPlayer.create({ src: "...", name: "klip.mp4", size: 4200000 });
 *      container.appendChild(el);
 *
 *  Seçenekler: src (zorunlu), name, size, download (varsayılan false),
 *  downloadUrl (varsayılan src).
 *
 *  Uygulama dili için VNLPlayer.setLabels({...}) çağrılır;
 *  varsayılan etiketler İngilizcedir.
 *  Authors: Voxnup Authors
 * ============================================================
 */
(function (window, document) {
    'use strict';

    var SPEEDS = [0.5, 0.75, 1, 1.25, 1.5, 2];
    var CONTROLS_HIDE_DELAY = 2500;
    var SEEK_STEP = 5;
    var VOLUME_STEP = 0.1;
    var STORAGE_VOLUME = 'vnl_player_volume';
    var STORAGE_MUTED = 'vnl_player_muted';

    var labels = {
        play: 'Play',
        pause: 'Pause',
        mute: 'Mute',
        unmute: 'Unmute',
        volume: 'Volume',
        seek: 'Seek',
        fullscreen: 'Fullscreen',
        exitFullscreen: 'Exit fullscreen',
        speed: 'Playback speed',
        pip: 'Miniplayer',
        error: 'The video could not be played.',
        download: 'Download'
    };

    // Aynı anda yalnızca bir video oynasın diye tutulan kayıt.
    var videos = [];

    /* ---------------- yardımcılar ---------------- */

    function clamp(value, min, max) {
        return value < min ? min : (value > max ? max : value);
    }

    function formatTime(seconds) {
        if (!isFinite(seconds) || seconds < 0) seconds = 0;
        var total = Math.floor(seconds);
        var s = total % 60;
        var m = Math.floor(total / 60) % 60;
        var h = Math.floor(total / 3600);
        var mm = (h > 0 && m < 10) ? '0' + m : String(m);
        return (h > 0 ? h + ':' : '') + mm + ':' + (s < 10 ? '0' + s : String(s));
    }

    function createEl(tag, className, attrs) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (attrs) {
            for (var key in attrs) {
                if (Object.prototype.hasOwnProperty.call(attrs, key)) node.setAttribute(key, attrs[key]);
            }
        }
        return node;
    }

    function createIcon(name) {
        return createEl('i', 'fa-solid ' + name);
    }

    function setIcon(node, name) {
        node.className = 'fa-solid ' + name;
    }

    function readableSize(bytes) {
        if (typeof bytes !== 'number' || !isFinite(bytes) || bytes < 0) return '';
        if (typeof window.convertBytesToReadableFormat === 'function') {
            return window.convertBytesToReadableFormat(bytes);
        }
        return Math.round(bytes / 1024) + ' KB';
    }

    function storageGet(key) {
        try { return window.localStorage.getItem(key); } catch (e) { return null; }
    }

    function storageSet(key, value) {
        try { window.localStorage.setItem(key, value); } catch (e) { /* özel mod */ }
    }

    function pauseOthers(current) {
        for (var i = videos.length - 1; i >= 0; i--) {
            var other = videos[i];
            if (!other.isConnected) {
                videos.splice(i, 1);
                continue;
            }
            if (other !== current && !other.paused) other.pause();
        }
    }

    function isFullscreen(node) {
        var active = document.fullscreenElement || document.webkitFullscreenElement;
        return active === node;
    }

    /* ---------------- oynatıcı ---------------- */

    function create(options) {
        if (!options || !options.src) return null;

        var src = options.src;
        var downloadUrl = options.downloadUrl || src;

        var root = createEl('div', 'vnl-player', { tabindex: '0' });

        var video = createEl('video', 'vnl-video', { preload: 'metadata' });
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        // #t=0.1 ilk kareyi poster olarak çizdirir; sunucu Range desteklediği için ucuzdur.
        video.src = src + '#t=0.1';

        // Sağ üst köşe: marka ve dosya adı.
        var topbar = createEl('div', 'vnl-topbar');
        var watermark = createEl('div', 'vnl-watermark', { 'aria-hidden': 'true' });
        watermark.innerHTML =
            '<svg viewBox="0 0 16 16" width="13" height="13">' +
            '<circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.4"></circle>' +
            '<path d="M6.4 4.8 11.2 8l-4.8 3.2z" fill="currentColor"></path>' +
            '</svg><span>Voxnup</span>';
        topbar.appendChild(watermark);

        if (options.name) {
            var title = createEl('div', 'vnl-title');
            title.textContent = options.name;
            var sizeText = readableSize(options.size);
            title.title = sizeText ? options.name + ' · ' + sizeText : options.name;
            topbar.appendChild(title);
        }

        var spinner = createEl('div', 'vnl-spinner');
        spinner.hidden = true;

        var bigPlay = createEl('button', 'vnl-bigplay', { type: 'button', 'aria-label': labels.play });
        bigPlay.appendChild(createIcon('fa-play'));

        var errorBox = createEl('div', 'vnl-error');
        errorBox.hidden = true;
        errorBox.appendChild(createIcon('fa-triangle-exclamation'));
        var errorText = createEl('span');
        errorText.textContent = labels.error;
        errorBox.appendChild(errorText);
        var errorLink = createEl('a', null, { href: downloadUrl, target: '_blank', rel: 'noopener' });
        errorLink.textContent = labels.download;
        errorBox.appendChild(errorLink);

        /* --- ilerleme çubuğu --- */
        var progress = createEl('div', 'vnl-progress', {
            role: 'slider',
            tabindex: '-1',
            'aria-label': labels.seek,
            'aria-valuemin': '0',
            'aria-valuemax': '100',
            'aria-valuenow': '0'
        });
        var track = createEl('div', 'vnl-progress-track');
        var buffer = createEl('div', 'vnl-progress-buffer');
        var played = createEl('div', 'vnl-progress-played');
        var thumb = createEl('div', 'vnl-progress-thumb');
        track.appendChild(buffer);
        track.appendChild(played);
        track.appendChild(thumb);

        var preview = createEl('div', 'vnl-preview');
        preview.hidden = true;
        var previewVideo = createEl('video', 'vnl-preview-video', { preload: 'metadata' });
        previewVideo.muted = true;
        previewVideo.setAttribute('playsinline', '');
        var previewTime = createEl('span', 'vnl-preview-time');
        previewTime.textContent = '0:00';
        preview.appendChild(previewVideo);
        preview.appendChild(previewTime);

        progress.appendChild(track);
        progress.appendChild(preview);

        /* --- alt çubuk --- */
        var playBtn = createEl('button', 'vnl-btn vnl-play', { type: 'button', 'aria-label': labels.play });
        var playIcon = createIcon('fa-play');
        playBtn.appendChild(playIcon);

        var volumeWrap = createEl('div', 'vnl-volume-wrap');
        var muteBtn = createEl('button', 'vnl-btn vnl-mute', { type: 'button', 'aria-label': labels.mute });
        var muteIcon = createIcon('fa-volume-high');
        muteBtn.appendChild(muteIcon);
        var volumeInput = createEl('input', 'vnl-volume', {
            type: 'range', min: '0', max: '1', step: '0.01', value: '1', 'aria-label': labels.volume
        });
        volumeWrap.appendChild(muteBtn);
        volumeWrap.appendChild(volumeInput);

        var timeBox = createEl('span', 'vnl-time');
        var currentEl = createEl('span', 'vnl-current');
        currentEl.textContent = '0:00';
        var sepEl = createEl('span', 'vnl-time-sep');
        sepEl.textContent = '/';
        var durationEl = createEl('span', 'vnl-duration');
        durationEl.textContent = '0:00';
        timeBox.appendChild(currentEl);
        timeBox.appendChild(sepEl);
        timeBox.appendChild(durationEl);

        var speedWrap = createEl('div', 'vnl-speed-wrap');
        var speedBtn = createEl('button', 'vnl-btn vnl-speed-btn', {
            type: 'button', 'aria-label': labels.speed, 'aria-haspopup': 'true', 'aria-expanded': 'false'
        });
        speedBtn.textContent = '1x';
        var speedMenu = createEl('div', 'vnl-speed-menu', { role: 'menu' });
        speedMenu.hidden = true;
        for (var i = 0; i < SPEEDS.length; i++) {
            var item = createEl('button', null, {
                type: 'button',
                role: 'menuitemradio',
                'data-speed': String(SPEEDS[i]),
                'aria-checked': SPEEDS[i] === 1 ? 'true' : 'false'
            });
            item.textContent = SPEEDS[i] + 'x';
            speedMenu.appendChild(item);
        }
        speedWrap.appendChild(speedBtn);
        speedWrap.appendChild(speedMenu);

        // İndirme düğmesi yalnızca istendiğinde eklenir (varsayılan: kapalı).
        var downloadBtn = null;
        if (options.download) {
            // Sunucu Content-Disposition: attachment gönderdiği için sayfa değişmez,
            // indirme aynı sekmede başlar.
            downloadBtn = createEl('a', 'vnl-btn vnl-download', {
                href: downloadUrl, download: '',
                title: labels.download, 'aria-label': labels.download
            });
            downloadBtn.appendChild(createIcon('fa-download'));
        }

        var pipBtn = createEl('button', 'vnl-btn vnl-pip', { type: 'button', 'aria-label': labels.pip });
        pipBtn.appendChild(createIcon('fa-clone'));
        pipBtn.hidden = true;

        var fsBtn = createEl('button', 'vnl-btn vnl-fs', { type: 'button', 'aria-label': labels.fullscreen });
        var fsIcon = createIcon('fa-expand');
        fsBtn.appendChild(fsIcon);

        var bar = createEl('div', 'vnl-bar');
        bar.appendChild(playBtn);
        bar.appendChild(volumeWrap);
        bar.appendChild(timeBox);
        bar.appendChild(createEl('span', 'vnl-spacer'));
        bar.appendChild(speedWrap);
        if (downloadBtn) bar.appendChild(downloadBtn);
        bar.appendChild(pipBtn);
        bar.appendChild(fsBtn);

        var controls = createEl('div', 'vnl-controls');
        controls.appendChild(progress);
        controls.appendChild(bar);

        root.appendChild(video);
        root.appendChild(topbar);
        root.appendChild(spinner);
        root.appendChild(bigPlay);
        root.appendChild(errorBox);
        root.appendChild(controls);

        videos.push(video);

        /* ---------------- durum ---------------- */

        var scrubbing = false;
        var hideTimer = null;
        var previewLoaded = false;
        var previewPending = null;

        /* ---------------- güncellemeler ---------------- */

        function updateProgress() {
            var duration = video.duration;
            var percent = (isFinite(duration) && duration > 0)
                ? clamp((video.currentTime / duration) * 100, 0, 100)
                : 0;
            played.style.width = percent + '%';
            thumb.style.left = percent + '%';
            currentEl.textContent = formatTime(video.currentTime);
            progress.setAttribute('aria-valuenow', String(Math.round(percent)));
            progress.setAttribute('aria-valuetext', formatTime(video.currentTime) + ' / ' + formatTime(duration));
        }

        function updateBuffer() {
            var duration = video.duration;
            if (!isFinite(duration) || duration <= 0 || !video.buffered.length) return;
            var end = 0;
            for (var b = 0; b < video.buffered.length; b++) {
                if (video.buffered.start(b) <= video.currentTime && video.buffered.end(b) > end) {
                    end = video.buffered.end(b);
                }
            }
            buffer.style.width = clamp((end / duration) * 100, 0, 100) + '%';
        }

        function showControls() {
            root.classList.remove('vnl-hide-controls');
            if (hideTimer) window.clearTimeout(hideTimer);
            if (video.paused) return;
            hideTimer = window.setTimeout(function () {
                if (!video.paused && !scrubbing && speedMenu.hidden) {
                    root.classList.add('vnl-hide-controls');
                }
            }, CONTROLS_HIDE_DELAY);
        }

        /* ---------------- eylemler ---------------- */

        function togglePlay() {
            if (video.paused || video.ended) {
                pauseOthers(video);
                var promise = video.play();
                if (promise && promise.catch) promise.catch(function () { /* autoplay reddi */ });
            } else {
                video.pause();
            }
        }

        function seekBy(delta) {
            if (!isFinite(video.duration)) return;
            video.currentTime = clamp(video.currentTime + delta, 0, video.duration);
        }

        function setVolume(value) {
            var next = clamp(value, 0, 1);
            video.volume = next;
            video.muted = next === 0;
        }

        function toggleFullscreen() {
            if (isFullscreen(root)) {
                if (document.exitFullscreen) document.exitFullscreen();
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                return;
            }
            if (root.requestFullscreen) {
                var req = root.requestFullscreen();
                if (req && req.catch) req.catch(function () { /* izin yok */ });
            } else if (root.webkitRequestFullscreen) {
                root.webkitRequestFullscreen();
            } else if (video.webkitEnterFullscreen) {
                // iOS Safari: yalnızca video elemanı tam ekrana geçebilir, native kontroller devreye girer.
                video.webkitEnterFullscreen();
            }
        }

        /* ---------------- seek önizlemesi ---------------- */

        function seekPreview(time) {
            if (previewVideo.readyState < 1 || previewVideo.seeking) {
                previewPending = time;
                return;
            }
            previewPending = null;
            try { previewVideo.currentTime = time; } catch (e) { /* seek edilemedi */ }
        }

        function showPreview(clientX) {
            var duration = video.duration;
            if (!isFinite(duration) || duration <= 0) return;

            var trackRect = track.getBoundingClientRect();
            if (trackRect.width <= 0) return;
            var time = clamp((clientX - trackRect.left) / trackRect.width, 0, 1) * duration;

            preview.hidden = false;
            previewTime.textContent = formatTime(time);

            var hostRect = progress.getBoundingClientRect();
            var width = preview.offsetWidth;
            preview.style.left = clamp(clientX - hostRect.left - width / 2, 0, Math.max(0, hostRect.width - width)) + 'px';

            if (!previewLoaded) {
                previewLoaded = true;
                previewVideo.src = src;
            }
            seekPreview(time);
        }

        function hidePreview() {
            preview.hidden = true;
        }

        previewVideo.addEventListener('loadedmetadata', function () {
            if (previewPending !== null) seekPreview(previewPending);
        });
        previewVideo.addEventListener('seeked', function () {
            if (previewPending !== null) seekPreview(previewPending);
        });

        /* ---------------- ilerleme çubuğu etkileşimi ---------------- */

        function timeFromPointer(clientX) {
            var rect = track.getBoundingClientRect();
            if (rect.width <= 0 || !isFinite(video.duration)) return 0;
            return clamp((clientX - rect.left) / rect.width, 0, 1) * video.duration;
        }

        progress.addEventListener('pointerdown', function (event) {
            if (event.button !== 0) return;
            scrubbing = true;
            root.classList.add('vnl-scrubbing');
            progress.setPointerCapture(event.pointerId);
            video.currentTime = timeFromPointer(event.clientX);
            updateProgress();
        });

        progress.addEventListener('pointermove', function (event) {
            if (scrubbing) {
                video.currentTime = timeFromPointer(event.clientX);
                updateProgress();
            }
            if (event.pointerType === 'mouse') showPreview(event.clientX);
        });

        function endScrub(event) {
            if (!scrubbing) return;
            scrubbing = false;
            root.classList.remove('vnl-scrubbing');
            if (progress.hasPointerCapture(event.pointerId)) progress.releasePointerCapture(event.pointerId);
            showControls();
        }

        progress.addEventListener('pointerup', endScrub);
        progress.addEventListener('pointercancel', endScrub);
        progress.addEventListener('pointerleave', hidePreview);

        /* ---------------- video olayları ---------------- */

        video.addEventListener('loadedmetadata', function () {
            durationEl.textContent = formatTime(video.duration);
            updateProgress();
        });

        video.addEventListener('timeupdate', function () {
            updateProgress();
            updateBuffer();
        });

        video.addEventListener('progress', updateBuffer);

        video.addEventListener('play', function () {
            root.classList.add('vnl-started', 'vnl-playing');
            setIcon(playIcon, 'fa-pause');
            playBtn.setAttribute('aria-label', labels.pause);
            showControls();
        });

        video.addEventListener('pause', function () {
            root.classList.remove('vnl-playing');
            setIcon(playIcon, 'fa-play');
            playBtn.setAttribute('aria-label', labels.play);
            showControls();
        });

        video.addEventListener('ended', function () {
            root.classList.remove('vnl-started', 'vnl-playing');
            showControls();
        });

        video.addEventListener('waiting', function () { spinner.hidden = false; });
        video.addEventListener('playing', function () { spinner.hidden = true; });
        video.addEventListener('canplay', function () { spinner.hidden = true; });

        video.addEventListener('error', function () {
            root.classList.add('vnl-errored');
            errorBox.hidden = false;
            spinner.hidden = true;
        });

        video.addEventListener('volumechange', function () {
            var level = video.muted ? 0 : video.volume;
            volumeInput.value = String(level);
            setIcon(muteIcon, level === 0 ? 'fa-volume-xmark' : (level < 0.5 ? 'fa-volume-low' : 'fa-volume-high'));
            muteBtn.setAttribute('aria-label', video.muted ? labels.unmute : labels.mute);
            storageSet(STORAGE_VOLUME, String(video.volume));
            storageSet(STORAGE_MUTED, video.muted ? '1' : '0');
        });

        video.addEventListener('ratechange', function () {
            speedBtn.textContent = video.playbackRate + 'x';
            var items = speedMenu.querySelectorAll('button[data-speed]');
            for (var s = 0; s < items.length; s++) {
                var value = parseFloat(items[s].getAttribute('data-speed'));
                items[s].setAttribute('aria-checked', value === video.playbackRate ? 'true' : 'false');
            }
        });

        /* ---------------- kontrol etkileşimleri ---------------- */

        video.addEventListener('click', togglePlay);
        video.addEventListener('dblclick', toggleFullscreen);
        bigPlay.addEventListener('click', togglePlay);
        playBtn.addEventListener('click', togglePlay);

        muteBtn.addEventListener('click', function () { video.muted = !video.muted; });
        volumeInput.addEventListener('input', function () { setVolume(parseFloat(volumeInput.value)); });

        speedBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = speedMenu.hidden;
            closeSpeedMenus();
            speedMenu.hidden = !willOpen;
            speedBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        speedMenu.addEventListener('click', function (event) {
            var button = event.target.closest('button[data-speed]');
            if (!button) return;
            video.playbackRate = parseFloat(button.getAttribute('data-speed'));
            speedMenu.hidden = true;
            speedBtn.setAttribute('aria-expanded', 'false');
        });

        if (document.pictureInPictureEnabled && video.requestPictureInPicture && !video.disablePictureInPicture) {
            pipBtn.hidden = false;
            pipBtn.addEventListener('click', function () {
                if (document.pictureInPictureElement === video) {
                    document.exitPictureInPicture();
                } else {
                    video.requestPictureInPicture().catch(function () { /* reddedildi */ });
                }
            });
        }

        fsBtn.addEventListener('click', toggleFullscreen);

        root.addEventListener('pointermove', showControls);
        root.addEventListener('focusin', showControls);
        root.addEventListener('pointerleave', function () {
            if (!video.paused && !scrubbing) root.classList.add('vnl-hide-controls');
        });

        root.addEventListener('keydown', function (event) {
            if (event.target === volumeInput) return; // range kendi ok tuşlarını kullanır

            switch (event.key) {
                case ' ':
                case 'Spacebar':
                case 'k':
                    togglePlay();
                    break;
                case 'ArrowRight':
                    seekBy(SEEK_STEP);
                    break;
                case 'ArrowLeft':
                    seekBy(-SEEK_STEP);
                    break;
                case 'ArrowUp':
                    setVolume(video.volume + VOLUME_STEP);
                    break;
                case 'ArrowDown':
                    setVolume(video.volume - VOLUME_STEP);
                    break;
                case 'm':
                case 'M':
                    video.muted = !video.muted;
                    break;
                case 'f':
                case 'F':
                    toggleFullscreen();
                    break;
                default:
                    return;
            }
            event.preventDefault();
            showControls();
        });

        // Kayıtlı ses tercihi
        var savedVolume = parseFloat(storageGet(STORAGE_VOLUME));
        video.volume = isFinite(savedVolume) ? clamp(savedVolume, 0, 1) : 1;
        video.muted = storageGet(STORAGE_MUTED) === '1';

        return root;
    }

    /* ---------------- belge geneli ---------------- */

    function closeSpeedMenus() {
        var menus = document.querySelectorAll('.vnl-speed-menu:not([hidden])');
        for (var i = 0; i < menus.length; i++) {
            menus[i].hidden = true;
            var button = menus[i].parentNode.querySelector('.vnl-speed-btn');
            if (button) button.setAttribute('aria-expanded', 'false');
        }
    }

    function syncFullscreen() {
        var players = document.querySelectorAll('.vnl-player');
        for (var i = 0; i < players.length; i++) {
            var button = players[i].querySelector('.vnl-fs');
            if (!button) continue;
            var active = isFullscreen(players[i]);
            setIcon(button.querySelector('i'), active ? 'fa-compress' : 'fa-expand');
            button.setAttribute('aria-label', active ? labels.exitFullscreen : labels.fullscreen);
        }
    }

    document.addEventListener('click', closeSpeedMenus);
    document.addEventListener('fullscreenchange', syncFullscreen);
    document.addEventListener('webkitfullscreenchange', syncFullscreen);

    window.VNLPlayer = {
        create: create,
        formatTime: formatTime,
        setLabels: function (values) {
            for (var key in values) {
                if (Object.prototype.hasOwnProperty.call(values, key) && values[key]) labels[key] = values[key];
            }
        }
    };
})(window, document);
