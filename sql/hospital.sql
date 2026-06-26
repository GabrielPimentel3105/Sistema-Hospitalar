SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS hospital;
SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE hospital
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE hospital;

-- ============================================================
-- USUÁRIOS
-- ============================================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario VARCHAR(30) NOT NULL DEFAULT 'Recepção',
    status_usuario VARCHAR(20) NOT NULL DEFAULT 'Ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONVÊNIOS
-- ============================================================
CREATE TABLE convenios (
    id_convenio INT AUTO_INCREMENT PRIMARY KEY,
    nome_convenio VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NULL,
    procedimentos_autorizados TEXT NULL,
    status_convenio VARCHAR(20) NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PACIENTES
-- ============================================================
CREATE TABLE pacientes (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    data_nascimento DATE NULL,
    telefone VARCHAR(20) NULL,
    endereco VARCHAR(150) NULL,
    alergias TEXT NULL,
    historico_clinico TEXT NULL,
    tipo_sanguineo VARCHAR(5) NULL,
    id_convenio INT NULL,
    numero_carteirinha VARCHAR(50) NULL,
    validade_carteirinha DATE NULL,
    status_paciente VARCHAR(20) NOT NULL DEFAULT 'Ativo',

    INDEX idx_pacientes_convenio (id_convenio),

    CONSTRAINT fk_pacientes_convenios
        FOREIGN KEY (id_convenio)
        REFERENCES convenios(id_convenio)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÉDICOS
-- ============================================================
CREATE TABLE medicos (
    id_medico INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    crm VARCHAR(30) NOT NULL UNIQUE,
    especialidade VARCHAR(80) NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    status_medico VARCHAR(20) NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SALAS
-- ============================================================
CREATE TABLE salas (
    id_sala INT AUTO_INCREMENT PRIMARY KEY,
    numero_sala VARCHAR(20) NOT NULL,
    tipo_sala VARCHAR(60) NULL,
    status_sala VARCHAR(30) NOT NULL DEFAULT 'Disponível'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONSULTAS
-- Inclui campos destino_paciente e status_fluxo para controlar o fluxo: atendimento, exame, internação e alta.
-- Atenção: status_consulta ficou como VARCHAR, não ENUM,
-- porque o sistema usa vários status diferentes em telas/processamentos.
-- ============================================================
CREATE TABLE consultas (
    id_consulta INT AUTO_INCREMENT PRIMARY KEY,
    data_consulta DATE NOT NULL,
    horario TIME NOT NULL,
    status_consulta VARCHAR(50) NOT NULL DEFAULT 'Agendada',
    destino_paciente VARCHAR(50) NOT NULL DEFAULT 'ATENDIMENTO',
    status_fluxo VARCHAR(80) NOT NULL DEFAULT 'Aguardando atendimento',
    id_paciente INT NOT NULL,
    id_medico INT NOT NULL,
    id_sala INT NULL,
    id_convenio INT NULL,

    INDEX idx_consultas_paciente (id_paciente),
    INDEX idx_consultas_medico (id_medico),
    INDEX idx_consultas_sala (id_sala),
    INDEX idx_consultas_convenio (id_convenio),
    INDEX idx_consultas_data (data_consulta),
    INDEX idx_consultas_destino (destino_paciente),
    INDEX idx_consultas_fluxo (status_fluxo),

    CONSTRAINT fk_consultas_pacientes
        FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_consultas_medicos
        FOREIGN KEY (id_medico)
        REFERENCES medicos(id_medico)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_consultas_salas
        FOREIGN KEY (id_sala)
        REFERENCES salas(id_sala)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_consultas_convenios
        FOREIGN KEY (id_convenio)
        REFERENCES convenios(id_convenio)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRIAGEM / PROTOCOLO DE MANCHESTER
-- Inclui saturacao e saturacao_oxigenio para manter compatibilidade
-- com arquivos diferentes do sistema.
-- ============================================================
CREATE TABLE triagem (
    id_triagem INT AUTO_INCREMENT PRIMARY KEY,
    temperatura DECIMAL(4,1) NULL,
    pressao_arterial VARCHAR(20) NULL,
    frequencia_cardiaca INT NULL,
    saturacao INT NULL,
    saturacao_oxigenio INT NULL,
    escala_dor INT NULL,
    queixa_principal TEXT NULL,
    observacoes TEXT NULL,
    classificacao_risco VARCHAR(20) NOT NULL DEFAULT 'Azul',
    protocolo_manchester VARCHAR(40) NOT NULL DEFAULT 'Manchester',
    data_triagem DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hora_triagem TIME NULL,
    id_paciente INT NOT NULL,
    id_consulta INT NULL,

    INDEX idx_triagem_paciente (id_paciente),
    INDEX idx_triagem_consulta (id_consulta),
    INDEX idx_triagem_classificacao (classificacao_risco),

    CONSTRAINT fk_triagem_pacientes
        FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_triagem_consultas
        FOREIGN KEY (id_consulta)
        REFERENCES consultas(id_consulta)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PRONTUÁRIO
-- O código usa o nome da tabela no singular: prontuario
-- ============================================================
CREATE TABLE prontuario (
    id_prontuario INT AUTO_INCREMENT PRIMARY KEY,
    evolucao_clinica TEXT NULL,
    observacoes TEXT NULL,
    data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_consulta INT NOT NULL,

    UNIQUE KEY uq_prontuario_consulta (id_consulta),

    CONSTRAINT fk_prontuario_consultas
        FOREIGN KEY (id_consulta)
        REFERENCES consultas(id_consulta)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- EXAMES
-- ============================================================
CREATE TABLE exames (
    id_exame INT AUTO_INCREMENT PRIMARY KEY,
    nome_exame VARCHAR(120) NOT NULL,
    resultado TEXT NULL,
    data_exame DATE NULL,
    valor_exame DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    id_prontuario INT NOT NULL,
    status_exame VARCHAR(30) NOT NULL DEFAULT 'Solicitado',

    INDEX idx_exames_prontuario (id_prontuario),
    INDEX idx_exames_status (status_exame),

    CONSTRAINT fk_exames_prontuario
        FOREIGN KEY (id_prontuario)
        REFERENCES prontuario(id_prontuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MEDICAMENTOS
-- ============================================================
CREATE TABLE medicamentos (
    id_medicamento INT AUTO_INCREMENT PRIMARY KEY,
    nome_medicamento VARCHAR(120) NOT NULL,
    contraindicacoes TEXT NULL,
    interacoes_medicamentosas TEXT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantidade_estoque INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PRESCRIÇÕES
-- Inclui status_prescricao, usado pelo código.
-- ============================================================
CREATE TABLE prescricoes (
    id_prescricao INT AUTO_INCREMENT PRIMARY KEY,
    dosagem VARCHAR(80) NULL,
    frequencia VARCHAR(100) NULL,
    duracao_tratamento VARCHAR(80) NULL,
    data_prescricao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_prontuario INT NOT NULL,
    id_medicamento INT NOT NULL,
    status_prescricao VARCHAR(30) NOT NULL DEFAULT 'Ativa',

    INDEX idx_prescricoes_prontuario (id_prontuario),
    INDEX idx_prescricoes_medicamento (id_medicamento),

    CONSTRAINT fk_prescricoes_prontuario
        FOREIGN KEY (id_prontuario)
        REFERENCES prontuario(id_prontuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_prescricoes_medicamentos
        FOREIGN KEY (id_medicamento)
        REFERENCES medicamentos(id_medicamento)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- LEITOS
-- ============================================================
CREATE TABLE leitos (
    id_leito INT AUTO_INCREMENT PRIMARY KEY,
    numero_leito VARCHAR(20) NOT NULL,
    ala VARCHAR(60) NULL,
    status_leito VARCHAR(30) NOT NULL DEFAULT 'Disponível',
    tempo_higienizacao INT NOT NULL DEFAULT 30,
    inicio_higienizacao DATETIME NULL,

    INDEX idx_leitos_status (status_leito)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INTERNAÇÕES
-- ============================================================
CREATE TABLE internacoes (
    id_internacao INT AUTO_INCREMENT PRIMARY KEY,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_alta DATETIME NULL,
    status_internacao VARCHAR(30) NOT NULL DEFAULT 'Ativa',
    id_paciente INT NOT NULL,
    id_leito INT NOT NULL,

    INDEX idx_internacoes_paciente (id_paciente),
    INDEX idx_internacoes_leito (id_leito),
    INDEX idx_internacoes_status (status_internacao),

    CONSTRAINT fk_internacoes_pacientes
        FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_internacoes_leitos
        FOREIGN KEY (id_leito)
        REFERENCES leitos(id_leito)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSUMOS
-- ============================================================
CREATE TABLE insumos (
    id_insumo INT AUTO_INCREMENT PRIMARY KEY,
    nome_insumo VARCHAR(120) NOT NULL,
    quantidade_estoque INT NOT NULL DEFAULT 0,
    valor_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estoque_minimo INT NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USO DE INSUMOS
-- ============================================================
CREATE TABLE uso_insumos (
    id_uso INT AUTO_INCREMENT PRIMARY KEY,
    quantidade_utilizada INT NOT NULL,
    data_uso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_leito INT NOT NULL,
    id_insumo INT NOT NULL,
    id_internacao INT NULL,

    INDEX idx_uso_insumos_leito (id_leito),
    INDEX idx_uso_insumos_insumo (id_insumo),
    INDEX idx_uso_insumos_internacao (id_internacao),

    CONSTRAINT fk_uso_insumos_leitos
        FOREIGN KEY (id_leito)
        REFERENCES leitos(id_leito)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_uso_insumos_insumos
        FOREIGN KEY (id_insumo)
        REFERENCES insumos(id_insumo)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_uso_insumos_internacoes
        FOREIGN KEY (id_internacao)
        REFERENCES internacoes(id_internacao)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FATURAMENTO
-- ============================================================
CREATE TABLE faturamento (
    id_faturamento INT AUTO_INCREMENT PRIMARY KEY,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    data_faturamento DATE NULL,
    status_pagamento VARCHAR(30) NOT NULL DEFAULT 'Pendente',
    observacoes TEXT NULL,
    id_paciente INT NOT NULL,
    id_consulta INT NULL,
    id_internacao INT NULL,

    INDEX idx_faturamento_paciente (id_paciente),
    INDEX idx_faturamento_consulta (id_consulta),
    INDEX idx_faturamento_internacao (id_internacao),
    INDEX idx_faturamento_status (status_pagamento),

    CONSTRAINT fk_faturamento_pacientes
        FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_faturamento_consultas
        FOREIGN KEY (id_consulta)
        REFERENCES consultas(id_consulta)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_faturamento_internacoes
        FOREIGN KEY (id_internacao)
        REFERENCES internacoes(id_internacao)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ITENS DO FATURAMENTO
-- ============================================================
CREATE TABLE itens_faturamento (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(180) NOT NULL,
    tipo_item VARCHAR(40) NOT NULL DEFAULT 'Outro',
    quantidade DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    valor_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    id_faturamento INT NOT NULL,

    INDEX idx_itens_faturamento (id_faturamento),

    CONSTRAINT fk_itens_faturamento
        FOREIGN KEY (id_faturamento)
        REFERENCES faturamento(id_faturamento)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDITORIA
-- ============================================================
CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    descricao TEXT NOT NULL,
    data_auditoria DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status_auditoria VARCHAR(30) NOT NULL DEFAULT 'Pendente',
    id_faturamento INT NULL,

    INDEX idx_auditoria_faturamento (id_faturamento),

    CONSTRAINT fk_auditoria_faturamento
        FOREIGN KEY (id_faturamento)
        REFERENCES faturamento(id_faturamento)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADOS INICIAIS PARA TESTE
-- Login inicial:
-- E-mail: admin@hospital.com
-- Senha: 123456
-- ============================================================

INSERT INTO usuarios (nome, email, senha, tipo_usuario, status_usuario) VALUES
('Administrador', 'admin@hospital.com', '$2y$12$9I3C0terQ2T76fS8I1vyTeppjChQbIZaBwr4Gmd8eqvGp86IKiLXS', 'Administrador', 'Ativo');

INSERT INTO convenios (nome_convenio, telefone, procedimentos_autorizados, status_convenio) VALUES
('Unimed', '(32) 3333-1111', 'Consultas, exames laboratoriais, internação clínica', 'Ativo'),
('SUS', '(32) 3333-2222', 'Atendimento público, triagem, consultas e exames autorizados', 'Ativo'),
('Bradesco Saúde', '(32) 3333-3333', 'Consultas, exames, internações e procedimentos ambulatoriais', 'Ativo');

INSERT INTO pacientes (
    nome,
    cpf,
    data_nascimento,
    telefone,
    endereco,
    alergias,
    historico_clinico,
    tipo_sanguineo,
    id_convenio,
    numero_carteirinha,
    validade_carteirinha,
    status_paciente
) VALUES
('Paciente Teste Ativo', '111.111.111-11', '1990-05-10', '(32) 99999-0001', 'Rua A, 100', 'Nenhuma alergia registrada.', 'Sem histórico relevante.', 'O+', 1, 'UNI123456', DATE_ADD(CURDATE(), INTERVAL 180 DAY), 'Ativo'),
('Paciente Carteirinha Vencida', '222.222.222-22', '1985-08-20', '(32) 99999-0002', 'Rua B, 200', 'Alergia a penicilina.', 'Histórico de hipertensão.', 'A+', 1, 'UNI654321', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Ativo'),
('Paciente Particular', '333.333.333-33', '2001-02-15', '(32) 99999-0003', 'Rua C, 300', 'Nenhuma alergia registrada.', 'Sem histórico relevante.', 'B+', NULL, NULL, NULL, 'Ativo');

INSERT INTO medicos (nome, crm, especialidade, telefone, email, status_medico) VALUES
('Dr. João Almeida', 'CRM-MG 12345', 'Clínica Médica', '(32) 99999-1111', 'joao.almeida@hospital.com', 'Ativo'),
('Dra. Mariana Souza', 'CRM-MG 67890', 'Cardiologia', '(32) 99999-2222', 'mariana.souza@hospital.com', 'Ativo'),
('Dra. Fernanda Lima', 'CRM-MG 54321', 'Pediatria', '(32) 99999-3333', 'fernanda.lima@hospital.com', 'Ativo');

INSERT INTO salas (numero_sala, tipo_sala, status_sala) VALUES
('101', 'Consultório', 'Disponível'),
('102', 'Consultório', 'Disponível'),
('201', 'Sala de Procedimentos', 'Disponível');

INSERT INTO leitos (numero_leito, ala, status_leito, tempo_higienizacao, inicio_higienizacao) VALUES
('L001', 'Clínica Médica', 'Disponível', 30, NULL),
('L002', 'Clínica Médica', 'Disponível', 30, NULL),
('L003', 'Pediatria', 'Disponível', 30, NULL),
('L004', 'Observação', 'Manutenção', 30, NULL);

INSERT INTO medicamentos (nome_medicamento, contraindicacoes, interacoes_medicamentosas, valor_unitario, quantidade_estoque) VALUES
('Paracetamol', 'Evitar em caso de alergia ao medicamento ou insuficiência hepática grave.', 'Pode interagir com álcool e anticoagulantes.', 5.00, 100),
('Dipirona', 'Evitar em caso de alergia a dipirona ou histórico de agranulocitose.', 'Pode potencializar efeitos de alguns anti-hipertensivos.', 4.50, 100),
('Amoxicilina', 'Contraindicada em caso de alergia a penicilinas.', 'Pode reduzir efeito de anticoncepcionais.', 18.00, 50),
('Ibuprofeno', 'Evitar em úlcera ativa, insuficiência renal grave ou alergia a anti-inflamatórios.', 'Pode interagir com anticoagulantes e anti-hipertensivos.', 9.00, 80);

INSERT INTO insumos (nome_insumo, quantidade_estoque, valor_unitario, estoque_minimo) VALUES
('Seringa 5ml', 200, 1.20, 20),
('Luva de Procedimento', 500, 0.80, 50),
('Soro Fisiológico 500ml', 100, 8.50, 10),
('Equipo Macrogotas', 150, 2.30, 20);

-- Consultas iniciais para visualização do dashboard e teste de triagem/prontuário.
INSERT INTO consultas (data_consulta, horario, status_consulta, destino_paciente, status_fluxo, id_paciente, id_medico, id_sala, id_convenio) VALUES
(CURDATE(), '08:30:00', 'Pendente', 'ATENDIMENTO', 'Aguardando atendimento', 1, 1, 1, 1),
(CURDATE(), '09:30:00', 'Pendente', 'ATENDIMENTO', 'Aguardando atendimento', 2, 2, 2, 1),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Agendada', 'ATENDIMENTO', 'Aguardando atendimento', 3, 3, 1, NULL);

INSERT INTO auditoria (descricao, status_auditoria, id_faturamento) VALUES
('Banco de dados inicial criado para o sistema hospitalar.', 'Log', NULL);

