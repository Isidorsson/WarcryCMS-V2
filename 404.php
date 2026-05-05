<?php
http_response_code(404);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/warcry/404.php');
$basePath = preg_replace('#/404\.php$#i', '', $scriptName);
$basePath = rtrim($basePath, '/');
if ($basePath === '' || $basePath === '.') {
    $basePath = '/warcry';
}

$homeUrl = $basePath . '/index.php';
$assetsBase = $basePath . '/template/forums/style/images';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5;url=<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <title>404 - Page Not Found | WarcryCMS</title>
    <style>
        * { box-sizing: border-box; }
        html, body { min-height: 100%; margin: 0; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            color: #c7b98a;
            background:
                radial-gradient(circle at 50% 18%, rgba(169, 94, 24, .20), transparent 30%),
                radial-gradient(circle at 50% 100%, rgba(0, 0, 0, .70), transparent 38%),
                url('<?php echo htmlspecialchars($assetsBase, ENT_QUOTES, 'UTF-8'); ?>/background.jpg') center top / cover no-repeat fixed,
                #050403;
            overflow-x: hidden;
        }
        body:before {
            content: "";
            position: fixed;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(0,0,0,.92), rgba(0,0,0,.30) 35%, rgba(0,0,0,.30) 65%, rgba(0,0,0,.92)),
                radial-gradient(circle, transparent 0 45%, rgba(0,0,0,.78) 100%);
            pointer-events: none;
        }
        .wrap {
            position: relative;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }
        .panel {
            width: min(860px, 96vw);
            min-height: 520px;
            text-align: center;
            padding: 42px 34px 38px;
            border: 1px solid rgba(147, 106, 48, .55);
            border-radius: 6px;
            background:
                linear-gradient(180deg, rgba(39, 31, 24, .96), rgba(14, 13, 11, .97)),
                rgba(20, 16, 12, .96);
            box-shadow:
                0 0 0 1px rgba(255, 214, 122, .07) inset,
                0 35px 90px rgba(0,0,0,.86),
                0 0 80px rgba(120, 45, 0, .18);
            position: relative;
            overflow: hidden;
        }
        .panel:before, .panel:after {
            content: "";
            position: absolute;
            left: 22px;
            right: 22px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(229, 166, 70, .55), transparent);
        }
        .panel:before { top: 18px; }
        .panel:after { bottom: 18px; }
        .ornament {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, transparent 0 48%, rgba(255,255,255,.025) 50%, transparent 52%),
                repeating-linear-gradient(0deg, rgba(255,255,255,.018) 0 1px, transparent 1px 4px);
            opacity: .55;
            pointer-events: none;
        }
        .gear-stage {
            position: relative;
            width: 154px;
            height: 154px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            filter: drop-shadow(0 0 28px rgba(186, 111, 31, .35));
        }
        .gear {
            width: 112px;
            height: 112px;
            border-radius: 50%;
            background:
                repeating-conic-gradient(from 0deg, #bd8135 0 8deg, #5d3b1e 8deg 15deg),
                radial-gradient(circle, #2b2118 0 31%, transparent 32% 100%);
            position: relative;
            animation: spin 4s linear infinite;
            border: 2px solid #e0a44b;
            box-shadow:
                inset 0 0 0 13px #19140f,
                inset 0 0 0 18px rgba(232, 177, 83, .55),
                0 0 0 1px rgba(0,0,0,.8);
        }
        .gear:before {
            content: "";
            position: absolute;
            inset: 32px;
            border-radius: 50%;
            background: #090807;
            border: 2px solid rgba(231, 175, 81, .72);
            box-shadow: inset 0 0 16px rgba(0,0,0,.8);
        }
        .gear:after {
            content: "";
            position: absolute;
            inset: -18px;
            border-radius: 50%;
            background: repeating-conic-gradient(from 0deg, #9b632b 0 7deg, transparent 7deg 15deg);
            z-index: -1;
        }
        .gear-small {
            position: absolute;
            right: 14px;
            bottom: 18px;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: repeating-conic-gradient(from 0deg, #c9954c 0 10deg, #52351c 10deg 20deg);
            border: 1px solid #d49b4b;
            animation: spinReverse 2.4s linear infinite;
            box-shadow: inset 0 0 0 8px #12100d;
        }
        .gear-small:before {
            content: "";
            position: absolute;
            inset: 20px;
            border-radius: 50%;
            background: #090807;
            border: 1px solid rgba(231, 175, 81, .7);
        }
        .label {
            margin: 0 auto 8px;
            color: #e7b45d;
            font: 700 13px Arial, Helvetica, sans-serif;
            text-transform: uppercase;
            letter-spacing: 4px;
            text-shadow: 0 0 12px rgba(199, 79, 29, .45);
        }
        h1 {
            margin: 0;
            font-size: clamp(60px, 11vw, 112px);
            line-height: .85;
            color: #f0d89b;
            text-shadow: 0 2px 0 #2a1708, 0 0 34px rgba(190, 83, 20, .38);
        }
        h2 {
            margin: 16px 0 12px;
            font-size: clamp(22px, 4vw, 34px);
            color: #fff1c5;
            font-weight: normal;
        }
        p {
            max-width: 620px;
            margin: 0 auto;
            color: #94886a;
            font-size: 16px;
            line-height: 1.65;
        }
        .count-row {
            margin: 30px auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            color: #8f846d;
            font: 700 13px Arial, Helvetica, sans-serif;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .count {
            width: 64px;
            height: 64px;
            display: grid;
            place-items: center;
            border-radius: 4px;
            color: #ffcf68;
            font-size: 32px;
            background: linear-gradient(180deg, rgba(0,0,0,.65), rgba(41,31,20,.88));
            border: 1px solid rgba(218, 163, 72, .55);
            box-shadow: inset 0 0 20px rgba(0,0,0,.75), 0 0 22px rgba(169,86,20,.18);
            letter-spacing: 0;
        }
        .btn {
            display: inline-block;
            padding: 13px 28px;
            color: #201206;
            text-decoration: none;
            font: 900 14px Arial, Helvetica, sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 3px;
            border: 1px solid #f4cf78;
            background: linear-gradient(180deg, #f2c76d, #a86419);
            box-shadow: 0 10px 28px rgba(0,0,0,.38), inset 0 1px 0 rgba(255,255,255,.35);
        }
        .btn:hover { filter: brightness(1.08); }
        .small {
            margin-top: 18px;
            font-size: 12px;
            color: #635944;
            font-family: Arial, Helvetica, sans-serif;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes spinReverse { to { transform: rotate(-360deg); } }
        @media (prefers-reduced-motion: reduce) {
            .gear, .gear-small { animation: none; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="panel" aria-label="Page not found">
            <div class="ornament"></div>
            <div class="gear-stage" aria-hidden="true">
                <div class="gear"></div>
                <div class="gear-small"></div>
            </div>
            <div class="label">WarcryCMS Navigation Error</div>
            <h1>404</h1>
            <h2>Page Not Found</h2>
            <p>The requested page does not exist, has been moved, or the link is invalid. The portal mechanism is recalibrating and will return you to Warcry automatically.</p>
            <div class="count-row">
                <span>Redirecting in</span>
                <span class="count" id="countdown">5</span>
                <span>seconds</span>
            </div>
            <a class="btn" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Return to Warcry</a>
            <p class="small">Destination: <?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?></p>
        </section>
    </main>
    <script>
        (function () {
            var seconds = 5;
            var target = <?php echo json_encode($homeUrl); ?>;
            var box = document.getElementById('countdown');
            var timer = setInterval(function () {
                seconds -= 1;
                box.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = target;
                }
            }, 1000);
        })();
    </script>
</body>
</html>
