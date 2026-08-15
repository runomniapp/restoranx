// Tezgah Burger - Admin canlı sipariş bildirimi
// Tüm admin sayfalarında çalışır: yeni sipariş geldiğinde alarm sesi + toast + rozet.
// admin/orders.php ayrıca window.TezgahOrders.onNewOrders ile listeyi tazeler.

(function () {
    'use strict';

    const API = (window.TEZGAH_ADMIN_API || '../api/orders.php');
    const POLL_INTERVAL = 7000;
    const STORAGE_ALARM = 'tezgah_admin_alarm';
    const STORAGE_SOUND = 'tezgah_admin_alarm_sound';
    const STORAGE_LAST_ID = 'tezgah_admin_last_order_id';
    const SOUND_BASE = '../notification/';

    let lastOrderId = parseInt(localStorage.getItem(STORAGE_LAST_ID) || '0', 10);
    let alarmEnabled = localStorage.getItem(STORAGE_ALARM) !== 'off';
    let audioCtx = null;
    let pollTimer = null;
    let firstRun = true;
    let polling = false;              // eşzamanlı istekleri engeller
    const announced = new Set();      // aynı sipariş iki kez duyurulmasın

    /* ---------------------------------------------------------- */
    /* Alarm sesi - notification/ klasöründeki mp3 dosyaları       */
    /* ---------------------------------------------------------- */

    const soundSelect = document.getElementById('alarmSoundSelect');
    let alarmAudio = null;

    // Seçili melodi: kayıtlı tercih -> sunucudan gelen varsayılan (1) -> ilk seçenek
    function currentSoundFile() {
        if (!soundSelect) return null;

        const saved = localStorage.getItem(STORAGE_SOUND);
        const options = [...soundSelect.options].map(o => o.value);

        if (saved && options.includes(saved)) return saved;

        const fallback = soundSelect.getAttribute('data-default');
        return options.includes(fallback) ? fallback : (options[0] || null);
    }

    function getAlarmAudio() {
        const file = currentSoundFile();
        if (!file) return null;

        const src = SOUND_BASE + encodeURIComponent(file);

        if (!alarmAudio) {
            alarmAudio = new Audio();
            alarmAudio.preload = 'auto';
        }

        // Kaynak yalnızca melodi değiştiğinde yeniden atanır
        if (!alarmAudio.dataset.file || alarmAudio.dataset.file !== file) {
            alarmAudio.dataset.file = file;
            alarmAudio.src = src;
        }

        return alarmAudio;
    }

    // Melodiyi baştan çalar. Dosya çalınamazsa WebAudio zilini kullanır.
    function playSoundFile() {
        const audio = getAlarmAudio();
        if (!audio) return false;

        try {
            audio.currentTime = 0;
        } catch (e) { /* henüz yüklenmemiş olabilir */ }

        const p = audio.play();
        if (p && typeof p.catch === 'function') {
            p.catch(() => fallbackBeep(1));
        }
        return true;
    }

    function getAudioContext() {
        if (!audioCtx) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') audioCtx.resume();
        return audioCtx;
    }

    function beep(ctx, startAt, frequency, duration) {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'triangle';
        osc.frequency.setValueAtTime(frequency, startAt);

        // Yumuşak giriş/çıkış - kulak tırmalamayan bir zil sesi
        gain.gain.setValueAtTime(0.0001, startAt);
        gain.gain.exponentialRampToValueAtTime(0.35, startAt + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(startAt);
        osc.stop(startAt + duration + 0.02);
    }

    function playAlarm(repeat) {
        if (!alarmEnabled) return;

        // Önce klasördeki melodi; dosya yoksa üretilen zil sesi
        if (playSoundFile()) return;
        fallbackBeep(repeat);
    }

    function fallbackBeep(repeat) {
        const ctx = getAudioContext();
        if (!ctx) return;

        const times = Math.min(repeat || 1, 3);
        const now = ctx.currentTime;

        for (let r = 0; r < times; r++) {
            const base = now + r * 0.75;
            beep(ctx, base, 880, 0.16);
            beep(ctx, base + 0.2, 1174, 0.16);
            beep(ctx, base + 0.4, 1568, 0.22);
        }
    }

    /* ---------------------------------------------------------- */
    /* Alarm aç/kapa düğmesi                                      */
    /* ---------------------------------------------------------- */

    function renderAlarmToggle() {
        const btn = document.getElementById('alarmToggleBtn');
        if (!btn) return;

        btn.classList.toggle('armed', alarmEnabled);
        btn.setAttribute('aria-pressed', alarmEnabled ? 'true' : 'false');

        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = alarmEnabled ? 'fa-solid fa-bell' : 'fa-solid fa-bell-slash';
        }

        const text = btn.querySelector('.alarm-text');
        if (text) text.textContent = alarmEnabled ? 'Alarm Açık' : 'Alarm Kapalı';
    }

    function initAlarmToggle() {
        const btn = document.getElementById('alarmToggleBtn');
        if (!btn) return;

        btn.addEventListener('click', () => {
            alarmEnabled = !alarmEnabled;
            localStorage.setItem(STORAGE_ALARM, alarmEnabled ? 'on' : 'off');
            renderAlarmToggle();

            if (alarmEnabled) {
                // Tarayıcı ses iznini bu tıklama ile açıyoruz.
                playAlarm(1);
                requestDesktopPermission();
                notify('Alarm etkinleştirildi', 'Yeni siparişler geldiğinde sesli uyarı alacaksınız.');
            }
        });

        renderAlarmToggle();
    }

    function initSoundPicker() {
        if (!soundSelect) return;

        // Kayıtlı tercihi (yoksa varsayılan 1. melodiyi) seçili göster
        const active = currentSoundFile();
        if (active) soundSelect.value = active;

        soundSelect.addEventListener('change', () => {
            localStorage.setItem(STORAGE_SOUND, soundSelect.value);
            playSoundFile();   // seçilen melodiyi anında dinlet
        });

        const previewBtn = document.getElementById('alarmPreviewBtn');
        if (!previewBtn) return;

        previewBtn.addEventListener('click', () => {
            const audio = getAlarmAudio();
            if (!audio) return;

            if (!audio.paused) {
                audio.pause();
                audio.currentTime = 0;
                previewBtn.classList.remove('playing');
                previewBtn.querySelector('i').className = 'fa-solid fa-play';
                return;
            }

            playSoundFile();
            previewBtn.classList.add('playing');
            previewBtn.querySelector('i').className = 'fa-solid fa-stop';

            audio.onended = () => {
                previewBtn.classList.remove('playing');
                previewBtn.querySelector('i').className = 'fa-solid fa-play';
            };
        });
    }

    /* ---------------------------------------------------------- */
    /* Masaüstü bildirimi                                         */
    /* ---------------------------------------------------------- */

    function requestDesktopPermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    function sendDesktopNotification(order) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;

        const target = order.type === 'dine_in' ? order.table_no : order.type_label;
        const n = new Notification('Yeni Sipariş - ' + target, {
            body: order.item_count + ' ürün · ' + order.total_text + ' · ' + order.code,
            tag: 'tezgah-order-' + order.id
        });

        n.onclick = () => {
            window.focus();
            if (!location.pathname.endsWith('orders.php')) location.href = 'orders.php';
        };
    }

    /* ---------------------------------------------------------- */
    /* Ekran içi bildirim yığını                                  */
    /* ---------------------------------------------------------- */

    function getStack() {
        let stack = document.getElementById('notifyStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'notifyStack';
            stack.className = 'notify-stack';
            stack.setAttribute('role', 'status');
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }
        return stack;
    }

    function notify(title, body, onClick) {
        const stack = getStack();

        const toast = document.createElement('div');
        toast.className = 'notify-toast';
        toast.innerHTML =
            '<div class="notify-icon"><i class="fa-solid fa-bell"></i></div>' +
            '<div><div class="notify-title"></div><div class="notify-body"></div></div>';

        toast.querySelector('.notify-title').textContent = title;
        toast.querySelector('.notify-body').textContent = body;

        const dismiss = () => {
            toast.classList.add('leaving');
            setTimeout(() => toast.remove(), 300);
        };

        toast.addEventListener('click', () => {
            if (onClick) onClick();
            dismiss();
        });

        stack.prepend(toast);
        setTimeout(dismiss, 9000);

        while (stack.children.length > 4) stack.lastElementChild.remove();
    }

    /* ---------------------------------------------------------- */
    /* Rozetler + başlık sayacı                                   */
    /* ---------------------------------------------------------- */

    function applyStats(stats) {
        if (!stats) return;

        document.querySelectorAll('[data-order-badge]').forEach(el => {
            el.textContent = stats.unseen;
            el.classList.toggle('show', stats.unseen > 0);
        });

        const baseTitle = window.TEZGAH_BASE_TITLE || document.title.replace(/^\(\d+\)\s*/, '');
        window.TEZGAH_BASE_TITLE = baseTitle;
        document.title = stats.unseen > 0 ? '(' + stats.unseen + ') ' + baseTitle : baseTitle;

        document.querySelectorAll('[data-stat]').forEach(el => {
            const key = el.getAttribute('data-stat');
            if (stats[key] === undefined) return;
            el.textContent = key === 'today_revenue'
                ? stats[key].toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺'
                : stats[key];
        });
    }

    /* ---------------------------------------------------------- */
    /* Polling                                                    */
    /* ---------------------------------------------------------- */

    async function poll() {
        if (polling) return;
        polling = true;

        try {
            const res = await fetch(API + '?action=poll&since=' + lastOrderId, { cache: 'no-store' });
            const data = await res.json();
            if (!data.ok) return;

            applyStats(data.stats);

            const fresh = (data.new_orders || []).filter(o => !announced.has(o.id));
            fresh.forEach(o => announced.add(o.id));

            if (firstRun) {
                // İlk turda geçmiş siparişler için alarm çalmayalım.
                firstRun = false;
                lastOrderId = data.last_id;
                localStorage.setItem(STORAGE_LAST_ID, String(lastOrderId));

                if (typeof window.TezgahOrders.onNewOrders === 'function' && fresh.length) {
                    window.TezgahOrders.onNewOrders(fresh, false);
                }
                return;
            }

            if (fresh.length > 0) {
                lastOrderId = data.last_id;
                localStorage.setItem(STORAGE_LAST_ID, String(lastOrderId));

                playAlarm(fresh.length);

                fresh.forEach(order => {
                    const target = order.type === 'dine_in' ? order.table_no : order.type_label;
                    notify(
                        'Yeni Sipariş · ' + target,
                        order.item_count + ' ürün · ' + order.total_text + ' · Kod: ' + order.code,
                        () => {
                            if (!location.pathname.endsWith('orders.php')) location.href = 'orders.php';
                        }
                    );
                    sendDesktopNotification(order);
                });

                if (typeof window.TezgahOrders.onNewOrders === 'function') {
                    window.TezgahOrders.onNewOrders(fresh, true);
                }
            }
        } catch (err) {
            /* Ağ hatasında sessizce bir sonraki tura geç */
        } finally {
            polling = false;
        }
    }

    function startPolling() {
        poll();
        clearInterval(pollTimer);
        pollTimer = setInterval(poll, POLL_INTERVAL);
    }

    /* ---------------------------------------------------------- */
    /* Public API                                                 */
    /* ---------------------------------------------------------- */

    window.TezgahOrders = {
        api: API,
        onNewOrders: null,          // orders.php tarafından atanır
        notify: notify,
        playAlarm: () => playAlarm(1),
        refreshStats: applyStats,
        pollNow: poll
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.TEZGAH_BASE_TITLE = document.title;
        initAlarmToggle();
        initSoundPicker();
        startPolling();

        // Tarayıcılar sesi yalnızca kullanıcı etkileşiminden sonra açar;
        // ilk tıklamada ses motorunu hazırlıyoruz.
        const unlock = () => {
            if (alarmEnabled) {
                getAudioContext();
                const audio = getAlarmAudio();
                if (audio) audio.load();   // melodiyi önceden hazırla
            }
            document.removeEventListener('click', unlock);
        };
        document.addEventListener('click', unlock);
    });

    // Sekmeye geri dönüldüğünde anında tazele
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) poll();
    });
})();
