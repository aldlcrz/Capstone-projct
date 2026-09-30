<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="1;url={{ url()->previous() ?: route('home') }}">
    <title>Session Expired — Refreshing...</title>
    <script>
        // Immediately reload/redirect back with fresh token
        (function() {
            var target = "{{ url()->previous() ?: route('home') }}";
            try {
                if (window.history.length > 1) {
                    window.location.replace(target);
                } else {
                    window.location.href = "{{ route('home') }}";
                }
            } catch(e) {
                window.location.reload();
            }
        })();
    </script>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #0f172a;
            color: #f8fafc;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .container {
            text-align: center;
            padding: 24px;
            max-width: 420px;
        }
        .spinner {
            width: 44px;
            height: 44px;
            margin: 0 auto 20px;
            border: 3.5px solid rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            border-top-color: #C0422A;
            animation: spin 0.8s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h1 {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0 0 8px;
            color: #ffffff;
            letter-spacing: -0.02em;
        }
        p {
            font-size: 0.875rem;
            color: #94a3b8;
            margin: 0 0 20px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #C0422A;
            color: #ffffff;
            text-decoration: none;
            border-radius: 12px;
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Refreshing Your Session</h1>
        <p>Your session expired. We are automatically reloading the page with a fresh security token for you.</p>
        <a href="{{ url()->previous() ?: route('home') }}" class="btn" onclick="window.location.reload(); return false;">Reload Now</a>
    </div>
</body>
</html>
