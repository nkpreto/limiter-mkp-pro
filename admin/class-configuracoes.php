<?php
/**
 * Classe responsável pelas configurações do plugin.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin
 */
class Limiter_MKP_Pro_Configuracoes {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function get_configuracoes() {
        $configuracoes = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        
        // Define valores padrão
        $defaults = self::get_defaults();

        return wp_parse_args($configuracoes, $defaults);
    }

    public function save_configuracoes($data) {
        // 1. Recupera as configurações atuais do banco para evitar perda de dados (Overwrite vs Merge)
        $current_config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        
        // Se não for array (primeira instalação), inicializa vazio
        if (!is_array($current_config)) {
            $current_config = array();
        }

        // 2. Processa a Blacklist
        $blacklist = array();
        if (isset($data['subdomain_blacklist']) && is_array($data['subdomain_blacklist'])) {
            foreach ($data['subdomain_blacklist'] as $word) {
                $word = sanitize_text_field(trim($word));
                if (!empty($word)) {
                    $blacklist[] = $word;
                }
            }
        } else {
            // Mantém a blacklist existente se não foi fornecida no $data
            $blacklist = isset($current_config['subdomain_blacklist']) ? $current_config['subdomain_blacklist'] : self::get_subdomain_blacklist();
        }

        // 3. Prepara os novos dados, usando o operador de coalescência nula (??) 
        // para manter o valor atual caso o campo não venha no $data.
        $novos_dados = array(
            'email_notificacao' => isset($data['email_notificacao']) ? sanitize_email($data['email_notificacao']) : ($current_config['email_notificacao'] ?? ''),
            'limite_alerta' => isset($data['limite_alerta']) ? intval($data['limite_alerta']) : ($current_config['limite_alerta'] ?? 1),
            'mensagem_limite' => isset($data['mensagem_limite']) ? sanitize_textarea_field($data['mensagem_limite']) : ($current_config['mensagem_limite'] ?? ''),
            'mensagem_alerta' => isset($data['mensagem_alerta']) ? sanitize_textarea_field($data['mensagem_alerta']) : ($current_config['mensagem_alerta'] ?? ''),
            'planos_url_global' => isset($data['planos_url_global']) ? esc_url_raw($data['planos_url_global']) : ($current_config['planos_url_global'] ?? ''),
            'planos_url_jp' => isset($data['planos_url_jp']) ? esc_url_raw($data['planos_url_jp']) : ($current_config['planos_url_jp'] ?? ''),
            'sufixo_subdominio' => isset($data['sufixo_subdominio']) ? sanitize_text_field($data['sufixo_subdominio']) : ($current_config['sufixo_subdominio'] ?? '-mkp'),
            'subdomain_blacklist' => $blacklist,
            'nome_sistema' => isset($data['nome_sistema']) ? sanitize_text_field($data['nome_sistema']) : ($current_config['nome_sistema'] ?? ''),
            'widget_alerta_limite' => isset($data['widget_alerta_limite']) ? sanitize_textarea_field($data['widget_alerta_limite']) : ($current_config['widget_alerta_limite'] ?? ''),
            'widget_sem_plano' => isset($data['widget_sem_plano']) ? sanitize_textarea_field($data['widget_sem_plano']) : ($current_config['widget_sem_plano'] ?? ''),
            'alerta_limite_arquivos_80' => isset($data['alerta_limite_arquivos_80']) ? sanitize_textarea_field($data['alerta_limite_arquivos_80']) : ($current_config['alerta_limite_arquivos_80'] ?? ''),
            'alerta_limite_arquivos_100' => isset($data['alerta_limite_arquivos_100']) ? sanitize_textarea_field($data['alerta_limite_arquivos_100']) : ($current_config['alerta_limite_arquivos_100'] ?? ''),
            'subdominio_disponivel' => isset($data['subdominio_disponivel']) ? sanitize_text_field($data['subdominio_disponivel']) : ($current_config['subdominio_disponivel'] ?? ''),
            'subdominio_indisponivel' => isset($data['subdominio_indisponivel']) ? sanitize_text_field($data['subdominio_indisponivel']) : ($current_config['subdominio_indisponivel'] ?? ''),
            'subdominio_curto' => isset($data['subdominio_curto']) ? sanitize_text_field($data['subdominio_curto']) : ($current_config['subdominio_curto'] ?? ''),
            'registro_concluido' => isset($data['registro_concluido']) ? sanitize_text_field($data['registro_concluido']) : ($current_config['registro_concluido'] ?? ''),
            'email_solicitacao_titulo' => isset($data['email_solicitacao_titulo']) ? sanitize_text_field($data['email_solicitacao_titulo']) : ($current_config['email_solicitacao_titulo'] ?? ''),
            'email_solicitacao_corpo' => isset($data['email_solicitacao_corpo']) ? sanitize_textarea_field($data['email_solicitacao_corpo']) : ($current_config['email_solicitacao_corpo'] ?? ''),
            'email_confirmacao_aprovada_titulo' => isset($data['email_confirmacao_aprovada_titulo']) ? sanitize_text_field($data['email_confirmacao_aprovada_titulo']) : ($current_config['email_confirmacao_aprovada_titulo'] ?? ''),
            'email_confirmacao_aprovada_corpo' => isset($data['email_confirmacao_aprovada_corpo']) ? sanitize_textarea_field($data['email_confirmacao_aprovada_corpo']) : ($current_config['email_confirmacao_aprovada_corpo'] ?? ''),
            'email_configuracao_subdominio_titulo' => isset($data['email_configuracao_subdominio_titulo']) ? sanitize_text_field($data['email_configuracao_subdominio_titulo']) : ($current_config['email_configuracao_subdominio_titulo'] ?? ''),
            'email_configuracao_subdominio_corpo' => isset($data['email_configuracao_subdominio_corpo']) ? sanitize_textarea_field($data['email_configuracao_subdominio_corpo']) : ($current_config['email_configuracao_subdominio_corpo'] ?? ''),
            'botao_ver_planos' => isset($data['botao_ver_planos']) ? sanitize_text_field($data['botao_ver_planos']) : ($current_config['botao_ver_planos'] ?? ''),
            'botao_acessar_loja' => isset($data['botao_acessar_loja']) ? sanitize_text_field($data['botao_acessar_loja']) : ($current_config['botao_acessar_loja'] ?? '')
        );

        // 4. Merge final: Garante que chaves extras (não presentes no form) sejam preservadas
        $config_final = array_merge($current_config, $novos_dados);

        return update_network_option(null, 'limiter_mkp_pro_configuracoes', $config_final);
    }

