<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/cli-queue</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Konsol, Kuyruk ve Zamanlayıcı (3.4.0)</h1>
                <div class="vnl-lead">Web isteği uzun ya da ayrıcalık gerektiren işi kendisi yapmaz: kuyruğa yazar, ayrı bir süreç (<code>queue:work</code>) çalıştırır. Periyodik işler <code>config/schedule.php</code>'de, projenin kendi komutları <code>commands/</code> altında durur.</div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Proje komutları</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>commands/&lt;ad&gt;.php</code> bir komuttur; dosya adındaki ilk <code>-</code> komut adında <code>:</code> olur
                            (<code>commands/backup-prune.php</code> → <code>php vnl.php backup:prune</code>). Çekirdek adları ezilemez.
                            Uygulamaya (modeller, <code>Queue</code>, <code>Audit</code>, <code>lang()</code>) ihtiyacı olan komut ilk satırında
                            <code>vnl_cli_boot()</code> çağırır.
                        </p>
                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
/** &#64;summary Eski yedekleri siler */      // yardım listesinde görünür

return function (array $args): int {
    vnl_cli_boot();
    $o = vnl_cli_options($args);              // --days=7 → ['days' =&gt; '7']

    // ...
    return 0;                                 // çıkış kodu
};</code></pre>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Kuyruk</h3>
                        <div class="vnl-code mb-4">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// action içinde
$jobId = Queue::push('domain.provision', ['domain_id' =&gt; $id]);
$jobId = Queue::push('backup.nightly', [], ['queue' =&gt; 'system', 'delay' =&gt; 60, 'max_attempts' =&gt; 3]);

Queue::status($jobId);   // ['status' =&gt; 'running', 'progress' =&gt; 40, 'output' =&gt; '...', 'error' =&gt; null]
Queue::find($jobId);     // tüm satır, payload çözülmüş
Queue::cancel($jobId);   // yalnız başlamamış iş
Queue::retry($jobId);    // failed/canceled → yeniden kuyruğa

// jobs/domain.provision.php
return function (array $payload, VnlJob $job): void {
    $job-&gt;progress(20, 'Sistem kullanıcısı');   // 0–100
    $r = $job-&gt;exec(['useradd', '--create-home', $payload['user']]);   // kabuksuz, nabız atar
    if ($r['code'] !== 0) {
        throw new RuntimeException('useradd: ' . $r['stderr']);
    }
    $job-&gt;log('useradd tamam');                  // çıktıya satır
    // hata = istisna: max_attempts dolana kadar 30 sn × deneme sonra yeniden
};</code></pre>
                        </div>
                        <ul class="fs-6 text-body mb-0">
                            <li class="mb-2">Durumlar: <code>queued → running → done | failed | canceled</code>. Tablo: <code>tbl_vnl_jobs</code>; zamanlar veritabanı saatiyle yazılır.</li>
                            <li class="mb-2">Ayırma atomiktir (<code>UPDATE … ORDER BY id LIMIT 1</code>): aynı kuyruğu dinleyen iki işçi aynı işi alamaz. <code>--stale</code> saniye boyunca <b>nabız atmayan</b> <code>running</code> iş çökmüş sayılır ve kurtarılır (deneme hakkı varsa yeniden kuyruğa, yoksa <code>failed</code>). Nabız: <code>progress()</code>, <code>log()</code>, <code>heartbeat()</code> ve <code>exec()</code>'in bekleme döngüsü.</li>
                            <li class="mb-2"><b>Uzun ve sessiz adımlar</b> (büyük <code>mysqldump</code>, <code>rsync</code>, <code>tar</code>): <code>$job-&gt;exec([...], ['timeout' =&gt; 3600])</code> ile çalıştırın ya da döngüde <code>$job-&gt;heartbeat()</code> çağırın. <code>--stale</code> en uzun sessiz adımdan uzun olmalıdır.</li>
                            <li class="mb-2"><b>Ayırma çiti:</b> işin bütün yazmaları ayırma jetonuna bağlıdır. İş elinden alınmışsa (başka işçi, yeniden kuyruk, <code>Queue::retry()</code>) <code>progress()</code>/<code>log()</code>/<code>heartbeat()</code> <code>VnlJobLostException</code> fırlatır — yan etkileri sürdürmemek için; bu istisnayı yutmayın. Eski işçinin sonucu yeni sahibin durumunu ezmez.</li>
                            <li class="mb-2"><code>max_attempts</code>'i (varsayılan 1) yalnız ikinci kez çalıştırılması güvenli (idempotent) işlerde artırın: kurtarılan iş baştan çalışır.</li>
                            <li class="mb-2">İşleyici <b>exit/die çağırmaz</b> ve <code>returnErrorMessage()</code> kullanmaz; yine de çağırırsa ya da süreç ölümcül hatayla (bellek dahil) biterse işçi kapanırken iş başarısız deneme olarak yazılır. Doğrulayıcılar (<code>DefaultValidator</code>, <code>StandartException</code> …) komut satırında yanıt basmaz, istisna fırlatır: iş gerçek mesajıyla başarısız olur. Dış komut: <code>$job-&gt;exec([...])</code> ya da <code>proc_open([...])</code> argüman dizisiyle — kabuk dizgisi asla.</li>
                            <li class="mb-2">İşleyici dosyası işçi süreci başına <b>bir kez</b> yüklenir: dosya düzeyindeki fonksiyon/sınıf tanımları güvenlidir; kod değişikliği işçi yeniden başlayınca (<code>--max-time</code>) gelir. Yetki önbelleği her işte sıfırlanır.</li>
                            <li>Yük yönetim ekranında görünür: parola ya da gizli anahtar koymayın.</li>
                        </ul>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. İşçi</h3>
                        <div class="vnl-code mb-4">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>php vnl.php queue:work [--queue=default] [--once] [--sleep=3] [--max-time=3600]
                       [--max-jobs=0] [--stale=3600] [--memory=256]

