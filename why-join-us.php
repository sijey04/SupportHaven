<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Why Join Us - SupportHaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            font-family: -apple-system, system-ui, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }
        
        .header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .hero {
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/hiring/01.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .benefits {
            padding: 60px 0;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .benefit-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
        }
        
        .icon {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #007bff;
        }
        
        h1 {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        h2 {
            font-size: 2.5em;
            text-align: center;
            margin-bottom: 40px;
        }
        
        h3 {
            font-size: 1.5em;
            margin-bottom: 15px;
        }
        
        p {
            line-height: 1.6;
            color: #666;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        
        .cta-button:hover {
            background-color: #0056b3;
        }
        
        @media (max-width: 768px) {
            .benefits-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        
        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #f3f4f6;
            border-radius: 0.5rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background-color: #e5e7eb;
            color: #111827;
        }
        
        .back-button i {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-container">
                <a href="index.html">
                    <img src="images/logo.png" alt="SupportHaven Logo" style="height: 40px;">
                </a>
                <a href="hiring.php" class="back-button">
                    <i class="fas fa-chevron-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1 class="animate__animated animate__fadeInDown">Why Join SupportHaven?</h1>
            <p class="animate__animated animate__fadeInUp">Discover the benefits of being part of our growing tech support community</p>
        </div>
    </section>

    <section class="benefits">
        <div class="container">
            <h2>Benefits of Joining Our Team</h2>
            <div class="benefits-grid">
                <div class="benefit-card animate__animated animate__fadeInUp">
                    <div class="icon">💰</div>
                    <h3>Competitive Earnings</h3>
                    <p>Earn up to ₱2000 per day based on your skills and customer ratings. Set your own rates for specialized services.</p>
                </div>
                
                <div class="benefit-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                    <div class="icon">🕒</div>
                    <h3>Flexible Schedule</h3>
                    <p>Work when you want, where you want. Choose jobs that fit your schedule and maintain work-life balance.</p>
                </div>
                
                <div class="benefit-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                    <div class="icon">📈</div>
                    <h3>Career Growth</h3>
                    <p>Access training resources, earn certifications, and build your professional portfolio with real-world experience.</p>
                </div>
                
                <div class="benefit-card animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
                    <div class="icon">🤝</div>
                    <h3>Supportive Community</h3>
                    <p>Join a network of skilled professionals. Share knowledge and grow together with peer support.</p>
                </div>
                
                <div class="benefit-card animate__animated animate__fadeInUp" style="animation-delay: 0.8s">
                    <div class="icon">🛡️</div>
                    <h3>Job Security</h3>
                    <p>Steady stream of work opportunities with our growing customer base and increasing demand for tech services.</p>
                </div>
                
                <div class="benefit-card animate__animated animate__fadeInUp" style="animation-delay: 1s">
                    <div class="icon">🎯</div>
                    <h3>Performance Rewards</h3>
                    <p>Earn bonuses and rewards for maintaining high customer satisfaction ratings and completing more jobs.</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="apply.php" class="cta-button animate__animated animate__pulse animate__infinite">Join Our Team Today</a>
            </div>
        </div>
    </section>
</body>
</html> 