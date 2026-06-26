# Sistema de Gestão Hospitalar e Prontuário Eletrônico

Este repositório contém o código-fonte do projeto **Sistema de Gestão Hospitalar e Prontuário Eletrônico**, desenvolvido em **PHP** com banco de dados **MySQL/MariaDB**. O projeto foi estruturado em iterações, com o objetivo de entregar funcionalidades de forma incremental, utilizando boas práticas de desenvolvimento, organização em camadas e integração com banco de dados.

O sistema tem como finalidade auxiliar no gerenciamento de processos hospitalares, como cadastro de pacientes, médicos, consultas, triagem, prontuários, exames, prescrições, internações, leitos, convênios, insumos, faturamento e auditoria.

---

## 🔄 Release 1

### ✅ Iteração 1: Configuração Inicial e Estrutura do Projeto

📅 **Data:** 02/05/2026 a 08/05/2026  
🎯 **Objetivo:** Preparar o ambiente de desenvolvimento e estabelecer a estrutura base do sistema.

**Atividades:**

- Configuração do ambiente de desenvolvimento utilizando XAMPP, PHP, MySQL/MariaDB, MySQL Workbench e VS Code.
- Criação da estrutura inicial do projeto.
- Definição da organização em camadas:
  - `config`
  - `model`
  - `dao`
  - `controller`
  - `views`
  - `sql`
- Criação do banco de dados inicial no MySQL/MariaDB.
- Modelagem das principais entidades do sistema.
- Criação das primeiras telas do sistema utilizando HTML, CSS, Bootstrap e PHP.
- Configuração inicial da conexão com o banco de dados.

---

### 🔐 Iteração 2: Autenticação e Controle de Acesso

📅 **Data:** 09/05/2026 a 15/05/2026  
🎯 **Objetivo:** Permitir o acesso ao sistema por meio de autenticação de usuários.

**Atividades:**

- Criação da tela de login.
- Implementação do controle de sessão com PHP.
- Validação de acesso ao sistema.
- Criação do processo de logout.
- Restrição de acesso às páginas internas do sistema.
- Estruturação inicial da tabela de usuários.
- Organização do menu principal para usuários autenticados.

---

### 📋 Iteração 3: Cadastros Principais do Sistema

📅 **Data:** 16/05/2026 a 22/05/2026  
🎯 **Objetivo:** Implementar os cadastros essenciais para o funcionamento do sistema hospitalar.

**Atividades:**

- Implementação do cadastro de pacientes.
- Implementação do cadastro de médicos.
- Implementação do cadastro de convênios.
- Implementação do cadastro de medicamentos.
- Implementação do cadastro de salas e leitos.
- Criação das listagens dos registros cadastrados.
- Implementação de validações básicas nos formulários.
- Integração dos cadastros com o banco de dados MySQL/MariaDB.
- Desenvolvimento das operações de CRUD: criar, listar, editar e excluir/inativar registros.

---

### 🏥 Iteração 4: Consultas, Triagem e Prontuário

📅 **Data:** 23/05/2026 a 29/05/2026  
🎯 **Objetivo:** Adicionar funcionalidades relacionadas ao atendimento do paciente.

**Atividades:**

- Implementação do agendamento de consultas.
- Relacionamento entre pacientes, médicos, salas e consultas.
- Implementação da triagem do paciente.
- Inclusão da Classificação de Manchester no processo de triagem.
- Criação do prontuário eletrônico vinculado à consulta.
- Registro da evolução clínica e observações do atendimento.
- Criação de alertas relacionados a alergias e histórico clínico do paciente.
- Organização do fluxo de atendimento, desde o cadastro até o prontuário.

---

### 🧪 Iteração 5: Exames e Prescrições

📅 **Data:** 30/05/2026 a 03/06/2026  
🎯 **Objetivo:** Implementar funcionalidades clínicas complementares ao prontuário.

**Atividades:**

