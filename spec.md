# UniControl — Especificação Final do Sistema (spec.md)

> **Documento:** Especificação de Requisitos, Arquitetura e Guia Completo de Instalação  
> **Projeto:** UniControl — Sistema de Controle de Estoque de Uniformes e EPIs  
> **Repositório GitHub:** [https://github.com/ryannesoares56-ux/UniControl](https://github.com/ryannesoares56-ux/UniControl)  
> **Versão:** 2.0 (Final)

---

## 1. Objetivo
O **UniControl** é um sistema web corporativo para controlar com precisão a compra, a guarda e a entrega de uniformes e EPIs aos funcionários de empresas clientes.

Ele resolve definitivamente o problema do controle manual feito em planilhas ou papel — que geram furos de estoque, falta de comprovação jurídica de entrega e compras duplicadas desnecessárias —, centralizando cadastro, estoque, movimentações e protocolos de entrega em um único ambiente, com suporte nativo a **múltiplas empresas clientes (multiempresa)** na mesma plataforma.

---

## 2. Usuários
O sistema possui 3 níveis de usuários com perfis e permissões bem definidos:

- **Visitante / Novo Usuário:** Acessa a vitrine comercial com simulador de economia (`index.php`), a tela de login (`login.php`) e pode realizar o auto-cadastro de sua empresa e de sua conta de administrador (`cadastro.php`).
- **Operador:** Cadastra colaboradores da empresa, cadastra peças de uniformes e variações (tamanho/cor/SKU), registra entradas e saídas de estoque, registra entregas de uniformes a funcionários, emite termos de recebimento para assinatura e consulta o dashboard com alertas de estoque baixo.
- **Administrador:** Possui acesso irrestrito a todas as funcionalidades do operador e acesso exclusivo para gerenciar empresas clientes (`empresas.php`), criar e gerenciar usuários do sistema (`usuarios.php`), redefinir senhas e alternar instantaneamente entre empresas pelo seletor de empresa ativa no topo da página.

---

## 3. Casos de Uso Principais
1. Usuário faz login no sistema com e-mail e senha.
2. Novo usuário realiza auto-cadastro de sua conta e de sua empresa.
3. Sistema restringe o acesso às telas de Empresas e Usuários apenas para administradores (bloqueio 403 no servidor).
4. Administrador alterna a empresa ativa que deseja gerenciar via seletor no topo.
5. Administrador cadastra uma nova empresa cliente e novos usuários.
6. Operador cadastra um funcionário vinculado à empresa (e pode editar ou inativar seu status).
7. Operador cadastra uma peça de uniforme e suas variações de tamanho, cor, SKU e estoque inicial.
8. Operador registra uma movimentação de estoque (entrada, saída, devolução ou baixa).
9. Operador registra a entrega de um uniforme a um funcionário, abatendo a quantidade do estoque físico.
10. Sistema bloqueia automaticamente qualquer entrega ou saída que supere a quantidade em estoque (trava anti-furo).
11. Operador emite o Termo de Recebimento de Uniforme com protocolo e campos para assinatura física.
12. Sistema exibe destaque visual no estoque e alerta no dashboard quando o estoque atinge o limite mínimo.
13. Usuário consulta o dashboard com os 4 indicadores consolidados e o gráfico por categoria.

---

## 4. Requisitos Funcionais e Não Funcionais

### 4.1 Requisitos Funcionais (RF)
- **RF01:** O sistema deve permitir login com e-mail e senha.
- **RF01-B:** O sistema deve permitir que novos usuários realizem auto-cadastro criando sua conta e empresa.
- **RF02:** O sistema deve restringir telas de Empresas e Usuários apenas ao nível de acesso administrador.
- **RF03:** O sistema deve permitir cadastrar empresas clientes (multiempresa).
- **RF03-B:** O sistema deve permitir ao administrador alternar a empresa ativa diretamente pela interface.
- **RF04:** O sistema deve permitir cadastrar e editar funcionários vinculados a uma empresa.
- **RF04-B:** O sistema deve permitir alternar a situação do funcionário (ativo/inativo). Colaboradores inativos não podem receber entregas.
- **RF05:** O sistema deve permitir cadastrar uniformes (peças) com categoria e descrição.
- **RF06:** O sistema deve permitir cadastrar variações de um uniforme (tamanho, cor, SKU, estoque mínimo e estoque inicial).
- **RF07:** O sistema deve manter a quantidade em estoque atualizada atomicamente a cada movimentação (entrada, saída, devolução, baixa).
- **RF08:** O sistema deve permitir registrar a entrega de uma variação de uniforme a um funcionário, abatendo do estoque automaticamente.
- **RF08-B:** O sistema deve emitir termo de entrega formatado para impressão A4 com dados da entrega e linhas de assinatura.
- **RF09:** O sistema deve impedir o registro de uma entrega ou saída maior que a quantidade disponível em estoque.
- **RF10:** O sistema deve exibir no dashboard a quantidade total de peças, o número de funcionários ativos, os itens com estoque abaixo do mínimo e o total de entregas do mês.
- **RF11:** O sistema deve listar, na tela de estoque, todas as variações com destaque visual (amarelo/vermelho) para as que estão em estoque baixo ou esgotadas.
- **RF12:** O sistema deve disponibilizar uma vitrine de apresentação (`index.php`) com simulador dinâmico de economia em tempo real e tabela comparativa.

### 4.2 Requisitos Não Funcionais (RNF) e Segurança
- **Segurança (Hash):** Senhas obrigatoriamente armazenadas com hash criptográfico seguro via `password_hash($senha, PASSWORD_DEFAULT)` (bcrypt).
- **Segurança (SQL Injection):** Toda consulta ao banco de dados realizada com prepared statements via PDO, nunca concatenando variáveis do usuário.
- **Segurança (Validação Servidor):** Todo formulário é validado no servidor (backend), impedindo envios com dados vazios ou inconsistentes.
- **Segurança (Isolamento Multi-Tenant):** Toda página filtra dados estritamente pela `empresa_id` armazenada na sessão segura.
- **Desempenho e Leveza:** Frontend construído sem frameworks externos pesados (HTML5 + CSS3 puros), garantindo tempo de resposta instantâneo.
- **Portabilidade:** Conexão PDO com leitura de `.env` e suporte automático a MySQL e SQLite embutido para zero fricção na avaliação.

---

## 5. Modelo de Dados

### 5.1 Diagrama Entidade-Relacionamento (DER)
```text
empresas (1) ──────────< (N) usuarios
empresas (1) ──────────< (N) funcionarios
empresas (1) ──────────< (N) uniformes
uniformes (1) ─────────< (N) uniforme_variacoes
uniforme_variacoes (1) ─ (1) estoque
uniforme_variacoes (1) ─< (N) movimentacoes
usuarios (1) ──────────< (N) movimentacoes
funcionarios (1) ──────< (N) entregas
uniforme_variacoes (1) ─< (N) entregas
usuarios (1) ──────────< (N) entregas
```

### 5.2 Descrição das Tabelas (Dicionário de Dados)

| Tabela | Campos principais | Chave primária | Relacionamentos |
|---|---|---|---|
| **`empresas`** | `id, nome, cnpj, status, criado_em` | `id` | — |
| **`usuarios`** | `id, empresa_id, nome, email, senha, nivel, status, criado_em` | `id` | `empresa_id → empresas.id` |
| **`funcionarios`** | `id, empresa_id, nome, matricula, setor, cargo, status, criado_em` | `id` | `empresa_id → empresas.id` |
| **`uniformes`** | `id, empresa_id, nome, categoria, descricao, criado_em` | `id` | `empresa_id → empresas.id` |
| **`uniforme_variacoes`** | `id, uniforme_id, tamanho, cor, sku, estoque_minimo, criado_em` | `id` | `uniforme_id → uniformes.id` |
| **`estoque`** | `id, variacao_id, quantidade, atualizado_em` | `id` | `variacao_id → uniforme_variacoes.id` (UNIQUE) |
| **`movimentacoes`** | `id, variacao_id, usuario_id, tipo, quantidade, motivo, data_movimentacao` | `id` | `variacao_id → uniforme_variacoes.id`<br>`usuario_id → usuarios.id` |
| **`entregas`** | `id, funcionario_id, variacao_id, usuario_id, quantidade, observacao, data_entrega` | `id` | `funcionario_id → funcionarios.id`<br>`variacao_id → uniforme_variacoes.id`<br>`usuario_id → usuarios.id` |

### 5.3 Dados Iniciais (Seeds)
O arquivo `database/seed.sql` já insere os dados iniciais completos necessários para testar e demonstrar o sistema:
- **Empresas:** Indústria Alfa (ID 1) e Logística Beta (ID 2).
- **Usuários:** Administrador (`admin@unicontrol.com`) e Operador (`operador@unicontrol.com`).
- **Funcionários:** 3 colaboradores cadastrados em setores distintos (Operações, Logística, Manutenção).
- **Uniformes e Grade:** Camisas Polo, Calças Operacionais e Botas com variações M, G, GG, 41, 42 e 44.
- **Estoque:** Saldos normais e saldos abaixo do mínimo (para validação dos alertas do dashboard).
- **Movimentações e Entregas:** Histórico pré-carregado no mês para exibição imediata dos totais.

---

## 6. Critérios de Aceite

- **CA01:** Dado que um usuário não está logado, quando tenta acessar `dashboard.php` diretamente pela URL, então é redirecionado para `login.php`.
- **CA02:** Dado que um usuário com nível "operador" está logado, quando tenta acessar `empresas.php` ou `usuarios.php`, então o acesso é negado no servidor (HTTP 403).
- **CA03:** Dado que um operador cadastra um novo funcionário, quando a operação é confirmada, então o funcionário passa a aparecer na lista de funcionários ativos da empresa.
- **CA04:** Dado que um operador cadastra uma peça e sua variação com estoque inicial de 10 unidades, então a variação fica imediatamente disponível para entregas com saldo 10.
- **CA05:** Dado que uma variação tem 3 unidades em estoque, quando um operador tenta registrar uma entrega de 5 unidades, então o sistema bloqueia a gravação e informa que apenas 3 unidades estão disponíveis.
- **CA06:** Dado que uma entrega válida é registrada, então o saldo daquela variação diminui automaticamente, gera registro em movimentações e disponibiliza o botão de impressão do termo de entrega.
- **CA07:** Dado que uma variação está com quantidade menor ou igual ao estoque mínimo, quando o dashboard ou tela de estoque são carregados, então essa variação aparece em destaque visual de alerta.
- **CA08:** Dado que um administrador cadastra uma nova empresa, então essa empresa passa a estar disponível para seleção e vínculo de usuários e estoque.

---

## 7. Decisão de Stack e Deploy

- **Linguagem & Backend:** PHP 8.2 puro com extensão PDO (sem frameworks como Laravel/Symfony, mantendo compatibilidade total com o conteúdo pedagógico e com o laboratório).
- **Banco de Dados:** MySQL 8 / MariaDB (com fallback transparente para SQLite integrado via PDO, garantindo execução com zero configuração em qualquer computador).
- **Frontend:** HTML5 semântico, CSS3 moderno próprio e JavaScript puro para micro-interações e cálculos.
- **Deploy na Nuvem:** O sistema foi preparado com dados isolados e variáveis de ambiente para publicação gratuita em provedores compatíveis com PHP + MySQL (ex.: Render, InfinityFree ou Hostinger).

---

## 8. Instalação e Execução (Guia do Avaliador)

> **Nota para o Professor/Avaliador:** Este passo a passo foi testado rigorosamente para garantir que você consiga clonar e rodar o projeto em um computador limpo sem nenhum erro.

### 8.1 Ambiente
- **Linguagem:** PHP 8.2 ou superior (com extensões `pdo_mysql` e `pdo_sqlite` habilitadas).
- **Servidor Local:** Servidor embutido do PHP (`php -S`) ou Apache/XAMPP.
- **Bibliotecas e Dependências:** **Nenhuma dependência externa.** Não é necessário rodar `composer install` nem `npm install`. O projeto foi construído em PHP e CSS puros para máxima portabilidade.
- **Sistemas Operacionais Testados:** Windows 10 e Windows 11.

---

### 8.2 Banco de Dados
- **SGBD Recomendado:** MySQL 8.0 ou MariaDB 10+.
- **Nome do Banco:** `unicontrol_db`.
- **Scripts SQL no Repositório:**
  - `database/schema.sql` — Cria as 8 tabelas e chaves estrangeiras.
  - `database/seed.sql` — Insere os dados iniciais (empresas, usuários com senhas hash, uniformes e funcionários).
- **Como Executar o Script:**
  - *Via phpMyAdmin:* Crie a base `unicontrol_db` e importe `database/schema.sql` e depois `database/seed.sql`.
  - *Via Terminal MySQL:*
    ```bash
    mysql -u root -p -e "CREATE DATABASE unicontrol_db;"
    mysql -u root -p unicontrol_db < database/schema.sql
    mysql -u root -p unicontrol_db < database/seed.sql
    ```
  - *Via Navegador/Instalador Automático:* Acesse `http://localhost:8000/database/setup.php`.
  - *Fallback Automático:* Caso nenhum MySQL esteja configurado no momento da avaliação, o sistema utiliza automaticamente o banco SQLite pré-configurado em `database/unicontrol.sqlite`, sem que a aplicação quebre!

---

### 8.3 Variáveis de Ambiente
O repositório contém o arquivo `.env.example`. Para configurar sua conexão local, copie-o para `.env`:

```bash
cp .env.example .env
```

| Variável | Descrição | Exemplo de Preenchimento Local |
|---|---|---|
| `DB_DRIVER` | Driver de banco de dados (`mysql` ou `sqlite`) | `mysql` |
| `DB_HOST` | Endereço do servidor MySQL | `127.0.0.1` ou `localhost` |
| `DB_PORT` | Porta de conexão do MySQL | `3306` |
| `DB_NAME` | Nome do banco de dados | `unicontrol_db` |
| `DB_USER` | Usuário do MySQL | `root` |
| `DB_PASS` | Senha do usuário do MySQL | *(deixe em branco se padrão XAMPP)* |

---

### 8.4 Passo a Passo de Instalação Numerado

Siga a sequência exata abaixo no terminal:

1. **Clonar o Repositório do GitHub:**
   ```bash
   git clone https://github.com/ryannesoares56-ux/UniControl.git
   ```

2. **Entrar na Pasta do Projeto:**
   ```bash
   cd UniControl
   ```

3. **Configurar as Variáveis de Ambiente:**
   Copie o arquivo de exemplo para criar o seu `.env`:
   ```bash
   copy .env.example .env
   ```
   *(Ajuste o usuário e senha do seu MySQL local se necessário).*

4. **Criar e Popular o Banco de Dados:**
   Importe os arquivos `database/schema.sql` e `database/seed.sql` no seu MySQL (ou simplesmente execute o passo 5, pois o sistema possui inicialização com fallback automático).

5. **Iniciar o Servidor Local:**
   - **No Windows (Mais Fácil):** Dê 2 cliques no arquivo `iniciar_sistema.bat`. Ele subirá o servidor e abrirá o navegador sozinho.
   - **Ou pelo Terminal:**
     ```bash
     php -S 127.0.0.1:8000
     ```

6. **Abrir no Navegador:**
   Acesse o endereço:
   ```text
   http://localhost:8000
   ```

---

### 8.5 Como Testar o Sistema

#### Credenciais de Teste Pré-Configuradas (Fictícias):
- **Perfil Administrador:**
  - **E-mail:** `admin@unicontrol.com`
  - **Senha:** `admin123`
- **Perfil Operador:**
  - **E-mail:** `operador@unicontrol.com`
  - **Senha:** `operador123`

#### Roteiro do Fluxo Principal para Avaliação:
1. **Vitrine Inicial (`/index.php`):** Teste o simulador dinâmico de economia arrastando os controles deslizantes.
2. **Login:** Clique em *"Acessar o Sistema"* e entre com `admin@unicontrol.com` (ou use os botões de 1 clique da vitrine).
3. **Dashboard (`/dashboard.php`):** Veja os 4 indicadores em tempo real e a tabela de alertas com itens com estoque baixo.
4. **Catálogo (`/uniformes.php`):** Cadastre uma nova peça (ex: "Jaleco Anti-chama") e adicione uma variação com tamanho "G", cor "Azul" e estoque inicial de 5 peças.
5. **Estoque Geral (`/estoque.php`):** Verifique a peça recém-cadastrada no inventário geral.
6. **Teste da Trava Anti-Furo (`/entregas.php`):** Tente registrar a entrega de **10 peças** para um colaborador. O sistema **bloqueará no servidor** avisando que só há 5 unidades disponíveis.
7. **Entrega Válida e Recibo:** Registre a entrega de **2 peças**. O estoque cairá para 3 un. Na tabela abaixo, clique no botão **`🖨️ Recibo`** para visualizar o termo de recebimento oficial formatado para impressão.
8. **Teste de Permissões:** Deslogue e entre como `operador@unicontrol.com`. Tente acessar `http://localhost:8000/empresas.php` e comprove o bloqueio de segurança (HTTP 403).

#### Problemas Conhecidos e Limitações:
- O upload de foto física do uniforme não foi implementado nesta versão (o catálogo utiliza categorias e descrição textual detalhada).
- A assinatura no termo de entrega impresso é realizada fisicamente pelo colaborador com caneta, conforme praxe da maioria dos almoxarifados industriais.
