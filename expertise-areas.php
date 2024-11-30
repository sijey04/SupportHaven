<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Areas of Expertise - SupportHaven</title>
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
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/hiring/03.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .expertise-areas {
            padding: 60px 0;
        }
        
        .areas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .area-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .area-card:hover {
            transform: translateY(-5px);
        }
        
        .icon {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #007bff;
        }
        
        .skills-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .skills-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #666;
        }
        
        .skills-list li:last-child {
            border-bottom: none;
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
            .areas-grid {
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
            <h1 class="animate__animated animate__fadeInDown">Areas of Expertise</h1>
            <p class="animate__animated animate__fadeInUp">Discover the technical domains where you can make an impact</p>
        </div>
    </section>

    <section class="expertise-areas">
        <div class="container">
            <h2>Specialized Technical Services</h2>
            <div class="areas-grid">
                <div class="area-card animate__animated animate__fadeInUp">
                    <div class="icon">💻</div>
                    <h3>Computer Repair</h3>
                    <p>Hardware and software solutions for desktop and laptop computers.</p>
                    <ul class="skills-list">
                        <li>Hardware diagnostics and repair</li>
                        <li>Operating system installation</li>
                        <li>Software troubleshooting</li>
                        <li>Data recovery</li>
                        <li>Virus removal</li>
                    </ul>
                </div>
                
                <div class="area-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                    <div class="icon">🌐</div>
                    <h3>Network Setup</h3>
                    <p>Network installation and troubleshooting services.</p>
                    <ul class="skills-list">
                        <li>WiFi network setup</li>
                        <li>Router configuration</li>
                        <li>Network security</li>
                        <li>Cable management</li>
                        <li>Network optimization</li>
                    </ul>
                </div>
                
                <div class="area-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                    <div class="icon">📱</div>
                    <h3>Mobile Device Support</h3>
                    <p>Smartphone and tablet technical support services.</p>
                    <ul class="skills-list">
                        <li>Screen replacement</li>
                        <li>Battery replacement</li>
                        <li>Software updates</li>
                        <li>Data transfer</li>
                        <li>Performance optimization</li>
                    </ul>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="apply.php" class="cta-button animate__animated animate__pulse animate__infinite">Apply Your Expertise</a>
            </div>
        </div>
    </section>
</body>
</html> 