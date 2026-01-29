<?php
$title = 'Propagandas';
ob_start();

$leftUrl = $left_url ?? '';
$rightUrl = $right_url ?? '';
$leftFile = $left_file ?? '';
$rightFile = $right_file ?? '';

function render_ad_preview(string $url, string $file): string {
    if (!$url || !$file) return '<span style="color:var(--text-muted);">Nenhuma propaganda definida.</span>';
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'mp4') {
        return '<video src="' . htmlspecialchars($url) . '" muted loop autoplay playsinline style="width:100%; max-width:360px; border-radius:12px; border:1px solid var(--border); background:#000;"></video>';
    }
    return '<img src="' . htmlspecialchars($url) . '" alt="Propaganda" style="width:100%; max-width:360px; border-radius:12px; border:1px solid var(--border); background:#000;" />';
}
?>

<div class="container">
    <h1>Propagandas</h1>
    <p style="color:var(--text-muted); margin-top:-0.5rem;">
        Envie <strong>MP4</strong> ou <strong>GIF</strong>. Em mobile, as propagandas ficam ocultas automaticamente.
    </p>

    <div class="form-card" style="max-width:760px;">
        <form method="post" action="<?= base_url('/admin/propagandas') ?>" enctype="multipart/form-data">
            <?= \TigerZone\Core\Security::csrfField() ?>

            <label>Propaganda esquerda (MP4/GIF)</label>
            <input type="file" name="ad_left" accept="video/mp4,image/gif">
            <div style="margin-top:0.5rem;"><?= render_ad_preview($leftUrl, $leftFile) ?></div>

            <label style="margin-top:1.25rem;">Propaganda direita (MP4/GIF)</label>
            <input type="file" name="ad_right" accept="video/mp4,image/gif">
            <div style="margin-top:0.5rem;"><?= render_ad_preview($rightUrl, $rightFile) ?></div>

            <button type="submit" class="btn btn-primary" style="margin-top:1.25rem;">Salvar</button>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>

