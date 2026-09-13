# GUIA DE DEPLOY — InfinityFree (Passo a Passo)

> Sistema: **WMS Agiliza** (PHP 8 + MySQL + Bootstrap local)
> Objetivo: publicar o sistema na InfinityFree e ver o site online.

---

## 0. O que você precisa ter pronto (pré-requisitos)

Antes de começar, confirme que:

- [ ] Você tem uma conta de **e-mail** acessível (para criar a conta na InfinityFree).
- [ ] O projeto **WMS Agiliza** está completo na sua máquina
      (`C:\Users\vsant\OneDrive\Documentos\Vibe Coding\Sistema WMS OC`).
- [ ] O XAMPP está rodando localmente (Apache + MySQL) — já está.
- [ ] (Recomendado) O arquivo de banco `database/deploy_limpo.sql` foi gerado — já foi.

> O que é "deploy"?
> Deploy = publicar o seu site para que outras pessoas (ou outros computadores)
> possam acessar pela internet. O sistema WMS vive em um "XAMPP escondido" na
> máquina da InfinityFree (a hospedagem), e você só sobe os arquivos e o banco.

---

## 1. Criar a conta na InfinityFree

1. Abra o navegador e acesse: **https://www.infinityfree.com/**
2. Clique em **Register Now** (botão verde).
3. Preencha:
   - **E-mail**: seu melhor e-mail (vai receber link de confirmação).
   - **Senha**: crie uma senha forte e salve em um lugar seguro.
4. Confirme o e-mail pelo link enviado.
5. Faça login no painel.

---

## 2. Criar o site (domínio gratuito)

1. Dentro do painel, clique em **Create Website / Create Account**.
2. Escolha **Free subdomain** e digite um nome (ex.: `wmsagiliza`).
   - Você receberá algo como: `https://wmsagiliza.great-site.net`
   - Guarde esse endereço: **ele é o "endereço" do seu sistema.**
3. Clique em **Create**.
4. Aguarde a criação (leva alguns minutos).

---

## 3. Criar o banco de dados MySQL (no painel)

1. No painel da InfinityFree, vá em **MySQL databases** (abas/card MySQL).
2. Clique em **Create Database**.
3. Anote as 4 informações geradas (muito importante!):
   - **Database Name**: algo como `if0_xxxxxx_wmsagiliza`
   - **Username**: algo como `if0_xxxxxx`
   - **Password**: a senha que você definiu (ou gerada)
   - **Hostname**: algo como `sqlXXX.infinityfree.com` (ou `sqlXXX.epizy.com`)
4. Salve essas 4 informações — vamos usá-las no passo 6.

---

## 4. Importar o banco de dados (phpMyAdmin)

1. No painel, abra o **phpMyAdmin** (ícone/aba na área do banco).
2. No menu da esquerda, **clique no nome do seu banco**
   (ex.: `if0_xxxxxx_wmsagiliza`) para selecioná-lo.
3. Vá na aba **Import** (menu superior).
4. Clique em **Choose File** e selecione o arquivo:
   `C:\Users\vsant\OneDrive\Documentos\Vibe Coding\Sistema WMS OC\database\deploy_limpo.sql`
5. Clique em **Go / Importar**.
6. Aguarde a mensagem de sucesso.
7. **Confira**: no menu da esquerda, o nome do banco deve agora exibir as
   tabelas: `usuarios`, `produtos`, `enderecos`, `pedidos`, etc.
   (14 tabelas no total).

> Esse arquivo já cria as tabelas e os dados de exemplo (produtos, endereços
> C01-C05 x P01-P05, usuários GESTOR01/OPERADOR01/ADMINISTRADOR).

---

## 5. Enviar os arquivos do sistema (File Manager ou FTP)

> Os arquivos do projeto vão para a pasta `htdocs` do seu site na InfinityFree
> (essa é a "raiz" — o mesmo que a pasta `htdocs` do seu XAMPP).

