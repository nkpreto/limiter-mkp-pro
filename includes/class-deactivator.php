<?php
/**
 * Acionado durante a desativação do plugin.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */
if (!defined('ABSPATH')) {
    exit;
}
class Limiter_MKP_Pro_Deactivator {

    /**
     * Método executado durante a desativação do plugin.
     *
     * Realiza:
     * - Cancelamento do cron diário
     * - Limpeza do transient da rotina de tokens
     * - Registro no log (caso a tabela exista)
     *
     * Não remove tabelas para preservar os dados.
     *
     * @since    1.0.0
     */
    public static function deactivate() {

        // -------------------------------------------------------
        // 1. CANCELA O EVENTO CRON DIÁRIO AO DESATIVAR O PLUGIN
        // -------------------------------------------------------
        if ( function_exists( 'wp_next_scheduled' ) ) {
            $timestamp = wp_next_scheduled( 'limiter_mkp_pro_cron_daily' );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, 'limiter_mkp_pro_cron_daily' );
            }
        }

        // -------------------------------------------------------
        // 2. REMOVE O TRANSIENT DE LIMPEZA DE TOKENS
        // -------------------------------------------------------
        delete_transient( 'mkp_token_cleanup_last_run' );

        // -------------------------------------------------------
        // 3. REGISTRO EM LOG DA DESATIVAÇÃO (SE A TABELA EXISTIR)
        // -------------------------------------------------------
        global $wpdb;

        $table_logs = $wpdb->base_prefix . 'limiter_mkp_pro_logs';

        // Verifica existência da tabela corretamente
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_logs
        ) );

        if ( $exists === $table_logs ) {
            // Sanitização mínima — todos os valores são fixos/sob controle
            $wpdb->insert(
                $table_logs,
                array(
                    'blog_id'   => 0, // 0 = ação em nível de rede
                    'acao'      => 'desativacao',
                    'descricao' => 'Plugin desativado pelo administrador da rede.',
                ),
                array( '%d', '%s', '%s' )
            );
        }
    }
}
