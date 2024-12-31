<?php

defined('BASEPATH') OR exit('No direct script access allowed');
// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Translations extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
       // $this->load->model('Carriers_model');
    }
    
    public function get_get() {

        $data = [
    "about" => "Sobre",
    "account_1" => "Conta",
    "Account" => "Conta",
    "active" => "Ativo",
    "Active" => "Ativo",
    "add_item" => "Adicionar item",
    "address" => "Endereço",
    "admin_apis" => "Apis",
    "admin_config" => "Configuração",
    "admin_emails" => "Emails",
    "admin_languages" => "Idiomas",
    "admin_options" => "Opções",
    "admin_product_create" => "Criar produto",
    "admin_product_edit" => "Editar produto",
    "admin_product_list" => "Lista de produtos",
    "admin_user_create" => "Criar usuário",
    "admin_user_edit" => "Editar usuário",
    "admin_user_list" => "Lista de usuários",
    "admin" => "Administrador",
    "allow_registration" => "Permitir registro",
    "already_activated" => "A conta já está ativada.",
    "amount_max_required" => "Valor máximo é obrigatório",
    "amount_min_required" => "Valor mínimo é obrigatório",
    "amount" => "Valor",
    "annual" => "Anual",
    "apis" => "Apis",
    "app_name" => "Nome do aplicativo",
    "auth_throttle" => "Limitação de autenticação",
    "avatar_helper_text" => "Permitido *.jpeg, *.jpg, *.png, *.gif",
    "avatar_invalid" => "Avatar inválido",
    "avatar_max_size" => "Tamanho máximo de",
    "balance" => "Saldo",
    "banned" => "Banido",
    "seller_cycle_placeholder" => "Espaço reservado para ciclo de bônus",
    "seller_cycle" => "Ciclo do bônus",
    "seller_referral" => "Bônus de indicação",
    "seller_residual" => "Bônus residual",
    "seller_sharing" => "Bônus de compartilhamento",
    "seller_subheader" => "Configure e gerencie a estrutura de bônus do seu sistema",
    "seller_title" => "Gerenciamento de bônus",
    "seller" => "Bônus",
    "brl" => "BRL",
    "button_digital" => "Comprar agora",
    "button_investiment" => "Investir Agora",
    "button_physical" => "Comprar Agora",
    "button_buy" => "Comprar Agora",
    "button_withdraw" => "Sacar",
    "buy_button" => "Comprar agora",
    "buy_title" => "Comprar agora",
    "buy" => "Comprar",
    "cancel" => "Cancelar",
    "canceled" => "Cancelado",
    "category_required" => "Categoria é obrigatória",
    "category" => "Categoria",
    "city" => "Cidade",
    "code_expired" => "O código expirou.",
    "code_expires_in" => "O código expira em:",
    "code_min" => "O código deve ter pelo menos 6 caracteres.",
    "code_not_found" => "Código não encontrado.",
    "code_required" => "O código é obrigatório.",
    "code_sent" => "Código de verificação enviado.",
    "clients" => "Clientes",
    "pdv" => "PDV",
    "code" => "Código",
    "color" => "Cor",
    "coming_soon_days" => "Dias",
    "coming_soon_email_placeholder" => "Digite seu e-mail para ser notificado",
    "coming_soon_hours" => "Horas",
    "coming_soon_minutes" => "Minutos",
    "coming_soon_notify_button" => "Notificar-me",
    "coming_soon_seconds" => "Segundos",
    "coming_soon_subtitle" => "Estamos trabalhando duro para lançar este recurso.",
    "coming_soon_title" => "Em breve",
    "config_1" => "Configuração",
    "config_subheader" => "Modifique as configurações para personalizar o comportamento do seu aplicativo.",
    "config_title" => "Atualizar configuração do aplicativo",
    "config" => "Configuração",
    "confirm_email" => "Por favor, confirme seu endereço de e-mail.",
    "confirm_new_password" => "Confirme a nova senha",
    "confirm_password_placeholder" => "Confirme sua senha",
    "confirm_password_required" => "A confirmação da senha é obrigatória.",
    "confirm_password" => "Confirme a senha",
    "content" => "Conteúdo",
    "continuous" => "Contínuo",
    "country_invalid" => "País inválido",
    "country_placeholder" => "Insira seu país",
     "country" => "País",
            "cash" => "Caixas",
    "cpf_invalid" => "CPF inválido",
    "cpf" => "CPF",
    "create_new_key" => "Criar nova chave",
    "create_success" => "Criado com sucesso",
    "create" => "Criar",
    "credit" => "Crédito",
    "daily" => "Diário",
    "dashboard_1" => "Gerencie suas vendas de forma simples e eficiente.",
    "dashboard_10" => "Produtos em Estoque",
    "dashboard_11" => "Distribuição de receita",
    "dashboard_12" => "Acompanhe a origem das receitas",
    "dashboard_13" => "Desempenho do Negócio",
    "dashboard_14" => "Visualize o histórico anual de vendas e crescimento do seu PDV.",
    "dashboard_2" => "Oferecemos uma solução moderna e completa para o seu negócio, facilitando a gestão de vendas de produtos diversos com agilidade e praticidade!",
    "dashboard_3" => "Confira nossos planos",
    "dashboard_4" => "Meus planos",
    "dashboard_5" => "Você ainda não possui planos ativos.",
    "dashboard_6" => "Ative um novo plano agora!",
    "dashboard_7" => "Ver planos",
    "dashboard_8" => "Minhas Vendas",
    "dashboard_9" => "Total de Vendas",
    "dashboard" => "Painel",
    "day" => "Dia",
    "days" => "Dias",
    "delete_action" => "Excluir",
    "delete_button_content" => "Tem certeza de que deseja excluir isso?",
    "delete_button" => "Botão de exclusão",
    "delete_content" => "Tem certeza de que deseja excluir este item?",
    "delete_title" => "Confirmação de exclusão",
    "delete" => "Excluir",
    "deposit" => "Depósito",
    "description" => "Descrição",
    "details_subheader" => "Forneça informações detalhadas sobre o seu produto",
    "details_title" => "Detalhes do produto",
    "digital" => "Digital",
    "Drop files here or click tobrowsethrough your machine." => "Solte os arquivos aqui ou clique para navegar pelo seu dispositivo.",
    "Drop or select file" => "Solte ou selecione arquivo",
    "duration_required" => "Duração é obrigatória",
    "duration_type_required" => "Tipo de duração é obrigatório",
    "duration" => "Duração",
    "edit_success" => "Editado com sucesso",
    "edit" => "Editar",
    "email_already_exists" => "E-mail já existente",
    "email_confirmation" => "Confirmação de email",
    "email_invalid" => "O endereço de e-mail é inválido.",
    "email_placeholder" => "Digite seu endereço de e-mail",
    "email_required" => "O e-mail é obrigatório.",
    "email_verified" => "E-mail verificado com sucesso",
    "email" => "E-mail",
    "Email" => "E-mail",
    "emails" => "E-mails",
    "english" => "Inglês",
    "Enter phone number" => "Digite o número de telefone",
    "enter_new_password" => "Digite sua nova senha.",
    "error_403_go_home" => "Ir para início",
    "error_403_no_permission" => "Você não tem permissão para acessar esta página",
    "error_403_restricted_access" => "Acesso restrito",
    "error_500_button" => "Tentar novamente",
    "error_500_message" => "Ocorreu um erro inesperado no servidor.",
    "error_500_title" => "Erro do servidor",
    "error_not_found_button" => "Voltar para a página inicial",
    "error_not_found_description" => "A página que você está procurando não existe ou foi movida.",
    "error_not_found_title" => "Página não encontrada",
    "error_on_salve" => "Erro ao salvar alterações",
    "eur" => "EUR",
    "export" => "Exportar",
    "failed" => "Falhou",
    "fee_required" => "Taxa é obrigatória",
    "fee" => "Taxa",
    "Forgot Password" => "Esqueceu a senha",
    "forgot_1" => "Esqueceu sua senha?",
    "forgot_2" => "Não se preocupe! digite seu e-mail para redefinir sua senha.",
    "forgot_button" => "Enviar link de redefinição",
    "forgot_password" => "Esqueci a senha",
    "forgot" => "Esqueceu",
    "french" => "Francês",
    "general" => "Geral",
    "generate_pix_code" => "Gerar código pix",
    "generate_wallet" => "Gerar carteira",
    "goal" => "Meta",
    "google_recaptcha" => "Google recaptcha",
    "heading" => "Cabeçalho",
    "home" => "Início",
    "icon_dark" => "Ícone escuro",
    "icon_light" => "Ícone claro",
    "icon" => "Ícone",
    "id" => "Id",
    "image" => "Imagem",
    "images" => "Imagens",
    "import" => "Importar",
    "inactive" => "Inativo",
    "incorrect_credentials" => "Credenciais incorretas fornecidas.",
    "invalid_password" => "Senha inválida",
    "invalid_token" => "Token inválido",
    "investiment" => "Investimento",
    "product" => "Produto",
    "irr_note" => "Taxa de retorno",
    "irr_required" => "TIR é obrigatória",
    "irr_type_required" => "Tipo de tir é obrigatório",
    "irr" => "TIR",
    "key_required" => "Chave é obrigatória",
    "key" => "Chave",
    "label_price_required" => "Preço do rótulo é obrigatório",
    "label_price" => "Rótulo do preço",
    "label_required" => "Rótulo é obrigatório",
    "label" => "Rótulo",
    "languages_1" => "Idiomas",
    "languages_2" => "Criar novo",
    "languages_3" => "Idioma 3",
    "languages_4" => "Idioma 4",
    "languages_5" => "Idioma 5",
    "languages_6" => "Idioma 6",
    "languages_7" => "Idioma 7",
    "languages_th_0" => "Id",
    "languages_th_1" => "En",
    "languages_th_2" => "Fr",
    "languages_th_3" => "Es",
    "languages_th_4" => "Pt",
    "languages_th_5" => "",
    "languages" => "Idiomas",
    "last" => "Últimos",
    "level" => "Nível",
    "link_1" => "Sua rede de contatos",
    "link_2" => "Aqui você pode visualizar e acompanhar suas conexões, incluindo indicações e participações em projetos. mantenha-se atualizado com as indicações que você fez ou recebeu, e acompanhe o impacto de suas interações em sua rede.",
    "link_3" => "Link de indicação:",
    "link" => "Link",
    "list_required" => "Lista é obrigatória",
    "lockout_duration_required" => "Duração do bloqueio é obrigatória",
    "lockout_duration" => "Duração do bloqueio",
    "login_success" => "Usuário conectado com sucesso",
    "logo_dark" => "Logo escuro",
    "logo_light" => "Logo claro",
    "mail_forgot_button" => "Redefinir senha",
    "mail_forgot_content" => "Clique no botão abaixo para redefinir sua senha e recuperar o acesso à sua conta.",
    "mail_forgot_footer" => "Se você não solicitou a redefinição de senha, ignore este e-mail.",
    "mail_forgot_subtitle" => "Esqueceu sua senha?",
    "mail_forgot_title" => "Redefina sua senha",
    "mail_resend_button" => "Reenviar código",
    "mail_resend_content" => "Parece que você não recebeu o código de verificação. clique no botão abaixo para solicitar um novo código.",
    "mail_resend_footer" => "Se você não solicitou isso, ignore este e-mail.",
    "mail_resend_subtitle" => "Não recebeu o código?",
    "mail_resend_title" => "Reenviar código de verificação",
    "mail_signup_button" => "Ative sua conta",
    "mail_signup_content" => "Estamos animados em tê-lo conosco. clique no botão abaixo para ativar sua conta e começar.",
    "mail_signup_footer" => "Se você não se cadastrou para esta conta, desconsidere este e-mail.",
    "mail_signup_subtitle" => "Obrigado por se cadastrar.",
    "mail_signup_title" => "Bem-vindo à nossa plataforma!",
    "maintenance_button_home" => "Ir para início",
    "maintenance_message_description" => "Estamos realizando uma manutenção programada. por favor, volte mais tarde.",
    "maintenance_message_title" => "Modo de manutenção",        
     "manager" => "Gerente",
    "max_active_sessions_required" => "Número máximo de sessões ativas é obrigatório",
    "max_active_sessions" => "Máximo de sessões ativas",
    "max_login_attempts_required" => "Número máximo de tentativas de login é obrigatório",
    "max_login_attempts" => "Máximo de tentativas de login",
    "max_value_required" => "Valor máximo é obrigatório",
    "max_value" => "Valor máximo",
    "max" => "Max",
    "min_value_required" => "Valor mínimo é obrigatório",
    "min_value" => "Valor mínimo",
    "min" => "Min",
    "month" => "Mês",
    "monthly" => "Mensal",
    "my_account" => "Minha conta",
    "name_required" => "Nome é obrigatório",
    "name" => "Nome",
    "Name" => "Nome",
    "network_level" => "Nível da rede",
    "new_password" => "Nova senha",
    "old_password" => "Senha antiga",
    "option" => "Opção",
    "options_1" => "Opções",
    "options_2" => "Criar novo",
    "options_3" => "Opção 3",
    "options_4" => "Opção 4",
    "options_5" => "Opção 5",
    "options_6" => "Opção 6",
    "options_7" => "Opção 7",
    "options_th_0" => "Id",
    "options_th_1" => "Categoria",
    "options_th_2" => "Tipo",
    "options_th_3" => "Descrição",
    "options_th_4" => "Criado em",
    "options_th_5" => "Atualizado em",
    "options_th_6" => "",
    "options" => "Opções",
    "password_button" => "Alterar senha",
    "password_confirm_password_required" => "Confirmar a senha é obrigatório",
    "password_confirm_required" => "Confirmação de senha é obrigatória",
    "password_different" => "A nova senha deve ser diferente da antiga",
    "password_lowercase" => "Pelo menos uma letra minúscula",
    "password_min_length" => "Comprimento mínimo da senha",
    "password_min" => "No mínimo 8 caracteres",
    "password_number" => "Pelo menos um número",
    "password_placeholder" => "Digite sua senha",
    "password_required" => "Senha é obrigatória",
    "password_reset" => "Senha redefinida com sucesso",
    "password_special" => "Pelo menos um caractere especial ($, @, !, %, *, ?, &)",
    "password_uppercase" => "Pelo menos uma letra maiúscula",
    "password_validation_length" => "No mínimo 8 caracteres",
    "password_validation_lowercase" => "Pelo menos uma letra minúscula",
    "password_validation_number" => "Pelo menos um número",
    "password_validation_special" => "Pelo menos um caractere especial ($, @, !, %, *, ?, &)",
    "password_validation_uppercase" => "Pelo menos uma letra maiúscula",
    "password" => "Senha",
    "passwords_match" => "As senhas devem coincidir.",
    "path" => "Caminho",
    "pending" => "Pendente",
    "phone_invalid" => "Número de telefone inválido",
    "phone" => "Telefone",
    "physical" => "Físico",
    "portuguese" => "Português",
    "prduct_create_1" => "Criar novo produto",
    "prduct_edit_1" => "Editar produto",
    "price_required" => "Preço é obrigatório",
    "price" => "Preço",
    "print" => "Imprimir",
    "product_1" => "Produtos",
    "product_2" => "Criar um novo produto",
    "product_3" => "Produto 3",
    "product_4" => "Produto 4",
    "product_5" => "Produto 5",
    "product_6" => "Produto 6",
    "product_7" => "Produto 7",
    "product_create_1" => "Criação de produto",
    "product_th_0" => "Imagem",
    "product_th_1" => "Nome",
    "product_th_10" => "",
    "product_th_2" => "Rótulo",
    "product_th_3" => "Tipo",
    "product_th_4" => "Categoria",
     "product_th_5" => "Quantidade",
    "product_th_6" => "TIR",
    "product_th_7" => "Status",
    "product_th_8" => "Criado em",
    "product_th_9" => "Atualizado em",
    "credit_card" => "Cartão de Crédito",
    "products_1" => "Catálogo de Produtos",
    "products_2" => "Encontre o produto ideal para você e adquira agora mesmo!",
    "products_3" => "Nenhum produto disponível no momento.",
    "products" => "Catálogo",
    "profile_updated" => "Perfil atualizado com sucesso",
    "properties_subheader" => "Defina os principais atributos e configurações do seu produto",
    "properties_title" => "Propriedades do produto",
    "publish" => "Publicar",
    "quick_edit" => "Edição rápida",
    "quick_update" => "Atualização rápida",
    "referrer_not_found" => "Referente não encontrado",
    "referrer_required" => "Referente é obrigatório",
    "register_success" => "Usuário registrado com sucesso",
    "rejected" => "Rejeitado",
    "remember_me" => "Lembrar-me",
    "resend_1" => "Não recebeu o código?",
    "resend_2" => "Reenviar código de verificação.",
    "reset_token_lifetime_required" => "Tempo de vida do token de redefinição é obrigatório",
    "reset_token_lifetime" => "Tempo de vida do token de redefinição",
    "return_1" => "Voltar à página anterior.",
    "role_required" => "Função é obrigatória",
    "role" => "Função",
    "sale_price_required" => "Preço de venda é obrigatório",
    "sale_price" => "Preço de venda",
    "save_changes" => "Salvar alterações",
    "save" => "Salvar",
    "search" => "Buscar",
    "security" => "Segurança",
    "session_expired" => "A sessão expirou.",
    "session_not_found" => "Sessão não encontrada.",
    "session_valid" => "A sessão é válida.",
    "settings_edit_success" => "Configurações atualizadas com sucesso",
    "settings_languages_delete_button" => "Excluir",
    "settings_languages_delete_content" => "Tem certeza de que deseja excluir este idioma?",
    "settings_languages_delete_title" => "Confirmação de exclusão de idioma",
    "settings_languages_delete_tooltip" => "Excluir este idioma",
    "settings_options_delete_content" => "Tem certeza de que deseja excluir esta opção?",
    "settings_options_delete_title" => "Confirmação de exclusão de opção",
    "settings_options_delete" => "Excluir",
    "shipper_link_required" => "Link do remetente é obrigatório",
    "shipper_link" => "Link do remetente",
    "shipper_zip_required" => "Cep do remetente é obrigatório",
    "shipper_zip" => "Cep do remetente",
    "signin_1" => "Bem-vindo!",
    "signin_2" => "Por favor, faça login na sua conta.",
    "signin_3" => "Não tem uma conta?",
    "signin_4" => "Cadastre-se aqui.",
    "signin_button" => "Entrar",
    "signin" => "Entrar",
    "signup_1" => "Junte-se a nós!",
    "signup_2" => "Crie uma nova conta.",
    "signup_3" => "Já tem uma conta?",
    "signup_4" => "Faça login aqui.",
    "signup_button" => "Cadastrar-se",
    "signup_success" => "Cadastro realizado com sucesso.",
    "signup" => "Cadastrar",
    "spanish" => "Espanhol",
    "state" => "Estado",
    "status_required" => "Status é obrigatório",
    "status" => "Status",
    "success_message" => "Operação concluída com sucesso",
    "success" => "Operação concluída com sucesso.",
    "tab" => "Aba",
    "terms_1" => "Ao se cadastrar, você concorda com nossos termos e condições.",
    "terms_conditions" => "Termos e condições",
    "title_required" => "Título é obrigatório",
    "title" => "Título",
    "token_expired" => "Token expirado",
    "total" => "Total",
    "transactions_1" => "Transações da conta",
    "transactions_2" => "Assinar plano",
    "transactions_3" => "Nenhuma transação no momento.",
     "transactions_4" => "Transações 4",
    "transactions_5" => "Transações 5",
    "transactions_6" => "Transações 6",
    "transactions_7" => "Transações 7",
    "transactions_th_0" => "Id",
    "transactions_th_1" => "Operação",
    "transactions_th_2" => "Tipo",
    "transactions_th_3" => "Moeda",
    "transactions_th_4" => "Quantia",
    "transactions_th_5" => "Status",
    "transactions_th_6" => "Criado em",
    "transactions_th_7" => "Atualizado em",
    "transactions" => "Transações",
    "two_factor_auth" => "Autenticação em duas etapas",
    "type_required" => "Type_required",
    "type" => "Tipo",
    "update_success" => "Atualizado com sucesso",
    "update" => "Atualizar",
    "boleto" => "Boleto",
    "Upload photo" => "Carregar foto",
    "usd" => "USD",
    "client_1" => "Clientes",
    "client_2" => "Criar um novo cliente",
    "client_3" => "Cliente 3",
    "client_4" => "Cliente 4",
    "client_5" => "Cliente 5",
    "client_6" => "Cliente 6",
    "client_7" => "Cliente 7",
    "client_blocked" => "O cliente está bloqueado.",
    "client_list_edit" => "Editar lista de clientes",
    "client_list_quick_edit" => "Edição rápida da lista de clientes",
    "client_not_found" => "Cliente não encontrado",
    "client_registered" => "Cliente registrado com sucesso.",
    "client_th_0" => "Cliente",
    "client_th_1" => "Telefone",
    "client_th_2" => "Função",
    "client_th_3" => "Status",
    "client_th_4" => "Criado em",
    "client_th_5" => "Atualizado em",
    "client_th_6" => "",
    "user_1" => "Usuários",
    "user_2" => "Criar um novo usuário",
    "user_3" => "Usuário 3",
    "user_4" => "Usuário 4",
    "user_5" => "Usuário 5",
    "user_6" => "Usuário 6",
    "user_7" => "Usuário 7",
    "user_blocked" => "O usuário está bloqueado.",
    "user_list_edit" => "Editar lista de usuários",
    "user_list_quick_edit" => "Edição rápida da lista de usuários",
    "user_not_found" => "Usuário não encontrado",
    "user_registered" => "Usuário registrado com sucesso.",
    "user_th_0" => "Usuário",
    "user_th_1" => "Telefone",
    "user_th_2" => "Função",
    "user_th_3" => "Status",
    "user_th_4" => "Criado em",
    "user_th_5" => "Atualizado em",
    "user_th_6" => "",
    "user" => "Usuário",
    "username_invalid" => "O nome de usuário é inválido.",
    "username_placeholder" => "Digite seu nome de usuário",
    "username_required" => "O nome de usuário é obrigatório.",
    "username" => "Nome de usuário",
    "users" => "Usuários",
    "value" => "Valor",
    "verify_1" => "Verifique seu e-mail",
    "verify_2" => "Por favor, verifique seu e-mail para o código de verificação.",
    "verify_button" => "Verificar",
    "verify" => "Verificar",
    "wait" => "Por favor, aguarde.",
    "wallet_expires_in" => "A carteira expira em:",
    "week" => "Semana",
    "width" => "Largura",
    "year" => "Ano",
    "zipcode" => "Cep"
];   
        

          $this->response($data, REST_Controller::HTTP_OK);
       
    }

    /**
     * @api {get} api/customers/:id Request customer information
     * @apiName GetCustomer
     * @apiGroup Customer
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id customer unique ID.
     *
     * @apiSuccess {Object} customer information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "id": "28",
     *          "name": "Test1",
     *          "description": null,
     *          "status": "1",
     *          "clientid": "11",
     *          "billing_type": "3",
     *          "start_date": "2019-04-19",
     *          "deadline": "2019-08-30",
     *          "customer_created": "2019-07-16",
     *          "date_finished": null,
     *          "progress": "0",
     *          "progress_from_tasks": "1",
     *          "customer_cost": "0.00",
     *          "customer_rate_per_hour": "0.00",
     *          "estimated_hours": "0.00",
     *          "addedfrom": "5",
     *          "rel_type": "customer",
     *          "potential_revenue": "0.00",
     *          "potential_margin": "0.00",
     *          "external": "E",
     *         ...
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_get($id = '') {



        $page = $this->get('page') ? (int) $this->get('page') : 1; // Página atual, padrão 1
        $limit = $this->get('limit') ? (int) $this->get('limit') : 10; // Itens por página, padrão 10
        $search = $this->get('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->get('sortField') ?: 'id'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente


        $data = $this->Carriers_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder);

        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    /**
     * @api {post} api/customers Add New Customer
     * @apiName PostCustomer
     * @apiGroup Customer
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} company               Mandatory Customer company.
     * @apiParam {String} [vat]                 Optional Vat.
     * @apiParam {String} [phonenumber]         Optional Customer Phone.
     * @apiParam {String} [website]             Optional Customer Website.
     * @apiParam {Number[]} [groups_in]         Optional Customer groups.
     * @apiParam {String} [default_language]    Optional Customer Default Language.
     * @apiParam {String} [default_currency]    Optional default currency.
     * @apiParam {String} [address]             Optional Customer address.
     * @apiParam {String} [city]                Optional Customer City.
     * @apiParam {String} [state]               Optional Customer state.
     * @apiParam {String} [zip]                 Optional Zip Code.
     * @apiParam {String} [partnership_type]    Optional Customer partnership type.
     * @apiParam {String} [country]             Optional country.
     * @apiParam {String} [billing_street]      Optional Billing Address: Street.
     * @apiParam {String} [billing_city]        Optional Billing Address: City.
     * @apiParam {Number} [billing_state]       Optional Billing Address: State.
     * @apiParam {String} [billing_zip]         Optional Billing Address: Zip.
     * @apiParam {String} [billing_country]     Optional Billing Address: Country.
     * @apiParam {String} [shipping_street]     Optional Shipping Address: Street.
     * @apiParam {String} [shipping_city]       Optional Shipping Address: City.
     * @apiParam {String} [shipping_state]      Optional Shipping Address: State.
     * @apiParam {String} [shipping_zip]        Optional Shipping Address: Zip.
     * @apiParam {String} [shipping_country]    Optional Shipping Address: Country.
     *
     * @apiParamExample {Multipart Form} Request-Example:
     *   array (size=22)
     *     'company' => string 'Themesic Interactive' (length=38)
     *     'vat' => string '123456789' (length=9)
     *     'phonenumber' => string '123456789' (length=9)
     *     'website' => string 'AAA.com' (length=7)
     *     'groups_in' =>
     *       array (size=2)
     *         0 => string '1' (length=1)
     *         1 => string '4' (length=1)
     *     'default_currency' => string '3' (length=1)
     *     'default_language' => string 'english' (length=7)
     *     'address' => string '1a The Alexander Suite Silk Point' (length=27)
     *     'city' => string 'London' (length=14)
     *     'state' => string 'London' (length=14)
     *     'zip' => string '700000' (length=6)
     *     'country' => string '243' (length=3)
     *     'billing_street' => string '1a The Alexander Suite Silk Point' (length=27)
     *     'billing_city' => string 'London' (length=14)
     *     'billing_state' => string 'London' (length=14)
     *     'billing_zip' => string '700000' (length=6)
     *     'billing_country' => string '243' (length=3)
     *     'shipping_street' => string '1a The Alexander Suite Silk Point' (length=27)
     *     'shipping_city' => string 'London' (length=14)
     *     'shipping_state' => string 'London' (length=14)
     *     'shipping_zip' => string '700000' (length=6)
     *     'shipping_country' => string '243' (length=3)
     *
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Customer add successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Customer add successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Customer add fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Customer add fail."
     *     }
     *
     */
    public function data_post() {

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
     

        $this->load->model('Carriers_model');
        $this->form_validation->set_rules('nome', 'nome', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
        if ($this->form_validation->run() == FALSE) {
            // form validation error
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {

            $output = $this->Carriers_model->add($_POST);
        
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Carrier add successful.', 'data'=>$output);
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $this->response('Error', REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
    }

    /**
     * @api {delete} api/delete/customers/:id Delete a Customer
     * @apiName DeleteCustomer
     * @apiGroup Customer
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Customer unique ID.
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Customer Delete Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Customer Delete Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Customer Delete Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Customer Delete Fail."
     *     }
     */
    public function data_delete($id = '') {

        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Address ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $this->load->model('Carriers_model');
            $output = $this->Carriers_model->delete($id);
            if ($output === TRUE) {
                // success
                $message = array('status' => TRUE, 'message' => 'Carrier Delete Successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Carrier Delete Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/customers/:id Update a Customer
     * @apiName PutCustomer
     * @apiGroup Customer
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} company               Mandatory Customer company.
     * @apiParam {String} [vat]                 Optional Vat.
     * @apiParam {String} [phonenumber]         Optional Customer Phone.
     * @apiParam {String} [website]             Optional Customer Website.
     * @apiParam {Number[]} [groups_in]         Optional Customer groups.
     * @apiParam {String} [default_language]    Optional Customer Default Language.
     * @apiParam {String} [default_currency]    Optional default currency.
     * @apiParam {String} [address]             Optional Customer address.
     * @apiParam {String} [city]                Optional Customer City.
     * @apiParam {String} [state]               Optional Customer state.
     * @apiParam {String} [zip]                 Optional Zip Code.
     * @apiParam {String} [country]             Optional country.
     * @apiParam {String} [billing_street]      Optional Billing Address: Street.
     * @apiParam {String} [billing_city]        Optional Billing Address: City.
     * @apiParam {Number} [billing_state]       Optional Billing Address: State.
     * @apiParam {String} [billing_zip]         Optional Billing Address: Zip.
     * @apiParam {String} [billing_country]     Optional Billing Address: Country.
     * @apiParam {String} [shipping_street]     Optional Shipping Address: Street.
     * @apiParam {String} [shipping_city]       Optional Shipping Address: City.
     * @apiParam {String} [shipping_state]      Optional Shipping Address: State.
     * @apiParam {String} [shipping_zip]        Optional Shipping Address: Zip.
     * @apiParam {String} [shipping_country]    Optional Shipping Address: Country.
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *     "company": "Công ty A",
     *     "vat": "",
     *     "phonenumber": "0123456789",
     *     "website": "",
     *     "default_language": "",
     *     "default_currency": "0",
     *     "country": "243",
     *     "city": "TP London",
     *     "zip": "700000",
     *     "state": "Quận 12",
     *     "address": "hẻm 71, số 34\/3 Đường TA 16, Phường Thới An, Quận 12",
     *     "billing_street": "hẻm 71, số 34\/3 Đường TA 16, Phường Thới An, Quận 12",
     *     "billing_city": "TP London",
     *     "billing_state": "Quận 12",
     *     "billing_zip": "700000",
     *     "billing_country": "243",
     *     "shipping_street": "",
     *     "shipping_city": "",
     *     "shipping_state": "",
     *     "shipping_zip": "",
     *     "shipping_country": "0"
     *   }
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Customer Update Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Customer Update Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Customer Update Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Customer Update Fail."
     *     }
     */
    public function data_put($id = '') {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);

        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Customers ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            // update data
            $this->load->model('Carriers_model');
            $output = $this->Carriers_model->update($update_data, $id);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data'=>$this->Carriers_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Customers Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }
}
