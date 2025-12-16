/**
 * Verificador de atualização de planos em tempo real
 */
jQuery(document).ready(function($) {
    let lastPlanCheck = 0;
    const CHECK_INTERVAL = 300000; // 5 minutos (300.000 ms)
    let lastKnownPlanId = null;

    function checkPlanUpdate() {
        const now = Date.now();
        
        // Evita verificações muito rápidas
        if (now - lastPlanCheck < CHECK_INTERVAL) {
            return;
        }

        lastPlanCheck = now;

        $.ajax({
            url: limiter_mkp_pro_public.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_mkp_pro_check_plan_update',
                nonce: limiter_mkp_pro_public.nonce
            },
            success: function(response) {
                if (response.success) {
                    updatePlanDisplay(response.data);
                }
            },
            error: function() {
                console.log('Erro ao verificar atualização de plano');
            }
        });
    }

    function updatePlanDisplay(data) {
        // Atualiza o widget do dashboard se existir
        const $planElement = $('.limiter-mkp-pro-current-plan');
        if ($planElement.length) {
            $planElement.text('Plano: ' + data.current_plan);
        }

        // Mostra notificação se o plano mudou
        if (lastKnownPlanId !== null && lastKnownPlanId !== data.current_plan_id) {
            showPlanUpdateNotification(data.current_plan);
        }

        lastKnownPlanId = data.current_plan_id;
    }

    function showPlanUpdateNotification(newPlanName) {
        // Cria notificação elegante
        $('body').append(`
            <div class="limiter-plan-update-notice" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4CAF50;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 999999;
                animation: slideInRight 0.5s ease;
            ">
                <strong>🎉 Plano Atualizado!</strong>
                <p>Seu plano foi alterado para: ${newPlanName}</p>
                <small>Você já pode usar todos os recursos do novo plano.</small>
            </div>
        `);

        // Remove após 5 segundos
        setTimeout(function() {
            $('.limiter-plan-update-notice').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Inicia verificações periódicas
    setInterval(checkPlanUpdate, CHECK_INTERVAL);
    
    // Verifica também em eventos específicos
    $(document).on('click', '.limiter-check-plan-update', checkPlanUpdate);

    // Verificação inicial
    checkPlanUpdate();
});