# /etc/systemd/system/vnl-worker-system.service
[Unit]
Description=VNL kuyruk iscisi (system)
After=network.target mysql.service

[Service]
ExecStart=/usr/bin/php /opt/vnl-panel/vnl.php queue:work --queue=system
Restart=always
RestartSec=2
KillMode=mixed          # SIGTERM yalnız PHP'ye; işin alt süreçleri yarıda ölmesin
KillSignal=SIGTERM
TimeoutStopSec=3600     # en uzun işten uzun (varsayılan 90 sn sonra SIGKILL)
# Ayrıcalıksız bir kuyruk için: User=www-data / Group=www-data
# ('system' kuyruğu useradd, certbot, systemctl için root çalışır)

[Install]
WantedBy=multi-user.target</code></pre>
                        </div>
                        <p class="fs-6 text-body mb-0">
                            SIGTERM/SIGINT gelince (<code>pcntl</code> varsa) eldeki işi bitirip çıkar; <code>--max-time</code> ya da bellek
                            sınırı dolunca da temiz çıkar, systemd yeniden başlatır. <b><code>KillMode=mixed</code> şarttır:</b> systemd'nin
                            varsayılanı (<code>control-group</code>) <code>systemctl stop/restart</code>'ta SIGTERM'i birimin bütün süreçlerine
                            aynı anda gönderir — işin başlattığı <code>useradd</code>/<code>certbot</code>/<code>rsync</code> yarıda ölür ve
                            "işi bitirip çık" sözü tutulmaz. <code>TimeoutStopSec</code> en uzun işten kısa olursa iş SIGKILL ile kesilir ve
                            <code>--stale</code> dolana kadar <code>running</code> kalır. <code>php vnl.php doctor</code> <code>queue:work</code>
                            çalıştıran birimlerde <code>KillMode=mixed</code>'i denetler. Elle Ctrl+C de SIGINT'i ön plandaki bütün süreç
                            grubuna gönderir. Her iş temiz bir kimlikle başlar; denetim kaydı işi kuyruğa yazan kullanıcıya düşer
                            (<code>$job-&gt;createdBy()</code>).
                        </p>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Zamanlayıcı</h3>
                        <div class="vnl-code mb-4">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code># crontab
* * * * * www-data php /opt/vnl-panel/vnl.php schedule:run &gt;&gt; /dev/null 2&gt;&amp;1

// config/schedule.php
return [
    ['cron' =&gt; '*/5 * * * *', 'command' =&gt; 'backup:prune', 'args' =&gt; ['--days=7']],
    ['cron' =&gt; '0 3 * * *',   'job' =&gt; 'backup.nightly', 'payload' =&gt; [], 'queue' =&gt; 'system'],
];</code></pre>
                        </div>
                        <ul class="fs-6 text-body mb-0">
                            <li class="mb-2">Cron: beş alan; <code>*</code>, <code>n</code>, <code>a-b</code>, <code>*/n</code>, <code>a,b</code> ve <code>&#64;hourly</code>, <code>&#64;daily</code>, <code>&#64;weekly</code>, <code>&#64;monthly</code>, <code>&#64;yearly</code>. <code>VnlCron::isDue($ifade)</code>. Gün ve haftanın günü vixie/cronie gibi: ikisi de kısıtlıysa (<code>*</code> ile başlamıyorsa) biri yeter, biri <code>*</code> ile başlıyorsa (<code>*/2</code> dahil) ikisi birden tutmalı — <code>0 0 */2 * 1</code> yalnız tek günlere denk gelen pazartesiler.</li>
                            <li class="mb-2"><code>command</code> girdileri ayrı süreçte paralel çalışır; önceki çalıştırma sürüyorsa o dakika atlanır. Her girdinin kilidi <b>kendi</b> komutu biter bitmez bırakılır (uzun bir yedek, arkasındaki 5 dakikalık görevi bekletmez); kilit dosyaları close-on-exec açılır. <code>job</code> girdileri <code>Queue::push</code> olur.</li>
                            <li class="mb-2">Aynı dakikada iki kez çağrılsa da her girdi bir kez çalışır (<code>files/cache/schedule-*.lock</code>).</li>
                            <li><code>php vnl.php schedule:run --list</code> girdileri ve şu an çalışacakları gösterir; komut çıktısı <code>LOG_PATH/schedule-YYYY-MM-DD.log</code>.</li>
                        </ul>
                    </div>
                    <!--end::Section 4-->

                </div>
            </div>

        </div>
    </div>
</div>
