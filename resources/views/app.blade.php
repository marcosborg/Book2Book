<!doctype html>
<html lang="pt">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Book2Book</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            window.Book2Book = {
                page: @json($page ?? request()->route()?->defaults['page'] ?? 'books'),
                bookId: @json(request()->route('book')),
                tradeId: @json(request()->route('trade')),
            };
        </script>
    </head>
    <body class="min-h-screen bg-stone-50 text-stone-950 antialiased">
        <div id="book2book-app" class="min-h-screen"></div>
    </body>
</html>
