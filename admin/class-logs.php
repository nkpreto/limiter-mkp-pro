<?php
/**
 * Classe responsável pelo gerenciamento de logs.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin
 */

// Segurança: Prevenção de acesso direto ao arquivo.
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Logs {

    /**
     * O identificador único deste plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $plugin_name;

    /**
     * A versão atual do plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $version;

    /**
     * Inicializa a classe e define suas propriedades.
     *
     * @since    1.0.0
     * @param    string    $plugin_name
     * @param    string    $version
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Obtém os logs do sistema.
     *
     * @since    1.0.0
     * @param    int       $limit
     * @param    int|null  $blog_id
     * @return   array
     */
    public function get_logs($limit = 100, $blog_id = null) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::get_logs($limit, $blog_id);
    }

    /**
     * Registra uma ação no log.
     *
     * @since    1.0.0
     * @param    int       $blog_id
     * @param    string    $acao
     * @param    string    $descricao
     * @param    array     $dados_extras
     * @return   int|false
     */
    public function log($blog_id, $acao, $descricao, $dados_extras = []) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        $log_data = [
            'blog_id' => (int) $blog_id,
            'acao' => sanitize_text_field($acao),
            'descricao' => sanitize_text_field($descricao),
            'dados_extras' => maybe_serialize($dados_extras),
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        return Limiter_MKP_Pro_Database::log_enhanced($log_data);
    }

    /**
     * Limpa logs antigos com segurança.
     *
     * @since    1.0.0
     * @param    int   $dias     Número de dias para manter logs.
     * @return   int             Quantidade de logs removidos.
     */
    public function limpar_logs_antigos($dias = 90) {
        global $wpdb;

        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';

        // Obtém nome da tabela
        $table_logs = Limiter_MKP_Pro_Database::get_table_name('logs');

        // Validação obrigatória do nome da tabela
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_logs)) {
            trigger_error(
                'Tentativa de limpar logs usando nome de tabela inválido: ' . esc_html($table_logs),
                E_USER_WARNING
            );
            return 0;
        }

        // Data limite
        $data_limite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        // Query segura usando prepared statement
        $sql = $wpdb->prepare(
            "DELETE FROM {$table_logs} WHERE timestamp < %s",
            $data_limite
        );

        $result = $wpdb->query($sql);
        
        if ($result === false) {
            $this->log(0, 'erro_limpeza_logs', 'Falha ao limpar logs antigos: ' . $wpdb->last_error);
            return 0;
        }

        return intval($wpdb->rows_affected);
    }
}