<?php
if (!defined('init_engine')) {
    header('HTTP/1.0 404 not found');
    exit;
}

if (!function_exists('warcry_render_database_error')) {
    function warcry_render_database_error($title, $message, $configFile, $debug = false, $exceptionMessage = '')
    {
        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
        }

        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $safeConfigFile = htmlspecialchars($configFile, ENT_QUOTES, 'UTF-8');
        $safeException = htmlspecialchars($exceptionMessage, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . $safeTitle . ' - Warcry</title>
    <style>
        :root{--bg:#0b0f17;--panel:#111827;--panel2:#172033;--text:#eef2ff;--muted:#9ca3af;--accent:#d4af37;--danger:#ef4444;--line:rgba(255,255,255,.1)}
        *{box-sizing:border-box} body{margin:0;min-height:100vh;font-family:Arial,Helvetica,sans-serif;background:radial-gradient(circle at top,#26324d 0,#0b0f17 42%,#05070c 100%);color:var(--text);display:flex;align-items:center;justify-content:center;padding:24px}
        .wrap{width:min(900px,100%)} .card{background:linear-gradient(145deg,rgba(17,24,39,.96),rgba(10,14,22,.96));border:1px solid var(--line);border-radius:22px;box-shadow:0 24px 80px rgba(0,0,0,.45);overflow:hidden}
        .top{padding:26px 30px;border-bottom:1px solid var(--line);background:linear-gradient(90deg,rgba(212,175,55,.16),rgba(239,68,68,.09),transparent)}
        .brand{letter-spacing:.18em;text-transform:uppercase;color:var(--accent);font-weight:700;font-size:13px;margin-bottom:10px}.title{font-size:30px;font-weight:800;margin:0 0 8px}.sub{color:var(--muted);font-size:16px;line-height:1.55;margin:0}
        .content{padding:28px 30px;display:grid;gap:18px}.alert{border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.09);border-radius:16px;padding:18px}.alert strong{color:#fecaca}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.box{background:rgba(255,255,255,.04);border:1px solid var(--line);border-radius:16px;padding:16px}.box h3{margin:0 0 8px;font-size:15px;color:var(--accent);text-transform:uppercase;letter-spacing:.08em}.box p,.box li{color:var(--muted);line-height:1.55}.box p{margin:0}.box ul{margin:0;padding-left:18px}code{background:rgba(0,0,0,.35);border:1px solid var(--line);border-radius:8px;padding:2px 6px;color:#fff}.debug{white-space:pre-wrap;background:#05070c;border:1px solid var(--line);border-radius:14px;padding:14px;color:#fca5a5;overflow:auto}.foot{padding:16px 30px;border-top:1px solid var(--line);color:var(--muted);font-size:13px}@media(max-width:720px){.grid{grid-template-columns:1fr}.title{font-size:24px}.top,.content,.foot{padding-left:20px;padding-right:20px}}
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <div class="top">
                <div class="brand">Warcry CMS</div>
                <h1 class="title">' . $safeTitle . '</h1>
                <p class="sub">The website is online, but the database connection must be configured before continuing.</p>
            </div>

            <div class="content">
                <div class="alert">
                    <strong>Details:</strong> ' . $safeMessage . '
                </div>

                <div class="grid">
                    <div class="box">
                        <h3>File to edit</h3>
                        <p><code>' . $safeConfigFile . '</code></p>
                    </div>

                    <div class="box">
                        <h3>What to check</h3>
                        <ul>
                            <li>Database name</li>
                            <li>MySQL user</li>
                            <li>MySQL password</li>
                            <li>Server (usually <code>localhost</code>)</li>
                        </ul>
                    </div>
                </div>

                <div class="box">
                    <h3>WAMP Tip</h3>
                    <p>On a local WAMP setup, the user is usually <code>root</code> and the password is often empty. Also make sure to import the SQL file in phpMyAdmin if the database does not exist yet.</p>
                </div>';
        
        if ($debug && $safeException !== '') {
            echo '<div class="box">
                    <h3>Technical Debug</h3>
                    <div class="debug">' . $safeException . '</div>
                  </div>';
        }

        echo '</div>
            <div class="foot">Temporary Error 503 — Configuration required.</div>
        </section>
    </main>
</body>
</html>';
        exit;
    }
}