- Implementação da solicitação de exames.
- Vinculação dos exames ao prontuário do paciente.
- Controle de status dos exames: solicitado, em análise, finalizado e cancelado.
- Registro de resultado dos exames finalizados.
- Implementação da emissão de prescrições médicas.
- Vinculação das prescrições ao prontuário e aos medicamentos.
- Validação de medicamentos, dosagens, frequência e duração do tratamento.
- Registro de informações importantes, como contraindicações e interações medicamentosas.

---

### 🛏️ Iteração 6: Internações, Leitos e Insumos

📅 **Data:** 04/06/2026 a 07/06/2026  
🎯 **Objetivo:** Controlar os processos relacionados à internação hospitalar.

**Atividades:**

- Implementação do cadastro e controle de leitos.
- Controle de status dos leitos: disponível, ocupado, manutenção, higienização e inativo.
- Implementação do processo de internação do paciente.
- Vinculação entre paciente, internação e leito.
- Implementação do processo de alta hospitalar.
- Liberação ou alteração do status do leito após a alta.
- Implementação do cadastro de insumos.
- Registro do uso de insumos durante a internação.
- Controle de quantidade utilizada e data de uso.

---

### 💰 Iteração 7: Faturamento e Auditoria

📅 **Data:** 08/06/2026 a 10/06/2026  
🎯 **Objetivo:** Implementar o controle financeiro e o registro de ações importantes no sistema.

**Atividades:**

- Implementação do módulo de faturamento.
- Geração de guia/fatura por paciente.
- Consolidação de gastos relacionados a consultas, exames, prescrições, insumos e internações.
- Controle de status do faturamento: pendente, pago e cancelado.
- Implementação da baixa de faturamento.
- Implementação do cancelamento de guias.
- Criação de registros de auditoria para ações importantes do sistema.
- Registro de informações como ação realizada, tabela afetada, usuário e data/hora.

---

### 🚀 Iteração 8: Testes Finais e Preparação para Entrega

📅 **Data:** 11/06/2026 a 12/06/2026  
🎯 **Objetivo:** Revisar o sistema, corrigir erros e preparar a entrega final do projeto.

**Atividades:**

- Revisão geral das funcionalidades implementadas.
- Correção de erros em cadastros, exclusões, consultas, exames e prontuários.
- Revisão do banco de dados.
- Teste dos relacionamentos entre tabelas.
- Teste do fluxo completo do sistema: login, cadastro de paciente, agendamento de consulta, triagem, prontuário, exames, prescrição, internação e faturamento.
- Revisão da interface visual com Bootstrap.
- Organização dos arquivos do projeto.
- Criação do script SQL final para entrega.
- Revisão da documentação final.
- Preparação do projeto para envio via GitHub.

---

## 📦 Tecnologias Utilizadas

- **PHP:** desenvolvimento da lógica do sistema, processamento dos formulários e integração com o banco de dados.
- **MySQL/MariaDB:** armazenamento relacional das informações do sistema hospitalar.
- **XAMPP:** ambiente local de desenvolvimento com Apache, PHP e MySQL/MariaDB.
- **Bootstrap:** construção da interface visual responsiva e padronizada.
- **HTML5:** estruturação das páginas.
- **CSS3:** ajustes visuais e personalização da interface.
- **JavaScript:** interações de interface.
- **jQuery:** apoio em interações visuais e comportamentos da interface.
- **Git/GitHub:** versionamento e organização das iterações do projeto.

---

## 📁 Estrutura de Pastas

