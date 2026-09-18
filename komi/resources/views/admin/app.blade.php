<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komi Admin</title>
    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
</head>
<body class="bg-gray-100 antialiased">
    <div id="admin-app"></div>

    <script>
        @php
            $user = auth()->user();
            $userData = $user ? $user->only(['id', 'name', 'username', 'email', 'is_global_admin']) : null;
        @endphp
        window.adminUser = {!! json_encode($userData) !!};
        window.adminToken = @json($user ? $user->createToken('admin-panel')->plainTextToken : null);
    </script>
</body>
</html>
