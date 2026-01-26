<?php
$title = 'Convites';
ob_start();
?>
<div class="container">
    <h1>Convites</h1>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Referidor</th>
                    <th>Convidado</th>
                    <th>IP</th>
                    <th>Bonus</th>
                    <th>Bloqueio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invites as $i): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($i['created_at'])) ?></td>
                    <td><?= htmlspecialchars($i['referrer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($i['invited_name'] ?? $i['invited_email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($i['invited_ip'] ?? '-') ?></td>
                    <td><?= !empty($i['bonus_given']) ? 'Sim' : 'Nao' ?></td>
                    <td><?= htmlspecialchars($i['bonus_blocked_reason'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
