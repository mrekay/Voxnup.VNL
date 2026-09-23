<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Hata Sayfası
 *  Yakalanmamış istisnaları ve ölümcül hataları karşılar;
 *  sayfa isteğinde HTML, AJAX/API isteğinde JSON döner.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Geliştirme kipi açık mı? (.env: APP_DEBUG=true)
 *
 * Varsayılan KAPALIDIR. VNL 3.4'e kadar ayrıntılı hata sayfası (kaynak kod,
 * dosya yolları, yığın izi) ve display_errors koşulsuz açıktı; canlıdaki
 * her ziyaretçi SQL metnini ve sunucu yollarını görebiliyordu. Artık
 * ayrıntı yalnız APP_DEBUG=true iken basılır; kapalıyken kullanıcı bir
 * başvuru kodu görür, ayrıntı sunucu günlüğüne yazılır.
 *
 * env.php bu dosyadan sonra yüklenir; fonksiyon çağrıldığı anda VnlEnv
 * henüz yoksa (önyüklemenin ilk satırlarındaki bir hata) kapalı sayılır.
 */
function vnl_debug(): bool
{
    if (!class_exists('VnlEnv', false)) {
        return false;
    }

    return VnlEnv::get('APP_DEBUG', false) === true;
}

/**
 * İstisnayı sunucu günlüğüne yazar ve kullanıcıya gösterilecek başvuru
 * kodunu döner. Kullanıcı kodu bildirir, geliştirici günlükte arar.
 */
function vnl_log_exception(Throwable $e, string $channel = 'VNL'): string
{
    $ref = bin2hex(random_bytes(6));

    error_log("[{$channel}][{$ref}] " . get_class($e) . ': ' . $e->getMessage()
        . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());

    return $ref;
}

/**
 * Kullanıcıya gösterilecek genel hata metni. Dil sistemi henüz yüklenmemiş
 * olabilir (önyükleme hatası); o durumda sabit Türkçe metne düşülür.
 */
function vnl_generic_error_message(): string
{
    if (function_exists('lang_has') && class_exists('Language', false)) {
        try {
            if (lang_has('ERR_UNEXPECTED')) {
                return lang('ERR_UNEXPECTED');
            }
        } catch (Throwable $_e) {
            // dil sistemi kullanılamıyor — sabit metne düş
        }
    }

    return 'Beklenmeyen bir hata oluştu.';
}

/**
 * İsteğin HTML hata sayfası mı yoksa JSON mı beklediğini belirler.
 */
function vnl_wants_json(): bool
{
    // jQuery/DataTables — VNL'in tüm AJAX çağrıları bu başlığı gönderir
    if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0) {
        return true;
    }

    // Modern tarayıcılar: sayfa gezinmesi "document", fetch/XHR "empty" gönderir
    if (strcasecmp($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '', 'empty') === 0) {
        return true;
    }

    // Başlık yoksa Accept'e bak: JSON isteyip HTML istemeyen istemciler
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if ($accept !== '' && stripos($accept, 'application/json') !== false && stripos($accept, 'text/html') === false) {
        return true;
    }

    return false;
}

/**
 * AJAX/API isteklerine, ön yüzün beklediği {message, devcode} biçiminde hata döner.
 */
