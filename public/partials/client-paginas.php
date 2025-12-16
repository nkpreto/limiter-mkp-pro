<?php
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado.');
}
$blog_id = get_current_blog_id();
$domain = wp_parse_url(network_site_url(), PHP_URL_HOST);
$current_url = wp_parse_url(home_url(), PHP_URL_HOST);
$subdomain = str_replace('.' . $domain, '', $current_url);

$posts = get_posts([
    'post_type' => ['page', 'post'],
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);
?>
<div class="wrap">
    <h1>Minhas Páginas e URLs</h1>
    <p>Use as URLs abaixo para compartilhar suas páginas:</p>
    <?php if ($posts): ?>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>URL Real</th>
                    <th>URL para Anúncios</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <?php
                    $real_url = get_permalink($post->ID);
                    $slug = $post->post_name;
                    $proxy_url = "https://{$slug}.{$domain}/{$subdomain}/";
                    ?>
                    <tr>
                        <td><?php echo esc_html($post->post_title); ?></td>
                        <td>
                            <a href="<?php echo esc_url($real_url); ?>" target="_blank"><?php echo esc_html($real_url); ?></a>
                            <button type="button" class="button button-secondary copy-btn" data-url="<?php echo esc_attr($real_url); ?>">📋 Copiar</button>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($proxy_url); ?>" target="_blank"><?php echo esc_html($proxy_url); ?></a>
                            <button type="button" class="button button-secondary copy-btn" data-url="<?php echo esc_attr($proxy_url); ?>">📋 Copiar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma página ou post publicado encontrado.</p>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const url = this.getAttribute('data-url');
        navigator.clipboard.writeText(url).then(() => {
            const original = this.textContent;
            this.textContent = '✔ Copiado!';
            setTimeout(() => this.textContent = original, 2000);
        });
    });
});
</script>