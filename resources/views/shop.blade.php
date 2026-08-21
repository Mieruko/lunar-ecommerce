<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | SHOP.CO</title>
    <style>
        :root {
            --bg: #f5f1ee;
            --card: #ffffff;
            --text: #111111;
            --muted: rgba(17,17,17,0.7);
            --line: rgba(17,17,17,0.08);
            --soft: #f0ece7;
            --accent: #f7efe8;
            --dark: #111111;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }
        button, select { font: inherit; }
        .container { width: min(1240px, calc(100% - 30px)); margin: 0 auto; }
        .promo-bar { background: #000; color: #fff; text-align: center; padding: 12px 16px; font-size: 14px; }
        .promo-bar a { text-decoration: underline; font-weight: 700; }
        .topbar { background: rgba(245,241,238,0.96); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; justify-content: space-between; min-height: 78px; gap: 20px; }
        .brand { font-size: clamp(2rem, 2vw, 2.6rem); font-weight: 900; letter-spacing: -0.08em; }
        .nav-links { display: flex; gap: 28px; color: var(--muted); font-weight: 500; }
        .nav-actions { display: flex; align-items: center; gap: 12px; }
        .pill { display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.7); border: 1px solid var(--line); border-radius: 999px; height: 46px; padding: 0 18px; min-width: 100px; }
        .btn-dark { background: #000; color: #fff; border: none; border-radius: 999px; height: 48px; padding: 0 22px; font-weight: 700; }
        .shop-header { padding: 26px 0 18px; }
        .shop-title { font-size: clamp(2.4rem, 4vw, 4rem); letter-spacing: -0.08em; margin: 0 0 18px; }
        .shop-layout { display: grid; grid-template-columns: 280px 1fr; gap: 22px; }
        .filters { background: rgba(255,255,255,0.5); border: 1px solid var(--line); border-radius: 26px; padding: 20px; }
        .filter-group { padding: 18px 0; border-bottom: 1px solid var(--line); }
        .filter-group:last-child { border-bottom: none; }
        .filter-group h3 { margin: 0 0 12px; font-size: 1.1rem; }
        .tags { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 8px 12px; font-size: 14px; }
        .range { display: flex; align-items: center; justify-content: space-between; color: var(--muted); font-weight: 600; }
        .range input[type="range"] { width: 100%; margin-top: 12px; }
        .product-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
        .product-card { background: rgba(255,255,255,0.56); border: 1px solid var(--line); border-radius: 24px; overflow: hidden; }
        .product-visual { height: 280px; background: linear-gradient(135deg, #eadac8, #dec7b1); position: relative; }
        .product-visual.v2 { background: linear-gradient(135deg, #f7ecdf, #e9dfd6); }
        .product-visual.v3 { background: linear-gradient(135deg, #d6e6dc, #eef1e5); }
        .product-visual.v4 { background: linear-gradient(135deg, #efe2d8, #d0c3bd); }
        .product-visual.v5 { background: linear-gradient(135deg, #f2e8da, #d1d3de); }
        .product-visual.v6 { background: linear-gradient(135deg, #f0ede8, #d8d7d2); }
        .product-visual::before {
            content: "";
            position: absolute;
            width: 180px; height: 180px;
            background: rgba(255,255,255,0.38);
            border-radius: 38px;
            left: 50%; top: 52%;
            transform: translate(-50%, -50%) rotate(23deg);
        }
        .product-visual::after {
            content: "";
            position: absolute;
            inset: 58px 48px 26px 48px;
            border-radius: 26px 26px 36px 36px;
            background: linear-gradient(180deg, #fdf9f6 0%, #eed3b1 34%, #e8d6c8 100%);
            box-shadow: inset 0 0 0 1px rgba(17,17,17,0.08);
            transform: rotate(-12deg);
        }
        .product-info { padding: 18px 18px 22px; }
        .stars { color: #f0b03a; letter-spacing: 0.12em; font-size: 12px; }
        .name { margin: 12px 0 14px; font-weight: 700; font-size: 1.1rem; }
        .price-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .price { font-size: 1.7rem; font-weight: 800; letter-spacing: -0.06em; }
        .badge { background: #f7eee5; color: #a95c34; border-radius: 999px; padding: 8px 10px; font-size: 12px; font-weight: 700; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 0 18px; gap: 16px; }
        .sort { padding: 12px 16px; border: 1px solid var(--line); border-radius: 12px; background: rgba(255,255,255,0.7); }
        .pagination { display: flex; justify-content: center; gap: 10px; margin: 26px 0 56px; }
        .page { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--line); background: rgba(255,255,255,0.6); font-weight: 700; }
        .page.active { background: #000; color: #fff; }
        @media (max-width: 980px) {
            .shop-layout { grid-template-columns: 1fr; }
            .product-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 680px) {
            .nav-links { display: none; }
            .product-list { grid-template-columns: 1fr; }
            .toolbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="promo-bar">Sign up and get 20% off to your first order. <a href="#">Sign Up Now</a></div>

    <header class="topbar">
        <div class="container nav">
            <a href="{{ url('/') }}" class="brand">SHOP.CO</a>
            <nav class="nav-links">
                <a href="{{ url('/shop') }}">Shop</a>
                <a href="#">On Sale</a>
                <a href="#">New Arrivals</a>
                <a href="#">Brands</a>
            </nav>
            <div class="nav-actions">
                <span class="pill">Search</span>
                <a href="{{ url('/cart') }}" class="pill">Cart (2)</a>
                <button class="btn-dark">Shop now</button>
            </div>
        </div>
    </header>

    <main class="container shop-header">
        <h1 class="shop-title">SHOP</h1>

        <div class="toolbar">
            <div>Showing 1-8 of 20 products</div>
            <div class="sort">Sort by: Most Popular</div>
        </div>

        <div class="shop-layout">
            <aside class="filters">
                <div class="filter-group">
                    <h3>Categories</h3>
                    <div class="tags">
                        <span class="chip">Tops</span>
                        <span class="chip">Jeans</span>
                        <span class="chip">Dress</span>
                        <span class="chip">Accessories</span>
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Price</h3>
                    <div class="range">
                        <span>$10</span>
                        <span>$200</span>
                    </div>
                    <input type="range" min="10" max="200" value="120">
                </div>

                <div class="filter-group">
                    <h3>Colors</h3>
                    <div class="tags">
                        <span class="chip">Black</span>
                        <span class="chip">Beige</span>
                        <span class="chip">Blue</span>
                        <span class="chip">Green</span>
                    </div>
                </div>
            </aside>

            <section class="product-list">
                <article class="product-card">
                    <div class="product-visual v1"></div>
                    <div class="product-info">
                        <div class="stars">★★★★★</div>
                        <div class="name">T-shirt with Tape Details</div>
                        <div class="price-row">
                            <div class="price">$120</div>
                            <div class="badge">New</div>
                        </div>
                    </div>
                </article>

                <article class="product-card">
                    <div class="product-visual v2"></div>
                    <div class="product-info">
                        <div class="stars">★★★★★</div>
                        <div class="name">Skinny Fit Jeans</div>
                        <div class="price-row">
                            <div class="price">$260</div>
                            <div class="badge">Hot</div>
                        </div>
                    </div>
                </article>

                <article class="product-card">
                    <div class="product-visual v3"></div>
                    <div class="product-info">
                        <div class="stars">★★★★☆</div>
                        <div class="name">Chechered Shirt</div>
                        <div class="price-row">
                            <div class="price">$180</div>
                            <div class="badge">Sale</div>
                        </div>
                    </div>
                </article>

                <article class="product-card">
                    <div class="product-visual v4"></div>
                    <div class="product-info">
                        <div class="stars">★★★★★</div>
                        <div class="name">Loose Fit Bermuda Shorts</div>
                        <div class="price-row">
                            <div class="price">$80</div>
                            <div class="badge">New</div>
                        </div>
                    </div>
                </article>

                <article class="product-card">
                    <div class="product-visual v5"></div>
                    <div class="product-info">
                        <div class="stars">★★★★★</div>
                        <div class="name">Courage Graphic T-shirt</div>
                        <div class="price-row">
                            <div class="price">$145</div>
                            <div class="badge">Top</div>
                        </div>
                    </div>
                </article>

                <article class="product-card">
                    <div class="product-visual v6"></div>
                    <div class="product-info">
                        <div class="stars">★★★★★</div>
                        <div class="name">Faded Skinny Jeans</div>
                        <div class="price-row">
                            <div class="price">$210</div>
                            <div class="badge">Popular</div>
                        </div>
                    </div>
                </article>
            </section>
        </div>

        <div class="pagination">
            <span class="page active">1</span>
            <span class="page">2</span>
            <span class="page">3</span>
            <span class="page">→</span>
        </div>
    </main>
</body>
</html>
