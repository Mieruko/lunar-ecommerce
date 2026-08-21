<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | SHOP.CO</title>
    <style>
        :root {
            --bg: #f5f1ee;
            --card: #fff;
            --text: #111111;
            --muted: rgba(17,17,17,0.68);
            --line: rgba(17,17,17,0.08);
            --soft: #f4efe9;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--text); }
        a { text-decoration: none; color: inherit; }
        button { font: inherit; }
        .container { width: min(1200px, calc(100% - 30px)); margin: 0 auto; }
        .promo-bar { background: #000; color: #fff; text-align: center; padding: 12px 16px; font-size: 14px; }
        .promo-bar a { text-decoration: underline; font-weight: 700; }
        .topbar { background: rgba(245,241,238,0.96); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; justify-content: space-between; min-height: 78px; gap: 20px; }
        .brand { font-size: clamp(2rem, 2vw, 2.6rem); font-weight: 900; letter-spacing: -0.08em; }
        .nav-links { display: flex; gap: 28px; color: var(--muted); }
        .nav-actions { display: flex; align-items: center; gap: 12px; }
        .pill { display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 999px; background: rgba(255,255,255,0.7); height: 46px; padding: 0 18px; }
        .btn-dark { background: #000; color: white; border: none; border-radius: 999px; height: 48px; padding: 0 22px; font-weight: 700; }
        .page { padding: 36px 0 60px; }
        .title { margin: 0 0 24px; font-size: clamp(2.4rem, 4vw, 4rem); letter-spacing: -0.08em; }
        .cart-layout { display: grid; grid-template-columns: 1.5fr 0.8fr; gap: 24px; }
        .card { background: rgba(255,255,255,0.56); border: 1px solid var(--line); border-radius: 26px; }
        .items { padding: 18px; }
        .item { display: grid; grid-template-columns: 120px 1fr auto; gap: 18px; align-items: center; padding: 18px 0; border-bottom: 1px solid var(--line); }
        .item:last-child { border-bottom: none; }
        .thumb { height: 120px; border-radius: 18px; background: linear-gradient(135deg, #f5e7db, #e7d0b5); position: relative; overflow: hidden; }
        .thumb::before { content: ""; position: absolute; inset: 20px 18px 22px 18px; border-radius: 14px; background: rgba(255,255,255,0.45); transform: rotate(-10deg); }
        .item-info h3 { margin: 0 0 8px; font-size: 1.3rem; }
        .muted { color: var(--muted); }
        .qty { display: inline-flex; align-items: center; border: 1px solid var(--line); border-radius: 999px; padding: 8px 10px; gap: 14px; background: rgba(255,255,255,0.7); }
        .qty button { background: transparent; border: none; width: 20px; height: 20px; font-size: 1.2rem; cursor: pointer; }
        .price-tag { font-size: 1.5rem; font-weight: 800; }
        .summary { padding: 22px; }
        .summary h3 { margin: 0 0 18px; font-size: 1.7rem; }
        .line { display: flex; justify-content: space-between; padding: 10px 0; color: var(--muted); }
        .line.total { font-size: 1.6rem; color: var(--text); font-weight: 800; margin-top: 12px; }
        .checkout { display: block; width: 100%; background: #000; color: #fff; border: none; border-radius: 999px; height: 52px; margin-top: 18px; font-weight: 700; }
        @media (max-width: 900px) {
            .cart-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 680px) {
            .nav-links { display: none; }
            .item { grid-template-columns: 90px 1fr; }
            .price-tag { grid-column: 2; }
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
                <a href="{{ url('/shop') }}" class="pill">Shop</a>
                <span class="pill">Cart (2)</span>
            </div>
        </div>
    </header>

    <main class="page container">
        <h1 class="title">Your Cart</h1>

        <div class="cart-layout">
            <section class="card items">
                <div class="item">
                    <div class="thumb"></div>
                    <div class="item-info">
                        <h3>Gradient Graphic T-shirt</h3>
                        <div class="muted">Size: Large</div>
                        <div class="muted">Color: Black</div>
                    </div>
                    <div>
                        <div class="qty"><button>-</button><span>1</span><button>+</button></div>
                        <div class="price-tag" style="margin-top: 12px;">$145</div>
                    </div>
                </div>

                <div class="item">
                    <div class="thumb" style="background: linear-gradient(135deg, #e4ddd1, #cfbda9);"></div>
                    <div class="item-info">
                        <h3>Skinny Fit Jeans</h3>
                        <div class="muted">Size: Medium</div>
                        <div class="muted">Color: Blue</div>
                    </div>
                    <div>
                        <div class="qty"><button>-</button><span>1</span><button>+</button></div>
                        <div class="price-tag" style="margin-top: 12px;">$200</div>
                    </div>
                </div>
            </section>

            <aside class="card summary">
                <h3>Order Summary</h3>
                <div class="line"><span>Subtotal</span><span>$345</span></div>
                <div class="line"><span>Discount</span><span>-$25</span></div>
                <div class="line"><span>Delivery</span><span>$10</span></div>
                <div class="line total"><span>Total</span><span>$330</span></div>
                <button class="checkout">Proceed to Checkout</button>
            </aside>
        </div>
    </main>
</body>
</html>
