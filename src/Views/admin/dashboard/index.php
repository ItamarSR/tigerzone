<?php
$title = 'Dashboard';
ob_start();
?>
<div class="container">
    <h1>Dashboard</h1>
    <div class="admin-stats">
        <div class="admin-stat"><span class="val"><?= (int) $usersTotal ?></span> <span class="lbl">Usuários ativos</span></div>
        <div class="admin-stat"><span class="val"><?= (int) $bannedTotal ?></span> <span class="lbl">Usuários banidos</span></div>
        <div class="admin-stat"><span class="val"><?= config('app.currency_display') ?> <?= number_format((float) $creditsInCirculation, 2, ',', '.') ?></span> <span class="lbl">Créditos em circulação</span></div>
    </div>
    <h2>IPs suspeitos (múltiplas contas)</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>IP</th><th>Contas</th><th>Última atividade</th></tr>
            </thead>
            <tbody>
                <?php foreach ($suspiciousIps as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['ip']) ?></td>
                    <td><?= (int) $r['accounts'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['last_seen'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($suspiciousIps)): ?>
    <p>Nenhum IP suspeito.</p>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../../layouts/admin.php'; ?>
