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
> A-E x P01-P05, usuários GESTOR01/OPERADOR01/ADMINISTRADOR).

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

### Opção B — FTP (FileZilla, RECOMENDADA)

> ⚠️ **Lições aprendidas no 1º deploy (seguir à risca):**

1. Instale o **FileZilla Client** (NÃO o Server) em
   https://filezilla-project.org/download.php.
2. **A senha do FTP NÃO é a senha do seu login no painel.** Ela é gerada
   automaticamente pela InfinityFree e fica na tabela **"FTP Details"**:
   - Acesse `https://dash.infinityfree.com` → **Accounts** → **Manage**
     na sua conta → procure a tabela **"FTP Details"**.
   - Pegue dali o **host** (ex.: `ftpupload.net`), o **username** e a
     **password** (clique em **Show/Mostrar**).
3. Se você criou a conta com **"Login com Google"**: ainda assim vá em
   **Settings → Change password** e crie uma senha, e use a que estiver
   na tabela FTP Details para o FileZilla (o painel e o FTP podem usar
   senhas diferentes).
4. No FileZilla, preencha nos campos do topo: Host, Usuário, Senha,
   Porta `21` → **Conexão rápida**.
5. Conexão com sucesso = mensagem **`Listagem do diretório "/" bem sucedida`**
   no log. Se aparecer `530 Login authentication failed`, a senha usada não
   é a do FTP Details (ou ainda não sincronizou — espere ~15 min e tente de novo).
6. Lado direito (servidor): **entre em `htdocs`**.
   > ─── DICA IMPORTANTE ───
   > Em contas recém-criadas, o `htdocs` pode demorar **algumas horas** para
   > aparecer (provisionamento). O site fica servindo uma página de segurança
   > (JS) e o FileZilla/FIle Manager mostram tudo **vazio** nesse período.
   > **Aguarde, não é erro.** Teste: acesse o site e crie/delete uma pasta de
   > teste no FTP. Quando `htdocs` aparecer, o deploy já pode ser feito.
7. Delete o que houver em `htdocs` (opcional; evita conflito).
8. **Suba o CONTEÚDO** do projeto (arquivos soltos, NÃO a pasta embrulhada):
   - Local: extraia o `.zip` preparado (com o `config/config.php` de produção)
     numa pasta, ex.: `wms_para_subir`.
   - FileZilla: **Ctrl+A** em `wms_para_subir` → arraste para dentro de `htdocs`.
   - Espere a fila de envio terminar (status "Successful").
   - **NUNCA suba a pasta `.git` nem `.gitignore`.**
9. O **File Manager** do navegador é instável (lista vazia, sumiço de
   arquivos). **Prefira sempre o FileZilla para uploads.**

---

## 6. Ajustar o arquivo de configuração do banco

> **Na prática do 1º deploy:** o `config/config.php` de produção já é
> gerado dentro do `.zip` de deploy (apontando para o hostname/banco da
> InfinityFree) **ANTES** de subir os arquivos. Assim você não mexe em
> nada no servidor. Os passos abaixo servem para refazer/confirmar à mão.

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

## 9. ATUALIZAÇÕES DO SISTEMA (v2 — novo pacote de mudanças)

> Este trecho explica como aplicar o pacote de melhorias **sem perder os dados**
> produzidos no site (usuários reais, pedidos, estoque).

### 9.1 O que vem nesse pacote

- **Limpeza total dos dados de teste** via SQL (phpMyAdmin) — o site fica "como
  se nunca tivesse sido usado", mantendo as contas de login reais.
- **Dashboard corrigido**: a ocupação de estoque não conta mais produtos
  excluídos, e excluir um produto agora zera seus saldos (some o número
  "fantasma" de itens).
- **Usuários & Contas**: novo botão **"Excluir conta"** (exclusão lógica).
- **Recebimento**: nova opção **"Entrada manual por código de barras"** — sem
  XML, bipe o produto + quantidade (pode ser vários itens) e a carga vai
  direto para a Guarda e atualiza o Kanban.
