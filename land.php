<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hamro ElectroStore - Landing Page</title>
    <link rel="icon" href="fav.png" type="image/x-icon">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            display: flex;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            background: #0e0e2c;
            color: white;
        }
        .left, .right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .left {
            background: linear-gradient(135deg, #1f005c, #5c258d);
            flex-direction: column;
            text-align: center;
            padding: 40px;
            animation: fadeInLeft 1s ease;
        }
        .left h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #f9f6ff;
        }
        .left p {
            font-size: 1.3rem;
            font-weight: 300;
            color: #d3bfff;
        }
        .right {
            background: #000;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            animation: fadeInRight 1s ease;
        }
        .right h2 {
            font-size: 2rem;
            margin-bottom: 30px;
        }
        .button-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        .btn-login {
            background: #1a1aff;
            color: white;
        }
        .btn-login:hover {
            background: #3f3fff;
        }
        .btn-signup {
            background: #00cc99;
            color: white;
        }
        .btn-signup:hover {
            background: #1affb2;
        }
        .guest-link {
            color: #bbb;
            text-decoration: underline;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .footer {
            position: absolute;
            bottom: 15px;
            text-align: center;
            width: 100%;
            font-size: 0.85rem;
            color: #888;
        }
        .footer a {
            color: #aaa;
            margin: 0 8px;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }

      
    </style>
</head>
<body>
    <div class="left">
        <h1>Explore the Latest Electronics</h1>
        <p>Discover cutting-edge gadgets, phones, accessories and more only at Hamro ElectroStore</p>
    </div>
    <div class="right">
        <h2>Get Started</h2>
        <div class="button-container">
            <a href="login.php"><button class="btn btn-login">Log in</button></a>
            <a href="register.php"><button class="btn btn-signup">Sign up</button></a>
        </div>
        <div>Browse as much <br>Get the gadget you want..</div>
    </div>
    <div class="footer">
        <a href="#">About Us</a> 
        </div>
</body>
</html>
