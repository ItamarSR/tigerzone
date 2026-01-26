# TigerZone

Plataforma de jogos digitais (estilo Fortune). Arquitetura preparada para pagamentos, antifraude e monetização.

## Requisitos

- PHP 8.1+
- MySQL 5.7+ ou MariaDB 10.3+
- Apache com `mod_rewrite` (ou nginx equivalente)

## Instalação

1. **Clonar / copiar** o projeto para o servidor.

2. **Document root**: apontar o raiz do site para a pasta `public/`.  
   Exemplo Apache:
   ```apache
   DocumentRoot "/caminho/para/TigerZone/public"
   ```

3. **Composer**:
   ```bash
   composer install
   ```

4. **Instalador**: aceder a `/install/` no browser.  
   - Preencher dados da base de dados (MySQL).  
   - Criar o primeiro utilizador administrador.  
   - O instalador cria as tabelas, grava `config/installed.php` e bloqueia-se com `install.lock`.

5. **Acesso**:
   - Site: `/`
   - Painel admin: `/admin` (login com o admin criado no instalador).

**Produção (CSS/JS e links):** O document root deve ser a pasta `public/`. Os assets usam caminhos relativos (`/assets/...`). Se o site estiver noutro domínio, defina `APP_URL` (ex.: `https://seudominio.com`). Se a app estiver num subdiretório, defina `APP_BASE_PATH` (ex.: `/tigerzone`).

## Estrutura

```
TigerZone/
├── config/          # app.php, database.php (installed.php criado pelo instalador)
├── database/        # schema.sql
├── install/         # Instalador (bootstrap.php)
├── public/          # Document root
│   ├── assets/      # CSS, JS
│   ├── .htaccess
│   └── index.php    # Front controller
├── src/
│   ├── Controllers/ # MVC
│   ├── Core/        # Database, Router, Security, helpers
│   ├── Models/
│   ├── Payment/     # PaymentGatewayInterface, SimulatedGateway
│   └── Views/
└── vendor/
```

## Funcionalidades

- **Carteira**: depósitos, saques e ganhos (R$), ligados aos jogos.
- **Jogos**: Fortune Tiger, Fortune Dragon, Fortune Ox — apostas e ganhos, mesma carteira.
- **Convites**: código por utilizador; antifraude (mesmo IP, múltiplas contas) não gera bónus.
- **Banimentos**: por utilizador, IP ou dispositivo (fingerprint). Registo de motivo e admin.
- **Estatísticas em “tempo real”**: contadores dinâmicos (online, ganhos, depósitos) via AJAX, dados controlados no backend.
- **Painel admin**: dashboard, utilizadores, jogos, convites, banimentos, estatísticas, configurações.

## Segurança

- PDO + prepared statements (anti SQL injection).
- CSRF em formulários.
- Sanitização de inputs.
- `password_hash` / `password_verify`.
- Logs de acesso (IP, user-agent, fingerprint).

## Gateway de pagamento (PIX)

Interface `PaymentGatewayInterface` com:

- `createPayment()`
- `checkStatus()`
- `confirmTransaction()`

Usa-se `SimulatedGateway`; a estrutura permite integração futura com PIX.

## Licença

Uso interno. Ajustar conforme necessário.