```text
projeto_webii_final/
├── 1-Diagramas/
│   ├── Caso-de-Uso.pdf
│   ├── Caso-de-Uso.png
│   ├── DER.png
│   ├── DER.pdf
│   └── Modelo-DER.mwb
│
├── 2-Principais-Telas/
│   └── imagens das principais telas do sistema
│
├── 3-README-Documentacao/
│   └── arquivos complementares de documentação
│
├── config/
│   ├── Database.php
│   ├── config.php
│   └── helpers.php
│
├── controller/
│   ├── pacienteController.php
│   ├── medicoController.php
│   ├── consultaController.php
│   ├── prontuarioController.php
│   ├── exameController.php
│   ├── prescricaoController.php
│   ├── internacaoController.php
│   ├── faturamentoController.php
│   ├── leitoController.php
│   ├── convenioController.php
│   ├── insumoController.php
│   └── usoInsumoController.php
│
├── dao/
│   ├── PacienteDAO.php
│   ├── MedicoDAO.php
│   ├── ConsultaDAO.php
│   ├── ProntuarioDAO.php
│   ├── ExameDAO.php
│   ├── PrescricaoDAO.php
│   ├── InternacaoDAO.php
│   ├── LeitoDAO.php
│   ├── ConvenioDAO.php
│   ├── FaturamentoDAO.php
│   ├── InsumoDAO.php
│   └── UsoInsumoDAO.php
│
├── model/
│   ├── Paciente.php
│   ├── Medico.php
│   ├── Consulta.php
│   ├── Prontuario.php
│   ├── Exame.php
│   ├── Prescricao.php
│   ├── Medicamento.php
│   ├── Internacao.php
│   ├── Leito.php
│   ├── Convenio.php
│   ├── Insumo.php
│   ├── UsoInsumo.php
│   ├── Faturamento.php
│   └── Auditoria.php
│
├── views/
│   ├── header.php
│   └── footer.php
│
├── sql/
│   └── hospital.sql
│
├── Documentacao_Sistema_Hospitalar.docx
├── README.md
├── index.php
├── login.php
├── logout.php
└── demais telas e arquivos processadores do sistema
```

---

## 🧪 Testes

Os testes do sistema foram realizados de forma manual, simulando o uso real das funcionalidades desenvolvidas.

Foram testados os seguintes fluxos:

- Login e logout do sistema.
- Cadastro, edição, listagem e exclusão/inativação de registros.
- Cadastro de pacientes.
- Cadastro de médicos.
- Cadastro de convênios.
- Cadastro de medicamentos.
- Cadastro e edição de salas/leitos.
- Agendamento de consultas.
- Registro de triagem.
- Classificação de Manchester.
- Abertura e preenchimento de prontuário.
- Solicitação e finalização de exames.
- Emissão de prescrições.
- Processo de internação.
- Processo de alta hospitalar.
- Registro de uso de insumos.
- Geração de faturamento.
- Baixa e cancelamento de faturamento.
- Registro de auditoria.

Também foram realizados testes de relacionamento entre as tabelas do banco de dados, verificando se as chaves estrangeiras estavam funcionando corretamente e se os dados eram exibidos adequadamente nas telas do sistema.

---

## 🗂️ Principais Módulos do Sistema

### Pacientes

Permite cadastrar, listar, editar e excluir pacientes. Também armazena informações como convênio, carteirinha, validade da carteirinha, alergias, tipo sanguíneo e histórico clínico.

### Médicos

Permite cadastrar, listar e editar médicos, incluindo CRM, especialidade, telefone, e-mail e status.

### Convênios

Permite cadastrar, listar, editar e inativar convênios utilizados pelos pacientes.

### Consultas e Triagem

Permite realizar o agendamento de consultas, vinculando paciente, médico e sala. Também permite registrar sinais vitais, classificar o risco do paciente e definir o destino após a triagem.

### Prontuário

Permite registrar evolução clínica, observações, exames e prescrições relacionadas ao atendimento.

### Exames

Permite solicitar exames, alterar status, registrar resultados e controlar o fluxo do paciente após o exame.

### Prescrições

Permite emitir prescrições médicas vinculadas ao prontuário e aos medicamentos cadastrados.

### Medicamentos

Permite cadastrar e consultar medicamentos, incluindo informações de dosagem, contraindicações e interações.

### Leitos e Internações

Permite controlar os leitos hospitalares e registrar internações, altas, status do paciente internado e higienização de leitos.

### Insumos

Permite cadastrar insumos hospitalares, controlar estoque e registrar o uso durante internações.

### Faturamento

Permite gerar guias de cobrança, consolidar gastos, baixar pagamentos e cancelar faturamentos.

### Auditoria

Permite registrar ações importantes realizadas no sistema, contribuindo para controle e rastreabilidade.

---

## 🧩 Banco de Dados

O banco de dados utilizado no projeto se chama:

```sql
hospital
```

As principais tabelas do sistema são:

