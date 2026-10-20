# UniControl — Registro de Decisões e Mudanças (CHANGELOG.md)

Este documento registra a evolução do projeto **UniControl**, as decisões técnicas de arquitetura, os erros corrigidos e, principalmente, **as revisões humanas realizadas sobre as propostas do agente de inteligência artificial (Google Antigravity)** durante todo o ciclo de desenvolvimento, em conformidade com as diretrizes da apostila *"Programar com IA de forma responsável"*.

---

## 📅 Resumo dos Ciclos de Desenvolvimento e Decisões

### Ciclo 1: Inicialização do Repositório e Conexão com GitHub
- **O que foi proposto pelo agente:** O agente tentou executar `git clone https://... .` diretamente na pasta do workspace.
- **Problema encontrado:** O Git falhou com `destination path '.' already exists and is not an empty directory`, pois a pasta já continha o arquivo da especificação original (`spec.pdf`).
- **Decisão / Correção:** Em vez de apagar os arquivos existentes, optou-se por rodar `git init`, definir a branch `main`, conectar a origem remota `origin` e preservar o documento `spec.pdf`.
- **Tentativa de uso do GitHub CLI (`gh`):** O agente tentou instalar o `gh` via `winget`, que disparou solicitações de elevação no Windows. A aluna recusou comandos intrusivos. Foi decidido utilizar o próprio **Git nativo do Windows com Git Credential Manager**, que autenticou de forma transparente pelo navegador.

---

### Ciclo 2: Modelagem do Banco de Dados e Arquitetura Inicial
- **O que foi proposto pelo agente:** Criar o banco relacional completo em MySQL (8 tabelas) cobrindo empresas, usuários, funcionários, uniformes, variações, estoque, movimentações e entregas, utilizando PHP com PDO.
- **Revisão humana / Decisão:** Aprovado pela aluna após validação do plano de implementação inicial (`implementation_plan.md`).
- **Segurança implementada:**
  - Criptografia com `password_hash($senha, PASSWORD_DEFAULT)` (bcrypt).
  - Consultas 100% parametrizadas (Prepared Statements) para impedir qualquer risco de SQL Injection.
  - Isolamento multiempresa obrigatório por sessão (`empresa_id`).

---

### Ciclo 3: Resolução de Conectividade de Banco de Dados (Dual Driver)
- **Problema encontrado:** Ao executar a aplicação nas máquinas do laboratório da escola, o serviço do MySQL local (`MySQL80` / `MariaDB`) exigia senha de root diferente das senhas padrão de sala de aula.
- **Decisão arquitetural crucial (Revisão da IA):** Para garantir que o professor consiga clonar e avaliar o sistema **sem nenhuma chance de erro ou travamento**, foi implementada uma camada de compatibilidade no `config/database.php`:
  1. O sistema tenta conectar ao MySQL configurado no `.env`.
  2. Se o MySQL não responder ou recusar a senha, o sistema ativa um **fallback transparente para SQLite local** (`database/unicontrol.sqlite`), que já inicializa automaticamente todas as tabelas e dados de teste.
  3. Todas as queries SQL de data (como entregas do mês) foram refatoradas para o padrão **ANSI SQL** (intervalo de datas `inicio_mes` e `fim_mes`), funcionando perfeitamente em MySQL, MariaDB e SQLite.

---

### Ciclo 4: Usabilidade para a Aluna nos Laptops da Escola (`iniciar_sistema.bat`)
- **Intervenção da aluna:** *"quando volto no outro dia, fico perdida... o que devo abrir para verificar?"*
- **Ação tomada:** Foi criado o arquivo executável **`iniciar_sistema.bat`**. Com apenas **dois cliques**, ele inicia o servidor local embutido do PHP (`php -S 127.0.0.1:8000`) e abre automaticamente o navegador na tela inicial, eliminando a necessidade de digitar comandos no terminal a cada reinicialização da máquina da escola.

---

