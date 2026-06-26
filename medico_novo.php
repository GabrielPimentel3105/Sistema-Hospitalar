<?php
if (file_exists(__DIR__ . '/config/config.php')) {
    if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}
}

require_once __DIR__ . '/views/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Cadastrar Novo Médico</h1>

    <a href="medicos.php" class="btn btn-sm btn-outline-secondary">
        Voltar
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="processar_medico.php" method="POST">
                    <input type="hidden" name="acao" value="cadastrar">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nome Completo</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="nome" 
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CRM</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="crm" 
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Especialidade</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="especialidade" 
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="telefone"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>

                            <input 
                                type="email" 
                                class="form-control" 
                                name="email"
                            >
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary w-100">
                                Finalizar Cadastro
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>