- usuarios
- convenios
- pacientes
- medicos
- salas
- consultas
- triagem
- prontuario
- exames
- medicamentos
- prescricoes
- leitos
- internacoes
- insumos
- uso_insumos
- faturamento
- itens_faturamento
- auditoria

O banco foi modelado com chaves primárias e estrangeiras para garantir o relacionamento correto entre as entidades.

Exemplos de relacionamentos:

- Um convênio pode estar vinculado a vários pacientes.
- Um paciente pode ter várias consultas.
- Um médico pode atender várias consultas.
- Uma consulta pode gerar uma triagem e um prontuário.
- Um prontuário pode possuir vários exames.
- Um prontuário pode possuir várias prescrições.
- Uma prescrição está vinculada a um medicamento.
- Um paciente pode possuir várias internações.
- Uma internação está vinculada a um leito.
- Um paciente pode gerar faturamentos.
- Um usuário pode gerar registros de auditoria.

---

## 🔗 Diagramas

Os diagramas do projeto estão na pasta:

```text
1-Diagramas/
```

Arquivos incluídos:

- `Caso-de-Uso.pdf`
- `Caso-de-Uso.png`
- `DER.png`
- `DER.pdf`
- `Modelo-DER.mwb`

A documentação final também contém os diagramas inseridos no arquivo:

```text
Documentacao_Sistema_Hospitalar.docx
```

---

## 📌 Processo Unificado

O desenvolvimento do sistema seguiu as fases do Processo Unificado:

### Iniciação

Nesta fase foi definido o tema do projeto, seus objetivos, escopo inicial, principais funcionalidades e levantamento dos requisitos.

### Elaboração

Nesta fase foram definidos os casos de uso, a modelagem do banco de dados, a estrutura do sistema e o planejamento das funcionalidades.

### Construção

Nesta fase ocorreu a implementação prática do sistema, com desenvolvimento das telas, controllers, models, DAOs, conexão com banco de dados e funcionalidades principais.

### Transição

Nesta fase foram realizados os testes finais, correções, organização da documentação, preparação do script SQL e envio do projeto para entrega.

---

## 🌿 Organização de Branches no Git

Para atender à proposta de gerenciamento das iterações por branches, recomenda-se utilizar a seguinte estrutura no GitHub:

```text
main
develop
iteracao-01-estrutura
iteracao-02-login
iteracao-03-cadastros
iteracao-04-consultas-triagem-prontuario
iteracao-05-exames-prescricoes
iteracao-06-internacoes-leitos-insumos
iteracao-07-faturamento-auditoria
iteracao-08-testes-documentacao
```

A branch `main` deve conter a versão final do sistema. As branches de iteração servem para registrar a evolução incremental do projeto.

---

## ⚙️ Como Executar o Projeto

1. Instale o XAMPP.
2. Copie a pasta do projeto para o diretório `htdocs`.
3. Inicie o Apache e o MySQL pelo painel do XAMPP.
4. Acesse o phpMyAdmin ou MySQL Workbench.
5. Importe o arquivo SQL localizado em:

```text
sql/hospital.sql
```

6. Verifique as configurações de conexão no arquivo:

```text
config/Database.php
```

7. Acesse o sistema pelo navegador usando o endereço:

```text
http://localhost/projeto_webii_final
```

---

## 👨‍💻 Objetivo Acadêmico

Este sistema foi desenvolvido como projeto acadêmico, com o objetivo de aplicar conceitos de Engenharia de Software, Desenvolvimento Web, Banco de Dados e Programação Orientada a Objetos.

Durante o desenvolvimento, foram aplicados conceitos como:

- Levantamento de requisitos;
- Processo Unificado;
- Diagrama de Caso de Uso;
- Diagrama de Entidade e Relacionamento;
- Modelagem de banco de dados;
- Relacionamentos entre tabelas;
- CRUD;
- Organização em camadas;
- Autenticação;
- Interface responsiva;
- Testes manuais;
- Documentação do sistema;
- Versionamento com Git.

---

## 📝 Licença

Este projeto foi desenvolvido para fins acadêmicos, como parte das atividades da disciplina de Engenharia de Software/Desenvolvimento Web.
