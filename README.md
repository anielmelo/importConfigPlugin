# Plugin ImportConfig para OJS

## Descrição

O plugin **ImportConfig** permite importar configurações do portal e outras revistas para uma revista específica. Isso inclui configurações de plugins, tema, barra lateral, folha de estilo e menus de navegação.

## Instalação

1. Clone o repositório e coloque-o no diretório de plugins genéricos (`plugins/generic/`) do OJS.
2. Altere o nome da pasta do plugin para `importConfig`.
3. No painel de administração do OJS, vá até a seção de plugins genéricos e ative o plugin.

## Uso

Após a ativação, você pode configurar o plugin:

1. Navegue até a página de configuração do plugin.
2. Leia as informações do formulário, selecione o contexto de importação e clique em "Salvar".

## Importação

O plugin permite a importação das configurações:
- **Plugins Customizados**: `customblockmanagerplugin`, `customheaderplugin`, `defaultchildthemeplugin`.
- **Configurações**: `plugin_theme`, `style_sheet`, `sidebar`, `navigation_menu`.
