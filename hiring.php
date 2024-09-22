<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Technician - Join Our Tech Services Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            font-family: -apple-system, system-ui, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', 'Fira Sans', Ubuntu, Oxygen, 'Oxygen Sans', Cantarell, 'Droid Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Lucida Grande', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            background-image: url('images/hiring/01.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        header {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }
        nav ul {
            list-style-type: none;
            display: flex;
            gap: 20px;
        }
        nav ul li a {
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        nav ul li a:hover {
            color: #007bff;
        }
        .cta-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background-color: #0056b3;
        }
        .hero {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 60px 0;
            color: white;
        }
        .hero-content {
            display: flex;
            align-items: center;
            gap: 40px;
        }
        .hero-text {
            flex: 1;
        }
        .hero-image {
            flex: 1;
        }
        h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        .earn-text {
            font-size: 1.5em;
            color: #007bff;
            margin-bottom: 20px;
        }
        p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        img {
            max-width: 100%;
            border-radius: 10px;
        }
        .section {
            padding: 60px 0;
            background-color: rgba(255, 255, 255, 0.9);
        }
        .section h2 {
            font-size: 2em;
            margin-bottom: 30px;
            text-align: center;
        }
        .reasons, .steps, .expertise-areas, .qualifications, .values {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .reason, .step, .expertise, .qualification, .value {
            flex-basis: calc(33.33% - 20px);
            margin-bottom: 30px;
            padding: 20px;
            background-color: rgba(249, 249, 249, 0.9);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .reason h3, .step h3, .expertise h3, .qualification h3, .value h3 {
            font-size: 1.3em;
            margin-bottom: 15px;
        }
        .icon {
            font-size: 2em;
            margin-bottom: 15px;
            color: #007bff;
        }
        .testimonial {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .get-started {
            text-align: center;
            padding: 60px 0;
            background-color: rgba(0, 123, 255, 0.9);
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="index.html" class="logo">
                    <img src="images/logo.png" alt="SupportHaven Logo">
                </a>
                <ul>
                    <li><a href="#why-join-us" class="nav-link">Why join us?</a></li>
                    <li><a href="#how-it-works" class="nav-link">How it works</a></li>
                    <li><a href="#expertise" class="nav-link">Areas of Expertise</a></li>
                    <li><a href="#testimonials" class="nav-link">Testimonials</a></li>
                </ul>
                <a href="apply.php" class="cta-button">Apply Today</a>
            </nav>
        </div>
    </header>
    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-content animate__animated animate__fadeInUp">
                    <div class="hero-text">
                        <h1>Become a Technician</h1>
                        <p class="earn-text">Earn up to Php2000/day</p>
                        <p>Join our growing community of tech support specialists and handymen! Get paid for your skills in helping customers solve technical issues and perform handyman tasks.</p>
                        <a href="#apply" class="cta-button">Join Now</a>
                    </div>
                    <div class="hero-image">
                        <img src="images/staff/05.jpg" alt="Friendly technician in branded attire">
                    </div>
                </div>
            </div>
        </section>

        <section id="why-join-us" class="section animate__animated animate__fadeInUp">
            <div class="container">
                <h2>Why Join SupportHaven?</h2>
                <div class="reasons">
                    <div class="reason">
                        <div class="icon">💰</div>
                        <h3>Competitive Pay</h3>
                        <p>Earn up to Php2000 per day based on your skills and customer ratings.</p>
                    </div>
                    <div class="reason">
                        <div class="icon">🕒</div>
                        <h3>Flexible Hours</h3>
                        <p>Choose your own schedule and work as much or as little as you want.</p>
                    </div>
                    <div class="reason">
                        <div class="icon">📈</div>
                        <h3>Career Growth</h3>
                        <p>Gain experience, build your skills, and advance your career in tech support.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="section animate__animated animate__fadeInUp" style="background: linear-gradient(to right, #e6f2ff, #99ccff);">
            <div class="container">
                <h2>How It Works</h2>
                <div class="steps">
                    <div class="step">
                        <div class="icon">📝</div>
                        <h3>1. Apply</h3>
                        <p>Fill out our online application and submit your qualifications.</p>
                    </div>
                    <div class="step">
                        <div class="icon">🤝</div>
                        <h3>2. Interview</h3>
                        <p>If selected, participate in a brief online interview with our team.</p>
                    </div>
                    <div class="step">
                        <div class="icon">🚀</div>
                        <h3>3. Start Working</h3>
                        <p>Once approved, start accepting jobs and earning money!</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="expertise" class="section animate__animated animate__fadeInUp" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/hiring/02.jpg'); background-size: cover; background-position: center;">
            <div class="container">
                <h2 style="color: white; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Choose Your Area of Expertise</h2>
                <div class="expertise-areas">
                    <div class="expertise">
                        <div class="icon">💻</div>
                        <h3>Computer Repair</h3>
                        <p>Troubleshoot and fix hardware and software issues on desktops and laptops.</p>
                    </div>
                    <div class="expertise">
                        <div class="icon">📱</div>
                        <h3>Mobile Device Support</h3>
                        <p>Assist customers with smartphone and tablet problems, including app installations and settings.</p>
                    </div>
                    <div class="expertise">
                        <div class="icon">🔧</div>
                        <h3>Handyman Services</h3>
                        <p>Perform various home repair and maintenance tasks for customers.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="qualifications" class="section animate__animated animate__fadeInUp">
            <div class="container">
                <h2>Qualifications</h2>
                <div class="qualifications">
                    <div class="qualification">
                        <div class="icon">🎓</div>
                        <h3>Education</h3>
                        <p>High school diploma or equivalent. Technical certifications are a plus.</p>
                    </div>
                    <div class="qualification">
                        <div class="icon">💼</div>
                        <h3>Experience</h3>
                        <p>At least 1 year of experience in tech support or related field.</p>
                    </div>
                    <div class="qualification">
                        <div class="icon">🗣️</div>
                        <h3>Communication</h3>
                        <p>Excellent verbal and written communication skills in English and Filipino.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="testimonials" class="section animate__animated animate__fadeInUp" style="background: linear-gradient(to right, #e6f2ff, #99ccff);">
            <div class="container">
                <h2>Hear from Our Pros</h2>
                <div class="testimonial">
                    <p>"Joining SupportHaven was the best career decision I've made. The flexibility and earning potential are unmatched!"</p>
                    <p><strong>- Juan Dela Cruz, Tech Support Specialist</strong></p>
                </div>
                <div class="testimonial">
                    <p>"I love being able to help people solve their tech problems while earning a great income. SupportHaven makes it all possible."</p>
                    <p><strong>- Maria Santos, Computer Repair Technician</strong></p>
                </div>
            </div>
        </section>

        <section id="values" class="section animate__animated animate__fadeInUp">
            <div class="container">
                <h2>Our Core Technician Values</h2>
                <div class="values">
                    <div class="value">
                        <div class="icon">🤝</div>
                        <h3>Customer First</h3>
                        <p>Always prioritize customer satisfaction and go the extra mile to solve their problems.</p>
                    </div>
                    <div class="value">
                        <div class="icon">🔍</div>
                        <h3>Continuous Learning</h3>
                        <p>Stay updated with the latest technologies and constantly improve your skills.</p>
                    </div>
                    <div class="value">
                        <div class="icon">⏱️</div>
                        <h3>Punctuality</h3>
                        <p>Respect customers' time by being punctual and efficient in your work.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="get-started" class="get-started animate__animated animate__fadeInUp">
            <div class="container">
                <h2>Get Started Today</h2>
                <p>Join our team of skilled technicians and start your journey towards a rewarding career in tech support.</p>
                <a href="apply.php" class="cta-button">Apply Now</a>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', (event) => {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    const headerHeight = document.querySelector('header').offsetHeight;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Add animation class
                    targetElement.classList.add('animate__animated', 'animate__pulse');
                    
                    // Remove animation class after animation ends
                    setTimeout(() => {
                        targetElement.classList.remove('animate__animated', 'animate__pulse');
                    }, 1000);
                }
            });
        });
    });
    </script>
</body>
</html>