    /**
     * Obtém uma mensagem específica da configuração
     */
    public static function get_mensagem($chave) {
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $defaults = self::get_defaults();
        return isset($config[$chave]) ? $config[$chave] : (isset($defaults[$chave]) ? $defaults[$chave] : '');
    }

    /**
     * Define valores padrão para todas as mensagens
     */
    private static function get_defaults() {
        $default_blacklist = array(
            'admin', 'suporte', 'root', 'api', 'www', 'mail', 'ftp', 'loja-oficial', 
            'login', 'wp', 'pagamento', 'cpanel', 'system', 'support', 'host', 'server',
            'webmail', 'backup', 'secure', 'ssl', 'cdn', 'test', 'demo'
        );

        return array(
            'email_notificacao' => 'alterar-plano@marketing-place.store',
            'limite_alerta' => 1,
            'mensagem_limite' => 'Desculpe o inconveniente, mas seu plano tem suporte a criação de [X] páginas. Considere fazer upgrade.',
            'mensagem_alerta' => 'Você está na penúltima página. Considere fazer upgrade.',
            'planos_url_global' => home_url('/planos/'),
            'planos_url_jp' => home_url('/planos-jp/'),
            'sufixo_subdominio' => '-mkp',
            'subdomain_blacklist' => $default_blacklist,
            'nome_sistema' => 'Marketing Place Store',
            'widget_titulo' => 'Limiter MKP Pro - Status do Plano',
            'widget_sem_plano' => 'Este subdomínio não possui um plano configurado. Entre em contato com o administrador da rede.',
            'widget_alerta_limite' => "⚠️ Limite Próximo\nVocê está usando [PERCENTUAL]% do seu limite. Considere fazer upgrade para continuar criando conteúdo.",
            'widget_sistema_automatico' => "Sistema Automático via WooCommerce\nPara mudar de plano, acesse nossa loja WooCommerce.\nApós o pagamento, a mudança é automática.",
            'alerta_limite_arquivos_80' => '⚠️ Aviso: 80% do limite de arquivos usado ([ARQUIVOS_USADOS]/[ARQUIVOS_LIMITE]) — Plano [NOME_PLANO].',
            'alerta_limite_arquivos_100' => "🚫 Limite de arquivos atingido: [ARQUIVOS_USADOS] de [ARQUIVOS_LIMITE] arquivos no plano [NOME_PLANO].\nExclua mídias antigas ou atualize seu plano.",
            'solicitacao_pendente' => "Sua solicitação está sendo analisada.\nVocê receberá um e-mail quando for processada.",
            'subdominio_disponivel' => '✓ Subdomínio disponível',
            'subdominio_indisponivel' => "✗ Este subdomínio não está disponível.\nEscolha outro nome.",
            'subdominio_curto' => 'Mínimo 3 caracteres.',
            'subdominio_termos_reservados' => 'Nome contém termos reservados.',
            'registro_concluido' => "Subdomínio criado com sucesso!\nVerifique seu e-mail para obter as credenciais de acesso.",
            'erro_registro' => 'Por favor, escolha um subdomínio disponível.',
            'botao_ver_planos' => '🛒 Ver Planos',
            'botao_acessar_loja' => '🛒 Acessar Loja de Planos',
            
            // E-mails
            'email_solicitacao_titulo' => '[Limiter MKP Pro] Nova solicitação de mudança de plano - [SITE]',
            'email_solicitacao_corpo' => "Uma nova solicitação de mudança de plano foi registrada:\nSite: [SITE]\nURL: [URL_SITE]\nID do Blog: [BLOG_ID]\nPlano Atual: [PLANO_ATUAL] (limite de [LIMITE_ATUAL] páginas)\nPlano Solicitado: [PLANO_SOLICITADO] (limite de [LIMITE_SOLICITADO] páginas)\nInformações do Cliente:\nNome: [NOME_CLIENTE]\nE-mail: [EMAIL_CLIENTE]\nTelefone: [TELEFONE_CLIENTE]\nPara processar esta solicitação, acesse o painel da rede:\n[PAINEL_LINK]\nMensagem automática do plugin Limiter MKP Pro.",
            'email_confirmacao_aprovada_titulo' => '[Limiter MKP Pro] Atualização da sua solicitação - [SITE]',
            'email_confirmacao_aprovada_corpo' => "Olá [NOME_CLIENTE],\nSua solicitação de mudança de plano foi APROVADA!\nSite: [SITE]\nURL: [URL_SITE]\nNovo Plano: [NOVO_PLANO]\nLimite de Páginas: [NOVO_LIMITE]\nSeu site já está atualizado com o novo limite.\nAtenciosamente,\nEquipe Marketing Place Store",
            'email_confirmacao_rejeitada_titulo' => '[Limiter MKP Pro] Atualização da sua solicitação - [SITE]',
            'email_confirmacao_rejeitada_corpo' => "Olá [NOME_CLIENTE],\nSua solicitação de mudança de plano foi REJEITADA.\nSite: [SITE]\nURL: [URL_SITE]\nPara mais detalhes, entre em contato com o suporte.\nAtenciosamente,\nEquipe Marketing Place Store",
            'email_alerta_limite_titulo' => '[Limiter MKP Pro] Alerta: limite de páginas quase atingido - [SITE]',
            'email_alerta_limite_corpo' => "Olá [NOME_CLIENTE],\nEste é um alerta automático:\nSeu site está próximo do limite de páginas permitido.\nSite: [SITE]\nURL: [URL_SITE]\nLimite: [LIMITE] páginas\nUtilizadas: [UTILIZADAS]\nRestantes: [RESTANTES]\nConsidere solicitar um plano maior para evitar interrupções.\nAtenciosamente,\nEquipe Marketing Place Store",
            'email_configuracao_subdominio_titulo' => 'Configure seu Subdomínio - Marketing Place Store',
            'email_configuracao_subdominio_corpo' => "Olá [NOME_CLIENTE],\nSua assinatura foi confirmada com sucesso! 🎉\nAgora você precisa configurar seu subdomínio para começar a usar nossa plataforma.\n📝 Configure seu subdomínio:\n[URL_CONFIGURACAO]\n⚠️ Este link expira em 7 dias\nComo funciona:\n1. Clique no link acima\n2. Escolha o nome do seu subdomínio (exemplo: 'loja' se tornará 'loja-mkp.marketing-place.store')\n3. Crie sua senha\n4. Seu subdomínio será criado automaticamente\nSe tiver qualquer dúvida, entre em contato conosco.\nAtenciosamente,\nEquipe Marketing Place Store",
            'email_boas_vindas_subdominio_titulo' => 'Bem-vindo ao seu Subdomínio - Marketing Place Store',
            'email_boas_vindas_subdominio_corpo' => "Olá [NOME_CLIENTE],\nSeu subdomínio foi criado com sucesso! 🎉\n📋 Detalhes do seu plano:\nPlano: [NOME_PLANO]\nLimite: [LIMITE_PAGINAS] páginas/posts\n🔐 Suas credenciais de acesso:\nSubdomínio: [NOME_SUBDOMINIO].marketing-place.store\nE-mail: [EMAIL_USUARIO]\nSenha: [SENHA_ACESSO]\n🌐 Links importantes:\nAcessar Administração: [URL_ADM]\nVer seu Site: [URL_SITE]\n💡 Dicas:\n- Você pode personalizar seu subdomínio acessando a área de administração\n- Crie páginas e posts dentro do limite do seu plano\n- Em caso de dúvidas, entre em contato conosco\nAtenciosamente,\nEquipe Marketing Place Store"
        );
    }

