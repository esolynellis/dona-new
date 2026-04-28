<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
?><!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DONA App Preview</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #1a1a2e; font-family: -apple-system, sans-serif; min-height: 100vh; }

.header {
    background: #16213e;
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #0f3460;
}
.header h1 { color: #e94560; font-size: 20px; font-weight: 700; }
.header a {
    background: #e94560;
    color: #fff;
    padding: 8px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.devices {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 60px;
    padding: 40px 20px;
    flex-wrap: wrap;
}

.device-wrap { text-align: center; }
.device-label {
    color: #a0a0b0;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 16px;
}
.device-name { color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 4px; }

/* Samsung phone frame */
.samsung-frame {
    width: 340px;
    height: 680px;
    background: #111;
    border-radius: 40px;
    padding: 14px 10px;
    box-shadow: 0 0 0 2px #333, 0 0 0 4px #555, 0 20px 60px rgba(0,0,0,0.6);
    position: relative;
    display: inline-block;
}
.samsung-frame::before {
    content: '';
    position: absolute;
    top: 14px; left: 50%; transform: translateX(-50%);
    width: 60px; height: 6px;
    background: #222;
    border-radius: 3px;
}
.samsung-frame::after {
    content: '';
    position: absolute;
    bottom: 12px; left: 50%; transform: translateX(-50%);
    width: 80px; height: 4px;
    background: #333;
    border-radius: 2px;
}
.samsung-screen {
    width: 100%;
    height: 100%;
    border-radius: 28px;
    overflow: hidden;
    background: #000;
}

/* iPhone frame */
.iphone-frame {
    width: 310px;
    height: 660px;
    background: #1c1c1e;
    border-radius: 50px;
    padding: 16px 10px;
    box-shadow: 0 0 0 2px #3a3a3c, 0 0 0 4px #636366, 0 20px 60px rgba(0,0,0,0.6);
    position: relative;
    display: inline-block;
}
.iphone-frame::before {
    content: '';
    position: absolute;
    top: 16px; left: 50%; transform: translateX(-50%);
    width: 100px; height: 26px;
    background: #1c1c1e;
    border-radius: 0 0 18px 18px;
    z-index: 10;
}
.iphone-screen {
    width: 100%;
    height: 100%;
    border-radius: 36px;
    overflow: hidden;
    background: #000;
}

iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

.refresh-btn {
    display: inline-block;
    margin-top: 16px;
    background: #0f3460;
    color: #fff;
    border: none;
    padding: 8px 24px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: background 0.2s;
}
.refresh-btn:hover { background: #e94560; }

.note {
    color: #555;
    font-size: 11px;
    margin-top: 8px;
    max-width: 340px;
    line-height: 1.5;
}
</style>
</head>
<body>

<div class="header">
    <h1>📱 DONA App Preview</h1>
    <a href="https://www.dona-trade.com/admin" target="_blank">⚙️ Admin Panel</a>
</div>

<div class="devices">

    <div class="device-wrap">
        <div class="device-name">Samsung (Android)</div>
        <div class="device-label">Galaxy S series · 360×780</div>
        <div class="samsung-frame">
            <div class="samsung-screen">
                <iframe id="samsung-frame" src="https://www.dona-trade.com/?preview=mobile" loading="lazy"></iframe>
            </div>
        </div>
        <button class="refresh-btn" onclick="document.getElementById('samsung-frame').src=document.getElementById('samsung-frame').src">↺ Refresh</button>
        <div class="note">Admin-д өөрчлөлт хийгээд энд Refresh дарахад шинэчлэгдэнэ</div>
    </div>

    <div class="device-wrap">
        <div class="device-name">iPhone</div>
        <div class="device-label">iPhone 15 · 390×844</div>
        <div class="iphone-frame">
            <div class="iphone-screen">
                <iframe id="iphone-frame" src="https://www.dona-trade.com/?preview=mobile" loading="lazy"></iframe>
            </div>
        </div>
        <button class="refresh-btn" onclick="document.getElementById('iphone-frame').src=document.getElementById('iphone-frame').src">↺ Refresh</button>
        <div class="note">Admin-д өөрчлөлт хийгээд энд Refresh дарахад шинэчлэгдэнэ</div>
    </div>

</div>

</body>
</html>
