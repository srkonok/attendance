<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Attendance Login</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
        }
        
        .container {
            width: 90%;
            max-width: 400px;
            padding: 2rem;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            text-align: center;
            margin: 1rem;
        }
        
        h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .search-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .search-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .search-form button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        
        .search-form button:hover {
            background-color: #218838;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
                width: 85%;
            }
            
            h2 {
                font-size: 1.4rem;
            }
            
            .search-form input,
            .search-form button {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Enter Password to Access Attendance Page</h2>
        <div class="search-form">
            <form method="POST">
                <input 
                    type="password" 
                    name="password" 
                    required
                    value="" 
                    placeholder="Enter Password"
                >
                <button type="submit">Login</button>
            </form>
        </div>
        <?php if (isset($error)) echo "<p style='color:red; margin-top:1rem;'>$error</p>"; ?>
    </div>
</body>
</html>