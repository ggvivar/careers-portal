<?php
$e = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Error</title>
    <style>
        body{margin:0;padding:32px 18px;background:#f5f7fb;color:#202331;font-family:"Segoe UI",Arial,sans-serif}
        .card{max-width:980px;margin:auto;overflow:hidden;border:1px solid #d9deea;border-radius:18px;background:#fff;box-shadow:0 18px 45px rgba(10,1,71,.08)}
        header{padding:26px 30px;background:#0a0147;color:#fff}main{padding:28px 30px}.type{color:#b42318;font-weight:700;overflow-wrap:anywhere}.message{font-size:20px;font-weight:650;overflow-wrap:anywhere}
        .meta{display:grid;grid-template-columns:90px minmax(0,1fr);gap:8px 12px;padding:16px;border-radius:12px;background:#f8fafc;font:13px Consolas,monospace}.trace{max-height:440px;overflow:auto;padding:16px;border-radius:12px;background:#111827;color:#e5e7eb;font:12px/1.55 Consolas,monospace;white-space:pre-wrap;overflow-wrap:anywhere}.btn{display:inline-block;margin-top:22px;padding:11px 17px;border-radius:10px;background:#ffc91c;color:#0a0147;font-weight:700;text-decoration:none}
    </style>
</head>
<body>
<section class="card">
<header><h1>Application error</h1><p>Development diagnostics are enabled.</p></header>
<main>
<div class="type"><?= $e($type ?? $title ?? 'Exception') ?></div>
<p class="message"><?= $e($message ?? 'Unknown error') ?></p>
<div class="meta"><div>File</div><div><?= $e($file ?? 'Unknown') ?></div><div>Line</div><div><?= $e($line ?? 'Unknown') ?></div><div>HTTP</div><div><?= $e($code ?? 500) ?></div></div>
<?php if (! empty($trace) && is_array($trace)): ?>
<h2>Stack trace</h2><div class="trace"><?php foreach ($trace as $i => $frame): ?><?= $e('#'.$i.' '.($frame['file'] ?? '[internal]').(isset($frame['line'])?':'.$frame['line']:'').' '.($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '')) . "\n" ?><?php endforeach; ?></div>
<?php endif; ?>
<a class="btn" href="<?= $e(site_url('jobs')) ?>">Return to jobs</a>
</main>
</section>
</body>
</html>
