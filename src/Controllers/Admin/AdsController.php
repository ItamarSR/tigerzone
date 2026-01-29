<?php

declare(strict_types=1);

namespace TigerZone\Controllers\Admin;

use TigerZone\Models\Settings;

final class AdsController extends BaseAdminController
{
    private function adsDir(): string
    {
        // /workspace/public/assets/ads (em produção será /public_html/public/assets/ads)
        $assets = realpath(__DIR__ . '/../../../public/assets');
        if ($assets === false) {
            $assets = __DIR__ . '/../../../public/assets';
        }
        return rtrim($assets, '/') . '/ads';
    }

    private function assetUrl(string $file): string
    {
        return \asset('ads/' . ltrim($file, '/'));
    }

    private function isAllowed(string $ext): bool
    {
        $ext = strtolower($ext);
        return in_array($ext, ['mp4', 'gif'], true);
    }

    public function index(): void
    {
        $this->requireAdmin();
        $settings = new Settings();
        $left = (string) ($settings->get('ad_left_file', '') ?? '');
        $right = (string) ($settings->get('ad_right_file', '') ?? '');

        $this->view('admin.ads.index', [
            'title' => 'Propagandas',
            'left_file' => $left,
            'right_file' => $right,
            'left_url' => $left ? $this->assetUrl($left) : '',
            'right_url' => $right ? $this->assetUrl($right) : '',
        ]);
    }

    public function upload(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $settings = new Settings();

        $dir = $this->adsDir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            \flash_set('error', 'Não foi possível criar a pasta de propagandas.');
            \redirect(\base_url('/admin/propagandas'));
        }

        $updated = false;
        foreach (['left' => 'ad_left_file', 'right' => 'ad_right_file'] as $slot => $key) {
            $input = 'ad_' . $slot;
            if (empty($_FILES[$input]) || !isset($_FILES[$input]['tmp_name']) || $_FILES[$input]['tmp_name'] === '') {
                continue;
            }
            if (!empty($_FILES[$input]['error'])) {
                \flash_set('error', 'Erro no upload (' . $slot . ').');
                \redirect(\base_url('/admin/propagandas'));
            }

            $orig = (string) ($_FILES[$input]['name'] ?? '');
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!$this->isAllowed($ext)) {
                \flash_set('error', 'Formato inválido (' . $slot . '). Envie MP4 ou GIF.');
                \redirect(\base_url('/admin/propagandas'));
            }

            $size = (int) ($_FILES[$input]['size'] ?? 0);
            if ($size <= 0 || $size > 25 * 1024 * 1024) {
                \flash_set('error', 'Arquivo muito grande (' . $slot . '). Máx 25MB.');
                \redirect(\base_url('/admin/propagandas'));
            }

            $name = 'ad_' . $slot . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target = rtrim($dir, '/') . '/' . $name;
            if (!@move_uploaded_file($_FILES[$input]['tmp_name'], $target)) {
                \flash_set('error', 'Falha ao salvar o arquivo (' . $slot . ').');
                \redirect(\base_url('/admin/propagandas'));
            }

            // Remove antigo
            $old = (string) ($settings->get($key, '') ?? '');
            if ($old !== '') {
                $oldPath = rtrim($dir, '/') . '/' . basename($old);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $settings->set($key, $name);
            $updated = true;
        }

        \flash_set('success', $updated ? 'Propagandas atualizadas.' : 'Nenhum arquivo enviado.');
        \redirect(\base_url('/admin/propagandas'));
    }
}