### Ciclo 5: Revisão Humana — Termo de Entrega com Assinatura Física (`recibo_entrega.php`)
- **Intervenção / Necessidade Real de Negócio:** Na spec original, a entrega de uniforme era apenas uma gravação no banco de dados. No entanto, no ambiente industrial e trabalhista (CLT e NR-6), a entrega só tem validade se houver comprovante assinado pelo colaborador.
- **Implementação:** Foi criada a tela `recibo_entrega.php` e adicionado o botão **`🖨️ Recibo`** na tela de entregas. A página gera o protocolo formal com dados da empresa, matrícula, cargo, itens recebidos, termo de zelo e linhas de assinatura para impressão em A4.

---

### Ciclo 6: Revisão Humana — Alternador de Empresas para Administrador
- **Problema:** A spec exigia suporte multiempresa, mas o administrador ficava fixado na primeira empresa cadastrada, exigindo deslogar e logar com outra conta para ver a outra filial.
- **Implementação:** Foi criado um seletor dinâmico de empresa ativa na barra superior (`includes/header.php`) e o botão *"Acessar"* em `empresas.php`, permitindo ao administrador alternar entre a Empresa 1 (Alfa) e a Empresa 2 (Beta) com 1 clique para demonstrar o isolamento de estoque ao professor.

---

### Ciclo 7: Revisão Humana — Vitrine Comercial Dinâmica (`index.php`)
- **Intervenção da aluna:** *"agora gravi, não tem aquela parte de todo site que tem que chamar atenção de quem vai ter que usar o produto? faz bem dinâmico, por favor"*
- **O que o agente fez:** O `index.php` antes era apenas um redirecionador silencioso para o dashboard. Foi totalmente transformado em uma **Landing Page de alto impacto visual** (estilo SaaS) contendo:
  - Vitrine das 6 habilidades únicas do sistema.
  - Tabela comparativa (UniControl vs Papel/Excel).
  - **Simulador Dinâmico de Economia:** Controle deslizante interativo em JavaScript que calcula em tempo real a economia estimada de extravios e horas de almoxarifado economizadas conforme o tamanho da empresa.
  - Botões de acesso rápido com 1 clique para testes de avaliação (Admin e Operador).

---

### Ciclo 8: Revisão Humana — Criação de Contas para Novos Usuários (`cadastro.php`)
- **Intervenção da aluna:** Enviou captura de tela da tela de login e apontou: *"a pessoa é nova nesse sistema e tem que criar a conta dela, ou fazer login, entendeu? como podemos resolver isso?"*
- **Problema identificado:** O sistema só permitia que o Administrador já cadastrado criasse outros usuários por dentro do painel restrito. Uma pessoa externa que chegasse ao sistema não tinha como se cadastrar.
- **Implementação:** Foi criada a tela `cadastro.php` permitindo o auto-registro completo (cria a empresa, cadastra a pessoa como Administradora dessa empresa, realiza o login automático e direciona ao painel). O link *"Novo no UniControl? Criar uma conta agora ➔"* foi integrado no `login.php` e no botão de destaque da vitrine `index.php`.

---

### Ciclo 9: Adequação Estrita aos Requisitos de Entrega da Disciplina
- **Criação do `.env.example`:** Variáveis de ambiente isoladas para conexão de banco (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_DRIVER`).
- **Implementação de Leitor Nativo de `.env` em `config/database.php`:** Permite carregar as variáveis sem exigir dependências pesadas de terceiros (Composer/vlucas/phpdotenv).
- **Consolidação da `spec.md` Final:** Substituição de documentações dispersas pelo arquivo oficial `spec.md`, estruturado exatamente nas seções exigidas pelo professor e contendo o guia passo a passo de instalação sem lacunas.

---

## 📊 Histórico de Commits no Repositório Oficial

1. `3255a8d` — `feat: estrutura inicial do projeto UniControl e documentacao`
2. `5811249` — `feat: implementacao completa das telas e regras de negocio do UniControl`
3. `ab8d71e` — `feat: script iniciar_sistema.bat e compatibilidade com servidor local`
4. `33f9f1e` — `feat: recibo de entrega para impressao, switcher multiempresa e gestao completa de status`
5. `4ffc872` — `feat: landing page dinamica com vitrine do produto e simulador de economia`
6. `943d967` — `feat: tela de auto-cadastro de conta e empresa (cadastro.php) e integracao com login`
7. `4b8cefc` — `docs: especificacao tecnica final consolidada (SPEC_FINAL.md) com guia passo a passo`
