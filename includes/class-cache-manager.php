<?php
/**
 * Classe responsável pelo gerenciamento de cache do plugin.
 *
 * @since      1.2.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */
if (!defined('ABSPATH')) {
    exit;
}
class Limiter_MKP_Pro_Cache_Manager {

    /**
     * Prefixo para as chaves de cache.
     *
     * @since    1.2.0
     * @access   private
     * @var      string    $cache_prefix    Prefixo para as chaves de cache.
     */
    private static $cache_prefix = 'limiter_mkp_pro_';

    /**
     * Obtém um valor do cache.
     *
     * @since    1.2.0
     * @param    string    $key    Chave do cache.
     * @return   mixed             Valor em cache ou false se não encontrado.
     */
    public static function get($key) {
        $full_key = self::$cache_prefix . $key;
        return get_transient($full_key);
    }

    /**
     * Define um valor no cache.
     *
     * @since    1.2.0
     * @param    string    $key       Chave do cache.
     * @param    mixed     $value     Valor a ser armazenado.
     * @param    int       $expire    Tempo de expiração em segundos (opcional).
     * @return   boolean              Verdadeiro se salvo com sucesso.
     */
    public static function set($key, $value, $expire = 0) {
        $full_key = self::$cache_prefix . $key;
        $expire = $expire ?: 15 * MINUTE_IN_SECONDS; // 15 minutos padrão
        return set_transient($full_key, $value, $expire);
    }

    /**
     * Remove um valor do cache.
     *
     * @since    1.2.0
     * @param    string    $key    Chave do cache.
     * @return   boolean           Verdadeiro se removido com sucesso.
     */
    public static function delete($key) {
        $full_key = self::$cache_prefix . $key;
        return delete_transient($full_key);
    }

    /**
     * Limpa todo o cache do plugin.
     *
     * @since    1.2.0
     * @return   boolean    Verdadeiro se limpo com sucesso.
     */
    public static function clear_all() {
        global $wpdb;
        
        // Limpa todos os transients do plugin
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::$cache_prefix . '%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . self::$cache_prefix . '%'
            )
        );
        
        return true;
    }

    /**
     * Obtém estatísticas com cache inteligente.
     *
     * @since    1.2.0
     * @param    int       $blog_id        ID do blog (opcional).
     * @param    bool      $force_refresh  Forçar atualização do cache.
     * @return   array                     Estatísticas do blog ou gerais.
     */
    public static function get_cached_stats($blog_id = null, $force_refresh = false) {
        if ($blog_id) {
            $cache_key = "estatisticas_blog_{$blog_id}";
        } else {
            $cache_key = "estatisticas_gerais";
        }
        
        $stats = self::get($cache_key);
        
        if ($stats === false || $force_refresh) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database-get-estatisticas.php';
            
            if ($blog_id) {
                $stats = Limiter_MKP_Pro_Database_Get_Estatisticas::get_estatisticas_blog($blog_id);
            } else {
                $stats = Limiter_MKP_Pro_Database_Get_Estatisticas::get_estatisticas_gerais();
            }
            
            // Cache mais longo para estatísticas gerais
            $expiration = $blog_id ? 300 : 900; // 5min para blog, 15min para geral
            self::set($cache_key, $stats, $expiration);
        }
        
        return $stats;
    }

    /**
     * Obtém contagem de posts com cache.
     *
     * @since    1.2.0
     * @param    int       $blog_id    ID do blog.
     * @return   int                   Total de posts e páginas.
     */
    public static function get_cached_post_count($blog_id) {
        $cache_key = "post_count_blog_{$blog_id}";
        $count = self::get($cache_key);
        
        if ($count === false) {
            switch_to_blog($blog_id);
            $count_pages = wp_count_posts('page');
            $count_posts = wp_count_posts('post');
            $count = (int) $count_pages->publish + (int) $count_posts->publish;
            restore_current_blog();
            
            self::set($cache_key, $count, 300); // 5 minutos
        }
        
        return $count;
    }
}