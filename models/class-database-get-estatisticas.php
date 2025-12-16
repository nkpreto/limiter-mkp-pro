<?php
/**
 * Classe responsável por obter estatísticas do sistema.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/models
 */
if (!defined('ABSPATH')) {
    exit;
}
class Limiter_MKP_Pro_Database_Get_Estatisticas {
    /**
     * Obtém estatísticas gerais do sistema para o dashboard do super admin.
     *
     * @since    1.0.0
     * @return   array    Array com as estatísticas gerais.
     */
    public static function get_estatisticas_gerais() {
        global $wpdb;
        $table_subdominios = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        $table_planos = Limiter_MKP_Pro_Database::get_table_name('planos');
        // Total de subdomínios gerenciados
        $total_subdominios = $wpdb->get_var("SELECT COUNT(*) FROM $table_subdominios");
        // Total de planos ativos
        $total_planos = $wpdb->get_var("SELECT COUNT(*) FROM $table_planos");
        return array(
            'total_subdominios' => $total_subdominios ?: 0,
            'total_planos' => $total_planos ?: 0
        );
    }
    /**
     * Obtém estatísticas de um blog específico para o dashboard do subdomínio.
     *
     * @since    1.0.0
     * @param    int       $blog_id    ID do blog.
     * @return   array                 Array com as estatísticas do blog.
     */
    public static function get_estatisticas_blog($blog_id) {
        // Obtém informações do subdomínio
        $subdominio = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$subdominio) {
            return array(
                'tem_plano' => false,
                'plano_nome' => 'Não definido',
                'limite_paginas' => 0,
                'paginas_publicadas' => 0,
                'paginas_rascunho' => 0,
                'paginas_lixeira' => 0,
                'paginas_total' => 0,
                'paginas_restantes' => 0,
                'percentual_uso' => 0,
                'mostrar_alerta' => false
            );
        }
        // Conta TODAS as páginas e posts (publicados, rascunhos, pendentes, agendados e lixeira)
        $paginas_total = Limiter_MKP_Pro_Database::count_pages_posts($blog_id, false, true);
        // Conta páginas e posts publicados
        $paginas_publicadas = Limiter_MKP_Pro_Database::count_published_pages_posts($blog_id);
        // Conta páginas e posts na lixeira
        $paginas_lixeira = Limiter_MKP_Pro_Database::count_trash_pages_posts($blog_id);
        // Calcula páginas em rascunho
        $paginas_rascunho = $paginas_total - $paginas_publicadas - $paginas_lixeira;
        // Define o limite de páginas
        $limite_paginas = !empty($subdominio->limite_personalizado) ? 
                          $subdominio->limite_personalizado : 
                          $subdominio->plano_limite;
        // Calcula páginas restantes
        $paginas_restantes = max(0, $limite_paginas - $paginas_total);
        // Calcula percentual de uso
        $percentual_uso = $limite_paginas > 0 ? 
                          min(100, round(($paginas_total / $limite_paginas) * 100)) : 
                          100;
        // Verifica se deve mostrar alerta (penúltima página)
        $mostrar_alerta = ($paginas_total == ($limite_paginas - 1));
        return array(
            'tem_plano' => true,
            'plano_nome' => $subdominio->plano_nome,
            'plano_id' => $subdominio->plano_id,
            'limite_paginas' => $limite_paginas,
            'paginas_publicadas' => $paginas_publicadas,
            'paginas_rascunho' => $paginas_rascunho,
            'paginas_lixeira' => $paginas_lixeira,
            'paginas_total' => $paginas_total,
            'paginas_restantes' => $paginas_restantes,
            'percentual_uso' => $percentual_uso,
            'mostrar_alerta' => $mostrar_alerta
        );
    }
    /**
     * Obtém a distribuição de subdomínios por plano.
     *
     * @since    1.0.0
     * @return   array    Array com a distribuição de subdomínios por plano.
     */
    public static function get_distribuicao_planos() {
        global $wpdb;
        $table_subdominios = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        $table_planos = Limiter_MKP_Pro_Database::get_table_name('planos');
        $query = "SELECT p.id, p.nome, COUNT(s.id) as total
                 FROM $table_planos p
                 LEFT JOIN $table_subdominios s ON p.id = s.plano_id
                 GROUP BY p.id
                 ORDER BY p.limite_paginas ASC";
        return $wpdb->get_results($query);
    }
}