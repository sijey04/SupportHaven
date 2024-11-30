<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Testimonials - SupportHaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            font-family: -apple-system, system-ui, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }
        
        .header {
            background-color: #fff  ;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .hero {
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/hiring/04.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .testimonials {
            padding: 60px 0;
        }
        
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .testimonial-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
        }
        
        .testimonial-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
            object-fit: cover;
        }
        
        .quote {
            font-size: 1.1em;
            font-style: italic;
            margin-bottom: 20px;
            color: #666;
        }
        
        .technician-info {
            text-align: center;
        }
        
        .technician-name {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .technician-role {
            color: #666;
            font-size: 0.9em;
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
            .testimonial-grid {
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
            <h1 class="animate__animated animate__fadeInDown">Technician Success Stories</h1>
            <p class="animate__animated animate__fadeInUp">Hear from our community of tech professionals</p>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <h2>What Our Technicians Say</h2>
            <div class="testimonial-grid">
                <div class="testimonial-card animate__animated animate__fadeInUp">
                    <img src="images/staff/01.jpg" alt="Christian Jude Faminiano" class="testimonial-image">
                    <p class="quote">"Starting SupportHaven was born from my vision to revolutionize tech support. Today, I'm proud to lead a team of exceptional technicians who share my passion for delivering outstanding service and building lasting client relationships."</p>
                    <div class="technician-info">
                        <div class="technician-name">Christian Jude Faminiano</div>
                        <div class="technician-role">Founder & Lead Technician</div>
                    </div>
                </div>
                
                <div class="testimonial-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                    <img src="images/staff/02.jpg" alt="Maria Santos" class="testimonial-image">
                    <p class="quote">"The support from the SupportHaven team is outstanding. They provide all the tools and resources needed to succeed as a technician."</p>
                    <div class="technician-info">
                        <div class="technician-name">Mike Timobbs</div>
                        <div class="technician-role">Network Setup Expert</div>
                    </div>
                </div>
                
                <div class="testimonial-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                    <img src="images/staff/03.jpg" alt="Pedro Reyes" class="testimonial-image">
                    <p class="quote">"I love being able to help people solve their tech problems. The platform makes it easy to connect with clients and manage bookings."</p>
                    <div class="technician-info">
                        <div class="technician-name">Remo Silvaus</div>
                        <div class="technician-role">Mobile Device Specialist</div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="apply.php" class="cta-button animate__animated animate__pulse animate__infinite">Join Our Success Story</a>
            </div>
        </div>
    </section>
</body>
</html> 