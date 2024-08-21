<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h1 {
            color: #007bff;
        }
        p {
            font-size: 16px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome to Our Application</h1>
            @auth
                <p>Hello, {{ Auth::user()->name }}! Welcome back.</p>
            @else
                <p>Welcome, Guest! Please <a href="{{ route('login') }}">login</a> to access more features.</p>
            @endauth
        </header>
        <main>
            <p>We are glad to have you here. Explore our application and enjoy the features we offer.</p>
        </main>
    </div>
</body>
</html>