    /**
     * Obtém a blacklist de subdomínios configurada
     */
    public static function get_subdomain_blacklist() {
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $default_blacklist = array(
            'admin', 'suporte', 'root', 'api', 'www', 'mail', 'ftp', 'loja-oficial', 
            'login', 'wp', 'pagamento', 'cpanel', 'system', 'support', 'host', 'server',
            'webmail', 'backup', 'secure', 'ssl', 'cdn', 'test', 'demo'
        );
        return isset($config['subdomain_blacklist']) && is_array($config['subdomain_blacklist']) 
            ? $config['subdomain_blacklist'] 
            : $default_blacklist;
    }

    /**
     * Verifica se um subdomínio está na blacklist
     */
    public static function is_subdomain_blacklisted($subdomain_name) {
        $blacklist = self::get_subdomain_blacklist();
        $subdomain_name = strtolower(trim($subdomain_name));
        
        // Verifica correspondência exata
        if (in_array($subdomain_name, $blacklist)) {
            return true;
        }
        
        // Verifica padrões comuns (ex: admin1, admin2, etc.)
        foreach ($blacklist as $blacklisted_word) {
            // Bloqueia apenas se a palavra inteira corresponder 
            // ou se for um padrão como "admin1", "admin2", etc.
            if (preg_match('/^' . preg_quote($blacklisted_word, '/') . '\d+$/', $subdomain_name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Substitui placeholders em mensagens
     */
    public static function substituir_placeholders($mensagem, $dados) {
        foreach ($dados as $placeholder => $valor) {
            $mensagem = str_replace('[' . strtoupper($placeholder) . ']', $valor, $mensagem);
            $mensagem = str_replace('[' . $placeholder . ']', $valor, $mensagem);
        }
        return $mensagem;
    }

    /**
     * Helper Estático: Obtém o sufixo de subdomínio
     */
    public static function get_sufixo_subdominio() {
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        return !empty($config['sufixo_subdominio']) ? $config['sufixo_subdominio'] : '-mkp';
    }
}