### Opção A — File Manager (mais fácil)

1. No painel, acesse **File Manager**.
2. Entre na pasta **htdocs**.
3. **Apague o conteúdo de exemplo** que vier por padrão (arquivos
   `index.php`, `applications.html`, etc.).
4. Faça **upload da pasta inteira** do projeto
   `C:\Users\vsant\OneDrive\Documentos\Vibe Coding\Sistema WMS OC`
   que contém: `index.php`, `.htaccess`, `app/`, `config/`, `assets/`,
   `database/`, `docs/`, `logs/`, `uploads/`.
   - **IMPORTANTE:** NÃO suba a pasta `.git`. Suba só o conteúdo do projeto.
5. Aguarde o upload terminar (pode levar alguns minutos).

### Opção B — FTP (FileZilla, recomendado para muitos arquivos)

1. Instale o **FileZilla** (https://filezilla-project.org/download.php).
2. No painel da InfinityFree, anote das **FTP credentials**: host, usuário, senha.
3. No FileZilla: `File → Site Manager → New Site`, preencha host/usuário/senha.
4. Conecte. Lado direito (servidor): entre em **htdocs**.
5. Arraste a pasta do projeto do lado esquerdo (seu computador) para o direito.
6. Espere a fila de envio terminar (status "Successful").

---

## 6. Ajustar o arquivo de configuração do banco

Agora você precisa dizer ao sistema onde está o banco de dados da InfinityFree.

1. No **File Manager**, navegue até `config/`.
2. O sistema local roda com `config.php` (credenciais do XAMPP).
   Para o deploy, **crie um novo arquivo** `config.php` com estas credenciais
   (substitua pelos valores da sua conta):
   ```php
   <?php
   defined('WMS_EXEC') or die('Acesso direto não permitido.');

   // https e caminho base são detectados automaticamente em produção.
   if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
       $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
           || strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';
       $esquema = $https ? 'https' : 'http';
       $host    = $_SERVER['HTTP_HOST'];
       $script  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
       $posicao = strrpos($script, '/index.php');
       $caminho = ($posicao !== false && $posicao > 0) ? substr($script, 0, $posicao) : '';
       $baseUrl = rtrim($esquema . '://' . $host . $caminho, '/');
   } else {
       $baseUrl = 'http://localhost/wms-agiliza';
   }

   define('BASE_URL', $baseUrl);
   define('URL_PUBLICA', $baseUrl);
   define('GALPAO_UNICO', 'G01');

   $configuracao = [
       'db' => [
           'host'    => 'SEU_HOSTNAME_MYSQL',      // ex.: sqlXXX.infinityfree.com
           'dbname'  => 'SEU_DATABASE_NAME',       // ex.: if0_xxxxxx_wmsagiliza
           'user'    => 'SEU_USERNAME',            // ex.: if0_xxxxxx
           'pass'    => 'SUA_SENHA_MYSQL',
           'charset' => 'utf8mb4',
       ],
       'app' => [
           'name'      => 'WMS Agiliza',
           'base_url'  => $baseUrl,
           'timezone'  => 'America/Sao_Paulo',
           'sla_padrao_minutos' => 120,
           'sessao_inatividade_segundos' => 28800,
       ],
       'otif' => [
           'expiracao_dias_uteis' => 10,
           'upload_max_bytes'     => 5242880,
           'tipos_permitidos'     => ['image/jpeg', 'image/png'],
       ],
       'sla' => [
           'RECEBIDO'    => 120,
           'A_ARMAZENAR' => 120,
           'A_SEPARAR'   => 120,
           'A_EXPEDIR'   => 120,
       ],
       'motivos_ajuste' => [
           'DANO_MANUSEIO'          => 'Danos no manuseio',
           'ERRO_CONTAGEM'          => 'Erro de contagem anterior',
           'ITEM_AVARIADO'          => 'Item avariado',
           'PERDA'                  => 'Perda no armazenamento',
           'DIVERGENCIA_NOTAFISCAL' => 'Divergência com nota fiscal',
           'OUTRO'                  => 'Outro motivo informado',
       ],
       'envio' => [
           'modo' => 'simulacao',
           'evolution_api' => ['url' => '', 'instance' => '', 'api_key' => ''],
           'resend' => ['api_key' => '', 'from_email' => ''],
       ],
   ];
   $GLOBALS['WMS_CONFIG'] = $configuracao;
   return $configuracao;
   ```
3. Salve com o nome **`config.php`** dentro de `config/`.
   > NUNCA envie esse arquivo para o GitHub nem compartilhe — contém a senha
   > do banco. O arquivo já está no `.gitignore` do projeto.

---

## 7. Primeiro acesso (site no ar!)

1. Abra o navegador e acesse o endereço do seu site:
   `https://SEUNOME.great-site.net`
2. Você deve ser redirecionado para a tela de **Login** do WMS Agiliza.
3. Faça login com um dos usuários de demonstração:
   - **ADMINISTRADOR** / `admin123` (mais completo)
   - **GESTOR01** / `gestor123`
   - **OPERADOR01** / `operador123`
4. 🎉 **Você está dentro do WMS rodando na internet!**

---

## 8. Segurança OBRIGATÓRIA logo após o 1º login

> As senhas acima são públicas (estão nos arquivos de migração do repositório).
> **Você DEVE trocá-las imediatamente** antes de divulgar o sistema.

1. Entre com **ADMINISTRADOR / admin123**.
2. Vá no menu **Usuários & Contas**.
3. Altere a senha de TODOS os usuários para senhas fortes e particulares.
4. Opcionalmente, crie usuários reais com nome de cada funcionário.

---

## 9. Verificação pós-deploy (checklist final)

Confirme que tudo funciona NO AR:

- [ ] Login do Gestor abre o **Dashboard** com os 9 indicadores.
- [ ] Login do Operador abre o **Kanban**.
- [ ] Menu **Produtos** lista os 8+ produtos de exemplo.
- [ ] Menu **Endereços** mostra a grade C01-C05 x P01-P05 (Galpão G01).
- [ ] Aba **Avaliar** OTIF funciona (teste um token).
- [ ] **Uploads**: envie uma foto em avarias; a imagem abre (sem 403). ✔ (corrigido)
- [ ] Acesso via **https://** funciona sem aviso de conteúdo misto. ✔ (corrigido)
- [ ] Teste de **logout** e login novamente.

---

## 10. Dicas finais (para seu 1º dia como dev)

- **Backup:** uma vez por semana, exporte o banco no phpMyAdmin (aba Export)
  e baixe para o seu computador. É o seu "seguro de vida".
- **Não edite arquivos direto na InfinityFree** para mudanças grandes:
  altere no seu computador, teste no XAMPP local, e só então suba. Mantenha
  o GitHub sincronizado.
- **Se der erro 500** (tela branca), o arquivo `logs/error.log` do seu site
  tem a causa. Consulte-o pelo File Manager.
- **Limite da InfinityFree:** arquivos de upload de até 10MB por quantidade
  e banco de dados até ~1GB (suficiente para o WMS).
- **Mantenha o GitHub atualizado:** antes de começar a trabalhar e ao terminar,
  rode `git add -A`, `git commit -m "descrição"` e `git push`. Assim seu código
  tem backup também.

---

## Resumo visual do fluxo

```
GitHub (código-fonte)
   │  (clone/download)
   ▼
Sua máquina (XAMPP local)  →  teste tudo aqui
   │  (upload do projeto p/ htdocs)
   ▼
InfinityFree
   ├─ Site:  https://SEUNOME.great-site.net
   ├─ Banco: importado via phpMyAdmin (deploy_limpo.sql)
   └─ config/config.php  →  credenciais do banco da InfinityFree
   │
   ▼
Login → Dashboard/Kanban → ✓ SISTEMA ONLINE
```