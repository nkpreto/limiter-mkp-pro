<?php
/**
 * Classe responsável pelo gerenciamento de subdomínios.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin
 */

class Limiter_MKP_Pro_Subdominios {

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
     * Obtém todos os subdomínios cadastrados.
     *
     * @since    1.0.0
     * @return   array    Array com os subdomínios cadastrados.
     */
    public function get_subdominios() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::get_subdominios();
    }

    /**
     * Obtém um subdomínio específico pelo blog_id.
     *
     * @since    1.0.0
     * @param    int       $blog_id    ID do blog.
     * @return   object                Objeto com os dados do subdomínio ou null se não encontrado.
     */
    public function get_subdominio_by_blog_id($blog_id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
    }

    /**
     * Salva um subdomínio (novo ou existente).
     *
     * @since    1.0.0
     * @param    array     $data    Dados do subdomínio.
     * @return   int|false          ID do subdomínio inserido/atualizado ou false em caso de erro.
     */
    public function save_subdominio($data) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::save_subdominio($data);
    }

    /**
     * Obtém informações de um site da rede.
     *
     * @since    1.0.0
     * @param    int       $blog_id    ID do blog.
     * @return   array                 Array com informações do site.
     */
    public function get_site_info($blog_id) {
        $site_info = get_blog_details($blog_id);
        
        if (!$site_info) {
            return null;
        }
        
        return array(
            'blog_id' => $blog_id,
            'domain' => $site_info->domain,
            'path' => $site_info->path,
            'name' => $site_info->blogname,
            'url' => $site_info->siteurl
        );
    }

    /**
     * Obtém o limite de páginas para um blog específico.
     *
     * @since    1.0.0
     * @param    int       $blog_id    ID do blog.
     * @return   int                   Limite de páginas ou 0 se não encontrado.
     */
    public function get_limite_paginas($blog_id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::get_limite_paginas($blog_id);
    }
}
