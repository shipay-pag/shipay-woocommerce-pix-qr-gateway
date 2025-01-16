=== Pix e Bolepix por Shipay ===
Tags: woocommerce, payment, pix, boleto, bolepix, shipay
Stable tag: 1.0.0
Requires at least: 5.4
Requires PHP: 7.2
WC requires at least: 5.0
WC tested up to: 9.4
Tested up to: 6.7
Author: Shipay
Requires at least: 5.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Este plugin integra os métodos de pagamento Pix e Bolepix ao WooCommerce, permitindo que os lojistas aceitem pagamentos diretamente em suas lojas online.

== Descrição ==

O Plugin Oficial Shipay para WooCommerce permite que lojistas aceitem pagamentos via Pix e Bolepix diretamente em suas lojas WooCommerce, oferecendo uma solução de pagamento simples e segura para os clientes.

== Requisitos ==

- WooCommerce 5.0 ou superior
- PHP 7.2 ou superior
- Conta na Shipay com acesso às credenciais da API

== Instalação ==

1. Faça o upload do plugin para o diretório `wp-content/plugins` do seu WordPress.
2. Ative o plugin no painel de administração do WordPress, na seção "Plugins".
3. Acesse `WooCommerce > Configurações > Pagamentos` e ative o método de pagamento.

== Configuração Pix ==

Após a ativação, configure o plugin com suas credenciais da Shipay e outras opções específicas:

1. **Título**: Título que será exibido para o cliente durante o checkout.
2. **Ambiente**: Selecione o ambiente da API Shipay (produção ou homologação).
3. **Access Key**: Chave de acesso da API Shipay. As instruções para obter essa chave estão disponíveis [aqui](https://docs.shipay.com.br/setup.html).
4. **Secret Key**: Chave secreta da API Shipay.
5. **Client ID**: ID do cliente fornecido pela Shipay.
6. **Carteira**: Selecione a carteira desejada após preencher as credenciais.
7. **Instruções de Check-out**: Texto a ser exibido para o cliente na página de checkout.
8. **Instruções para Pedido Recebido**: Texto a ser exibido para o cliente após a finalização do pedido.
9. **Segundos de Expiração do Pix**: Tempo de expiração do Pix em segundos. Para pagamento instantâneo, deixe em branco.
10. **Status do Novo Pedido**: Selecione o status que o pedido deve assumir ao ser criado.
11. **Status do Pedido Após o Pagamento**: Selecione o status que o pedido deve assumir após a confirmação do pagamento.
12. **Status para Pedido não Pago**: Selecione o status para pedidos cujo pagamento expirou.
13. **Debug Log**: Ative esta opção para registrar logs de depuração. Os logs podem ser visualizados em `WooCommerce > Status do Sistema > Logs`.

== Configuração Bolepix ==

Após a ativação, configure o plugin com suas credenciais da Shipay e outras opções específicas:

1. **Título**: Título que será exibido para o cliente durante o checkout.
2. **Ambiente**: Selecione o ambiente da API Shipay (produção ou homologação).
3. **Access Key**: Chave de acesso da API Shipay. As instruções para obter essa chave estão disponíveis [aqui](https://docs.shipay.com.br/setup.html).
4. **Secret Key**: Chave secreta da API Shipay.
5. **Client ID**: ID do cliente fornecido pela Shipay.
6. **Carteira**: Selecione a carteira desejada após preencher as credenciais.
7. **Instruções de Check-out**: Texto a ser exibido para o cliente na página de checkout.
8. **Instruções para Pedido Recebido**: Texto a ser exibido para o cliente após a finalização do pedido.
9. **Tipo de Boleto Bancário**: Natureza do boleto a ser gerado.
10. **Dias para Expirar**: Quantidades de dias para expiração do boleto.
11. **Status do Novo Pedido**: Selecione o status que o pedido deve assumir ao ser criado.
12. **Status do Pedido Após o Pagamento**: Selecione o status que o pedido deve assumir após a confirmação do pagamento.
13. **Status para Pedido não Pago**: Selecione o status para pedidos cujo pagamento expirou.
14. **Debug Log**: Ative esta opção para registrar logs de depuração. Os logs podem ser visualizados em `WooCommerce > Status do Sistema > Logs`.

== Atualização do Pedido ==

O plugin utiliza webhook para garantir que o status dos pedidos seja atualizado em tempo real. No entanto, para maior segurança e confiabilidade, o plugin também realiza uma verificação automática a cada 10 minutos. Esta verificação busca identificar e atualizar o status de pedidos que ainda não foram marcados como aprovados, caso ocorra alguma falha no webhook. Dessa forma, é garantido que os status dos pedidos sejam mantidos corretamente, mesmo em situações de instabilidade na comunicação via webhook.

== Licença ==

Este plugin é licenciado sob a Licença GPLv3.