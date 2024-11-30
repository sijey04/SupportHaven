<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works - SupportHaven</title>
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
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/hiring/02.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .process {
            padding: 60px 0;
        }
        
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 0;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: #007bff;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        
        .step {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }
        
        .step-content {
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .step::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            background-color: white;
            border: 4px solid #007bff;
            border-radius: 50%;
            top: 50%;
            right: -17px;
            z-index: 1;
        }
        
        .step.left {
            left: 0;
        }
        
        .step.right {
            left: 50%;
        }
        
        .step.right::after {
            left: -17px;
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
            color: #007bff;
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
            .timeline::after {
                left: 31px;
            }
            
            .step {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .step.right {
                left: 0;
            }
            
            .step::after {
                left: 15px;
                right: auto;
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
            <h1 class="animate__animated animate__fadeInDown">How It Works</h1>
            <p class="animate__animated animate__fadeInUp">Your journey to becoming a SupportHaven technician</p>
        </div>
    </section>

    <section class="process">
        <div class="container">
            <h2>The Process</h2>
            <div class="timeline">
                <div class="step left animate__animated animate__fadeInLeft">
                    <div class="step-content">
                        <h3>1. Apply Online</h3>
                        <p>Fill out our comprehensive application form with your personal information, skills, and experience.</p>
                    </div>
                </div>
                
                <div class="step right animate__animated animate__fadeInRight">
                    <div class="step-content">
                        <h3>2. Document Verification</h3>
                        <p>Submit required documents for verification, including ID, certifications, and background check.</p>
                    </div>
                </div>
                
                <div class="step left animate__animated animate__fadeInLeft">
                    <div class="step-content">
                        <h3>3. Skills Assessment</h3>
                        <p>Complete our technical assessment to demonstrate your expertise in your chosen field.</p>
                    </div>
                </div>
                
                <div class="step right animate__animated animate__fadeInRight">
                    <div class="step-content">
                        <h3>4. Interview</h3>
                        <p>Participate in a virtual interview with our team to discuss your experience and expectations.</p>
                    </div>
                </div>
                
                <div class="step left animate__animated animate__fadeInLeft">
                    <div class="step-content">
                        <h3>5. Onboarding</h3>
                        <p>Complete our orientation program and learn about our platform, policies, and best practices.</p>
                    </div>
                </div>
                
                <div class="step right animate__animated animate__fadeInRight">
                    <div class="step-content">
                        <h3>6. Start Working</h3>
                        <p>Set up your profile, choose your availability, and start accepting service requests!</p>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="apply.php" class="cta-button animate__animated animate__pulse animate__infinite">Start Your Journey</a>
            </div>
        </div>
    </section>
</body>
</html> 