<?php
$rootPath = dirname(__DIR__, 4);
require_once $rootPath . '/gateway/bootstrap.php';

function lw_public_prefix(): string
{
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if ($path === '/lawangsewu' || str_starts_with($path, '/lawangsewu/')) {
        return '/lawangsewu';
    }

    return '';
}

function lw_public_url(string $path): string
{
    return lw_public_prefix() . $path;
}

$portalUrl = lw_public_url('/portal');
$landingUrl = lw_public_url('/');
$loginError = '';
$submittedUsername = '';
$showLoginPanel = false;
$loggedInUser = gateway_auth_user();
$loggedInName = is_array($loggedInUser)
    ? (string) (($loggedInUser['full_name'] ?? '') !== '' ? $loggedInUser['full_name'] : ($loggedInUser['username'] ?? 'Portal User'))
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['landing_login'] ?? '') === '1') {
    $submittedUsername = trim((string) ($_POST['username'] ?? ''));
    $result = gateway_attempt_login($submittedUsername, (string) ($_POST['password'] ?? ''));
    if (!empty($result['ok'])) {
        header('Location: ' . $portalUrl);
        exit;
    }

    $loginError = (string) ($result['message'] ?? 'Login gagal.');
    $showLoginPanel = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAWANGSEWU</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-0: #03050b;
            --bg-1: #09111f;
            --bg-2: #101a2b;
            --ink: #eef7ff;
            --muted: rgba(207, 222, 237, 0.72);
            --line: rgba(174, 195, 221, 0.14);
            --silver-0: #ffffff;
            --silver-1: #f5f7fb;
            --silver-2: #d6ddea;
            --silver-3: #98a4b8;
            --blue-metal-0: #d9eeff;
            --blue-metal-1: #8bbdff;
            --blue-metal-2: #528dca;
            --accent: #8fd2ff;
            --portal-green-0: #3bc65f;
            --portal-green-1: #1a8917;
            --green: #209a34;
            --green-dark: #0f6d13;
            --danger: #ff6d74;
            --shadow: 0 28px 80px rgba(0, 0, 0, 0.46);
        }
        * { box-sizing: border-box; }
        html, body {
            width: 100%;
            height: 100%;
        }
        body {
            margin: 0;
            overflow: hidden;
            color: var(--ink);
            font-family: 'Manrope', sans-serif;
            background:
                radial-gradient(circle at 50% 16%, rgba(94, 135, 192, 0.10), transparent 16%),
                radial-gradient(circle at 18% 20%, rgba(96, 154, 231, 0.08), transparent 22%),
                radial-gradient(circle at 80% 14%, rgba(122, 165, 225, 0.08), transparent 18%),
                linear-gradient(180deg, #02040a 0%, #07101d 44%, #0a1321 66%, #050811 100%);
        }
        .backdrop,
        .backdrop::before,
        .backdrop::after {
            position: fixed;
            inset: 0;
            pointer-events: none;
        }
        .backdrop::before {
            content: '';
            background:
                radial-gradient(circle at 10% 18%, rgba(153, 212, 255, 0.95) 0 1px, transparent 2px),
                radial-gradient(circle at 22% 32%, rgba(132, 187, 255, 0.8) 0 1.2px, transparent 2.4px),
                radial-gradient(circle at 37% 12%, rgba(176, 224, 255, 0.85) 0 1px, transparent 2px),
                radial-gradient(circle at 49% 26%, rgba(130, 196, 255, 0.92) 0 1.3px, transparent 2.8px),
                radial-gradient(circle at 64% 14%, rgba(154, 211, 255, 0.78) 0 1px, transparent 2.2px),
                radial-gradient(circle at 77% 28%, rgba(109, 180, 255, 0.94) 0 1.1px, transparent 2.6px),
                radial-gradient(circle at 88% 10%, rgba(183, 229, 255, 0.78) 0 1px, transparent 2px),
                radial-gradient(circle at 15% 44%, rgba(146, 205, 255, 0.68) 0 1.1px, transparent 2.6px),
                radial-gradient(circle at 29% 52%, rgba(107, 174, 255, 0.82) 0 1px, transparent 2.4px),
                radial-gradient(circle at 52% 46%, rgba(175, 224, 255, 0.76) 0 1.2px, transparent 2.6px),
                radial-gradient(circle at 71% 40%, rgba(125, 193, 255, 0.84) 0 1.2px, transparent 2.8px),
                radial-gradient(circle at 84% 52%, rgba(170, 221, 255, 0.76) 0 1px, transparent 2.2px);
            animation: starTwinkle 6.8s ease-in-out infinite alternate;
        }
        .backdrop::after {
            content: '';
            background:
                radial-gradient(circle at 50% 12%, rgba(133, 191, 255, 0.12), transparent 22%),
                radial-gradient(circle at 50% 54%, rgba(72, 113, 171, 0.10), transparent 38%);
            filter: blur(46px);
        }
        .starfield,
        .starfield::before,
        .starfield::after {
            position: fixed;
            inset: 0;
            pointer-events: none;
        }
        .star-canvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0.78;
        }
        .starfield::before,
        .starfield::after {
            content: '';
            width: 2px;
            height: 2px;
            border-radius: 50%;
            background: rgba(163, 213, 255, 0.92);
        }
        .starfield::before {
            top: 0;
            left: 0;
            box-shadow:
                5vw 9vh rgba(171, 219, 255, 0.9), 12vw 22vh rgba(107, 176, 255, 0.76), 19vw 14vh rgba(174, 227, 255, 0.88), 24vw 35vh rgba(119, 191, 255, 0.78), 31vw 18vh rgba(171, 219, 255, 0.9), 38vw 10vh rgba(117, 187, 255, 0.82), 44vw 28vh rgba(177, 227, 255, 0.84), 51vw 16vh rgba(116, 184, 255, 0.72), 58vw 24vh rgba(175, 224, 255, 0.82), 64vw 8vh rgba(115, 180, 255, 0.84), 70vw 19vh rgba(167, 217, 255, 0.86), 77vw 30vh rgba(122, 192, 255, 0.8), 83vw 14vh rgba(177, 229, 255, 0.88), 90vw 26vh rgba(131, 197, 255, 0.76), 95vw 11vh rgba(174, 225, 255, 0.84), 8vw 41vh rgba(122, 191, 255, 0.68), 16vw 48vh rgba(172, 222, 255, 0.76), 27vw 43vh rgba(116, 182, 255, 0.7), 35vw 54vh rgba(174, 226, 255, 0.72), 47vw 39vh rgba(125, 191, 255, 0.72), 55vw 49vh rgba(177, 227, 255, 0.76), 67vw 44vh rgba(122, 187, 255, 0.68), 74vw 51vh rgba(166, 219, 255, 0.74), 88vw 42vh rgba(122, 189, 255, 0.7), 6vw 30vh rgba(178, 227, 255, 0.72), 21vw 9vh rgba(107, 176, 255, 0.66), 41vw 6vh rgba(183, 231, 255, 0.76), 59vw 12vh rgba(119, 191, 255, 0.7), 73vw 8vh rgba(176, 226, 255, 0.78), 92vw 18vh rgba(115, 180, 255, 0.72);
            animation: starDrift 18s linear infinite, starPulse 4.2s ease-in-out infinite;
        }
        .starfield::after {
            top: 0;
            left: 0;
            width: 3px;
            height: 3px;
            background: rgba(191, 233, 255, 0.96);
            box-shadow:
                10vw 15vh rgba(191, 233, 255, 0.92), 18vw 29vh rgba(98, 166, 255, 0.86), 26vw 8vh rgba(194, 235, 255, 0.94), 34vw 24vh rgba(98, 166, 255, 0.76), 41vw 17vh rgba(188, 232, 255, 0.88), 48vw 32vh rgba(98, 166, 255, 0.84), 56vw 12vh rgba(194, 235, 255, 0.92), 62vw 27vh rgba(104, 170, 255, 0.8), 69vw 6vh rgba(188, 232, 255, 0.88), 76vw 22vh rgba(98, 166, 255, 0.76), 82vw 18vh rgba(194, 235, 255, 0.92), 91vw 31vh rgba(98, 166, 255, 0.8), 13vw 38vh rgba(188, 232, 255, 0.84), 32vw 45vh rgba(104, 170, 255, 0.74), 52vw 41vh rgba(194, 235, 255, 0.88), 73vw 46vh rgba(98, 166, 255, 0.78), 87vw 37vh rgba(188, 232, 255, 0.82), 16vw 6vh rgba(194, 235, 255, 0.84), 45vw 10vh rgba(98, 166, 255, 0.74), 66vw 16vh rgba(188, 232, 255, 0.88), 93vw 8vh rgba(98, 166, 255, 0.78);
            filter: blur(0.2px);
            animation: starTwinkle 3.8s ease-in-out infinite alternate;
        }
        .shell {
            position: relative;
            width: min(1480px, calc(100% - 32px));
            height: 100svh;
            margin: 0 auto;
            display: grid;
            place-items: center;
            padding: 28px 0;
        }
        .hero {
            position: relative;
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            text-align: center;
        }
        .hero::before {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 14vh;
            transform: translateX(-50%);
            width: min(860px, 84vw);
            height: min(300px, 28vh);
            background: radial-gradient(circle at center, rgba(178, 214, 255, 0.14), rgba(78, 119, 179, 0.10) 44%, rgba(4, 7, 14, 0) 72%);
            filter: blur(28px);
        }
        .hero-aura {
            position: absolute;
            left: 50%;
            top: 43%;
            transform: translate(-50%, -50%);
            width: min(740px, 70vw);
            height: min(740px, 70vw);
            pointer-events: none;
            z-index: 0;
            border-radius: 50%;
            background:
                radial-gradient(circle at center, rgba(183, 221, 255, 0.22) 0%, rgba(105, 148, 212, 0.14) 18%, rgba(49, 77, 115, 0.08) 36%, rgba(5, 8, 16, 0) 70%);
            filter: blur(14px);
            opacity: 0.92;
        }
        .hero-aura::before,
        .hero-aura::after {
            content: '';
            position: absolute;
            inset: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            border: 1px solid rgba(196, 216, 240, 0.12);
        }
        .hero-aura::before {
            width: 72%;
            height: 72%;
            background:
                radial-gradient(circle at center, rgba(255,255,255,0.08), rgba(255,255,255,0) 62%),
                conic-gradient(from 210deg, rgba(146, 186, 231, 0) 0deg, rgba(146, 186, 231, 0.16) 34deg, rgba(146, 186, 231, 0) 72deg, rgba(234, 242, 252, 0.08) 118deg, rgba(146, 186, 231, 0) 180deg, rgba(146, 186, 231, 0.12) 232deg, rgba(146, 186, 231, 0) 280deg, rgba(255,255,255,0.10) 330deg, rgba(146, 186, 231, 0) 360deg);
            box-shadow: inset 0 0 80px rgba(203, 221, 245, 0.06);
        }
        .hero-aura::after {
            width: 92%;
            height: 92%;
            background:
                radial-gradient(circle at center, rgba(255,255,255,0.12), rgba(255,255,255,0) 58%);
            opacity: 0.72;
        }
        .hero-rings,
        .hero-rings::before,
        .hero-rings::after {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            border-radius: 50%;
            z-index: 0;
        }
        .hero-rings {
            width: min(620px, 62vw);
            height: min(620px, 62vw);
            border: 1px solid rgba(178, 199, 225, 0.08);
            opacity: 0.8;
        }
        .hero-rings::before,
        .hero-rings::after {
            content: '';
            border: 1px solid rgba(178, 199, 225, 0.08);
        }
        .hero-rings::before {
            width: 76%;
            height: 76%;
        }
        .hero-rings::after {
            width: 122%;
            height: 122%;
        }
        .hero-mist {
            position: absolute;
            left: 50%;
            bottom: 12vh;
            transform: translateX(-50%);
            width: min(920px, 86vw);
            height: 160px;
            border-radius: 999px;
            background: radial-gradient(circle at center, rgba(170, 208, 247, 0.16), rgba(84, 118, 163, 0.12) 40%, rgba(4, 6, 12, 0) 74%);
            filter: blur(32px);
            opacity: 0.72;
            pointer-events: none;
            z-index: 0;
        }
        .moon-horizon {
            position: absolute;
            inset: auto 0 -2vh;
            height: 30vh;
            z-index: 0;
            pointer-events: none;
        }
        .moon-surface {
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: min(1500px, 128vw);
            height: 100%;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
            background:
                radial-gradient(circle at 14% 46%, rgba(102, 123, 146, 0.22) 0 4%, rgba(166, 188, 214, 0.06) 5%, transparent 7%),
                radial-gradient(circle at 20% 38%, rgba(169, 194, 221, 0.18) 0 5%, transparent 6%),
                radial-gradient(circle at 31% 58%, rgba(89, 110, 134, 0.18) 0 3%, rgba(170, 196, 224, 0.06) 4%, transparent 6%),
                radial-gradient(circle at 37% 42%, rgba(150, 176, 206, 0.16) 0 4%, transparent 5%),
                radial-gradient(circle at 48% 34%, rgba(170, 194, 222, 0.14) 0 6%, transparent 7%),
                radial-gradient(circle at 56% 52%, rgba(95, 117, 142, 0.18) 0 3%, rgba(170, 196, 224, 0.05) 4%, transparent 6%),
                radial-gradient(circle at 64% 48%, rgba(143, 171, 201, 0.14) 0 4%, transparent 5%),
                radial-gradient(circle at 76% 41%, rgba(94, 116, 139, 0.18) 0 3.5%, rgba(171, 196, 224, 0.05) 5%, transparent 6.5%),
                radial-gradient(circle at 82% 38%, rgba(168, 194, 226, 0.13) 0 5%, transparent 6%),
                linear-gradient(180deg, rgba(77, 102, 138, 0.84) 0%, rgba(45, 62, 85, 0.94) 38%, rgba(24, 34, 48, 0.99) 100%);
            box-shadow: 0 -28px 80px rgba(89, 128, 184, 0.18), inset 0 22px 34px rgba(187, 217, 255, 0.10), inset 0 -16px 30px rgba(0, 0, 0, 0.28);
        }
        .moon-surface::before,
        .moon-surface::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            border-radius: inherit;
        }
        .moon-surface::before {
            background:
                radial-gradient(ellipse at 24% 32%, rgba(207, 227, 249, 0.10), rgba(207, 227, 249, 0) 18%),
                radial-gradient(ellipse at 54% 18%, rgba(197, 220, 245, 0.10), rgba(197, 220, 245, 0) 20%),
                radial-gradient(ellipse at 76% 28%, rgba(193, 217, 244, 0.08), rgba(193, 217, 244, 0) 16%);
        }
        .moon-surface::after {
            background: linear-gradient(180deg, rgba(221, 236, 255, 0.10), rgba(0, 0, 0, 0) 24%, rgba(0, 0, 0, 0.14) 100%);
        }
        .moon-ridge,
        .moon-ridge::before,
        .moon-ridge::after {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 50% 50% 0 0;
            pointer-events: none;
        }
        .moon-ridge {
            bottom: 12vh;
            width: min(1100px, 108vw);
            height: 12vh;
            background: linear-gradient(180deg, rgba(42, 67, 98, 0.28), rgba(17, 25, 36, 0.78));
            filter: blur(1px);
            z-index: 0;
        }
        .moon-ridge::before {
            content: '';
            bottom: -1vh;
            width: 78%;
            height: 8vh;
            background: linear-gradient(180deg, rgba(76, 108, 148, 0.24), rgba(18, 27, 39, 0.8));
        }
        .moon-ridge::after {
            content: '';
            bottom: 2vh;
            width: 40%;
            height: 4vh;
            background: linear-gradient(180deg, rgba(101, 136, 181, 0.22), rgba(24, 34, 48, 0.4));
        }
        .title-wrap {
            position: relative;
            z-index: 2;
            display: grid;
            gap: 14px;
            place-items: center;
            width: min(100%, 1440px);
            padding: 20px;
            justify-items: center;
        }
        .title-energy {
            position: absolute;
            inset: -22% -10%;
            opacity: 0;
            pointer-events: none;
        }
        .title-energy span {
            position: absolute;
            display: block;
            width: 22%;
            height: 2px;
            background: linear-gradient(90deg, rgba(126, 196, 255, 0), rgba(214, 244, 255, 1) 46%, rgba(126, 196, 255, 0));
            filter: drop-shadow(0 0 8px rgba(149, 211, 255, 0.92)) drop-shadow(0 0 18px rgba(69, 150, 255, 0.42));
            clip-path: polygon(0 50%, 14% 16%, 28% 70%, 43% 30%, 58% 76%, 76% 22%, 100% 50%, 82% 86%, 62% 40%, 48% 72%, 30% 24%, 14% 68%);
        }
        .title-energy span:nth-child(1) {
            top: 14%;
            left: 6%;
            transform: rotate(-9deg) scaleX(0.2);
        }
        .title-energy span:nth-child(2) {
            top: 26%;
            right: 10%;
            width: 18%;
            transform: rotate(11deg) scaleX(0.2);
        }
        .title-energy span:nth-child(3) {
            top: 54%;
            left: 10%;
            width: 16%;
            transform: rotate(7deg) scaleX(0.2);
        }
        .title-energy span:nth-child(4) {
            top: 72%;
            right: 14%;
            width: 20%;
            transform: rotate(-8deg) scaleX(0.2);
        }
        body.is-booting .title-energy {
            animation: titleEnergyFade 1.15s ease-out forwards;
        }
        body.is-booting .title-energy span:nth-child(1) { animation: arcRunOne 820ms ease-out forwards; }
        body.is-booting .title-energy span:nth-child(2) { animation: arcRunTwo 760ms ease-out 80ms forwards; }
        body.is-booting .title-energy span:nth-child(3) { animation: arcRunThree 720ms ease-out 120ms forwards; }
        body.is-booting .title-energy span:nth-child(4) { animation: arcRunFour 820ms ease-out 160ms forwards; }
        .hero-title {
            position: relative;
            left: 50%;
            display: block;
            width: max-content;
            max-width: min(94vw, 1440px);
            margin: 0;
            transform: translateX(calc(-50% - 14px));
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(44px, 8.2vw, 118px);
            line-height: 0.92;
            letter-spacing: clamp(0.03em, 0.18vw, 0.07em);
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
            color: transparent;
            background: linear-gradient(180deg, var(--silver-0) 0%, var(--silver-1) 14%, var(--silver-2) 40%, var(--silver-0) 56%, var(--silver-3) 80%, var(--silver-1) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            text-shadow:
                0 2px 0 rgba(255,255,255,0.16),
                0 12px 32px rgba(140, 160, 192, 0.16),
                0 0 28px rgba(226, 235, 248, 0.12);
            filter: drop-shadow(0 10px 28px rgba(0, 0, 0, 0.34));
        }
        .hero-title::before {
            content: '';
            position: absolute;
            inset: -8% 8%;
            background: linear-gradient(100deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.46) 46%, rgba(255,255,255,0) 70%);
            opacity: 0.52;
            transform: skewX(-16deg) translateX(-34%);
            filter: blur(8px);
            mix-blend-mode: screen;
            pointer-events: none;
        }
        .subtitle {
            position: relative;
            margin: 0;
            left: 50%;
            transform: translateX(calc(-50% - 6px));
            padding: 4px 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(12px, 1.8vw, 22px);
            letter-spacing: clamp(0.18em, 0.5vw, 0.32em);
            text-transform: uppercase;
            color: transparent;
            background: linear-gradient(180deg, var(--blue-metal-0), var(--blue-metal-1) 40%, var(--blue-metal-2) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            text-shadow: 0 0 12px rgba(107, 174, 255, 0.18), 0 0 24px rgba(107, 174, 255, 0.08);
            overflow: hidden;
        }
        .subtitle::after {
            content: '';
            position: absolute;
            top: -30%;
            bottom: -30%;
            left: -28%;
            width: 24%;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.95), rgba(255,255,255,0));
            transform: skewX(-18deg);
            opacity: 0;
            pointer-events: none;
        }
        .title-wrap:hover .subtitle::after {
            animation: subtitleShimmer 920ms ease 1;
        }
        .footer-credit {
            position: fixed;
            left: 18px;
            bottom: 16px;
            display: inline-flex;
            align-items: center;
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(10, 16, 28, 0.52);
            backdrop-filter: blur(12px);
            color: rgba(223, 236, 247, 0.74);
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            z-index: 5;
        }
        .dock {
            position: fixed;
            right: 18px;
            bottom: 16px;
            display: grid;
            gap: 10px;
            justify-items: end;
            z-index: 6;
        }
        .dock-button {
            appearance: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 18px;
            border: 1px solid rgba(112, 196, 136, 0.22);
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(244, 247, 252, 0.16), rgba(49, 74, 92, 0.20));
            backdrop-filter: blur(16px);
            color: var(--ink);
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: var(--shadow);
        }
        .dock-button::before {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: linear-gradient(180deg, #9bf0b4, #3bc65f);
            box-shadow: 0 0 12px rgba(59, 198, 95, 0.48);
        }
        .dock-panel {
            width: min(360px, calc(100vw - 24px));
            padding: 18px;
            border: 1px solid rgba(211, 225, 241, 0.14);
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(9, 16, 28, 0.96), rgba(10, 18, 31, 0.92));
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow);
            opacity: 0;
            transform: translateY(14px);
            pointer-events: none;
            transition: opacity 200ms ease, transform 200ms ease;
        }
        .dock-panel.is-open {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .panel-mark {
            margin: 0 0 14px;
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(212, 229, 247, 0.82);
        }
        .panel-user {
            margin: 0 0 12px;
            color: rgba(199, 221, 241, 0.84);
            font-size: 13px;
            line-height: 1.6;
        }
        .panel-user strong {
            color: var(--ink);
        }
        .panel-error {
            margin-bottom: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid rgba(255, 109, 116, 0.22);
            background: rgba(255, 109, 116, 0.08);
            color: #ffd5d8;
            font-size: 13px;
        }
        .panel-form {
            display: grid;
            gap: 10px;
        }
        .panel-form input {
            width: 100%;
            min-height: 52px;
            border: 1px solid rgba(211, 225, 241, 0.12);
            border-radius: 16px;
            background: rgba(17, 27, 44, 0.9);
            color: var(--ink);
            padding: 0 14px;
            font: inherit;
        }
        .panel-form input::placeholder {
            color: rgba(176, 196, 218, 0.68);
        }
        .panel-form input:focus {
            outline: none;
            border-color: rgba(143, 192, 238, 0.28);
            box-shadow: 0 0 0 4px rgba(143, 192, 238, 0.08);
        }
        .panel-submit,
        .panel-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            border-radius: 16px;
            border: 0;
            font: inherit;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
        }
        .panel-submit {
            background: linear-gradient(180deg, var(--portal-green-0), var(--portal-green-1));
            color: #fff;
            box-shadow: 0 12px 24px rgba(32, 154, 52, 0.16);
        }
        .panel-link {
            margin-top: 8px;
            background: rgba(245, 247, 252, 0.06);
            border: 1px solid rgba(211, 225, 241, 0.12);
            color: rgba(226, 236, 247, 0.88);
        }
        @keyframes subtitleShimmer {
            0% { transform: skewX(-18deg) translateX(0); opacity: 0; }
            15% { opacity: 1; }
            100% { transform: skewX(-18deg) translateX(520%); opacity: 0; }
        }
        @keyframes starTwinkle {
            0% { opacity: 0.56; }
            50% { opacity: 0.96; }
            100% { opacity: 0.72; }
        }
        @keyframes starDrift {
            0% { transform: translateY(0); }
            100% { transform: translateY(8px); }
        }
        @keyframes starPulse {
            0%, 100% { opacity: 0.64; }
            50% { opacity: 1; }
        }
        @keyframes titleEnergyFade {
            0% { opacity: 1; }
            72% { opacity: 1; }
            100% { opacity: 0; }
        }
        @keyframes arcRunOne {
            0% { opacity: 0; transform: rotate(-9deg) translate(-28px, 10px) scaleX(0.2); }
            22% { opacity: 1; }
            100% { opacity: 0; transform: rotate(-3deg) translate(156px, -8px) scaleX(1.2); }
        }
        @keyframes arcRunTwo {
            0% { opacity: 0; transform: rotate(11deg) translate(22px, -10px) scaleX(0.2); }
            18% { opacity: 1; }
            100% { opacity: 0; transform: rotate(3deg) translate(-140px, 14px) scaleX(1.05); }
        }
        @keyframes arcRunThree {
            0% { opacity: 0; transform: rotate(7deg) translate(-18px, 0) scaleX(0.2); }
            24% { opacity: 1; }
            100% { opacity: 0; transform: rotate(-4deg) translate(128px, -18px) scaleX(0.96); }
        }
        @keyframes arcRunFour {
            0% { opacity: 0; transform: rotate(-8deg) translate(18px, 0) scaleX(0.2); }
            26% { opacity: 1; }
            100% { opacity: 0; transform: rotate(6deg) translate(-150px, -10px) scaleX(1.08); }
        }
        @media (max-width: 920px) {
            .moon-horizon {
                height: 27vh;
            }
        }
        @media (max-width: 640px) {
            .shell {
                width: min(100% - 18px, 1240px);
                height: 100dvh;
            }
            .hero {
                height: 100%;
            }
            .hero-title {
                left: 0;
                width: 100%;
                max-width: 100%;
                margin-inline: auto;
                transform: none;
                font-size: clamp(34px, 13vw, 58px);
                letter-spacing: clamp(0.03em, 0.24vw, 0.05em);
                white-space: normal;
            }
            .subtitle {
                left: 0;
                transform: none;
                font-size: 12px;
                letter-spacing: 0.14em;
            }
            .hero-aura {
                width: 108vw;
                height: 108vw;
                top: 40%;
            }
            .hero-rings {
                width: 92vw;
                height: 92vw;
            }
            .hero-mist {
                width: 92vw;
                bottom: 18vh;
                height: 108px;
            }
            .moon-horizon {
                height: 24vh;
            }
            .moon-ridge {
                width: 124vw;
                bottom: 10vh;
            }
            .dock,
            .footer-credit {
                right: 10px;
                left: 10px;
            }
            .dock {
                justify-items: stretch;
            }
            .dock-button,
            .dock-panel,
            .panel-link {
                width: 100%;
            }
            .footer-credit {
                bottom: 84px;
                justify-content: center;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="backdrop"></div>
    <canvas class="star-canvas" id="starCanvas" aria-hidden="true"></canvas>
    <div class="starfield" aria-hidden="true"></div>
    <main class="shell">
        <section class="hero">
            <div class="hero-aura" aria-hidden="true"></div>
            <div class="hero-rings" aria-hidden="true"></div>
            <div class="hero-mist" aria-hidden="true"></div>
            <div class="moon-horizon" aria-hidden="true">
                <div class="moon-ridge"></div>
                <div class="moon-surface"></div>
            </div>
            <div class="title-wrap">
                <div class="title-energy" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <h1 class="hero-title">LAWANGSEWU</h1>
                <p class="subtitle">come back is real</p>
            </div>
        </section>
    </main>

    <div class="footer-credit">portal lawangsewu core developed by dubes prakom</div>

    <div class="dock">
        <div class="dock-panel<?php echo $showLoginPanel ? ' is-open' : ''; ?>" id="loginPanel">
            <?php if (is_array($loggedInUser)) : ?>
                <p class="panel-mark">Portal Ready</p>
                <p class="panel-user">Sesi aktif untuk <strong><?php echo htmlspecialchars($loggedInName, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
                <a class="panel-link" href="<?php echo htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8'); ?>">Buka Portal</a>
            <?php else : ?>
                <p class="panel-mark">Portal Login</p>
                <?php if ($loginError !== '') : ?>
                    <div class="panel-error"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form class="panel-form" method="post" action="<?php echo htmlspecialchars($landingUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="landing_login" value="1">
                    <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($submittedUsername, ENT_QUOTES, 'UTF-8'); ?>" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button class="panel-submit" type="submit">Masuk</button>
                </form>
            <?php endif; ?>
        </div>
        <button class="dock-button" type="button" id="toggleLoginPanel"><?php echo is_array($loggedInUser) ? 'Portal' : 'Login'; ?></button>
    </div>

    <script>
        (function () {
            document.body.classList.add('is-booting');

            const trigger = document.getElementById('toggleLoginPanel');
            const panel = document.getElementById('loginPanel');
            const canvas = document.getElementById('starCanvas');
            window.setTimeout(() => {
                document.body.classList.remove('is-booting');
            }, 1350);

            if (canvas) {
                const context = canvas.getContext('2d');
                const particleCount = 46;
                const particles = [];
                const pointer = { x: window.innerWidth / 2, y: window.innerHeight / 2, active: false };

                const randomBetween = (min, max) => min + Math.random() * (max - min);

                const resizeCanvas = () => {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                };

                const makeParticle = () => ({
                    x: randomBetween(0, window.innerWidth),
                    y: randomBetween(0, window.innerHeight * 0.62),
                    radius: randomBetween(0.7, 1.7),
                    alpha: randomBetween(0.16, 0.54),
                    pulse: randomBetween(0.002, 0.008),
                    driftX: randomBetween(-0.03, 0.03),
                    driftY: randomBetween(-0.02, 0.03),
                });

                const initParticles = () => {
                    particles.length = 0;
                    for (let index = 0; index < particleCount; index += 1) {
                        particles.push(makeParticle());
                    }
                };

                const draw = () => {
                    if (!context) {
                        return;
                    }

                    context.clearRect(0, 0, canvas.width, canvas.height);

                    particles.forEach((particle) => {
                        particle.x += particle.driftX;
                        particle.y += particle.driftY;
                        particle.alpha += particle.pulse;

                        if (particle.alpha >= 0.58 || particle.alpha <= 0.14) {
                            particle.pulse *= -1;
                        }

                        if (particle.x < -8) particle.x = canvas.width + 8;
                        if (particle.x > canvas.width + 8) particle.x = -8;
                        if (particle.y < -8) particle.y = canvas.height * 0.62;
                        if (particle.y > canvas.height * 0.66) particle.y = -8;

                        let offsetX = 0;
                        let offsetY = 0;
                        if (pointer.active) {
                            const deltaX = pointer.x - particle.x;
                            const deltaY = pointer.y - particle.y;
                            const distance = Math.hypot(deltaX, deltaY);
                            if (distance < 180) {
                                const force = (180 - distance) / 180;
                                offsetX = (-deltaX / Math.max(distance, 1)) * force * 4;
                                offsetY = (-deltaY / Math.max(distance, 1)) * force * 4;
                            }
                        }

                        context.beginPath();
                        context.fillStyle = `rgba(143, 210, 255, ${particle.alpha})`;
                        context.shadowBlur = 8;
                        context.shadowColor = 'rgba(98, 183, 255, 0.28)';
                        context.arc(particle.x + offsetX, particle.y + offsetY, particle.radius, 0, Math.PI * 2);
                        context.fill();
                    });

                    window.requestAnimationFrame(draw);
                };

                resizeCanvas();
                initParticles();
                draw();

                window.addEventListener('resize', () => {
                    resizeCanvas();
                    initParticles();
                });

                window.addEventListener('pointermove', (event) => {
                    pointer.x = event.clientX;
                    pointer.y = event.clientY;
                    pointer.active = true;
                });

                window.addEventListener('pointerleave', () => {
                    pointer.active = false;
                });
            }

            if (trigger && panel) {
                trigger.addEventListener('click', () => {
                    panel.classList.toggle('is-open');
                });

                document.addEventListener('click', (event) => {
                    if (!panel.contains(event.target) && event.target !== trigger) {
                        panel.classList.remove('is-open');
                    }
                });
            }
        })();
    </script>
</body>
</html>
