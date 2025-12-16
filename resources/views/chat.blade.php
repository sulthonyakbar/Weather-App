<!doctype html>
<html lang="id" class="antialiased" id="htmlTag">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>Weather App</title>
</head>

<body>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-6">
        <div class="w-full max-w-3xl bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden flex flex-col"
            style="height:80vh">
            <!-- Header -->
            <header class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">

                <!-- LEFT -->
                <div class="flex items-center space-x-3">
                    <div
                        class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-semibold">
                        {{ strtoupper(substr(config('app.name', 'AI'), 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ config('app.name', 'AI') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Weather chatbot assistant</p>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="relative flex items-center space-x-3">

                    <!-- Chat Status -->
                    <div id="chatStatus" class="flex items-center space-x-3">
                        <span id="statusDot" class="w-3 h-3 rounded-full bg-green-500"></span>
                        <div>
                            <div id="statusText" class="text-sm font-medium text-gray-900 dark:text-gray-100">Online
                            </div>
                            <div id="statusSub" class="text-xs text-gray-500 dark:text-gray-400">Active now</div>
                        </div>
                    </div>

                    <!-- Menu Button -->
                    <button id="menuBtn" type="button"
                        class="px-3 py-2 bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>

                    <!-- DROPDOWN -->
                    <div id="dropdownMenu"
                        class="hidden absolute right-0 top-12 w-44 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-20">

                        <!-- Toggle Theme -->
                        <button id="themeToggleDropdown" type="button"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                            <i class="fas fa-moon mr-2"></i> Toggle Theme
                        </button>

                        <!-- Clear Chat -->
                        <form action="{{ route('clearChat') }}" method="POST" class="w-full"
                            onsubmit="return confirm('Are you sure you want to clear all chat history?')">
                            @method('DELETE')
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer {{ count($chats) == 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ count($chats) == 0 ? 'disabled' : '' }}>
                                <i class="fas fa-trash-alt mr-2"></i> Clear Chat
                            </button>
                        </form>

                    </div>
                </div>
            </header>

            <!-- Messages -->
            <main id="messages"
                class="flex-1 p-6 space-y-4 overflow-y-auto bg-gradient-to-b from-transparent to-gray-50 dark:to-gray-900">

                <!-- Messages will be appended here -->
                @if ($chats && count($chats) > 0)
                    @foreach ($chats as $chat)
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const message = `{!! addslashes($chat->message) !!}`;
                                const path = '{{ $chat->attachment_path ?? '' }}';
                                const type = '{{ $chat->sender === 'user' ? 'sent' : 'received' }}';
                                const caption = '{{ $chat->caption ?? '' }}';
                                const dataType = '{{ $chat->type ?? 'text' }}';
                                const createdAt = '{{ $chat->created_at->toIso8601String() }}';

                                const chatDate = createdAt.split('T')[0];
                                const time = formatTime(createdAt);

                                if (lastDate !== chatDate) {
                                    appendDateDivider(formatChatDate(createdAt));
                                    lastDate = chatDate;
                                }

                                if (type === 'received') {
                                    if (dataType === 'video') {
                                        appendVideo(path, caption, time);
                                    } else if (dataType === 'image') {
                                        appendImage(message, caption, time);
                                    } else if (dataType === 'audio') {
                                        appendAudio(path, caption, time);
                                    } else if (dataType === 'gif') {
                                        appendGIF(message, caption, time);
                                    } else {
                                        appendMessage(message, type, time);
                                    }
                                } else {
                                    appendMessage(message, type, time);
                                }
                            });
                        </script>
                    @endforeach
                @endif

                <!-- Typing indicator -->
                <div id="typing" class="hidden px-6 pb-3 flex text-gray-500 dark:text-gray-400 text-sm italic">
                    <div
                        class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-300 flex items-center justify-center text-gray-700 text-sm mr-3">
                        {{ strtoupper(substr(config('app.name', 'AI'), 0, 1)) }}
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-xl rounded-tl-none max-w-xl">
                        {{ config('app.name', 'AI') }} sedang memproses...
                    </div>
                </div>

            </main>

            <!-- Quick Buttons -->
            <div id="quickActions"
                class="px-4 py-3 flex flex-wrap gap-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <button
                    class="quick-btn text-indigo-600 hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-indigo-700 dark:hover:text-indigo-100">Cuaca
                    Kota</button>
                <button
                    class="quick-btn text-indigo-600 hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-indigo-700 dark:hover:text-indigo-100">Gambar
                    Cuaca Kota</button>
                <button
                    class="quick-btn text-indigo-600 hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-indigo-700 dark:hover:text-indigo-100">Video
                    Cuaca Kota</button>
                <button
                    class="quick-btn text-indigo-600 hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-indigo-700 dark:hover:text-indigo-100">Audio
                    Cuaca Kota</button>
                <button
                    class="quick-btn text-indigo-600 hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-indigo-700 dark:hover:text-indigo-100">GIF
                    Cuaca Kota</button>
            </div>

            <!-- Composer -->
            <form id="composer"
                class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="flex items-end space-x-3">
                    <textarea id="input" rows="1" placeholder="Type a message..."
                        class="flex-1 resize-none px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-300"></textarea>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        Send
                    </button>
                </div>
            </form>

            <!-- Media Preview Modal -->
            <div id="mediaModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-50">

                <!-- Close -->
                <button id="closeMedia" class="absolute top-4 right-4 text-white text-2xl font-bold z-50">
                    ✕
                </button>

                <!-- Image -->
                <img id="modalImage" class="hidden max-h-full max-w-full rounded-lg shadow-lg">

                <!-- Video -->
                <video id="modalVideo" class="hidden max-h-full max-w-full rounded-lg shadow-lg" controls></video>
            </div>

        </div>
    </div>

    <script src="{{ asset('js/script.js') }}"></script>
</body>

</html>
