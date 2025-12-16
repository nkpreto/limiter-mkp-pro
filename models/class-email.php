<?php
/**
 * Classe responsável pelo envio de e-mails.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/models
 */
if (!defined('ABSPATH')) {
    exit;
}
class Limiter_MKP_Pro_Email {
    /**
     * Retorna cabeçalhos padronizados de envio de e-mails.
     */
    private static function get_default_headers($from_name = null) {
        if ($from_name === null) {
            $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
            $from_name = isset($config['nome_sistema']) ? $config['nome_sistema'] : 'Marketing Place Store';
        }
        $domain = parse_url(network_site_url(), PHP_URL_HOST);
        return array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sanitize_text_field($from_name) . ' <noreply@' . $domain . '>'
        );
    }
    /**
     * Envia e-mail de notificação sobre solicitação de mudança de plano.
     */
    public static function notificar_solicitacao($blog_id, $plano_atual_id, $plano_solicitado_id) {
        global $wpdb;
        // Configurações do plugin
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $email_notificacao = !empty($config['email_notificacao'])
            ? sanitize_email($config['email_notificacao'])
            : 'alterar-plano@marketing-place.store';
        $nome_sistema = !empty($config['nome_sistema']) ? $config['nome_sistema'] : 'Marketing Place Store';
        // Informações do blog
        $blog = get_blog_details($blog_id);
        if (!$blog) {
            return false;
        }
        // Planos
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $plano_atual = Limiter_MKP_Pro_Database::get_plano($plano_atual_id);
        $plano_solicitado = Limiter_MKP_Pro_Database::get_plano($plano_solicitado_id);
        if (!$plano_atual || !$plano_solicitado) {
            return false;
        }
        // Subdomínio
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        // Obtém mensagens personalizadas
        $assunto = isset($config['email_solicitacao_titulo']) ? $config['email_solicitacao_titulo'] : '[Limiter MKP Pro] Nova solicitação de mudança de plano - [SITE]';
        $mensagem = isset($config['email_solicitacao_corpo']) ? $config['email_solicitacao_corpo'] : "Uma nova solicitação de mudança de plano foi registrada:\nSite: [SITE]\nURL: [URL_SITE]\nID do Blog: [BLOG_ID]\nPlano Atual: [PLANO_ATUAL] (limite de [LIMITE_ATUAL] páginas)\nPlano Solicitado: [PLANO_SOLICITADO] (limite de [LIMITE_SOLICITADO] páginas)\nInformações do Cliente:\nNome: [NOME_CLIENTE]\nE-mail: [EMAIL_CLIENTE]\nTelefone: [TELEFONE_CLIENTE]\nPara processar esta solicitação, acesse o painel da rede:\n[PAINEL_LINK]\nMensagem automática do plugin Limiter MKP Pro.";
        // Substitui placeholders
        $dados = array(
            'SITE' => sanitize_text_field($blog->blogname),
            'URL_SITE' => esc_url($blog->siteurl),
            'BLOG_ID' => $blog_id,
            'PLANO_ATUAL' => sanitize_text_field($plano_atual->nome),
            'LIMITE_ATUAL' => intval($plano_atual->limite_paginas),
            'PLANO_SOLICITADO' => sanitize_text_field($plano_solicitado->nome),
            'LIMITE_SOLICITADO' => intval($plano_solicitado->limite_paginas),
            'NOME_CLIENTE' => $sub ? sanitize_text_field($sub->nome_cliente) : 'Não informado',
            'EMAIL_CLIENTE' => $sub ? sanitize_email($sub->email_cliente) : 'Não informado',
            'TELEFONE_CLIENTE' => $sub ? sanitize_text_field($sub->telefone_cliente) : 'Não informado',
            'PAINEL_LINK' => network_admin_url('admin.php?page=limiter-mkp-pro-solicitacoes')
        );
        $assunto = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($assunto, $dados);
        $mensagem = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($mensagem, $dados);
        $headers = self::get_default_headers($nome_sistema);
        // Envia para e-mail principal
        $enviado = wp_mail($email_notificacao, $assunto, $mensagem, $headers);
        // Envia para super administradores
        foreach (get_super_admins() as $admin_login) {
            $user = get_user_by('login', $admin_login);
            if ($user && sanitize_email($user->user_email) !== $email_notificacao) {
                wp_mail($user->user_email, $assunto, $mensagem, $headers);
            }
        }
        return $enviado;
    }
    /**
     * Envia e-mail de confirmação da solicitação (aprovada/rejeitada).
     */
    public static function confirmar_processamento($blog_id, $plano_id, $status) {
        $blog = get_blog_details($blog_id);
        if (!$blog) {
            return false;
        }
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$sub || empty($sub->email_cliente)) {
            return false;
        }
        $plano = Limiter_MKP_Pro_Database::get_plano($plano_id);
        if (!$plano) {
            return false;
        }
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $nome_sistema = !empty($config['nome_sistema']) ? $config['nome_sistema'] : 'Marketing Place Store';
        if ($status === 'aprovada') {
            $assunto_padrao = '[Limiter MKP Pro] Atualização da sua solicitação - [SITE]';
            $mensagem_padrao = "Olá [NOME_CLIENTE],\nSua solicitação de mudança de plano foi APROVADA!\nSite: [SITE]\nURL: [URL_SITE]\nNovo Plano: [NOVO_PLANO]\nLimite de Páginas: [NOVO_LIMITE]\nSeu site já está atualizado com o novo limite.\nAtenciosamente,\nEquipe Marketing Place Store";
            $assunto_config = isset($config['email_confirmacao_aprovada_titulo']) ? $config['email_confirmacao_aprovada_titulo'] : $assunto_padrao;
            $mensagem_config = isset($config['email_confirmacao_aprovada_corpo']) ? $config['email_confirmacao_aprovada_corpo'] : $mensagem_padrao;
        } else {
            $assunto_padrao = '[Limiter MKP Pro] Atualização da sua solicitação - [SITE]';
            $mensagem_padrao = "Olá [NOME_CLIENTE],\nSua solicitação de mudança de plano foi REJEITADA.\nSite: [SITE]\nURL: [URL_SITE]\nPara mais detalhes, entre em contato com o suporte.\nAtenciosamente,\nEquipe Marketing Place Store";
            $assunto_config = isset($config['email_confirmacao_rejeitada_titulo']) ? $config['email_confirmacao_rejeitada_titulo'] : $assunto_padrao;
            $mensagem_config = isset($config['email_confirmacao_rejeitada_corpo']) ? $config['email_confirmacao_rejeitada_corpo'] : $mensagem_padrao;
        }
        $dados = array(
            'NOME_CLIENTE' => $sub->nome_cliente ?: 'Cliente',
            'SITE' => sanitize_text_field($blog->blogname),
            'URL_SITE' => esc_url($blog->siteurl),
            'NOVO_PLANO' => sanitize_text_field($plano->nome),
            'NOVO_LIMITE' => intval($plano->limite_paginas)
        );
        $assunto = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($assunto_config, $dados);
        $mensagem = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($mensagem_config, $dados);
        $headers = self::get_default_headers($nome_sistema);
        return wp_mail($sub->email_cliente, $assunto, $mensagem, $headers);
    }
    /**
     * Alerta limite de páginas.
     */
    public static function alerta_limite($blog_id, $limite, $utilizadas) {
        $blog = get_blog_details($blog_id);
        if (!$blog) {
            return false;
        }
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$sub || empty($sub->email_cliente)) {
            return false;
        }
        $restantes = max(0, $limite - $utilizadas);
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $nome_sistema = !empty($config['nome_sistema']) ? $config['nome_sistema'] : 'Marketing Place Store';
        $assunto_padrao = '[Limiter MKP Pro] Alerta: limite de páginas quase atingido - [SITE]';
        $mensagem_padrao = "Olá [NOME_CLIENTE],\nEste é um alerta automático:\nSeu site está próximo do limite de páginas permitido.\nSite: [SITE]\nURL: [URL_SITE]\nLimite: [LIMITE] páginas\nUtilizadas: [UTILIZADAS]\nRestantes: [RESTANTES]\nConsidere solicitar um plano maior para evitar interrupções.\nAtenciosamente,\nEquipe Marketing Place Store";
        $assunto = isset($config['email_alerta_limite_titulo']) ? $config['email_alerta_limite_titulo'] : $assunto_padrao;
        $mensagem = isset($config['email_alerta_limite_corpo']) ? $config['email_alerta_limite_corpo'] : $mensagem_padrao;
        $dados = array(
            'NOME_CLIENTE' => $sub->nome_cliente ?: 'Cliente',
            'SITE' => sanitize_text_field($blog->blogname),
            'URL_SITE' => esc_url($blog->siteurl),
            'LIMITE' => intval($limite),
            'UTILIZADAS' => intval($utilizadas),
            'RESTANTES' => intval($restantes)
        );
        $assunto = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($assunto, $dados);
        $mensagem = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($mensagem, $dados);
        $headers = self::get_default_headers($nome_sistema);
        return wp_mail($sub->email_cliente, $assunto, $mensagem, $headers);
    }
    /**
     * Envia e-mail de configuração de subdomínio
     */
    public static function enviar_email_configuracao($email_destino, $nome_cliente, $url_configuracao) {
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $nome_sistema = !empty($config['nome_sistema']) ? $config['nome_sistema'] : 'Marketing Place Store';
        $assunto_padrao = 'Configure seu Subdomínio - Marketing Place Store';
        $mensagem_padrao = "Olá [NOME_CLIENTE],\nSua assinatura foi confirmada com sucesso! 🎉\nAgora você precisa configurar seu subdomínio para começar a usar nossa plataforma.\n📝 Configure seu subdomínio:\n[URL_CONFIGURACAO]\n⚠️ Este link expira em 7 dias\nComo funciona:\n1. Clique no link acima\n2. Escolha o nome do seu subdomínio (exemplo: 'loja' se tornará 'loja-mkp.marketing-place.store')\n3. Crie sua senha\n4. Seu subdomínio será criado automaticamente\nSe tiver qualquer dúvida, entre em contato conosco.\nAtenciosamente,\nEquipe Marketing Place Store";
        $assunto = isset($config['email_configuracao_subdominio_titulo']) ? $config['email_configuracao_subdominio_titulo'] : $assunto_padrao;
        $mensagem = isset($config['email_configuracao_subdominio_corpo']) ? $config['email_configuracao_subdominio_corpo'] : $mensagem_padrao;
        $dados = array(
            'NOME_CLIENTE' => $nome_cliente,
            'URL_CONFIGURACAO' => $url_configuracao
        );
        $assunto = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($assunto, $dados);
        $mensagem = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($mensagem, $dados);
        $headers = self::get_default_headers($nome_sistema);
        return wp_mail($email_destino, $assunto, $mensagem, $headers);
    }
    /**
     * Envia e-mail de boas-vindas ao subdomínio
     */
    public static function enviar_email_boas_vindas($email_destino, $dados_usuario) {
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $nome_sistema = !empty($config['nome_sistema']) ? $config['nome_sistema'] : 'Marketing Place Store';
        $assunto_padrao = 'Bem-vindo ao seu Subdomínio - Marketing Place Store';
        $mensagem_padrao = "Olá [NOME_CLIENTE],\nSeu subdomínio foi criado com sucesso! 🎉\n📋 Detalhes do seu plano:\nPlano: [NOME_PLANO]\nLimite: [LIMITE_PAGINAS] páginas/posts\n🔐 Suas credenciais de acesso:\nSubdomínio: [NOME_SUBDOMINIO].marketing-place.store\nE-mail: [EMAIL_USUARIO]\nSenha: [SENHA_ACESSO]\n🌐 Links importantes:\nAcessar Administração: [URL_ADM]\nVer seu Site: [URL_SITE]\n💡 Dicas:\n- Você pode personalizar seu subdomínio acessando a área de administração\n- Crie páginas e posts dentro do limite do seu plano\n- Em caso de dúvidas, entre em contato conosco\nAtenciosamente,\nEquipe Marketing Place Store";
        $assunto = isset($config['email_boas_vindas_subdominio_titulo']) ? $config['email_boas_vindas_subdominio_titulo'] : $assunto_padrao;
        $mensagem = isset($config['email_boas_vindas_subdominio_corpo']) ? $config['email_boas_vindas_subdominio_corpo'] : $mensagem_padrao;
        $dados = array(
            'NOME_CLIENTE' => $dados_usuario['nome_cliente'],
            'NOME_PLANO' => $dados_usuario['plano_nome'],
            'LIMITE_PAGINAS' => $dados_usuario['limite_paginas'],
            'NOME_SUBDOMINIO' => $dados_usuario['subdominio'],
            'EMAIL_USUARIO' => $dados_usuario['email_usuario'],
            'SENHA_ACESSO' => $dados_usuario['senha'],
            'URL_ADM' => $dados_usuario['url_admin'],
            'URL_SITE' => $dados_usuario['url_site']
        );
        $assunto = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($assunto, $dados);
        $mensagem = Limiter_MKP_Pro_Configuracoes::substituir_placeholders($mensagem, $dados);
        $headers = self::get_default_headers($nome_sistema);
        return wp_mail($email_destino, $assunto, $mensagem, $headers);
    }
}