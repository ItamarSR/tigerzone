<?php
$title = 'Convites';
ob_start();
?>
<div class="container">
    <h1>Convites</h1>
    <div class="invite-code-box">
        <label>Seu código</label>
        <div class="code-display"><?= htmlspecialchars($inviteCode) ?></div>
        <p><a href="<?= base_url('/convite/' . $inviteCode) ?>" target="_blank">Link de convite</a></p>
    </div>
    <h2>Histórico de convites</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Convidado</th>
                    <th>IP</th>
                    <th>Bônus</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invites as $i): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($i['created_at'])) ?></td>
                    <td><?= htmlspecialchars($i['invited_name'] ?? $i['invited_email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($i['invited_ip'] ?? '-') ?></td>
                    <td><?= !empty($i['bonus_given']) ? 'Sim' : (isset($i['bonus_blocked_reason']) ? $i['bonus_blocked_reason'] : 'Não') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($invites)): ?>
    <p>Nenhum convite utilizado ainda.</p>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
