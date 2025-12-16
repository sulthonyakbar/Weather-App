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
            appendVideo(data.attachment_path, data.caption);
        } else if (data.type === "audio") {
            appendAudio(data.attachment_path, data.caption);
        } else if (data.type === "gif") {
            appendGIF(data.url, data.caption);
        } else {
            appendMessage(data.reply, 'received');
        }

        // if (data.weather) {
        //     setWeatherBackground(data.weather);
        // }

    } catch (error) {
        hideTyping();
        appendMessage('Error: gagal menghubungi server.', 'received');
    }
});

function appendMessage(text, type = 'received', time) {
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
                    ${time}
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
                    ${time}
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
    const time = arguments[2] || '';

    wrapper.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">
                    W
                </div>
                <div>
                   <figure class="max-w-xs cursor-pointer">
                        <img src="${url}"
                            class="rounded-xl shadow block hover:opacity-90 transition"
                            onclick="openImage('${url}')">
                        ${caption ? `<figcaption class="text-xs text-gray-500 italic mt-2">${escapeHtml(caption)}</figcaption>` : ''}
                        <div class="text-xs text-gray-500 mt-1">${time}</div>
                    </figure>
                </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
}

function appendVideo(attachment_path) {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-start space-x-3';

    const caption = arguments[1] || '';
    const time = arguments[2] || '';

    wrapper.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">
                    W
                </div>
                <div>
                   <figure class="max-w-xs cursor-pointer">
                    <video
                        class="rounded-xl shadow max-w-xs hover:opacity-90 transition"
                        muted
                        onclick="openVideo('${attachment_path}')">
                        <source src="${attachment_path}" type="video/mp4">
                    </video>
                    ${caption ? `<figcaption class="text-xs text-gray-500 italic mt-2">${escapeHtml(caption)}</figcaption>` : ''}
                    <div class="text-xs text-gray-500 mt-1">${time}</div>
                </figure>
                </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
}

function appendGIF(url) {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-start space-x-3';

    const caption = arguments[1] || '';
    const time = arguments[2] || '';

    wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">W</div>
            <div>
                <figure class="max-w-xs">
                <img src="${url}"
                    class="rounded-xl shadow block mt-2">
                ${caption ? `<figcaption class="text-xs text-gray-500 dark:text-gray-400 italic mt-2">${escapeHtml(caption)}</figcaption>` : ''}
                <div class="text-xs text-gray-500 mt-1">${time}</div>
                </figure>
            </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
}

function appendAudio(attachment_path) {
    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-start space-x-3';

    const caption = arguments[1] || '';
    const time = arguments[2] || '';

    wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm">W</div>
            <div>
                <figure class="max-w-xs">
                <audio controls class="mt-2 w-full">
                    <source src="${attachment_path}" type="audio/mpeg">
                </audio>
                ${caption ? `<figcaption class="text-xs text-gray-500 dark:text-gray-400 italic mt-2">${escapeHtml(caption)}</figcaption>` : ''}
                <div class="text-xs text-gray-500 mt-1">${time}</div>
                </figure>
            </div>
            `;

    messages.appendChild(wrapper);
    scrollBottom();
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

const mediaModal = document.getElementById('mediaModal');
const modalImage = document.getElementById('modalImage');
const modalVideo = document.getElementById('modalVideo');
const closeMedia = document.getElementById('closeMedia');

// Open Image
function openImage(src) {
    modalVideo.pause();
    modalVideo.classList.add('hidden');

    modalImage.src = src;
    modalImage.classList.remove('hidden');

    mediaModal.classList.remove('hidden');
    mediaModal.classList.add('flex');
}

// Open Video
function openVideo(src) {
    modalImage.classList.add('hidden');

    modalVideo.src = src;
    modalVideo.load();
    modalVideo.classList.remove('hidden');

    mediaModal.classList.remove('hidden');
    mediaModal.classList.add('flex');
}

// Close modal
function closeModal() {
    modalVideo.pause();
    mediaModal.classList.add('hidden');
    mediaModal.classList.remove('flex');
}

// Events
closeMedia.addEventListener('click', closeModal);

mediaModal.addEventListener('click', (e) => {
    if (e.target === mediaModal) {
        closeModal();
    }
});
