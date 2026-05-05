<?php
http_response_code(404);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($basePath === '' || $basePath === '.') {
    $basePath = '';
}
$homeUrl = $basePath . '/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5;url=<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <title>404 - Page Not Found | Warcry</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #f4f4f4;
            background:
                radial-gradient(circle at top left, rgba(211, 17, 38, .22), transparent 38%),
                radial-gradient(circle at bottom right, rgba(255, 183, 64, .12), transparent 34%),
                #070709;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: min(720px, 100%);
            text-align: center;
            padding: 46px 34px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.035));
            box-shadow: 0 24px 80px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.08);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 92px;
            height: 92px;
            border-radius: 50%;
            margin-bottom: 22px;
            background: rgba(211, 17, 38, .18);
            border: 1px solid rgba(255, 80, 95, .35);
            color: #ff4052;
            font-size: 34px;
            font-weight: 900;
            letter-spacing: -2px;
        }
        h1 { margin: 0 0 10px; font-size: clamp(34px, 6vw, 64px); line-height: 1; }
        p { margin: 0 auto; max-width: 560px; color: #cfcfd6; font-size: 16px; line-height: 1.6; }
        .count {
            margin: 28px auto 24px;
            width: 82px;
            height: 82px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(0,0,0,.28);
            font-size: 32px;
            font-weight: 800;
            color: #ffd08a;
        }
        a {
            display: inline-block;
            padding: 13px 22px;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            background: linear-gradient(135deg, #c90d24, #ff3348);
            box-shadow: 0 10px 30px rgba(211,17,38,.25);
            font-weight: 700;
        }
        .small { margin-top: 18px; font-size: 13px; color: #8d8d98; }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">404</div>
        <h1>Page Not Found</h1>
        <p>The page you are looking for does not exist or has been moved. You will be redirected back to Warcry automatically.</p>
        <div class="count" id="countdown">5</div>
        <a href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Return to Warcry</a>
        <p class="small">Redirecting in <span id="countdownText">5</span> seconds...</p>
    </main>
    <script>
        (function () {
            var seconds = 5;
            var target = <?php echo json_encode($homeUrl); ?>;
            var box = document.getElementById('countdown');
            var text = document.getElementById('countdownText');
            var timer = setInterval(function () {
                seconds -= 1;
                box.textContent = seconds;
                text.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = target;
                }
            }, 1000);
        })();
    </script>
</body>
</html>
