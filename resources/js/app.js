import './bootstrap';

const root = document.getElementById('book2book-app');

if (root) {
    const config = window.Book2Book || {};
    const state = {
        page: config.page || 'books',
        bookId: config.bookId,
        tradeId: config.tradeId,
        token: localStorage.getItem('book2book_token'),
        user: JSON.parse(localStorage.getItem('book2book_user') || 'null'),
        books: [],
        myBooks: [],
        trades: [],
        messages: [],
        notifications: [],
        currentBook: null,
        currentTrade: null,
        loading: false,
        error: '',
        notice: '',
        filters: {
            q: '',
            genre: '',
            language: '',
            distance_km: '',
            order: 'recent',
        },
    };

    const api = async (path, options = {}) => {
        const headers = {
            Accept: 'application/json',
            ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...(state.token ? { Authorization: `Bearer ${state.token}` } : {}),
            ...(options.headers || {}),
        };

        const response = await fetch(`/api/v1${path}`, { ...options, headers });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validation = payload.errors
                ? Object.values(payload.errors).flat().join(' ')
                : null;

            throw new Error(validation || payload.message || 'Request failed.');
        }

        return payload;
    };

    const navigate = (path) => {
        window.location.href = path;
    };

    const setAuth = (payload) => {
        state.token = payload.data.token;
        state.user = payload.data.user;
        localStorage.setItem('book2book_token', state.token);
        localStorage.setItem('book2book_user', JSON.stringify(state.user));
    };

    const clearAuth = () => {
        state.token = null;
        state.user = null;
        localStorage.removeItem('book2book_token');
        localStorage.removeItem('book2book_user');
    };

    const withBusy = async (task) => {
        state.loading = true;
        state.error = '';
        state.notice = '';
        render();

        try {
            await task();
        } catch (error) {
            state.error = error.message;
        } finally {
            state.loading = false;
            render();
        }
    };

    const requireAuth = () => {
        if (!state.token) {
            navigate('/login');
            return false;
        }

        return true;
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const conditionLabel = (condition) => ({
        new_like: 'Like new',
        good: 'Good',
        acceptable: 'Acceptable',
        poor: 'Poor',
    }[condition] || condition || 'Not set');

    const statusLabel = (status) => ({
        pending: 'Pending',
        accepted: 'Accepted',
        declined: 'Rejected',
        cancelled: 'Cancelled',
        completed: 'Completed',
    }[status] || status);

    const pageTitle = () => ({
        login: 'Access your account',
        register: 'Create account',
        profile: 'Profile',
        books: 'Book catalog',
        'book-create': 'Add a book',
        'book-detail': 'Book detail',
        library: 'My library',
        trades: 'Trade requests',
        chat: 'Trade chat',
        notifications: 'Notifications',
    }[state.page] || 'Book2Book');

    const nav = () => `
        <header class="sticky top-0 z-20 border-b border-stone-200 bg-stone-50/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-3 font-semibold">
                    <span class="grid size-9 place-items-center rounded bg-amber-600 text-sm text-white">B2B</span>
                    <span>Book2Book</span>
                </a>
                <nav class="hidden items-center gap-1 md:flex">
                    ${navLink('/books', 'Catalog')}
                    ${navLink('/library', 'My Library')}
                    ${navLink('/trades', 'Trades')}
                    ${navLink('/notifications', 'Notifications')}
                    ${navLink('/profile', 'Profile')}
                </nav>
                <div class="flex items-center gap-2">
                    ${state.user ? `<span class="hidden max-w-36 truncate text-sm text-stone-600 sm:block">${escapeHtml(state.user.name)}</span><button data-action="logout" class="btn-secondary">Logout</button>` : `<a href="/login" class="btn-secondary">Login</a><a href="/register" class="btn-primary">Register</a>`}
                </div>
            </div>
        </header>
    `;

    const navLink = (href, label) => `<a class="rounded px-3 py-2 text-sm font-medium text-stone-700 hover:bg-white hover:text-stone-950" href="${href}">${label}</a>`;

    const shell = (content) => `
        ${nav()}
        <main class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-700">Book exchange MVP</p>
                    <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">${pageTitle()}</h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="/books" class="btn-secondary">Discover books</a>
                    ${state.user ? '<a href="/books/create" class="btn-primary">Add my book</a>' : '<a href="/login" class="btn-primary">Login to add books</a>'}
                </div>
            </div>
            ${state.error ? `<div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">${escapeHtml(state.error)}</div>` : ''}
            ${state.notice ? `<div class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">${escapeHtml(state.notice)}</div>` : ''}
            ${state.loading ? `<div class="rounded border border-stone-200 bg-white p-3 text-sm text-stone-600">Loading...</div>` : ''}
            ${content}
        </main>
    `;

    const authPage = (mode) => shell(`
        <section class="grid gap-6 lg:grid-cols-[1fr_420px]">
            <div class="rounded border border-stone-200 bg-white p-6">
                <h2 class="text-xl font-semibold">${mode === 'login' ? 'Welcome back' : 'Start exchanging books'}</h2>
                <p class="mt-2 max-w-2xl text-stone-600">Use the same API that the mobile app will use. Your token is stored locally in this browser.</p>
                <div class="mt-6 grid gap-3 text-sm text-stone-700">
                    <div class="rounded bg-stone-50 p-3">Add books to your personal library.</div>
                    <div class="rounded bg-stone-50 p-3">Request available books from nearby readers.</div>
                    <div class="rounded bg-stone-50 p-3">Chat after a request is accepted and review after completion.</div>
                </div>
            </div>
            <form data-form="${mode}" class="rounded border border-stone-200 bg-white p-6 shadow-sm">
                <div class="grid gap-4">
                    ${mode === 'register' ? input('name', 'Name', 'text', true) : ''}
                    ${input('email', 'Email', 'email', true)}
                    ${input('password', 'Password', 'password', true)}
                    ${mode === 'register' ? input('city', 'City', 'text') : ''}
                    ${mode === 'register' ? `<div class="grid grid-cols-2 gap-3">${input('lat', 'Latitude', 'number')} ${input('lng', 'Longitude', 'number')}</div>` : ''}
                    <button class="btn-primary w-full" type="submit">${mode === 'login' ? 'Login' : 'Create account'}</button>
                    <a class="text-center text-sm font-medium text-amber-700" href="${mode === 'login' ? '/register' : '/login'}">${mode === 'login' ? 'Need an account?' : 'Already have an account?'}</a>
                </div>
            </form>
        </section>
    `);

    const input = (name, label, type = 'text', required = false, value = '') => `
        <label class="grid gap-1 text-sm">
            <span class="font-medium text-stone-700">${label}</span>
            <input name="${name}" type="${type}" ${required ? 'required' : ''} value="${escapeHtml(value)}" class="field">
        </label>
    `;

    const textarea = (name, label, value = '') => `
        <label class="grid gap-1 text-sm">
            <span class="font-medium text-stone-700">${label}</span>
            <textarea name="${name}" rows="4" class="field">${escapeHtml(value)}</textarea>
        </label>
    `;

    const profilePage = () => {
        if (!requireAuth()) {
            return '';
        }

        const user = state.user || {};

        return shell(`
            <form data-form="profile" class="grid gap-4 rounded border border-stone-200 bg-white p-6 shadow-sm md:grid-cols-2">
                ${input('name', 'Name', 'text', true, user.name)}
                ${input('email', 'Email', 'email', true, user.email)}
                ${input('phone', 'Phone', 'text', false, user.phone)}
                ${input('city', 'City', 'text', false, user.city)}
                ${input('lat', 'Latitude', 'number', false, user.lat)}
                ${input('lng', 'Longitude', 'number', false, user.lng)}
                <div class="md:col-span-2">
                    <button class="btn-primary" type="submit">Save profile</button>
                </div>
            </form>
        `);
    };

    const booksPage = () => shell(`
        <section class="grid gap-5">
            <div class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <strong class="block">Tens livros para trocar?</strong>
                        <span>Adiciona-os na tua biblioteca pessoal. Não precisas de acesso ao backoffice.</span>
                    </div>
                    <a href="${state.user ? '/books/create' : '/login'}" class="btn-primary">${state.user ? 'Adicionar livro' : 'Entrar para adicionar'}</a>
                </div>
            </div>
            <form data-form="search" class="grid gap-3 rounded border border-stone-200 bg-white p-4 shadow-sm md:grid-cols-5">
                <input name="q" placeholder="Title or author" value="${escapeHtml(state.filters.q)}" class="field md:col-span-2">
                <input name="genre" placeholder="Genre" value="${escapeHtml(state.filters.genre)}" class="field">
                <input name="language" placeholder="Language" value="${escapeHtml(state.filters.language)}" class="field">
                <select name="order" class="field">
                    <option value="recent" ${state.filters.order === 'recent' ? 'selected' : ''}>Recent</option>
                    <option value="distance" ${state.filters.order === 'distance' ? 'selected' : ''}>Distance</option>
                </select>
                <input name="distance_km" placeholder="Max km" value="${escapeHtml(state.filters.distance_km)}" class="field">
                <button class="btn-primary md:col-span-4" type="submit">Search</button>
            </form>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">${state.books.map(bookCard).join('') || empty('No available books found.')}</div>
        </section>
    `);

    const bookForm = (submitLabel = 'Add to library') => `
        <form data-form="book" class="grid gap-3 rounded border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold">Book details</h2>
            ${input('title', 'Title', 'text', true)}
            ${input('author', 'Author', 'text', true)}
            ${input('isbn', 'ISBN')}
            ${input('genre', 'Genre')}
            ${input('language', 'Language')}
            <div class="grid gap-1 text-sm">
                <span class="font-medium text-stone-700">Cover image</span>
                <label class="cover-dropzone" data-dropzone>
                    <input name="cover_image" type="file" accept="image/*" class="sr-only" data-cover-input>
                    <span class="cover-dropzone__preview" data-cover-preview>
                        <span class="cover-dropzone__icon">+</span>
                    </span>
                    <span class="cover-dropzone__text">
                        <strong>Drag and drop the cover here</strong>
                        <small>or click to choose JPG, PNG or WebP up to 4 MB</small>
                    </span>
                </label>
            </div>
            <label class="grid gap-1 text-sm"><span class="font-medium text-stone-700">Condition</span><select name="condition" class="field"><option value="good">Good</option><option value="new_like">Like new</option><option value="acceptable">Acceptable</option><option value="poor">Poor</option></select></label>
            ${textarea('description', 'Description')}
            <label class="flex items-center gap-2 rounded border border-stone-200 bg-stone-50 p-3 text-sm font-medium text-stone-700">
                <input name="is_available" type="checkbox" value="1" checked>
                Available for exchange
            </label>
            <button class="btn-primary" type="submit">${submitLabel}</button>
            <a href="/library" class="btn-secondary">Back to my library</a>
        </form>
    `;

    const bookCard = (book) => `
        <article class="grid gap-3 rounded border border-stone-200 bg-white p-4 shadow-sm">
            ${cover(book)}
            <div>
                <h2 class="line-clamp-2 text-lg font-semibold">${escapeHtml(book.title)}</h2>
                <p class="text-sm text-stone-600">${escapeHtml(book.author)}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                ${badge(conditionLabel(book.condition))}
                ${book.genre ? badge(book.genre) : ''}
                ${book.language ? badge(book.language) : ''}
                ${book.owner?.distance_km ? badge(`${Number(book.owner.distance_km).toFixed(1)} km`) : ''}
            </div>
            <p class="text-sm text-stone-600">${escapeHtml(book.owner?.city || book.owner?.name || 'Unknown owner')}</p>
            <a class="btn-secondary text-center" href="/books/${book.id}">View details</a>
        </article>
    `;

    const cover = (book) => book.cover_image_url
        ? `<img src="${book.cover_image_url}" alt="" class="aspect-[4/3] w-full rounded object-cover">`
        : `<div class="grid aspect-[4/3] w-full place-items-center rounded bg-amber-100 text-amber-900">No cover</div>`;

    const badge = (label) => `<span class="rounded bg-stone-100 px-2 py-1 font-medium text-stone-700">${escapeHtml(label)}</span>`;

    const empty = (message) => `<div class="rounded border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-500">${message}</div>`;

    const bookDetailPage = () => {
        const book = state.currentBook;

        return shell(book ? `
            <section class="grid gap-6 lg:grid-cols-[420px_1fr]">
                <div class="rounded border border-stone-200 bg-white p-4">${cover(book)}</div>
                <div class="grid gap-5 rounded border border-stone-200 bg-white p-6 shadow-sm">
                    <div>
                        <h2 class="text-3xl font-semibold">${escapeHtml(book.title)}</h2>
                        <p class="mt-1 text-lg text-stone-600">${escapeHtml(book.author)}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        ${badge(conditionLabel(book.condition))}
                        ${book.genre ? badge(book.genre) : ''}
                        ${book.language ? badge(book.language) : ''}
                        ${book.owner?.distance_km ? badge(`${Number(book.owner.distance_km).toFixed(1)} km away`) : ''}
                    </div>
                    <p class="text-stone-700">${escapeHtml(book.description || 'No description yet.')}</p>
                    <div class="rounded bg-stone-50 p-4 text-sm">
                        <strong>Owner</strong>
                        <div>${escapeHtml(book.owner?.name || 'Unknown')}</div>
                        <div class="text-stone-600">${escapeHtml(book.owner?.city || 'Location not set')}</div>
                    </div>
                    <form data-form="request-trade" class="grid gap-3">
                        ${textarea('message', 'Message to owner')}
                        <button class="btn-primary" type="submit" ${book.is_available ? '' : 'disabled'}>Request exchange</button>
                    </form>
                </div>
            </section>
        ` : empty('Book not found.'));
    };

    const libraryPage = () => {
        if (!requireAuth()) {
            return '';
        }

        return shell(`
            <section class="grid gap-5 lg:grid-cols-[380px_1fr]">
                ${bookForm('Add to library')}
                <div class="grid gap-4 sm:grid-cols-2">${state.myBooks.map(myBookCard).join('') || empty('Your library is empty.')}</div>
            </section>
        `);
    };

    const bookCreatePage = () => {
        if (!requireAuth()) {
            return '';
        }

        return shell(`
            <section class="mx-auto grid w-full max-w-2xl gap-4">
                <div class="rounded border border-stone-200 bg-white p-5 text-sm text-stone-600">
                    Este formulário cria livros na tua biblioteca pessoal através da API da app, não no backoffice.
                </div>
                ${bookForm('Publish book')}
            </section>
        `);
    };

    const myBookCard = (book) => `
        <article class="grid gap-3 rounded border border-stone-200 bg-white p-4 shadow-sm">
            ${cover(book)}
            <div>
                <h2 class="text-lg font-semibold">${escapeHtml(book.title)}</h2>
                <p class="text-sm text-stone-600">${escapeHtml(book.author)}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">${badge(conditionLabel(book.condition))}${badge(book.is_available ? 'Available' : 'Unavailable')}</div>
            <button data-action="toggle-availability" data-book="${book.id}" data-available="${book.is_available ? '0' : '1'}" class="btn-secondary">${book.is_available ? 'Mark unavailable' : 'Mark available'}</button>
        </article>
    `;

    const tradesPage = () => {
        if (!requireAuth()) {
            return '';
        }

        return shell(`
            <section class="grid gap-4">
                <div class="grid gap-3 md:grid-cols-4">
                    ${['pending', 'accepted', 'declined', 'completed'].map(status => `<button data-action="filter-trades" data-status="${status}" class="btn-secondary">${statusLabel(status)}</button>`).join('')}
                </div>
                <div class="grid gap-4">${state.trades.map(tradeCard).join('') || empty('No trade requests yet.')}</div>
            </section>
        `);
    };

    const tradeCard = (trade) => {
        const isOwner = state.user && trade.owner?.id === state.user.id;
        const isRequester = state.user && trade.requester?.id === state.user.id;

        return `
            <article class="grid gap-4 rounded border border-stone-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_auto]">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold">${escapeHtml(trade.book?.title || 'Book')}</h2>
                        ${badge(statusLabel(trade.status))}
                    </div>
                    <p class="text-sm text-stone-600">Requester: ${escapeHtml(trade.requester?.name)} · Owner: ${escapeHtml(trade.owner?.name)}</p>
                    <p class="mt-2 text-sm text-stone-700">${escapeHtml(trade.message || 'No initial message.')}</p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    ${isOwner && trade.status === 'pending' ? `<button data-action="trade-action" data-trade="${trade.id}" data-endpoint="accept" class="btn-primary">Accept</button><button data-action="trade-action" data-trade="${trade.id}" data-endpoint="reject" class="btn-secondary">Reject</button>` : ''}
                    ${isRequester && trade.status === 'pending' ? `<button data-action="trade-action" data-trade="${trade.id}" data-endpoint="cancel" class="btn-secondary">Cancel</button>` : ''}
                    ${isOwner && trade.status === 'accepted' ? `<button data-action="trade-action" data-trade="${trade.id}" data-endpoint="complete" class="btn-primary">Complete</button>` : ''}
                    ${(trade.status === 'accepted' || trade.status === 'completed') ? `<a href="/trades/${trade.id}/chat" class="btn-secondary">Chat</a>` : ''}
                </div>
            </article>
        `;
    };

    const chatPage = () => {
        if (!requireAuth()) {
            return '';
        }

        const trade = state.currentTrade;

        return shell(`
            <section class="grid gap-4 lg:grid-cols-[320px_1fr]">
                <aside class="rounded border border-stone-200 bg-white p-4">
                    <h2 class="font-semibold">${escapeHtml(trade?.book?.title || 'Trade')}</h2>
                    <p class="text-sm text-stone-600">${statusLabel(trade?.status)}</p>
                    ${trade?.status === 'completed' ? reviewForm() : '<p class="mt-4 text-sm text-stone-500">Reviews open after completion.</p>'}
                </aside>
                <div class="grid gap-4 rounded border border-stone-200 bg-white p-4">
                    <div class="grid max-h-[520px] gap-3 overflow-y-auto">${state.messages.map(messageBubble).join('') || empty('No messages yet.')}</div>
                    <form data-form="message" class="flex gap-2">
                        <input name="message" class="field" placeholder="Write a message" required>
                        <button class="btn-primary" type="submit">Send</button>
                    </form>
                </div>
            </section>
        `);
    };

    const messageBubble = (message) => {
        const mine = state.user && message.sender?.id === state.user.id;

        return `<div class="grid ${mine ? 'justify-items-end' : 'justify-items-start'}"><div class="max-w-xl rounded ${mine ? 'bg-amber-600 text-white' : 'bg-stone-100 text-stone-900'} px-4 py-2"><p>${escapeHtml(message.message)}</p><span class="text-xs opacity-75">${escapeHtml(message.sender?.name || '')}</span></div></div>`;
    };

    const reviewForm = () => `
        <form data-form="review" class="mt-4 grid gap-3">
            <label class="grid gap-1 text-sm"><span class="font-medium text-stone-700">Rating</span><select name="rating" class="field"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select></label>
            ${textarea('comment', 'Comment')}
            <button class="btn-primary" type="submit">Send review</button>
        </form>
    `;

    const notificationsPage = () => {
        if (!requireAuth()) {
            return '';
        }

        return shell(`
            <section class="grid gap-3">${state.notifications.map(notificationCard).join('') || empty('No notifications yet.')}</section>
        `);
    };

    const notificationCard = (notification) => `
        <article class="flex items-start justify-between gap-4 rounded border border-stone-200 bg-white p-4 shadow-sm">
            <div>
                <h2 class="font-semibold">${escapeHtml(notification.data?.title || notification.type?.split('\\\\').pop() || 'Notification')}</h2>
                <p class="text-sm text-stone-600">${escapeHtml(notification.data?.body || notification.data?.message || '')}</p>
            </div>
            ${notification.read_at ? badge('Read') : `<button data-action="mark-read" data-notification="${notification.id}" class="btn-secondary">Mark read</button>`}
        </article>
    `;

    const loadMe = async () => {
        if (!state.token) {
            return;
        }

        const payload = await api('/me');
        state.user = payload.data;
        localStorage.setItem('book2book_user', JSON.stringify(state.user));
    };

    const loadBooks = async () => {
        const params = new URLSearchParams(Object.entries(state.filters).filter(([, value]) => value !== ''));
        if (state.user?.lat && state.user?.lng) {
            params.set('lat', state.user.lat);
            params.set('lng', state.user.lng);
        }

        const payload = await api(`/books/search?${params.toString()}`);
        state.books = payload.data;
    };

    const loadBook = async () => {
        const payload = await api(`/books/${state.bookId}`);
        state.currentBook = payload.data;
    };

    const loadLibrary = async () => {
        const payload = await api('/me/books?per_page=50');
        state.myBooks = payload.data;
    };

    const loadTrades = async (status = '') => {
        const query = status ? `?status=${status}&per_page=50` : '?per_page=50';
        const payload = await api(`/trades${query}`);
        state.trades = payload.data;
    };

    const loadChat = async () => {
        const [trade, messages] = await Promise.all([
            api(`/trades/${state.tradeId}`),
            api(`/trades/${state.tradeId}/messages?per_page=50`),
        ]);
        state.currentTrade = trade.data;
        state.messages = messages.data;
    };

    const loadNotifications = async () => {
        const payload = await api('/me/notifications?per_page=50');
        state.notifications = payload.data;
    };

    const bootPage = async () => {
        await withBusy(async () => {
            if (state.token) {
                await loadMe();
            }

            if (state.page === 'books') {
                await loadBooks();
            } else if (state.page === 'book-create') {
                // Auth is checked by the page renderer.
            } else if (state.page === 'book-detail') {
                await loadBook();
            } else if (state.page === 'library') {
                await loadLibrary();
            } else if (state.page === 'trades') {
                await loadTrades();
            } else if (state.page === 'chat') {
                await loadChat();
            } else if (state.page === 'notifications') {
                await loadNotifications();
            }
        });
    };

    const render = () => {
        root.innerHTML = {
            login: () => authPage('login'),
            register: () => authPage('register'),
            profile: profilePage,
            books: booksPage,
            'book-create': bookCreatePage,
            'book-detail': bookDetailPage,
            library: libraryPage,
            trades: tradesPage,
            chat: chatPage,
            notifications: notificationsPage,
        }[state.page]?.() || booksPage();
    };

    const formData = (form) => Object.fromEntries(new FormData(form).entries());

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-form]');
        if (!form) {
            return;
        }

        event.preventDefault();
        const data = formData(form);

        withBusy(async () => {
            if (form.dataset.form === 'login' || form.dataset.form === 'register') {
                const payload = await api(`/auth/${form.dataset.form}`, {
                    method: 'POST',
                    body: JSON.stringify(data),
                });
                setAuth(payload);
                navigate('/books');
            }

            if (form.dataset.form === 'profile') {
                const payload = await api('/me', { method: 'PUT', body: JSON.stringify(data) });
                state.user = payload.data;
                localStorage.setItem('book2book_user', JSON.stringify(state.user));
                state.notice = 'Profile saved.';
            }

            if (form.dataset.form === 'search') {
                state.filters = { ...state.filters, ...data };
                await loadBooks();
            }

            if (form.dataset.form === 'request-trade') {
                if (!state.token) {
                    navigate('/login');
                    return;
                }

                const payload = await api('/trades', {
                    method: 'POST',
                    body: JSON.stringify({ book_id: state.currentBook.id, message: data.message }),
                });
                navigate(`/trades/${payload.data.id}/chat`);
            }

            if (form.dataset.form === 'book') {
                const payload = new FormData(form);
                payload.set('is_available', data.is_available === '1' ? '1' : '0');

                if (!payload.get('cover_image')?.name) {
                    payload.delete('cover_image');
                }

                await api('/me/books', { method: 'POST', body: payload });
                form.reset();
                state.notice = 'Book added to your library.';
                if (state.page === 'library') {
                    await loadLibrary();
                } else {
                    navigate('/library');
                }
            }

            if (form.dataset.form === 'message') {
                await api(`/trades/${state.tradeId}/messages`, { method: 'POST', body: JSON.stringify(data) });
                form.reset();
                await loadChat();
            }

            if (form.dataset.form === 'review') {
                await api(`/trades/${state.tradeId}/review`, { method: 'POST', body: JSON.stringify(data) });
                state.notice = 'Review submitted.';
            }
        });
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }

        const action = button.dataset.action;

        withBusy(async () => {
            if (action === 'logout') {
                if (state.token) {
                    await api('/auth/logout', { method: 'POST' }).catch(() => {});
                }
                clearAuth();
                navigate('/login');
            }

            if (action === 'toggle-availability') {
                await api(`/me/books/${button.dataset.book}/availability`, {
                    method: 'POST',
                    body: JSON.stringify({ is_available: button.dataset.available === '1' }),
                });
                await loadLibrary();
            }

            if (action === 'trade-action') {
                await api(`/trades/${button.dataset.trade}/${button.dataset.endpoint}`, { method: 'POST' });
                await loadTrades();
            }

            if (action === 'filter-trades') {
                await loadTrades(button.dataset.status);
            }

            if (action === 'mark-read') {
                await api(`/me/notifications/${button.dataset.notification}/read`, { method: 'POST' });
                await loadNotifications();
            }
        });
    });

    document.addEventListener('dragover', (event) => {
        const dropzone = event.target.closest('[data-dropzone]');
        if (!dropzone) {
            return;
        }

        event.preventDefault();
        dropzone.classList.add('cover-dropzone--active');
    });

    document.addEventListener('dragleave', (event) => {
        const dropzone = event.target.closest('[data-dropzone]');
        if (!dropzone || dropzone.contains(event.relatedTarget)) {
            return;
        }

        dropzone.classList.remove('cover-dropzone--active');
    });

    document.addEventListener('drop', (event) => {
        const dropzone = event.target.closest('[data-dropzone]');
        if (!dropzone) {
            return;
        }

        event.preventDefault();
        dropzone.classList.remove('cover-dropzone--active');

        const file = event.dataTransfer?.files?.[0];
        const input = dropzone.querySelector('[data-cover-input]');

        if (file && input) {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    document.addEventListener('change', (event) => {
        const input = event.target.closest('[data-cover-input]');
        if (!input) {
            return;
        }

        const dropzone = input.closest('[data-dropzone]');
        const preview = dropzone?.querySelector('[data-cover-preview]');
        const file = input.files?.[0];

        if (!dropzone || !preview || !file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            input.value = '';
            state.error = 'Please choose an image file.';
            render();
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            preview.innerHTML = `<img src="${reader.result}" alt="">`;
            dropzone.classList.add('cover-dropzone--filled');
        };
        reader.readAsDataURL(file);
    });

    render();
    bootPage();
}
