<?php
/**
 * Classe otimizada para contagem de Inodes (Arquivos Reais)
 * Considera Arquivo Original + Variações de Tamanho (Miniaturas)
 * * @since 2.2.0
 * @package Limiter_MKP_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Inode_Counter {
    
    /**
     * Nome da opção no banco de dados para cache do contador
     */
    const OPTION_NAME = 'limiter_mkp_pro_inode_count';

    public function __construct() {
        // MUDANÇA: Usamos o filtro de metadados. Ele roda logo após o WP criar as miniaturas.
        // Isso garante que sabemos exatamente quantos arquivos físicos foram gerados.
        add_filter('wp_generate_attachment_metadata', [$this, 'increment_smart_count'], 10, 2);
        
        // Hook para quando um anexo é deletado
        add_action('delete_attachment', [$this, 'decrement_smart_count']);
        
        // Verificações de limite (Mantidas)
        add_action('admin_init', [$this, 'check_inodes']);
        add_action('init', [$this, 'check_inodes_frontend']);
    }
    
    /**
     * Incrementa o contador baseando-se nos arquivos REAIS gerados.
     * Soma: 1 (Original) + N (Miniaturas listadas nos metadados)
     */
    public function increment_smart_count($metadata, $attachment_id) {
        // Começamos com 1, que representa o arquivo original enviado
        $files_generated = 1;

        // Se houver tamanhos gerados (thumbnails), somamos eles à contagem
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $files_generated += count($metadata['sizes']);
        }

        // Obtém o contador atual e soma os novos arquivos
        $current = $this->get_inode_count();
        update_option(self::OPTION_NAME, $current + $files_generated, false); // autoload=false para performance

        // Retorna os metadados inalterados (obrigatório para filtros WP)
        return $metadata;
    }

    /**
     * Decrementa o contador considerando as miniaturas que serão apagadas.
     */
    public function decrement_smart_count($post_id) {
        $files_deleted = 1; // O arquivo original

        // Recupera os metadados ANTES de serem deletados para saber quantas cópias existiam
        $metadata = wp_get_attachment_metadata($post_id);

        if ($metadata && isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $files_deleted += count($metadata['sizes']);
        }

        $current = $this->get_inode_count();
        // Garante que não fique negativo
        $new_count = max(0, $current - $files_deleted);
        
        update_option(self::OPTION_NAME, $new_count, false);
    }

    /**
     * Obtém a contagem atual de forma OTIMIZADA.
     * Lê do cache (option). Se não existir, faz uma contagem básica no DB como fallback.
     *
     * @return int Número de arquivos
     */
    public function get_inode_count() {
        $count = get_option(self::OPTION_NAME);

        // Se o contador não existe (primeira instalação ou cache limpo)
        if ($count === false) {
            global $wpdb;
            // FALLBACK INICIAL: Conta apenas os anexos (1 por 1).
            // Nota: Isso subestima o valor real até que um recálculo total seja feito,
            // mas evita travar o servidor tentando ler metadados de tudo de uma vez.
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'");
            
            // Salva esse valor inicial
            update_option(self::OPTION_NAME, $count, false);
        }

        return (int) $count;
    }
    
    /**
     * Verifica limites de inodes no admin (Painel)
     */
    public function check_inodes() {
        if (!is_multisite() || !Limiter_MKP_Pro_Security::is_blog_admin()) {
            return;
        }
        
        // Verificação rápida de cache estático para evitar múltiplas chamadas na mesma página
        static $checked = false;
        if ($checked) return;
        $checked = true;

        $blog_id = get_current_blog_id();
        
        // Obtém limite do banco
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $subdominio = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        
        if (!$subdominio) return;
        
        $plano = Limiter_MKP_Pro_Database::get_plano($subdominio->plano_id);
        if (!$plano) return;

        // Define o limite (Personalizado ou do Plano)
        $limite_inodes = !empty($subdominio->limite_personalizado_inodes) ?
                         $subdominio->limite_personalizado_inodes : 
                         $plano->limite_inodes;

        if (empty($limite_inodes)) return;
        
        // Usa o método otimizado
        $count = $this->get_inode_count();

        // Atualiza a option auxiliar para uso em widgets/shortcodes
        update_option('limiter_mkp_pro_inode_limit', $limite_inodes, false);

        $this->show_admin_notices($count, $limite_inodes, $plano->nome);
    }
    
    /**
     * Verifica limites de inodes no frontend (Bloqueio de Upload - async-upload.php)
     */
    public function check_inodes_frontend() {
        if (!is_user_logged_in()) return;

        // Só roda se estiver tentando acessar mídia ou upload
        if (!is_admin() && strpos($_SERVER['REQUEST_URI'], 'async-upload.php') === false) {
            return;
        }

        $blog_id = get_current_blog_id();
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        $subdominio = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$subdominio) return;
        
        $plano = Limiter_MKP_Pro_Database::get_plano($subdominio->plano_id);
        if (!$plano) return;
        
        $limite_inodes = !empty($subdominio->limite_personalizado_inodes) ?
                         $subdominio->limite_personalizado_inodes : 
                         $plano->limite_inodes;

        if (empty($limite_inodes)) return;
        
        $count = $this->get_inode_count();

        // Bloqueia uploads se limite atingido
        if ($count >= $limite_inodes) {
            add_filter('user_has_cap', function($allcaps) {
                $allcaps['upload_files'] = false;
                return $allcaps;
            });
        }
    }
    
    /**
     * Exibe notificações no admin baseado no uso de inodes
     */
    private function show_admin_notices($count, $limit, $plan_name) {
        if ($limit <= 0) return;
        $percent = round(($count / $limit) * 100);
        
        // Alerta 80%
        if ($count >= ($limit * 0.8) && $count < $limit) {
            add_action('admin_notices', function() use ($count, $limit, $plan_name, $percent) {
                $class = 'notice notice-warning is-dismissible';
                $message = sprintf(
                    __('<strong>⚠️ Aviso de Armazenamento:</strong> Você usou %s%% do seu limite de arquivos (%s de %s). Plano: %s.', 'limiter-mkp-pro'),
                    $percent, $count, $limit, ucfirst($plan_name)
                );
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
            });
        }
        
        // Bloqueio total (100%)
        if ($count >= $limit) {
            add_action('admin_notices', function() use ($limit, $count, $plan_name) {
                $class = 'notice notice-error';
                $message = sprintf(
                    __('<strong>🚫 Limite de arquivos atingido:</strong> Você possui %s de %s arquivos permitidos no plano %s. O envio de novas mídias foi bloqueado. Por favor, exclua arquivos antigos ou faça um upgrade.', 'limiter-mkp-pro'),
                    $count, $limit, ucfirst($plan_name)
                );
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
            });
        }
    }
}