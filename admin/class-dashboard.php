<?php
/**
 * Classe responsável pelo dashboard do painel de administração.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin
 */
if (!defined('ABSPATH')) {
    exit;
}
class Limiter_MKP_Pro_Dashboard {

    /**
     * O identificador único deste plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    O nome ou identificador único deste plugin.
     */
    private $plugin_name;

    /**
     * A versão atual do plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    A versão atual do plugin.
     */
    private $version;

    /**
     * Inicializa a classe e define suas propriedades.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       O nome do plugin.
     * @param    string    $version           A versão do plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Obtém estatísticas para o dashboard.
     *
     * @since    1.0.0
     * @return   array    Array com estatísticas para o dashboard.
     */
    public function get_dashboard_stats() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database-get-estatisticas.php';
        
        $estatisticas = Limiter_MKP_Pro_Database_Get_Estatisticas::get_estatisticas_gerais();
        $distribuicao_planos = Limiter_MKP_Pro_Database_Get_Estatisticas::get_distribuicao_planos();
        $estatisticas_solicitacoes = Limiter_MKP_Pro_Database_Get_Estatisticas::get_estatisticas_solicitacoes();
        
        return array(
            'estatisticas' => $estatisticas,
            'distribuicao_planos' => $distribuicao_planos,
            'estatisticas_solicitacoes' => $estatisticas_solicitacoes
        );
    }
}
