# Lista de Testes para o Plugin "Limiter MKP Pro"

## Testes de Instalação
- [ ] Verificar se o plugin ativa corretamente em ambiente WordPress Multisite 6.8.1
- [ ] Confirmar criação das tabelas no banco de dados
- [ ] Verificar inserção dos planos padrão (Starter, Pro, Master)
- [ ] Confirmar criação das opções de rede

## Testes de Limitação de Páginas/Posts
- [ ] Verificar se o plugin bloqueia corretamente a criação de páginas quando o limite é atingido
- [ ] Confirmar que o plugin considera tanto páginas publicadas quanto na lixeira
- [ ] Testar restauração de itens da lixeira quando o limite já foi atingido
- [ ] Verificar se a mensagem personalizada é exibida corretamente ao atingir o limite

## Testes do Painel de Super Admin
- [ ] Verificar acesso ao menu "Limiter MKP Pro" no painel de administração da rede
- [ ] Confirmar exibição correta do dashboard com estatísticas
- [ ] Testar CRUD de planos (criar, editar, excluir)
- [ ] Testar associação de subdomínios a planos
- [ ] Verificar adição de subdomínios ao sistema (individual e em massa)
- [ ] Confirmar visualização e processamento de solicitações de mudança de plano
- [ ] Testar configurações gerais
- [ ] Verificar visualização de logs

## Testes do Widget e Dashboard para Subdomínios
- [ ] Confirmar exibição do widget no dashboard dos subdomínios
- [ ] Verificar se o logo do Marketing Place Store aparece corretamente
- [ ] Testar exibição de informações do plano atual
- [ ] Confirmar exibição correta do limite de páginas/posts
- [ ] Verificar contagem correta de páginas utilizadas e restantes
- [ ] Testar solicitação de mudança de plano
- [ ] Verificar cancelamento de solicitação pendente
- [ ] Testar dashboard visual completo
- [ ] Confirmar responsividade em diferentes tamanhos de tela

## Testes do Sistema de Solicitações
- [ ] Verificar criação de solicitação de mudança de plano
- [ ] Confirmar envio de e-mail de notificação
- [ ] Testar aprovação de solicitação pelo super admin
- [ ] Verificar rejeição de solicitação pelo super admin
- [ ] Confirmar cancelamento de solicitação pelo subdomínio
- [ ] Verificar atualização automática do widget após aprovação
- [ ] Testar limpeza automática de solicitações antigas

## Testes do Sistema de Notificações
- [ ] Verificar alertas quando um subdomínio está próximo do limite
- [ ] Confirmar exibição da mensagem personalizada ao atingir o limite
- [ ] Testar envio de e-mails para notificar sobre solicitações
- [ ] Verificar envio para alterar-plano@marketing-place.store
- [ ] Confirmar envio para todos os super administradores

## Testes de Segurança
- [ ] Verificar verificações de nonce em todas as operações
- [ ] Confirmar verificações de permissões
- [ ] Testar sanitização de dados de entrada
- [ ] Verificar escape de saída de dados

## Testes de Compatibilidade
- [ ] Verificar compatibilidade com PHP 7.4+
- [ ] Confirmar compatibilidade com MySQL 5.7+
- [ ] Testar em diferentes navegadores (Chrome, Firefox, Safari)
- [ ] Verificar responsividade em dispositivos móveis

## Testes de Desempenho
- [ ] Verificar impacto no tempo de carregamento das páginas
- [ ] Confirmar otimização de consultas ao banco de dados
- [ ] Testar com grande número de subdomínios

## Testes de Desativação/Reativação
- [ ] Verificar comportamento ao desativar o plugin
- [ ] Confirmar preservação de dados ao reativar
