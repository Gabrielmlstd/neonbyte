# Neonbyte

**Uma biblioteca comunitária de software open source, com foco em engenharia reversa.**
Descubra, compartilhe e acompanhe projetos como disassemblers, debuggers, decompiladores, editores hexadecimais e frameworks de análise binária, além de jogos e ferramentas open source.

**Site no ar:** https://neonbyte.freehosting.dev · **Licença:** MIT · **Tecnologias:** PHP 8 + MySQL/MariaDB + JS/CSS puro

[English version](README.md)

![Página inicial do Neonbyte](docs/screenshots/home.png)

## O que é o Neonbyte?

O Neonbyte é um catálogo onde as pessoas publicam projetos open source que criaram ou que simplesmente gostam, e onde qualquer um pode encontrá-los. Cada projeto tem uma página com descrição, licença, plataformas, capturas de tela, vídeo opcional e links para o repositório e a página de download **oficiais**.

Ele nasceu como uma biblioteca para **novos projetos de engenharia reversa (RE)**, porque boas ferramentas de RE ficam espalhadas pelo GitHub, GitLab e sites pessoais e é difícil acompanhá-las. Também lista jogos e programas open source em geral, para que um só lugar responda "o que há de novo e vale testar?".

![Categoria Reverse Engineering](docs/screenshots/explore-reverse-engineering.png)

### O que ele não faz

- **Nunca hospeda nem distribui software.** Os botões de download levam à página oficial de cada projeto. O visitante é avisado para pesquisar tudo antes e que qualquer download é de sua responsabilidade.
- **Só lista projetos open source.** Nada de cracks, keygens, código vazado, burla de DRM ou malware. Veja a [política de conteúdo](docs/CONTENT_POLICY.md) (em inglês).

## Funcionalidades

- Página inicial com banner dos projetos mais curtidos e faixas como *Trending*, *New releases* e *Needs contributors*
- Explorar com busca, filtros (categoria, licença, plataforma, "precisa de ajuda") e ordenação
- Categorias, incluindo **Reverse Engineering**, Games, Dev Tools, Multimedia, Security e Tools
- Páginas de projeto com capturas (envio ou link), vídeo, avaliações e comentários
- Curtidas, favoritos e uma biblioteca pessoal na barra lateral
- Notificações quando alguém curte ou comenta seu projeto
- Painel do desenvolvedor com visualizações e cliques no link de download
- Botão de denúncia em cada projeto e painel admin para projetos, usuários e denúncias
- Cinco temas de cor escolhidos pelo visitante (Neon Blue, Matrix Green, Synthwave, Amber Terminal, Blood Red)
- Espaços de anúncio reservados (nenhuma rede vem incluída), banner de cookies e páginas de Termos, Privacidade, Cookies e Remoção de conteúdo
- Layout responsivo com menu lateral recolhível; respeita `prefers-reduced-motion`

## Privacidade e segurança desde o projeto

O Neonbyte foi feito para que um vazamento do banco de dados revele o mínimo possível sobre quem o usa.

- **E-mails são criptografados** com AES-256-GCM. O login usa um "índice cego" HMAC-SHA256, então nenhum endereço fica guardado de forma legível.
- **Endereços IP não são guardados.** Só hashes HMAC, por pouco tempo, para limitar tentativas de login.
- Senhas usam `password_hash()` (bcrypt). As telas de admin mostram só e-mails mascarados (`a***@e***.com`).
- Prepared statements, escape de saída, tokens CSRF, cookies de sessão estritos e limite de tentativas de login.
- Uploads validados por tipo e tamanho, renomeados e servidos de uma pasta sem execução de scripts.
- Cabeçalhos de segurança e Content-Security-Policy no `.htaccess`.
- **Este repositório não contém segredos nem dados de usuários.** Chaves e credenciais vêm do `config.local.php` (ignorado pelo git) ou de variáveis de ambiente.

Detalhes e lista de reforço de segurança: [SECURITY.md](SECURITY.md) (em inglês).

## Início rápido (local, XAMPP ou qualquer hospedagem PHP)

Requisitos: PHP 8.1+ com `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`; MySQL ou MariaDB; Apache com `mod_headers` recomendado.

```bash
git clone https://github.com/<seu-usuario>/neonbyte.git
cd neonbyte

# 1. crie o banco e as tabelas
mysql -u root -p < database/schema.sql

# 2. (opcional) carregue um catálogo de demonstração: 27 projetos open source reais, sem contas
mysql -u root -p < database/demo_seed.sql

# 3. crie sua conta de administrador (não existe admin padrão)
php tools/create_admin.php "Seu Nome" voce@exemplo.com
```

No XAMPP, copie a pasta para `htdocs/neonbyte`. Em desenvolvimento, uma chave de criptografia aleatória é criada em `.dev-key` (ignorada pelo git).
Para produção, veja [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) (em inglês).

## Configuração

Defina os valores em `config.local.php` (copie `config.local.example.php`) ou em variáveis de ambiente: `APP_ENV`, `SITE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `NEONBYTE_KEY` e os e-mails das páginas legais. Gere a chave com `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`, **guarde um backup dela** e nunca a envie ao git. Para trocar a chave depois, use `tools/rotate_key.php`.

## Ainda não existe

Verificação de e-mail, recuperação de senha, login em dois fatores, coleções com nome, integração com releases do GitHub/GitLab e traduções. Contribuições são bem-vindas: veja o [CONTRIBUTING.md](CONTRIBUTING.md).

## Aviso

Os projetos são enviados pela comunidade. O Neonbyte não audita, hospeda nem endossa nenhum deles. Confira sempre a licença e a origem antes de baixar ou executar algo, principalmente binários e ferramentas que analisam ou modificam outros programas.

## Licença

[MIT](LICENSE). Nomes, logos e capturas de tela exibidos nas páginas de projeto pertencem a seus respectivos donos.
