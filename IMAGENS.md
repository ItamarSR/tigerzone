# TigerZone – Imagens e ícones

Imagens geradas a partir dos assets fornecidos (logo, tigre rosa, etc.) e respetivas dimensões.

---

## 1. Logo do site (header)

| Uso | Ficheiro sugerido | Dimensões | Notas |
|-----|-------------------|-----------|--------|
| Logo no header (site + admin) | `logo.png` ou `logo.svg` | **200×50 px** (ou proporção similar, alt. máx. ~60 px) | Configurável em Admin → Configurações → «URL do logo». Fundo transparente (PNG) ou SVG. Formato horizontal. |

**Formato:** PNG (transparente) ou SVG.  
**Pasta sugerida:** `public/assets/images/`

---

## 2. Ícones dos jogos (cards na home e em /jogos)

Os cards dos jogos usam atualmente placeholders em CSS (64×64 px). Para usar imagens reais:

| Jogo | Ficheiro sugerido | Dimensões | Uso |
|------|-------------------|-----------|-----|
| Fortune Tiger | `fortune-tiger.png` | **128×128 px** (ou 256×256 para retina) | Card do jogo |
| Fortune Dragon | `fortune-dragon.png` | **128×128 px** (ou 256×256 para retina) | Card do jogo |
| Fortune Ox | `fortune-ox.png` | **128×128 px** (ou 256×256 para retina) | Card do jogo |

**Formato:** PNG com fundo transparente ou quadrado.  
**Pasta sugerida:** `public/assets/images/games/`

*(Nota: o uso destas imagens nos cards implica alteração do código nas views e no CSS; hoje os ícones são divs com gradiente.)*

---

## 3. Favicon

| Uso | Ficheiro | Dimensões |
|-----|----------|-----------|
| Favicon (aba do browser) | `favicon.ico` | **32×32 px** (ou 16×16 + 32×32 em .ico) |
| Apple Touch Icon | `apple-touch-icon.png` | **180×180 px** |

**Pasta:** raiz de `public/` (ex.: `public/favicon.ico`) ou `public/assets/images/`.  
*(Atualmente o projeto não referencia favicon; é preciso adicionar `<link>` no `<head>` dos layouts.)*

---

## 4. Redes sociais / partilha (opcional)

| Uso | Ficheiro sugerido | Dimensões |
|-----|-------------------|-----------|
| Imagem OG (Facebook, etc.) | `og-image.png` | **1200×630 px** |

**Formato:** PNG ou JPG. Proporção 1.91:1.  
**Pasta sugerida:** `public/assets/images/`

---

## Resumo rápido

| Imagem | Dimensões | Obrigatório? |
|--------|-----------|--------------|
| Logo do site | 200×50 px (alt. máx. ~60 px) | Opcional (hoje há texto «TigerZone») |
| Fortune Tiger | 128×128 px (ou 256×256) | Opcional (existem placeholders) |
| Fortune Dragon | 128×128 px (ou 256×256) | Opcional |
| Fortune Ox | 128×128 px (ou 256×256) | Opcional |
| Favicon | 32×32 px | Recomendado |
| Apple Touch Icon | 180×180 px | Opcional |
| OG / partilha | 1200×630 px | Opcional |

---

## Ficheiros já criados em `public/assets/images/`

| Ficheiro | Dimensões | Uso |
|----------|-----------|-----|
| `logo.png` | 1024×1024 (original) | Logo no header (site e admin) |
| `favicon.png` | 32×32 | Favicon (aba do browser) |
| `apple-touch-icon.png` | 180×180 | Apple Touch Icon |
| `og-image.png` | 1200×630 | Partilha em redes sociais (meta og:image) |
| `tiger-pink.png` | original | Base para ícone Fortune Tiger |
| `tiger-logo.png` | original | Base para ícones Dragon/Ox |
| `games/fortune-tiger.png` | 128×128 | Card e página do Fortune Tiger |
| `games/fortune-dragon.png` | 128×128 | Card e página do Fortune Dragon |
| `games/fortune-ox.png` | 128×128 | Card e página do Fortune Ox |

O layout principal e o admin usam o logo, favicon e ícones automaticamente.
