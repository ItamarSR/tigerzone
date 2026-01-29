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

    private function isAllowedSide(string $ext): bool
    {
        $ext = strtolower($ext);
        return in_array($ext, ['mp4', 'gif'], true);
    }

    private function isAllowedTop(string $ext): bool
    {
        $ext = strtolower($ext);
        return in_array($ext, ['gif', 'png', 'jpg', 'jpeg'], true);
    }

    private function removeFile(string $dir, string $file): void
    {
        if ($file === '') return;
        $path = rtrim($dir, '/') . '/' . basename($file);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function index(): void
    {
        $this->requireAdmin();
        $settings = new Settings();
        $left = (string) ($settings->get('ad_left_file', '') ?? '');
        $right = (string) ($settings->get('ad_right_file', '') ?? '');
        $top = (string) ($settings->get('ad_top_file', '') ?? '');

        $this->view('admin.ads.index', [
            'title' => 'Propagandas',
            'left_file' => $left,
            'right_file' => $right,
            'top_file' => $top,
            'left_url' => $left ? $this->assetUrl($left) : '',
            'right_url' => $right ? $this->assetUrl($right) : '',
            'top_url' => $top ? $this->assetUrl($top) : '',
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

        // Remoções
        foreach (['left' => 'ad_left_file', 'right' => 'ad_right_file', 'top' => 'ad_top_file'] as $slot => $key) {
            $rm = !empty($_POST['remove_' . $slot]);
            if ($rm) {
                $old = (string) ($settings->get($key, '') ?? '');
                $this->removeFile($dir, $old);
                $settings->set($key, '');
                $updated = true;
            }
        }

        // Upload laterais (MP4/GIF)
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
            if (!$this->isAllowedSide($ext)) {
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
            $old = (string) ($settings->get($key, '') ?? '');
            $this->removeFile($dir, $old);
            $settings->set($key, $name);
            $updated = true;
        }

        // Upload topo (GIF/IMG 300x73)
        $inputTop = 'ad_top';
        if (!empty($_FILES[$inputTop]) && isset($_FILES[$inputTop]['tmp_name']) && $_FILES[$inputTop]['tmp_name'] !== '') {
            if (!empty($_FILES[$inputTop]['error'])) {
                \flash_set('error', 'Erro no upload (topo).');
                \redirect(\base_url('/admin/propagandas'));
            }
            $orig = (string) ($_FILES[$inputTop]['name'] ?? '');
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!$this->isAllowedTop($ext)) {
                \flash_set('error', 'Formato inválido (topo). Envie GIF/JPG/PNG.');
                \redirect(\base_url('/admin/propagandas'));
            }
            $size = (int) ($_FILES[$inputTop]['size'] ?? 0);
            if ($size <= 0 || $size > 8 * 1024 * 1024) {
                \flash_set('error', 'Arquivo muito grande (topo). Máx 8MB.');
                \redirect(\base_url('/admin/propagandas'));
            }
            $name = 'ad_top_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target = rtrim($dir, '/') . '/' . $name;
            if (!@move_uploaded_file($_FILES[$inputTop]['tmp_name'], $target)) {
                \flash_set('error', 'Falha ao salvar o arquivo (topo).');
                \redirect(\base_url('/admin/propagandas'));
            }
            $old = (string) ($settings->get('ad_top_file', '') ?? '');
            $this->removeFile($dir, $old);
            $settings->set('ad_top_file', $name);
            $updated = true;
        }

        \flash_set('success', $updated ? 'Propagandas atualizadas.' : 'Nenhuma alteração.');
        \redirect(\base_url('/admin/propagandas'));
    }
}

