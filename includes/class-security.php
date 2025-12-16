<?php
/**
 * Classe responsável pela segurança do plugin.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */

class Limiter_MKP_Pro_Security {

    /**
     * Verifica se o usuário atual é um super administrador.
     *
     * @since    1.0.0
     * @return   boolean    Verdadeiro se o usuário é super admin, falso caso contrário.
     */
    public static function is_super_admin() {
        return is_super_admin();
    }

    /**
     * Verifica se o usuário atual é um administrador do blog.
     *
     * @since    1.0.0
     * @return   boolean    Verdadeiro se o usuário é admin do blog, falso caso contrário.
     */
    public static function is_blog_admin() {
        return current_user_can('manage_options');
    }

    /**
     * Verifica se o nonce é válido.
     *
     * @since    1.0.0
     * @param    string    $nonce      O nonce a ser verificado.
     * @param    string    $action     A ação associada ao nonce.
     * @return   boolean               Verdadeiro se o nonce é válido, falso caso contrário.
     */
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Cria um nonce para uma ação específica.
     *
     * @since    1.0.0
     * @param    string    $action     A ação associada ao nonce.
     * @return   string                O nonce criado.
     */
    public static function create_nonce($action) {
        return wp_create_nonce($action);
    }

    /**
     * Verifica permissões para operações de rede.
     *
     * @since    1.0.0
     * @return   boolean    Verdadeiro se o usuário tem permissões de rede, falso caso contrário.
     */
    public static function check_network_permissions() {
        // Verifica se é uma instalação multisite
        if (!is_multisite()) {
            return false;
        }

        // Verifica se o usuário é super admin
        if (!self::is_super_admin()) {
            return false;
        }

        return true;
    }

    /**
     * Verifica permissões para operações de blog.
     *
     * @since    1.0.0
     * @return   boolean    Verdadeiro se o usuário tem permissões de blog, falso caso contrário.
     */
    public static function check_blog_permissions() {
        // Verifica se o usuário é admin do blog
        if (!self::is_blog_admin()) {
            return false;
        }

        return true;
    }

    /**
     * Sanitiza dados de entrada.
     *
     * @since    1.0.0
     * @param    mixed     $data       Os dados a serem sanitizados.
     * @param    string    $type       O tipo de sanitização a ser aplicada.
     * @return   mixed                 Os dados sanitizados.
     */
    public static function sanitize_data($data, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'int':
                return intval($data);
            case 'float':
                return floatval($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'html':
                return wp_kses_post($data);
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }

    /**
     * Registra uma tentativa de acesso não autorizado.
     *
     * @since    1.0.0
     * @param    string    $action     A ação que foi tentada.
     * @param    int       $blog_id    O ID do blog, se aplicável.
     */
    public static function log_unauthorized_attempt($action, $blog_id = 0) {
        global $wpdb;
        $table_logs = $wpdb->base_prefix . 'limiter_mkp_pro_logs';
        
        // Verifica se a tabela existe antes de tentar inserir
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs'") == $table_logs) {
            $wpdb->insert(
                $table_logs,
                array(
                    'blog_id' => $blog_id,
                    'acao' => 'acesso_nao_autorizado',
                    'descricao' => 'Tentativa de acesso não autorizado: ' . $action,
                ),
                array('%d', '%s', '%s')
            );
        }
    }
}