- **Login**: mensagens específicas em vermelho abaixo de cada campo
  ("Matrícula inexistente.", "Senha incorreta.", "Favor preencher o campo.").
- **Logo otimizado**: peso reduzido de 870 KB → 77 KB (login carrega rápido).
- **Sidebar**: item **"Sair da operação"** visível também no celular (o menu
  inferior era oculto no mobile).
- **Painel OTIF**: botão **"Limpar histórico"**, botão **"Ver"** (resposta
  individual por cliente com fotos e observações) e botão de **reenvio**.

### 9.2 Passo a passo

1. **Limpar os dados de teste (produção):**
   - phpMyAdmin do site (https://dash.infinityfree.com → phpMyAdmin).
   - Selecione o banco `if0_42908161_wms_agiliza` → aba **SQL**.
   - Cole o conteúdo de `database/limpeza_dados_teste.sql` → **Go**.
   - Conferência: a consulta final do script deve retornar **0** em tudo.
2. **Subir os novos arquivos (FileZilla):**
   - Local: pasta `wms_para_subir` (atualize o conteúdo com este pacote).
   - FileZilla → lado direito em `htdocs` → **Ctrl+A** do conteúdo local →
     arraste para `htdocs` → **Overwrite** tudo.
   - Subir arquivos NÃO apaga o banco — os dados continuam na InfinityFree.
3. **Ativar o WhatsApp real (OTIF)** — veja a seção 9.3.

### 9.3 Ativar envio real de WhatsApp (OTIF) — Evolution API

O sistema já sabe enviar por WhatsApp **quando recebe os dados de uma
instância Evolution API**. Você precisa de um servidor com a Evolution API
rodando (pode ser no seu computador via port forwarding, num VPS barato, ou
num host que ofereça o serviço), com um número do WhatsApp conectado.

1. Na sua instância Evolution API, conecte o número que enviará as pesquisas
   (ex.: o WhatsApp da empresa).
2. Copie da instância:
   - a **URL do endpoint** (ex.: `http://SEU_IP:8080/message/sendText`)
   - o **nome da instância** `instance`
   - a **chave de API** `apikey`
3. Abra `wms_para_subir/config/config.php` e altere:
   ```php
   'modo' => 'api',
   'evolution_api' => [
       'url'      => 'http://SEU_IP:8080/message/sendText',
       'instance' => 'minha_instancia',
       'api_key'  => 'SUA_CHAVE_DE_API',
   ],
   ```
4. Suba esse `config/config.php` via FileZilla para `htdocs/config/`.
5. Para o teste dirigido ao seu celular: em uma avaliação (Painel OTIF → **Ver**),
   informe **no campo de contato** o número do WhatsApp para onde deseja enviar o
   link de teste (o sistema não possui número padrão configurado) e clique em
   **"Disparar avaliação novamente"**.
   O WhatsApp da instância enviará o link ao número informado.
6. Enquanto `modo` estiver `simulacao`, **nenhuma mensagem é enviada de verdade**:
   o sistema apenas marca como "ENVIADO" e registra o link no log de segurança.

> ⚠️ A senha acessa redes sociais/mensageiros tem custo/regras da Meta. Para uso
> profissional contrate um provedor de WhatsApp Business API/Evolution API ou use
> um número próprio conectado à sua instância.

---

## 10. Verificação pós-deploy (checklist final)

Confirme que tudo funciona NO AR:

- [ ] Login do Gestor abre o **Dashboard** com os 9 indicadores.
- [ ] Login do Operador abre o **Kanban**.
- [ ] Menu **Produtos** lista os 8+ produtos de exemplo.
- [ ] Menu **Endereços** mostra a grade A-E x P01-P05 (Galpão G01).
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
- **O GitHub é o seu "backup do código", mas NÃO do site em si.** Dados criados
  no site (pedidos, usuários novos, fotos de upload) vivem no banco da
  InfinityFree e nos arquivos de upload — esses só são preservados com
  **exportação do banco (phpMyAdmin)** periodicamente.

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