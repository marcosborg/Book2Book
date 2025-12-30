<!doctype html>
<html lang="pt">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Book2Book - Troca de livros entre pessoas</title>
        <meta name="description" content="Book2Book conecta leitores para trocar livros com seguranca, perto de si.">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --ink: #0f141b;
                --paper: #f4f1ea;
                --sand: #efe2c9;
                --clay: #c76d3a;
                --ocean: #1c5d6f;
                --leaf: #0c7c59;
                --shadow: rgba(15, 20, 27, 0.12);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: "Space Grotesk", system-ui, sans-serif;
                color: var(--ink);
                background: radial-gradient(1200px 500px at 10% -10%, #f9e8d5 0%, transparent 60%),
                    radial-gradient(900px 600px at 90% 10%, #d5f0f2 0%, transparent 55%),
                    var(--paper);
                min-height: 100vh;
            }

            .container {
                width: min(1120px, 92vw);
                margin: 0 auto;
            }

            .nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 28px 0;
            }

            .logo {
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 700;
                letter-spacing: -0.02em;
            }

            .logo-mark {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                background: linear-gradient(135deg, var(--clay), #f1a26a);
                display: grid;
                place-items: center;
                color: #fff;
                font-weight: 700;
            }

            .nav a {
                color: inherit;
                text-decoration: none;
                font-weight: 500;
            }

            .nav-links {
                display: flex;
                gap: 22px;
                font-size: 15px;
            }

            .hero {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 40px;
                align-items: center;
                padding: 32px 0 64px;
            }

            .hero h1 {
                font-family: "Fraunces", serif;
                font-size: clamp(2.4rem, 4vw, 4.1rem);
                margin: 0 0 18px;
                line-height: 1.05;
            }

            .hero p {
                font-size: 1.05rem;
                line-height: 1.7;
                margin: 0 0 24px;
                color: #2a2f36;
            }

            .cta-group {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
            }

            .btn {
                padding: 12px 20px;
                border-radius: 999px;
                border: none;
                cursor: pointer;
                font-weight: 600;
                font-size: 15px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }

            .btn-primary {
                background: var(--ink);
                color: #fff;
                box-shadow: 0 18px 30px var(--shadow);
            }

            .btn-outline {
                background: transparent;
                color: var(--ink);
                border: 1px solid #b9b0a2;
            }

            .btn:hover {
                transform: translateY(-2px);
            }

            .hero-card {
                background: #fff;
                border-radius: 28px;
                padding: 28px;
                box-shadow: 0 30px 60px rgba(16, 24, 40, 0.16);
                position: relative;
                overflow: hidden;
            }

            .hero-card::after {
                content: "";
                position: absolute;
                width: 240px;
                height: 240px;
                right: -80px;
                top: -80px;
                background: radial-gradient(circle, rgba(199, 109, 58, 0.28), transparent 65%);
            }

            .hero-card h3 {
                margin: 0 0 12px;
                font-size: 18px;
            }

            .hero-card ul {
                list-style: none;
                padding: 0;
                margin: 0;
                display: grid;
                gap: 12px;
            }

            .pill {
                display: inline-flex;
                padding: 6px 12px;
                border-radius: 999px;
                background: #f7efe3;
                color: #5c3c28;
                font-size: 12px;
                font-weight: 600;
            }

            .section {
                padding: 64px 0;
            }

            .section h2 {
                font-family: "Fraunces", serif;
                margin: 0 0 16px;
                font-size: clamp(1.8rem, 3vw, 2.8rem);
            }

            .feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 18px;
            }

            .feature {
                background: #fff;
                border-radius: 20px;
                padding: 20px;
                box-shadow: 0 20px 40px rgba(15, 20, 27, 0.08);
                min-height: 180px;
            }

            .feature strong {
                display: block;
                margin-bottom: 10px;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 18px;
            }

            .stat {
                padding: 18px 20px;
                border-radius: 18px;
                background: linear-gradient(135deg, #0f141b, #1f2b36);
                color: #fff;
                box-shadow: 0 16px 30px rgba(0, 0, 0, 0.25);
            }

            .stat span {
                display: block;
                font-size: 12px;
                opacity: 0.7;
            }

            .timeline {
                display: grid;
                gap: 16px;
            }

            .timeline-item {
                padding: 18px 20px;
                border-radius: 18px;
                border: 1px solid #d9cbbb;
                background: #fffdf8;
            }

            .footer {
                padding: 40px 0 60px;
                font-size: 14px;
                color: #4a4f56;
            }

            .floating {
                animation: float 6s ease-in-out infinite;
            }

            .fade-up {
                animation: fadeUp 0.9s ease both;
            }

            .fade-delay-1 {
                animation-delay: 0.15s;
            }

            .fade-delay-2 {
                animation-delay: 0.3s;
            }

            @keyframes float {
                0% {
                    transform: translateY(0);
                }
                50% {
                    transform: translateY(-8px);
                }
                100% {
                    transform: translateY(0);
                }
            }

            @keyframes fadeUp {
                from {
                    opacity: 0;
                    transform: translateY(16px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @media (max-width: 720px) {
                .nav-links {
                    display: none;
                }

                .hero {
                    padding-top: 8px;
                }
            }
        </style>
    </head>
    <body>
        <header class="container nav">
            <div class="logo">
                <div class="logo-mark">B2B</div>
                <div>Book2Book</div>
            </div>
            <nav class="nav-links">
                <a href="#como-funciona">Como funciona</a>
                <a href="#seguranca">Seguranca</a>
                <a href="#mvp">MVP</a>
            </nav>
            <a class="btn btn-outline" href="/admin">Backoffice</a>
        </header>

        <main class="container">
            <section class="hero">
                <div class="fade-up">
                    <span class="pill">Trocas locais. Leitura sem fim.</span>
                    <h1>Troca livros com pessoas perto de ti. Simples, humano e seguro.</h1>
                    <p>Book2Book e uma plataforma feita para leitores que querem dar nova vida aos seus livros. Descobre titulos disponiveis, pede uma troca e conversa direto com o dono.</p>
                    <div class="cta-group">
                        <a class="btn btn-primary" href="/admin">Abrir dashboard</a>
                        <a class="btn btn-outline" href="#mvp">Ver MVP</a>
                    </div>
                </div>
                <div class="hero-card floating fade-up fade-delay-1">
                    <h3>O que o MVP entrega</h3>
                    <ul>
                        <li>Biblioteca pessoal com capa e disponibilidade.</li>
                        <li>Pesquisa por genero, lingua e distancia.</li>
                        <li>Pedidos de troca com estados claros.</li>
                        <li>Mensagens dentro do pedido.</li>
                        <li>Notificacoes no backoffice.</li>
                    </ul>
                </div>
            </section>

            <section id="como-funciona" class="section">
                <h2>Como funciona</h2>
                <div class="feature-grid">
                    <div class="feature fade-up">
                        <strong>1. Publica a tua estante</strong>
                        Adiciona livros, estado e disponibilidade. Uma capa por livro ja chega.
                    </div>
                    <div class="feature fade-up fade-delay-1">
                        <strong>2. Encontra perto de ti</strong>
                        Pesquisa por texto, genero ou lingua. Se tiveres coordenadas, ordena por distancia.
                    </div>
                    <div class="feature fade-up fade-delay-2">
                        <strong>3. Faz o pedido</strong>
                        So e possivel pedir livros disponiveis e de outros utilizadores. Sem spam.
                    </div>
                    <div class="feature fade-up">
                        <strong>4. Combina a troca</strong>
                        Conversa dentro do pedido e conclui quando o dono confirmar.
                    </div>
                </div>
            </section>

            <section id="seguranca" class="section">
                <h2>Mais seguranca, menos friccao</h2>
                <div class="stats">
                    <div class="stat fade-up">
                        <strong>Contas com bloqueio</strong>
                        <span>Admins podem bloquear abusos de forma rapida.</span>
                    </div>
                    <div class="stat fade-up fade-delay-1">
                        <strong>Historico de trocas</strong>
                        <span>Estados claros: pending, accepted, declined, cancelled, completed.</span>
                    </div>
                    <div class="stat fade-up fade-delay-2">
                        <strong>Notificacoes</strong>
                        <span>Database notifications para cada evento importante.</span>
                    </div>
                </div>
            </section>

            <section id="mvp" class="section">
                <h2>Roadmap imediato</h2>
                <div class="timeline">
                    <div class="timeline-item fade-up">
                        <strong>Agora</strong> API REST em /api/v1 + backoffice Filament com resources completos.
                    </div>
                    <div class="timeline-item fade-up fade-delay-1">
                        <strong>Proximo</strong> Push via FCM usando device tokens ja guardados.
                    </div>
                    <div class="timeline-item fade-up fade-delay-2">
                        <strong>Depois</strong> Melhorias nas avaliacoes e fotos multiplas por livro.
                    </div>
                </div>
            </section>
        </main>

        <footer class="container footer">
            Book2Book MVP. Backend Laravel + Filament prontos para a app Ionic.
        </footer>
    </body>
</html>