function vnl_render_error_json(Throwable $e): never
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    $root = (defined('VNL_ROOT') ? VNL_ROOT : '');

    $trace = [];
    foreach (array_slice($e->getTrace(), 0, 12) as $frame) {
        $trace[] = [
            'file'     => isset($frame['file']) ? str_replace($root, '', $frame['file']) : '[internal function]',
            'line'     => $frame['line'] ?? null,
            'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
        ];
    }

    echo json_encode([
        'message' => get_class($e) . ': ' . $e->getMessage(),
        'devcode' => 'VNL_FATAL',
        'error'   => [
            'type'  => get_class($e),
            'text'  => $e->getMessage(),
            'file'  => str_replace($root, '', $e->getFile()),
            'line'  => $e->getLine(),
            'trace' => $trace,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit(1);
}

/**
 * Canlı kip hata yanıtı: ayrıntısız JSON ya da tek başına duran, yol ve
 * kaynak kod içermeyen küçük bir HTML sayfası. Başvuru kodu her ikisinde de
 * görünür; aynı kod sunucu günlüğündeki kaydı işaretler.
 */
function vnl_render_error_production(string $ref): never
{
    $message = vnl_generic_error_message();

    if (vnl_wants_json()) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'message' => $message,
            'devcode' => 'VNL_FATAL',
            'ref'     => $ref,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit(1);
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }

    $_msg = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $_ref = htmlspecialchars($ref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{$_msg}</title>
<style>
  html, body { height: 100%; margin: 0; background: #0f172a; color: #e2e8f0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
  .box { min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; }
  .card { max-width: 460px; text-align: center; }
  .code { font-size: 64px; font-weight: 800; color: #f97316; letter-spacing: -.03em; }
  h1 { font-size: 20px; margin: 12px 0 8px; }
  p { color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 20px; }
  .ref { font-family: ui-monospace, monospace; background: #1e293b; border: 1px solid #334155;
    border-radius: 6px; padding: 4px 10px; font-size: 13px; color: #e2e8f0; }
  a { color: #17a65c; }
</style>
</head>
<body>
<div class="box"><div class="card">
  <div class="code">500</div>
  <h1>{$_msg}</h1>
  <p>Sorun kaydedildi. Destek ile iletişime geçerseniz aşağıdaki kodu iletin.</p>
  <span class="ref">{$_ref}</span>
  <p style="margin-top:24px"><a href="/">Ana sayfa</a></p>
</div></div>
</body>
</html>
HTML;

    exit(1);
}

/**
 * VNL Error Page
 *
 * Standalone hata sayfası — VNL'in view sistemine bağımlı değildir.
 * set_exception_handler ve extension loader hataları tarafından tetiklenir.
 *
 * Ayrıntılı sayfa GELİŞTİRİCİ içindir: kaynak kodu, dosya yollarını ve ortam
 * bilgisini basar ve YALNIZ APP_DEBUG=true iken gösterilir (3.4.0). Kapalıyken
 * istisna günlüğe yazılır, kullanıcı başvuru kodlu genel bir sayfa/JSON görür.
 * Kendi ekranını isteyen proje vnl_project_error_page() kancasını tanımlar —
 * bkz. /docs/errors.
 */
function vnl_render_error(Throwable $e): never
{
    // Hata sayfasının kendisi hata verirse sonsuz döngüye girmesin
    static $rendering = false;
    if ($rendering) {
        exit(1);
    }
    $rendering = true;

    // Tüm önceki çıktıları temizle
    while (ob_get_level()) ob_end_clean();

    /* Konsol (kuyruk işçisi, zamanlayıcı, komutlar): HTML sayfa değil,
       stderr'e düz metin. Günlüğe de yazılır; systemd/journal ikisini de görür. */
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        $ref = vnl_log_exception($e);
        $out = defined('STDERR') ? STDERR : fopen('php://stderr', 'w');

        fwrite($out, "\n  [VNL] " . get_class($e) . ': ' . $e->getMessage() . "\n"
            . '  ' . $e->getFile() . ':' . $e->getLine() . "  (ref {$ref})\n"
            . (vnl_debug() ? "\n" . $e->getTraceAsString() . "\n" : '') . "\n");

        exit(1);
    }

    /* Projenin hata ekranı.
     *
     * Proje tarafında vnl_project_error_page() tanımlıysa ekranı o üretir ve
     * istek orada biter; dönerse aşağıdaki ayrıntılı sayfa basılır. Kanca burada
     * durur çünkü üç yakalama katmanı da (exception handler, fatal kapanışı,
     * kernel'in denetleyici catch'i) tek bu fonksiyona çıkar: yalnız
     * set_exception_handler'ı değiştirmek denetleyici hatalarını kaçırırdı.
     *
     * Tampon temizlendikten AMA durum kodu yazılmadan önce çağrılır: proje
     * kendi durum kodunu (503 gibi) ve HTML/JSON kararını kendi verir.
     */
    if (function_exists('vnl_project_error_page')) {
        try {
            vnl_project_error_page($e);
        } catch (Throwable $_vnlProjectError) {
            /* Proje ekranının kendi hatası kullanıcıyı boş sayfada bırakmasın:
               sessizce çekirdeğin sayfasına düşülür. */
        }
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    /* Canlı kip: ayrıntı günlüğe, kullanıcıya yalnız başvuru kodu.
       Kaynak kod, dosya yolu, yığın izi ve SQL metni dışarı çıkmaz. */
    if (!vnl_debug()) {
        vnl_render_error_production(vnl_log_exception($e));
    }

    // AJAX/API isteğinde HTML sayfa değil JSON dön — aksi halde ön yüz sessizce takılır
    if (vnl_wants_json()) {
        vnl_render_error_json($e);
    }

    // Logo: yeşil arkaplanlı kare maskot (assets/'dan bağımsız). "VNL" yazısı HTML ile eklenir.
    $_logoPath = __DIR__ . '/media/VNLMASCOT.png';
    $_logoDataUri = file_exists($_logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($_logoPath))
        : '';

    // Hata bilgileri
    $_class   = get_class($e);
    $_message = $e->getMessage();
    $_file    = $e->getFile();
    $_line    = $e->getLine();
    $_trace   = $e->getTrace();

    // Kısa dosya yolu (document root'tan itibaren)
    $_shortFile = str_replace((defined('VNL_ROOT') ? VNL_ROOT : ''), '', $_file);

    // Kaynak kod satırlarını oku
    $_codeLines   = [];
    $_contextStart = 0;
    if (file_exists($_file)) {
        $_allLines    = file($_file);
        $_contextStart = max(0, $_line - 8);
        $_contextEnd   = min(count($_allLines) - 1, $_line + 6);
        for ($i = $_contextStart; $i <= $_contextEnd; $i++) {
            $_codeLines[$i + 1] = rtrim($_allLines[$i]);
        }
    }

    // PHP & VNL sürümleri
    $_phpVersion = PHP_VERSION;
    $_vnlVersion = defined('VNL_VERSION') ? VNL_VERSION : (
        class_exists('ConfigDefinitions') ? (new ReflectionClass('ConfigDefinitions'))->getMethod('vnl_version')->invoke(new ConfigDefinitions()) : '?'
    );

    echo <<<HTML
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>VNL — Uygulama Hatası</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:         #0f172a;
    --surface:    #1e293b;
    --surface2:   #273447;
    --border:     #334155;
    --text:       #e2e8f0;
    --text-muted: #94a3b8;
    --accent:     #f97316;
    --accent-dim: rgba(249,115,22,.15);
    --red:        #ef4444;
    --red-dim:    rgba(239,68,68,.12);
    --green:      #22c55e;
    --blue:       #3b82f6;
    --yellow:     #eab308;
    --purple:     #a855f7;
    --line-hl:    rgba(249,115,22,.18);
    --line-num:   #475569;
    --radius:     10px;
    --font-mono:  'JetBrains Mono', 'Fira Code', 'Cascadia Code', ui-monospace, monospace;
  }

  html, body {
    height: 100%;
    background: var(--bg);
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    line-height: 1.6;
  }

  /* ── Layout ── */
  .layout { display: flex; min-height: 100vh; }

  .sidebar {
    width: 260px; flex-shrink: 0;
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    position: sticky; top: 0; height: 100vh; overflow-y: auto;
  }

  .sidebar-brand {
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
  }
  .sidebar-brand img { height: 28px; width: 28px; flex-shrink: 0; }
  .sidebar-brand .brand-name { font-size: 18px; font-weight: 900; letter-spacing: -.02em; color: var(--text); }
  .sidebar-brand span { font-size: 11px; color: var(--text-muted); letter-spacing: .04em; }

  .sidebar-section { padding: 16px 0 8px; }
  .sidebar-label {
    padding: 0 16px 6px;
    font-size: 10px; font-weight: 700; letter-spacing: .1em;
    color: var(--text-muted); text-transform: uppercase;
  }
  .sidebar-item {
    padding: 6px 16px; font-size: 12px; color: var(--text-muted);
    cursor: pointer; transition: background .15s, color .15s;
    border-left: 2px solid transparent;
    word-break: break-all;
  }
  .sidebar-item:hover { background: var(--surface2); color: var(--text); }
  .sidebar-item.active {
    border-left-color: var(--accent);
    background: var(--accent-dim); color: var(--text);
  }
  .sidebar-item .frame-file { font-weight: 600; display: block; }
  .sidebar-item .frame-line { color: var(--accent); font-size: 11px; }
  .sidebar-item .frame-fn   { color: var(--text-muted); font-size: 11px; display: block; }

  .main { flex: 1; min-width: 0; padding: 32px 40px; overflow: auto; }

  /* ── Header ── */
  .error-header { margin-bottom: 28px; }
  .error-type {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--red-dim); border: 1px solid var(--red);
    color: var(--red); border-radius: 6px;
    padding: 4px 10px; font-size: 12px; font-weight: 600; letter-spacing: .03em;
    margin-bottom: 12px;
  }
  .error-type svg { width: 14px; height: 14px; }
  .error-message {
    font-size: 22px; font-weight: 700; color: var(--text);
    line-height: 1.35; margin-bottom: 10px;
    word-break: break-word;
  }
  .error-location {
    display: flex; align-items: center; gap: 8px;
    font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);
    flex-wrap: wrap;
  }
  .error-location .path { color: var(--blue); }
  .error-location .line-badge {
    background: var(--accent); color: #fff;
    border-radius: 4px; padding: 1px 7px; font-size: 11px; font-weight: 700;
  }

  /* ── Code Viewer ── */
  .code-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden; margin-bottom: 24px;
  }
  .code-card-header {
    padding: 10px 16px;
    background: var(--surface2); border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px;
  }
  .code-card-header .file-path {
    font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);
    word-break: break-all;
  }
  .code-card-header .file-path strong { color: var(--text); }

  .code-table { width: 100%; border-collapse: collapse; font-family: var(--font-mono); font-size: 13px; }
  .code-table tr { transition: background .1s; }
  .code-table tr.highlight { background: var(--line-hl); }
  .code-table tr.highlight td { color: var(--text); }
  .code-table td { padding: 2px 0; vertical-align: top; }
  .code-table .ln {
    width: 48px; text-align: right; padding-right: 16px;
    color: var(--line-num); user-select: none; padding-left: 12px;
  }
  .code-table tr.highlight .ln { color: var(--accent); font-weight: 700; }
  .code-table .code-line { white-space: pre; color: var(--text-muted); padding-right: 20px; }
  .code-table tr.highlight .code-line { color: var(--text); }
  .hl-arrow { color: var(--accent); margin-right: 4px; font-weight: 700; }

  /* ── Stack trace ── */
  .section-title {
    font-size: 11px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: var(--text-muted);
    margin-bottom: 10px;
  }

  .trace-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 24px; }
  .trace-item {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 8px; overflow: hidden;
  }
  .trace-item summary {
    list-style: none; padding: 10px 16px;
    cursor: pointer; display: flex; align-items: center; gap: 10px;
    transition: background .15s;
  }
  .trace-item summary::-webkit-details-marker { display: none; }
  .trace-item summary:hover { background: var(--surface2); }
  .trace-item[open] summary { background: var(--surface2); border-bottom: 1px solid var(--border); }
  .trace-num {
    width: 22px; height: 22px; border-radius: 50%;
    background: var(--surface2); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: var(--text-muted); flex-shrink: 0;
  }
  .trace-item[open] .trace-num { background: var(--accent); border-color: var(--accent); color: #fff; }
  .trace-info { flex: 1; min-width: 0; }
  .trace-fn {
    font-family: var(--font-mono); font-size: 12px; color: var(--text);
    word-break: break-word;
  }
  .trace-fn .class-name { color: var(--blue); }
  .trace-fn .method-sep { color: var(--text-muted); }
  .trace-fn .method-name { color: var(--green); }
  .trace-location {
    font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);
    margin-top: 2px;
  }
  .trace-location .path { color: var(--blue); }
  .trace-args {
    padding: 10px 16px;
    font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);
  }
  .arg-item { padding: 2px 0; }
  .arg-key { color: var(--purple); }
  .arg-val { color: var(--yellow); }
  .arg-type { color: var(--text-muted); font-size: 11px; }
  .no-args { color: var(--text-muted); font-style: italic; font-size: 12px; }

  /* ── Environment ── */
  .env-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;
    margin-bottom: 24px;
  }
  .env-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 8px; padding: 12px 14px;
  }
  .env-card .env-label { font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: var(--text-muted); margin-bottom: 4px; }
  .env-card .env-value { font-family: var(--font-mono); font-size: 13px; color: var(--text); font-weight: 600; }

  /* ── Theme toggle ── */
  [data-theme="light"] {
    --bg:        #f1f5f9;
    --surface:   #ffffff;
    --surface2:  #f8fafc;
    --border:    #e2e8f0;
    --text:      #0f172a;
    --text-muted:#64748b;
    --line-num:  #94a3b8;
    --line-hl:   rgba(249,115,22,.08);
    --accent-dim:rgba(249,115,22,.08);
    --red-dim:   rgba(239,68,68,.08);
  }
  [data-theme="light"] .code-table .code-line { color: #334155; }
  [data-theme="light"] .code-table tr.highlight .code-line { color: #0f172a; }

  .theme-btn {
    position: fixed; top: 16px; right: 16px; z-index: 100;
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); border-radius: 8px; padding: 6px 12px;
    font-size: 12px; cursor: pointer; transition: all .15s;
    display: flex; align-items: center; gap: 6px;
  }
  .theme-btn:hover { background: var(--border); }

  @media (max-width: 768px) {
    .layout { flex-direction: column; }
    .sidebar { width: 100%; height: auto; position: relative; }
    .main { padding: 20px; }
  }
</style>
</head>
<body>
<button class="theme-btn" onclick="toggleTheme()" id="themeBtn">
  <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.22 1.78a1 1 0 011.42 1.42l-.71.7a1 1 0 11-1.41-1.41l.7-.71zM18 9a1 1 0 110 2h-1a1 1 0 110-2h1zM5.06 4.2a1 1 0 00-1.41 1.41l.7.71A1 1 0 005.76 4.9l-.7-.7zM2 10a1 1 0 011-1h1a1 1 0 110 2H3a1 1 0 01-1-1zm2.64 4.22l.7.7a1 1 0 101.42-1.41l-.71-.7a1 1 0 10-1.41 1.41zM10 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm5.66-1.78a1 1 0 10-1.41 1.41l.7.71a1 1 0 001.42-1.42l-.71-.7zM10 6a4 4 0 100 8 4 4 0 000-8z"/></svg>
  <span id="themeLabel">Light</span>
</button>

<div class="layout">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
HTML;

    if ($_logoDataUri) {
        echo '<img src="' . $_logoDataUri . '" alt="VNL maskot">';
    }
    echo '<strong class="brand-name">VNL</strong>';

    echo <<<HTML
      <span>error handler</span>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-label">İstisna</div>
      <div class="sidebar-item active">
        <span class="frame-file">
HTML;

    echo htmlspecialchars(basename($_shortFile));

    echo <<<HTML
        </span>
        <span class="frame-line">line {$_line}</span>
        <span class="frame-fn">
HTML;

    echo htmlspecialchars($_class);

    echo <<<HTML
        </span>
      </div>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-label">Stack Trace</div>
HTML;

    foreach (array_slice($_trace, 0, 12) as $idx => $frame) {
        $frameFile = isset($frame['file']) ? str_replace((defined('VNL_ROOT') ? VNL_ROOT : ''), '', $frame['file']) : '[internal]';
        $frameLine = $frame['line'] ?? '?';
        $frameClass = $frame['class'] ?? '';
        $frameType  = $frame['type'] ?? '';
        $frameFn    = $frame['function'] ?? '';
        $fnDisplay  = $frameClass ? htmlspecialchars($frameClass . $frameType . $frameFn) : htmlspecialchars($frameFn);
        echo '<div class="sidebar-item" data-idx="' . $idx . '" onclick="scrollToFrame(' . $idx . ')">';
        echo '<span class="frame-file">' . htmlspecialchars(basename($frameFile)) . '</span>';
        echo '<span class="frame-line">line ' . $frameLine . '</span>';
        echo '<span class="frame-fn">' . $fnDisplay . '</span>';
        echo '</div>';
    }

    echo <<<HTML
    </div>
  </aside>

  <!-- ── Main ── -->
  <main class="main">

    <!-- Header -->
    <div class="error-header">
      <div class="error-type">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
HTML;
    echo htmlspecialchars($_class);
    echo <<<HTML
      </div>
      <div class="error-message">
HTML;
    echo htmlspecialchars($_message);
    echo <<<HTML
      </div>
      <div class="error-location">
        <span class="path">
HTML;
    echo htmlspecialchars($_shortFile);
    echo <<<HTML
        </span>
        <span>:</span>
        <span class="line-badge">{$_line}</span>
      </div>
    </div>

    <!-- Code viewer -->
HTML;

    if (!empty($_codeLines)) {
        echo '<div class="code-card">';
        echo '<div class="code-card-header">';
        echo '<span class="file-path">' . htmlspecialchars(dirname($_shortFile) . '/') . '<strong>' . htmlspecialchars(basename($_shortFile)) . '</strong></span>';
        echo '</div>';
        echo '<table class="code-table"><tbody>';
        foreach ($_codeLines as $ln => $code) {
            $isHL = ($ln === $_line);
            $rowClass = $isHL ? ' class="highlight"' : '';
            $arrow = $isHL ? '<span class="hl-arrow">▶</span>' : '';
            echo '<tr' . $rowClass . '>';
            echo '<td class="ln">' . $ln . '</td>';
            echo '<td class="code-line">' . $arrow . htmlspecialchars($code) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    // Stack trace frames
    echo '<p class="section-title">Stack Trace</p>';
    echo '<div class="trace-list">';

    foreach ($_trace as $idx => $frame) {
        $frameFile  = isset($frame['file']) ? str_replace((defined('VNL_ROOT') ? VNL_ROOT : ''), '', $frame['file']) : '[internal function]';
        $frameLine  = $frame['line'] ?? '';
        $frameClass = $frame['class'] ?? '';
        $frameType  = $frame['type'] ?? '';
        $frameFn    = $frame['function'] ?? '';
        $frameArgs  = $frame['args'] ?? [];

        $fnHtml = '';
        if ($frameClass) {
            $fnHtml = '<span class="class-name">' . htmlspecialchars($frameClass) . '</span>'
                    . '<span class="method-sep">' . htmlspecialchars($frameType) . '</span>'
                    . '<span class="method-name">' . htmlspecialchars($frameFn) . '()</span>';
        } else {
            $fnHtml = '<span class="method-name">' . htmlspecialchars($frameFn) . '()</span>';
        }

        echo '<details class="trace-item" id="frame-' . $idx . '">';
        echo '<summary>';
        echo '<span class="trace-num">' . ($idx + 1) . '</span>';
        echo '<div class="trace-info">';
        echo '<div class="trace-fn">' . $fnHtml . '</div>';
        echo '<div class="trace-location">';
        if ($frameLine) {
            echo '<span class="path">' . htmlspecialchars($frameFile) . '</span> <span style="color:var(--accent)">:' . $frameLine . '</span>';
        } else {
            echo '<span style="color:var(--text-muted)">' . htmlspecialchars($frameFile) . '</span>';
        }
        echo '</div>';
        echo '</div>';
        echo '</summary>';
        echo '<div class="trace-args">';
        if (empty($frameArgs)) {
            echo '<span class="no-args">Parametre yok</span>';
        } else {
            foreach ($frameArgs as $argIdx => $arg) {
                $type = gettype($arg);
                $val  = '';
                switch ($type) {
                    case 'string':  $val = '"' . htmlspecialchars(mb_substr($arg, 0, 120)) . (mb_strlen($arg) > 120 ? '…' : '') . '"'; break;
                    case 'integer':
                    case 'double':  $val = htmlspecialchars((string)$arg); break;
                    case 'boolean': $val = $arg ? 'true' : 'false'; break;
                    case 'NULL':    $val = 'null'; break;
                    case 'array':   $val = 'array(' . count($arg) . ')'; break;
                    case 'object':  $val = 'object(' . get_class($arg) . ')'; break;
                    default:        $val = htmlspecialchars($type); break;
                }
                echo '<div class="arg-item">';
                echo '<span class="arg-key">$arg' . $argIdx . '</span>';
                echo '<span class="arg-type"> ' . $type . ' </span>';
                echo '<span class="arg-val">= ' . $val . '</span>';
                echo '</div>';
            }
        }
        echo '</div>';
        echo '</details>';
    }

    echo '</div>';

    // Environment
    echo '<p class="section-title">Ortam Bilgisi</p>';
    echo '<div class="env-grid">';
    $envItems = [
        'PHP Sürümü'   => $_phpVersion,
        'VNL Sürümü'   => $_vnlVersion,
        'Ortam'        => (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'CLI'),
        'İstisna'      => $_class,
        'Tarih/Saat'   => date('d.m.Y H:i:s'),
        'PHP Arayüzü'  => PHP_SAPI,
    ];
    foreach ($envItems as $label => $value) {
        echo '<div class="env-card">';
        echo '<div class="env-label">' . htmlspecialchars($label) . '</div>';
        echo '<div class="env-value">' . htmlspecialchars($value) . '</div>';
        echo '</div>';
    }
    echo '</div>';

    echo <<<HTML
  </main>
</div>

<script>
  // Tema
  const saved = localStorage.getItem('vnl-err-theme') || 'dark';
  setTheme(saved);

  function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    document.getElementById('themeLabel').textContent = t === 'dark' ? 'Light' : 'Dark';
    localStorage.setItem('vnl-err-theme', t);
  }
  function toggleTheme() {
    const cur = document.documentElement.getAttribute('data-theme');
    setTheme(cur === 'dark' ? 'light' : 'dark');
  }

  // Sidebar aktif frame scroll
  function scrollToFrame(idx) {
    const el = document.getElementById('frame-' + idx);
    if (el) { el.open = true; el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    document.querySelectorAll('.sidebar-item[data-idx]').forEach(i => i.classList.remove('active'));
    const sItem = document.querySelector('.sidebar-item[data-idx="' + idx + '"]');
    if (sItem) sItem.classList.add('active');
  }
</script>
</body>
</html>
HTML;

    exit(1);
}
