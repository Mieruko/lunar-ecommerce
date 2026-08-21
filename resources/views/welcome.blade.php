<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOP.CO</title>
    <style>
        :root {
            --bg: #f2f0ee;
            --bg-soft: #f7f6f4;
            --panel: #ffffff;
            --line: rgba(17, 17, 17, 0.08);
            --text: #111111;
            --muted: rgba(17, 17, 17, 0.6);
            --brand: #111111;
            --accent: #f0eeeb;
            --tag: #f7f2ec;
            --gold: #f4a249;
            --shadow: 0 18px 45px rgba(17, 17, 17, 0.08);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        button, input { font: inherit; }

        .promo-bar {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 14px;
            padding: 12px 20px;
        }

        .promo-bar a {
            font-weight: 700;
            text-decoration: underline;
        }

        .container {
            width: min(1240px, calc(100% - 32px));
            margin: 0 auto;
        }

        .topbar {
            background: rgba(242, 240, 238, 0.96);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(8px);
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 78px;
            gap: 20px;
        }

        .brand {
            font-weight: 900;
            letter-spacing: -0.08em;
            font-size: clamp(1.8rem, 2vw, 2.5rem);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
            color: var(--muted);
            font-size: 15px;
            flex-wrap: wrap;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-pill,
        .cart-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 46px;
            padding: 0 16px;
            background: rgba(255,255,255,0.7);
            border: 1px solid var(--line);
            border-radius: 999px;
            font-weight: 600;
            color: var(--text);
        }

        .cart-pill { min-width: 116px; }

        .cta-black {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 48px;
            padding: 0 24px;
            border: none;
            border-radius: 999px;
            background: #000;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .hero {
            padding: 44px 0 26px;
        }

        .hero-inner {
            background: linear-gradient(135deg, #f4efeb 0%, #f2eee9 100%);
            border-radius: 30px;
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            gap: 30px;
            align-items: center;
            padding: 44px 42px;
            box-shadow: var(--shadow);
        }

        .hero-copy {
            max-width: 600px;
        }

        .hero-copy h1 {
            margin: 18px 0 18px;
            font-size: clamp(3rem, 5vw, 5.2rem);
            line-height: 0.95;
            letter-spacing: -0.08em;
            font-weight: 900;
        }

        .hero-copy p {
            margin: 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
            max-width: 540px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 28px;
            flex-wrap: wrap;
        }

        .btn-dark,
        .btn-light {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 26px;
            border-radius: 999px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .btn-dark {
            background: #000;
            color: #fff;
        }

        .btn-light {
            background: rgba(255,255,255,0.35);
            border-color: rgba(17,17,17,0.14);
            color: var(--text);
        }

        .stats {
            display: flex;
            align-items: center;
            gap: 28px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .stat-box {
            min-width: 120px;
        }

        .stat-box strong {
            display: block;
            font-size: clamp(1.8rem, 2vw, 2.7rem);
            letter-spacing: -0.06em;
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .stat-box span {
            color: var(--muted);
            font-size: 14px;
        }

        .hero-art {
            position: relative;
            min-height: 520px;
            background: linear-gradient(135deg, #e8d4c5 0%, #da8b4b 20%, #7a452f 55%, #4a2b21 100%);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid rgba(17,17,17,0.08);
        }

        .hero-art::before {
            content: "";
            position: absolute;
            inset: 24px 30px 24px 30px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 26px;
        }

        .floating-tag {
            position: absolute;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 128px;
            height: 52px;
            padding: 0 18px;
            border-radius: 999px;
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(17,17,17,0.08);
            font-weight: 700;
            box-shadow: var(--shadow);
            z-index: 2;
        }

        .tag-left { top: 24px; left: 22px; }
        .tag-right { right: 18px; bottom: 20px; }

        .model-wrap {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: end;
            justify-content: center;
            z-index: 1;
        }

        .model {
            position: relative;
            width: 350px;
            height: 420px;
            margin-bottom: 20px;
        }

        .head {
            position: absolute;
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #f1c5a9;
            left: 50%;
            top: 32px;
            transform: translateX(-50%);
            box-shadow: inset 0 0 0 1px rgba(17,17,17,0.06);
        }

        .hair {
            position: absolute;
            width: 110px;
            height: 80px;
            background: #2b1b1b;
            border-radius: 50% 50% 42% 42%;
            left: 50%;
            top: 22px;
            transform: translateX(-50%);
        }

        .body {
            position: absolute;
            width: 200px;
            height: 180px;
            background: linear-gradient(135deg, #f8f4ee 0%, #f4dfbb 100%);
            border-radius: 30% 30% 25% 25% / 20% 20% 25% 25%;
            left: 50%;
            top: 100px;
            transform: translateX(-50%);
            box-shadow: inset 0 0 0 1px rgba(17,17,17,0.06);
        }

        .body::before,
        .body::after {
            content: "";
            position: absolute;
            background: rgba(255,255,255,0.7);
            border-radius: 18px;
            top: 40px;
        }

        .body::before {
            width: 60px;
            height: 90px;
            left: 28px;
            transform: rotate(15deg);
        }

        .body::after {
            width: 60px;
            height: 90px;
            right: 28px;
            transform: rotate(-15deg);
        }

        .pants {
            position: absolute;
            width: 150px;
            height: 160px;
            background: linear-gradient(180deg, #f6f3ef 0%, #e9d7c8 100%);
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%);
            border-radius: 18px 18px 22px 22px;
            box-shadow: inset 0 0 0 1px rgba(17,17,17,0.06);
        }

        .pants::before,
        .pants::after {
            content: "";
            position: absolute;
            width: 32px;
            height: 120px;
            background: linear-gradient(180deg, #f0c7a8 0%, #f8efe7 100%);
            bottom: -18px;
            border-radius: 20px;
        }

        .pants::before { left: 26px; }
        .pants::after { right: 26px; }

        .brand-strip {
            background: #000;
            color: rgba(255,255,255,0.9);
            padding: 18px 0;
        }

        .brand-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            align-items: center;
            gap: 20px;
            text-align: center;
            font-size: clamp(1.1rem, 2vw, 2.3rem);
            font-weight: 900;
            letter-spacing: -0.06em;
            color: rgba(255,255,255,0.8);
        }

        .section {
            padding: 42px 0 16px;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 22px;
        }

        .section-head h2 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3rem);
            letter-spacing: -0.08em;
            font-weight: 900;
            text-transform: uppercase;
        }

        .view-all {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border-radius: 999px;
            border: 1px solid rgba(17,17,17,0.14);
            background: rgba(255,255,255,0.5);
            font-weight: 700;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .product-card {
            background: rgba(255,255,255,0.65);
            border: 1px solid var(--line);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 22px rgba(17,17,17,0.04);
        }

        .product-visual {
            position: relative;
            height: 255px;
            background: linear-gradient(135deg, #f4efe8 0%, #e2d5c8 100%);
            overflow: hidden;
        }

        .product-visual.v1 { background: linear-gradient(135deg, #f4e8d5 0%, #f4c194 100%); }
        .product-visual.v2 { background: linear-gradient(135deg, #f6e8e1 0%, #e1d3ce 100%); }
        .product-visual.v3 { background: linear-gradient(135deg, #f4f2ef 0%, #dbe5d7 100%); }
        .product-visual.v4 { background: linear-gradient(135deg, #f6ece1 0%, #e3d4c4 100%); }

        .product-visual::before {
            content: "";
            position: absolute;
            width: 170px;
            height: 170px;
            border-radius: 40px;
            left: 50%;
            top: 46%;
            transform: translate(-50%, -50%) rotate(28deg);
            background: rgba(255,255,255,0.38);
        }

        .product-visual::after {
            content: "";
            position: absolute;
            inset: 60px 48px 34px 48px;
            border-radius: 26px 26px 36px 36px;
            background: linear-gradient(180deg, #f8f3f0 0%, #f5d8bf 40%, #e4d2c5 100%);
            box-shadow: inset 0 0 0 1px rgba(17,17,17,0.06);
            transform: rotate(-10deg);
        }

        .product-info {
            padding: 16px 18px 20px;
        }

        .stars { color: #f2b12d; letter-spacing: 0.12em; font-size: 13px; }

        .product-name {
            margin: 10px 0 14px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.04em;
        }

        .product-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .price {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.06em;
        }

        .tag {
            background: #f6eee8;
            color: #ac5c34;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .dress-style-wrap {
            padding: 48px 0 20px;
        }

        .dress-style-box {
            background: #f0f0f0;
            border-radius: 36px;
            padding: 34px 28px 30px;
        }

        .dress-style-grid {
            display: grid;
            grid-template-columns: 1fr 1.55fr;
            gap: 18px;
            margin-top: 22px;
        }

        .dress-style-grid > div {
            display: grid;
            gap: 18px;
        }

        .style-card {
            position: relative;
            min-height: 200px;
            border-radius: 26px;
            overflow: hidden;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: end;
            padding: 16px 18px;
            color: white;
            font-size: 2rem;
            letter-spacing: -0.06em;
            font-weight: 800;
            box-shadow: inset 0 -120px 80px rgba(0,0,0,0.32);
        }

        .style-card:nth-child(1) { background-image: linear-gradient(135deg, rgba(17,17,17,0.1), rgba(17,17,17,0.15)), url('https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=900&q=80'); }
        .style-card:nth-child(2) { background-image: linear-gradient(135deg, rgba(17,17,17,0.08), rgba(17,17,17,0.2)), url('https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1200&q=80'); }
        .style-card:nth-child(3) { background-image: linear-gradient(135deg, rgba(17,17,17,0.12), rgba(17,17,17,0.18)), url('https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1200&q=80'); }
        .style-card:nth-child(4) { background-image: linear-gradient(135deg, rgba(17,17,17,0.08), rgba(17,17,17,0.2)), url('https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&q=80'); }

        .review-wrap {
            padding: 26px 0 60px;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .review-card {
            background: rgba(255,255,255,0.7);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 20px;
        }

        .review-card .stars { margin-bottom: 10px; }
        .review-card p {
            margin: 0 0 16px;
            color: var(--muted);
            line-height: 1.7;
        }

        .reviewer {
            font-weight: 700;
        }

        .newsletter {
            padding: 0 0 54px;
        }

        .newsletter-box {
            background: linear-gradient(180deg, rgba(255,255,255,0.8), rgba(250,240,232,0.8));
            border: 1px solid var(--line);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 28px 30px;
        }

        .newsletter-box h3 {
            margin: 0 0 8px;
            font-size: clamp(2rem, 3vw, 3rem);
            letter-spacing: -0.06em;
        }

        .newsletter-box p {
            margin: 0;
            color: var(--muted);
        }

        .email-box {
            display: flex;
            align-items: center;
            gap: 12px;
            width: min(520px, 100%);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 8px 8px 18px;
        }

        .email-box input {
            border: none;
            background: transparent;
            outline: none;
            flex: 1;
            min-width: 0;
            font-size: 15px;
        }

        .email-box button {
            min-height: 46px;
            padding: 0 18px;
            border: none;
            border-radius: 999px;
            background: #000;
            color: white;
            font-weight: 700;
        }

        footer {
            background: rgba(255,255,255,0.4);
            border-top: 1px solid rgba(17,17,17,0.08);
            padding: 28px 0 40px;
        }

        .footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            color: var(--muted);
        }

        @media (max-width: 980px) {
            .hero-inner {
                grid-template-columns: 1fr;
                padding: 28px 22px;
            }

            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dress-style-grid,
            .review-grid,
            .newsletter-box {
                grid-template-columns: 1fr;
                display: grid;
            }
        }

        @media (max-width: 760px) {
            .nav-links {
                display: none;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

            .brand-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .section-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .newsletter-box {
                padding: 22px 18px;
            }

            .email-box {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>
<body>
    <div class="promo-bar">
        Sign up and get 20% off to your first order. <a href="#">Sign Up Now</a>
    </div>

    <header class="topbar">
        <div class="container nav">
            <div class="brand">SHOP.CO</div>
            <nav class="nav-links">
                <a href="#">Shop</a>
                <a href="#">On Sale</a>
                <a href="#">New Arrivals</a>
                <a href="#">Brands</a>
            </nav>
            <div class="nav-actions">
                <span class="search-pill">Search</span>
                <span class="cart-pill">Cart (2)</span>
                <button class="cta-black">Shop now</button>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-inner">
                <div class="hero-copy">
                    <h1>FIND CLOTHES THAT MATCHES YOUR STYLE</h1>
                    <p>
                        Browse through our diverse range of meticulously crafted garments,
                        designed to bring out your individuality and cater to your sense of style.
                    </p>

                    <div class="hero-actions">
                        <a href="#" class="btn-dark">Shop now</a>
                        <a href="#" class="btn-light">View all products</a>
                    </div>

                    <div class="stats">
                        <div class="stat-box">
                            <strong>200+</strong>
                            <span>International Brands</span>
                        </div>
                        <div class="stat-box">
                            <strong>2000+</strong>
                            <span>High-quality Products</span>
                        </div>
                        <div class="stat-box">
                            <strong>30000+</strong>
                            <span>Happy Customers</span>
                        </div>
                    </div>
                </div>

                <div class="hero-art">
                    <div class="floating-tag tag-left">New Season</div>
                    <div class="floating-tag tag-right">From $29</div>

                    <div class="model-wrap">
                        <div class="model">
                            <div class="hair"></div>
                            <div class="head"></div>
                            <div class="body"></div>
                            <div class="pants"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="brand-strip">
            <div class="container brand-row">
                <span>VERSACE</span>
                <span>ZARA</span>
                <span>GUCCI</span>
                <span>PRADA</span>
                <span>CALVIN KLEIN</span>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="section-head">
                    <h2>NEW ARRIVALS</h2>
                    <a href="#" class="view-all">View All</a>
                </div>

                <div class="product-grid">
                    <article class="product-card">
                        <div class="product-visual v1"></div>
                        <div class="product-info">
                            <div class="stars">★★★★★</div>
                            <div class="product-name">T-shirt with Tape Details</div>
                            <div class="product-meta">
                                <div class="price">$120</div>
                                <div class="tag">New</div>
                            </div>
                        </div>
                    </article>

                    <article class="product-card">
                        <div class="product-visual v2"></div>
                        <div class="product-info">
                            <div class="stars">★★★★☆</div>
                            <div class="product-name">Skinny Fit Jeans</div>
                            <div class="product-meta">
                                <div class="price">$260</div>
                                <div class="tag">20% off</div>
                            </div>
                        </div>
                    </article>

                    <article class="product-card">
                        <div class="product-visual v3"></div>
                        <div class="product-info">
                            <div class="stars">★★★★★</div>
                            <div class="product-name">Chechered Shirt</div>
                            <div class="product-meta">
                                <div class="price">$180</div>
                                <div class="tag">Hot</div>
                            </div>
                        </div>
                    </article>

                    <article class="product-card">
                        <div class="product-visual v4"></div>
                        <div class="product-info">
                            <div class="stars">★★★★★</div>
                            <div class="product-name">Striped T-shirt</div>
                            <div class="product-meta">
                                <div class="price">$160</div>
                                <div class="tag">30% off</div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="section-head">
                    <h2>Top Selling</h2>
                    <a href="#" class="view-all">View All</a>
                </div>

                <div class="product-grid">
                    <article class="product-card">
                        <div class="product-visual v2"></div>
                        <div class="product-info">
                            <div class="stars">★★★★★</div>
                            <div class="product-name">Vertical Striped Shirt</div>
                            <div class="product-meta">
                                <div class="price">$232</div>
                                <div class="tag">Sale</div>
                            </div>
                        </div>
                    </article>

                    <article class="product-card">
                        <div class="product-visual v1"></div>
                        <div class="product-info">
                            <div class="stars">★★★★☆</div>
                            <div class="product-name">Courage Graphic T-shirt</div>
                            <div class="product-meta">
                                <div class="price">$145</div>
                                <div class="tag">Popular</div>
                            </div>
                        </div>
                    </article>

                    <article class="product-card">
                        <div class="product-visual v4"></div>
                        <div class="product-info">
                            <div class="stars">★★★★☆</div>
                            <div class="product-name">Loose Fit Bermuda Shorts</div>
                            <div class="product-meta">
                                <div class="price">$80</div>
                                <div class="tag">New</div>
                            </div>
                        </div>
                    </article>

                    <article class="product-card">
                        <div class="product-visual v3"></div>
                        <div class="product-info">
                            <div class="stars">★★★★★</div>
                            <div class="product-name">Faded Skinny Jeans</div>
                            <div class="product-meta">
                                <div class="price">$210</div>
                                <div class="tag">Top</div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="dress-style-wrap">
            <div class="container dress-style-box">
                <div class="section-head" style="margin-bottom: 12px;">
                    <h2>BROWSE BY dress STYLE</h2>
                </div>
                <div class="dress-style-grid">
                    <div>
                        <div class="style-card">Casual</div>
                        <div class="style-card">Gym</div>
                    </div>
                    <div>
                        <div class="style-card">Formal</div>
                        <div class="style-card">Party</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="review-wrap">
            <div class="container">
                <div class="section-head">
                    <h2>OUR HAPPY CUSTOMERS</h2>
                </div>
                <div class="review-grid">
                    <article class="review-card">
                        <div class="stars">★★★★★</div>
                        <p>“Finding clothes that align with my personal style used to be a challenge until I discovered SHOP.CO.”</p>
                        <div class="reviewer">Sarah M.</div>
                    </article>

                    <article class="review-card">
                        <div class="stars">★★★★★</div>
                        <p>“I’m blown away by the quality and style of the clothes I received. Every piece exceeded my expectations.”</p>
                        <div class="reviewer">Alex K.</div>
                    </article>

                    <article class="review-card">
                        <div class="stars">★★★★★</div>
                        <p>“This is one of the rare brands that balance comfort, craftsmanship, and modern design perfectly.”</p>
                        <div class="reviewer">Olivia P.</div>
                    </article>
                </div>
            </div>
        </section>

        <section class="newsletter">
            <div class="container newsletter-box">
                <div>
                    <h3>STAY UP TO DATE ABOUT</h3>
                    <p>Latest drops, sales, and exclusive offers.</p>
                </div>
                <div class="email-box">
                    <input type="email" placeholder="Enter your email address" aria-label="Email address">
                    <button type="button">Subscribe</button>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-row">
            <div class="brand">SHOP.CO</div>
            <div>© 2026 Shop.co</div>
            <div>Privacy Policy · Terms · Shipping</div>
        </div>
    </footer>
</body>
</html>
