<?php
/**
 * Classe para gerenciar tokens de configuração de subdomínios
 */
// Segurança: impede acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Token_Manager {
    /**
     * Gera um token vinculado a uma conta de usuário e assinatura
     *
     * @param int    $user_id          ID do usuário
     * @param int    $subscription_id  ID da assinatura no WooCommerce
     * @param string $email            E-mail do usuário
     * @param int    $plano_id         ID do plano no MKP Pro
     * @param string $regiao           Região do usuário (global, jp)
     * @return string|false            Token gerado ou false em caso de erro
     */
    public static function generate_token_with_region($user_id, $subscription_id, $email, $plano_id, $regiao = 'global') {
        global $wpdb;
        $table_tokens = Limiter_MKP_Pro_Database::get_table_name('tokens');
        
        // Valida se tabela existe
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_tokens)) {
            Limiter_MKP_Pro_Error_Handler::log_error('Tabela de tokens inválida na operação generate_token_with_region');
            return false;
        }
        
        // Verifica se já existe um token pendente para este e-mail
        $existing_token = $wpdb->get_var($wpdb->prepare(
            "SELECT token FROM $table_tokens WHERE email = %s AND status = 'pending'",
            $email
        ));
        
        if ($existing_token) {
            return $existing_token;
        }
        
        // Gera novo token (Criptograficamente seguro)
		$token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days', current_time('timestamp')));
        
        $result = $wpdb->insert(
            $table_tokens,
            array(
                'user_id' => $user_id,
                'subscription_id' => $subscription_id,
                'token' => $token,
                'email' => $email,
                'plano_id' => $plano_id,
                'regiao' => $regiao,
                'expires_at' => $expires_at,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if (Limiter_MKP_Pro_Error_Handler::check_db_error($result, 'generate_token', ['user_id' => $user_id, 'email' => $email])) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Gera um token (versão antiga, mantida para compatibilidade)
     */
    public static function generate_token($user_id, $subscription_id, $email, $plano_id) {
        return self::generate_token_with_region($user_id, $subscription_id, $email, $plano_id, 'global');
    }
    
    /**
     * Valida um token
     * 
     * Verifica se:
     * - Token existe e está válido
     * - Token não expirou
     * - Token ainda não foi usado
     * - Usuário/subdomínio associado está ativo
     */
    public static function validate_token($token) {
        global $wpdb;
        $table_tokens = Limiter_MKP_Pro_Database::get_table_name('tokens');
        
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_tokens)) {
            Limiter_MKP_Pro_Error_Handler::log_error('Tabela de tokens inválida na operação validate_token');
            return null;
        }
        
        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_tokens 
             WHERE token = %s 
             AND status = 'pending' 
             AND expires_at > %s",
            $token,
            current_time('mysql')
        ));
        
        if (!$token_data) {
            Limiter_MKP_Pro_Error_Handler::log_error('Token não encontrado ou inválido', ['token' => $token]);
            return null;
        }
        
        // Verifica se o usuário existe e está ativo
        $user = get_user_by('id', $token_data->user_id);
        if (!$user || !$user->exists()) {
            Limiter_MKP_Pro_Error_Handler::log_error('Usuário não encontrado para o token', ['user_id' => $token_data->user_id, 'token' => $token]);
            return null;
        }
        
        // Verifica se já existe um subdomínio ativo para este usuário
        $table_subdominios = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        if (Limiter_MKP_Pro_Database::validate_table_name($table_subdominios)) {
            $existing_sub = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_subdominios 
                 WHERE user_id = %d AND status = 'active'",
                $token_data->user_id
            ));
            
            if ($existing_sub > 0) {
                // Já possui subdomínio ativo - não permitir novo token
                Limiter_MKP_Pro_Error_Handler::log_error('Usuário já possui subdomínio ativo', ['user_id' => $token_data->user_id, 'token' => $token]);
                return null;
            }
        }
        
        return $token_data;
    }
    
    /**
     * Marca token como usado e salva subdomínio com região
     */
    public static function mark_token_used($token, $subdomain_name) {
        global $wpdb;
        $table_tokens = Limiter_MKP_Pro_Database::get_table_name('tokens');
        
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_tokens)) {
            Limiter_MKP_Pro_Error_Handler::log_error('Tabela de tokens inválida na operação mark_token_used');
            return false;
        }
        
        // Primeiro obtém os dados do token para registrar o subdomínio
        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_tokens WHERE token = %s",
            $token
        ));
        
        if (!$token_data) {
            Limiter_MKP_Pro_Error_Handler::log_error('Token não encontrado para marcar como usado', ['token' => $token]);
            return false;
        }
        
        // Verifica se o token já foi usado
        if ($token_data->status !== 'pending') {
            Limiter_MKP_Pro_Error_Handler::log_error('Tentativa de usar token já utilizado', ['token' => $token, 'status' => $token_data->status]);
            return false;
        }
        
        // Salva no subdomínio com região
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $result = Limiter_MKP_Pro_Database::save_subdominio(array(
            'blog_id' => $token_data->blog_id ?? 0,
            'user_id' => $token_data->user_id,
            'dominio' => $subdomain_name . '.marketing-place.store',
            'plano_id' => $token_data->plano_id,
            'regiao' => $token_data->regiao ?? 'global',
            'status' => 'active'
        ));
        
        if (!$result) {
            Limiter_MKP_Pro_Error_Handler::log_error('Falha ao salvar subdomínio para token', ['token' => $token, 'subdomain_name' => $subdomain_name]);
            return false;
        }
        
        // Atualiza status do token para "used"
        $update_result = $wpdb->update(
            $table_tokens, 
            array('status' => 'used'),
            array('token' => $token),
            array('%s'),
            array('%s')
        );
        
        if (!Limiter_MKP_Pro_Error_Handler::check_db_error($update_result, 'mark_token_used', ['token' => $token])) {
            Limiter_MKP_Pro_Error_Handler::log_error('Falha ao atualizar status do token', ['token' => $token]);
            return false;
        }
        
        // Log de sucesso
        Limiter_MKP_Pro_Database::log(0, 'token_utilizado', "Token utilizado com sucesso para subdomínio: {$subdomain_name}", [
            'token' => $token,
            'user_id' => $token_data->user_id,
            'plano_id' => $token_data->plano_id,
            'regiao' => $token_data->regiao
        ]);
        
        return true;
    }
    
    /**
     * Limpa tokens expirados
     */
    public static function cleanup_expired_tokens() {
        global $wpdb;
        $table_tokens = Limiter_MKP_Pro_Database::get_table_name('tokens');
        
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_tokens)) {
            Limiter_MKP_Pro_Error_Handler::log_error('Tabela de tokens inválida na operação cleanup_expired_tokens');
            return 0;
        }
        
        $result = $wpdb->query(
            "DELETE FROM $table_tokens 
             WHERE expires_at < NOW() 
             AND status = 'pending'"
        );
        
        if ($result !== false) {
            $log_message = "Tokens expirados limpos: {$result}";
            Limiter_MKP_Pro_Database::log(0, 'tokens_limpados', $log_message);
        } else {
            $error_message = "Erro ao limpar tokens expirados: " . $wpdb->last_error;
            Limiter_MKP_Pro_Error_Handler::log_error($error_message);
        }
        
        return $result !== false ? intval($result) : 0;
    }
    
    /**
     * Verifica se já existe um token pendente para um e-mail
     */
    public static function has_pending_token($email) {
        global $wpdb;
        $table_tokens = Limiter_MKP_Pro_Database::get_table_name('tokens');
        
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_tokens)) {
            Limiter_MKP_Pro_Error_Handler::log_error('Tabela de tokens inválida na operação has_pending_token');
            return false;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_tokens 
             WHERE email = %s AND status = 'pending'",
            $email
        ));
        
        return intval($count) > 0;
    }
    
    /**
     * Obtém o sufixo de subdomínio configurado
     */
    public static function get_sufixo_subdominio() {
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        return !empty($config['sufixo_subdominio']) ? $config['sufixo_subdominio'] : '-mkp';
    }
}