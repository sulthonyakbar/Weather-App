// Auto-resize textarea
const textarea = document.getElementById('input');
const messages = document.getElementById('messages');

function resize() {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}
textarea.addEventListener('input', resize);

// Quick Buttons Logic
document.querySelectorAll('.quick-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        textarea.value = btn.innerText;
        resize();
        textarea.focus();
    });
});

// Handle form submission
document.getElementById('composer').addEventListener('submit', async e => {
    e.preventDefault();

    const text = textarea.value.trim();
    if (!text) return;

    appendMessage(text, 'sent');
    textarea.value = '';
    resize();
    showTyping();

    try {
        const response = await fetch('/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                message: text
            })
        });

        const data = await response.json();

        hideTyping();

        if (data.type === "image") {
            appendImage(data.url, data.caption);
        } else if (data.type === "video") {
            appendVideo(data.video_url, data.caption);
        } else if (data.type === "audio") {
            appendAudio(data.url, data.caption);
        } else if (data.type === "gif") {
            appendGIF(data.gifUrl, data.caption);
        } else {
            appendMessage(data.reply, 'received');
        }

        if (data.weather) {
            setWeatherBackground(data.weather);
        }

    } catch (error) {
        hideTyping();
        appendMessage('Error: gagal menghubungi server.', 'received');
    }
});

function appendMessage(text, type = 'received') {
    const wrapper = document.createElement('div');
    wrapper.className =
        type === 'sent' ?
            'flex items-start justify-end' :
            'flex items-start space-x-3';

    if (type === 'sent') {
        wrapper.innerHTML = `
            <div class="text-right">
                <div class="inline-block bg-indigo-500 text-white px-4 py-2 rounded-xl rounded-tr-none max-w-xl">
                    ${escapeHtml(text)}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    ${timeNow()}
                </div>
            </div>
        `;
    } else {
        wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-300 flex items-center justify-center text-gray-700 dark:text-gray-700 text-sm">
                W
            </div>
            <div>
                <div class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-2 rounded-xl rounded-tl-none max-w-xl">
                    ${escapeHtml(text)}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    ${timeNow()}
                </div>
            </div>
        `;
    }

    messages.appendChild(wrapper);
    scrollBottom();
}

function appendImage(url) {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-start space-x-3';

    const caption = arguments[1] || '';

    wrapper.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">
                    W
                </div>
                <div>
                    <figure class="max-w-xs">
                        <img src="${url}" class="rounded-xl shadow block">
                        ${caption ? `<figcaption class="text-xs text-gray-500 dark:text-gray-400 italic mt-2">${escapeHtml(caption)}</figcaption>` : ''}
                        <div class="text-xs text-gray-500 mt-1">${timeNow()}</div>
                    </figure>
                </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
}

function appendVideo(video_url) {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-start space-x-3';

    const caption = arguments[1] || '';

    wrapper.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">
                    W
                </div>
                <div>
                    <figure class="max-w-xs">
                        <video controls class="rounded-xl max-w-xs shadow">
                            <source src="${video_url}" type="video/mp4">
                        </video>
                        ${caption ? `<figcaption class="text-xs text-gray-500 dark:text-gray-400 italic mt-2">${escapeHtml(caption)}</figcaption>` : ''}
                        <div class="text-xs text-gray-500 mt-1">${timeNow()}</div>
                    </figure>
                </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
}

function appendGIF(gifUrl) {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-start space-x-3';

    wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">AI</div>
            <div>
                <figure class="max-w-xs">
                <img src="${gifUrl}"
                    class="rounded-xl shadow block mt-2">
                <figcaption class="text-xs text-gray-500 dark:text-gray-400 italic mt-2">GIF: ${escapeHtml(caption)}</figcaption>
                <div class="text-xs text-gray-500 mt-1">${timeNow()}</div>
                </figure>
            </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
}

function appendAudio(url) {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-start space-x-3';

    const caption = arguments[1] || '';

    wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">AI</div>
            <div>
                <figure class="max-w-xs">
                <audio controls class="mt-2 w-full">
                    <source src="${url}" type="audio/mpeg">
                </audio>
                ${caption ? `<figcaption class="text-xs text-gray-500 dark:text-gray-400 italic mt-2">${escapeHtml(caption)}</figcaption>` : ''}
                <div class="text-xs text-gray-500 mt-1">${timeNow()}</div>
                </figure>
            </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
}

function timeNow() {
    const d = new Date();
    return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
}

function scrollBottom() {
    messages.scrollTop = messages.scrollHeight;
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, s => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[s])).replace(/\n/g, '<br>');
}

function showTyping() {
    const typing = document.getElementById('typing');
    typing.classList.remove('hidden');

    const sentMessages = [...messages.querySelectorAll('.justify-end')];
    const lastSent = sentMessages[sentMessages.length - 1];

    if (lastSent) {
        lastSent.insertAdjacentElement('afterend', typing);
    } else {
        messages.appendChild(typing);
    }

    scrollBottom();
}

function hideTyping() {
    const typing = document.getElementById('typing');
    typing.classList.add('hidden');
}

// Theme Toggle Logic
const menuBtn = document.getElementById('menuBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
const htmlTag = document.documentElement; // FIX: mengambil elemen <html>
const toggleInDropdown = document.getElementById('themeToggleDropdown');

// Buka/Tutup dropdown
menuBtn.addEventListener('click', () => {
    dropdownMenu.classList.toggle('hidden');
});

// Tutup dropdown saat klik di luar
document.addEventListener('click', (e) => {
    if (!e.target.closest('#menuBtn') &&
        !e.target.closest('#dropdownMenu')) {
        dropdownMenu.classList.add('hidden');
    }
});

// Toggle Dark Mode
toggleInDropdown.addEventListener('click', (e) => {
    e.stopPropagation();
    htmlTag.classList.toggle('dark');
    localStorage.setItem(
        'theme',
        htmlTag.classList.contains('dark') ? 'dark' : 'light'
    );
});

// Load saved theme
if (localStorage.getItem('theme') === 'dark') {
    htmlTag.classList.add('dark');
} else {
    htmlTag.classList.remove('dark');
}

// Scroll to bottom on load
window.addEventListener('load', () => setTimeout(scrollBottom, 50));

// function setWeatherBackground(condition) {
//     const bg = document.getElementById('weatherBg');

//     // reset class
//     bg.classList.remove('clear-bg', 'cloudy-bg', 'rain-bg', 'storm-bg');

//     condition = condition.toLowerCase();

//     if (condition.includes("cerah") || condition.includes("clear")) {
//         bg.classList.add('clear-bg');
//     }
//     else if (condition.includes("Clouds") || condition.includes("cloud")) {
//         bg.classList.add('cloudy-bg');
//     }
//     else if (condition.includes("Rain") || condition.includes("rain")) {
//         bg.classList.add('rain-bg');
//     }
//     else if (condition.includes("badai") || condition.includes("storm") || condition.includes("thunder")) {
//         bg.classList.add('storm-bg');
//     }
// }
