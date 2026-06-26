<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$paginaAtual = basename($_SERVER['PHP_SELF']);

function menuAtivo($arquivo, $paginaAtual) {
    return $arquivo === $paginaAtual ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Hospitalar - Gestão Completa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #1f3a5f;
            --secondary-color: #27496d;
            --accent-color: #0d6efd;
            --bg-light: #f4f7fb;
            --text-dark: #1f2937;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }

        .navbar {
            background: linear-gradient(135deg, #1f3a5f, #27496d);
            min-height: 64px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .sidebar {
            background: linear-gradient(180deg, #1f3a5f, #20364f);
            min-height: calc(100vh - 64px);
            color: white;
            box-shadow: 4px 0 18px rgba(0, 0, 0, 0.08);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.82);
            padding: 12px 20px;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #ffffff;
            background-color: rgba(255,255,255,0.14);
            border-left: 4px solid #74b9ff;
        }

        .sidebar .nav-heading {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08rem;
            padding: 18px 20px 8px;
            color: rgba(255,255,255,0.48);
            font-weight: 700;
        }

        main {
            background-color: var(--bg-light);
            min-height: calc(100vh - 64px);
        }

        h1, h2, h3, h4, h5 {
            color: #111827;
            font-weight: 700;
        }

        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            margin-bottom: 22px;
            overflow: hidden;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            font-weight: 700;
            color: var(--primary-color);
            padding: 16px 18px;
        }

        .btn {
            border-radius: 9px;
            font-weight: 500;
        }

        .table {
            background-color: #ffffff;
        }

        .table thead th {
            font-size: 0.86rem;
            text-transform: uppercase;
            color: #475569;
            background-color: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .badge {
            padding: 7px 10px;
            border-radius: 999px;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .footer {
            font-size: 0.86rem;
        }

        @media print {
            .navbar,
            .sidebar,
            .btn-toolbar,
            .btn,
            .modal,
            .footer {
                display: none !important;
            }

            main {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-hospital-user me-2"></i>Sistema Hospitalar
        </a>

        <div class="ms-auto">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a 
                        class="nav-link dropdown-toggle" 
                        href="#" 
                        id="userDropdown" 
                        role="button" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                    >
                        <i class="fas fa-user-md me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? 'Administrador'); ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Sair
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse p-0">
            <div class="position-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('index.php', $paginaAtual); ?>" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>

                    <div class="nav-heading">Atendimento</div>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('pacientes.php', $paginaAtual); ?>" href="pacientes.php">
                            <i class="fas fa-user-injured me-2"></i> Pacientes
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('consultas.php', $paginaAtual); ?>" href="consultas.php">
                            <i class="fas fa-calendar-check me-2"></i> Consultas & Triagem
                        </a>
                    </li>

                    <div class="nav-heading">Clínico</div>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('medicos.php', $paginaAtual); ?>" href="medicos.php">
                            <i class="fas fa-user-md me-2"></i> Médicos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('medicamentos.php', $paginaAtual); ?>" href="medicamentos.php">
                            <i class="fas fa-pills me-2"></i> Medicamentos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('prontuario.php', $paginaAtual); ?>" href="pacientes.php">
                            <i class="fas fa-file-medical me-2"></i> Prontuários
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('exames.php', $paginaAtual); ?>" href="exames.php">
                            <i class="fas fa-vial me-2"></i> Exames
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('prescricoes.php', $paginaAtual); ?>" href="prescricoes.php">
                            <i class="fas fa-prescription-bottle-alt me-2"></i> Prescrições
                        </a>
                    </li>

                    <div class="nav-heading">Operacional</div>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('leitos.php', $paginaAtual); ?>" href="leitos.php">
                            <i class="fas fa-bed me-2"></i> Gestão de Leitos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('internacoes.php', $paginaAtual); ?>" href="internacoes.php">
                            <i class="fas fa-procedures me-2"></i> Internações
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('estoque.php', $paginaAtual); ?>" href="estoque.php">
                            <i class="fas fa-boxes me-2"></i> Estoque Insumos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('uso_insumos.php', $paginaAtual); ?>" href="uso_insumos.php">
                            <i class="fas fa-clipboard-list me-2"></i> Uso de Insumos
                        </a>
                    </li>

                    <div class="nav-heading">Administrativo</div>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('convenios.php', $paginaAtual); ?>" href="convenios.php">
                            <i class="fas fa-id-card me-2"></i> Convênios
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('salas.php', $paginaAtual); ?>" href="salas.php">
                            <i class="fas fa-door-open me-2"></i> Salas
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('faturamento.php', $paginaAtual); ?>" href="faturamento.php">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Faturamento
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('auditoria.php', $paginaAtual); ?>" href="auditoria.php">
                            <i class="fas fa-history me-2"></i> Auditoria
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo menuAtivo('usuarios.php', $paginaAtual); ?>" href="usuarios.php">
                            <i class="fas fa-users-cog me-2"></i> Usuários
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">