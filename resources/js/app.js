import './bootstrap';

const root = document.getElementById('book2book-app');

if (root) {
    const config = window.Book2Book || {};
    const state = {
        page: config.page || 'books',
        bookId: config.bookId,
        tradeId: config.tradeId,
        userId: config.userId,
        token: localStorage.getItem('book2book_token'),
        user: JSON.parse(localStorage.getItem('book2book_user') || 'null'),
        books: [],
        myBooks: [],
        trades: [],
        messages: [],
        notifications: [],
        currentBook: null,
        currentTrade: null,
        currentUserProfile: null,
        genres: {},
        editingBook: null,
        unreadNotifications: 0,
        unreadMessages: 0,
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
        new_like: 'Como novo',
        good: 'Bom',
        acceptable: 'Aceitável',
        poor: 'Gasto',
    }[condition] || condition || 'Não indicado');

    const statusLabel = (status) => ({
        pending: 'Pendente',
        accepted: 'Aceite',
        declined: 'Recusada',
        cancelled: 'Cancelada',
        completed: 'Concluída',
    }[status] || status);

    const pageTitle = () => ({
        login: 'Entrar na conta',
        register: 'Criar conta',
        profile: 'Perfil',
        books: 'Catálogo de livros',
        'book-create': 'Adicionar livro',
        'book-detail': 'Detalhes do livro',
        'user-profile': 'Perfil do leitor',
        library: 'A minha biblioteca',
        trades: 'Pedidos de troca',
        chat: 'Conversa da troca',
        notifications: 'Notificações',
    }[state.page] || 'Book2Book');

    const nav = () => `
        <header class="sticky top-0 z-20 border-b border-stone-200 bg-stone-50/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-3 font-semibold">
                    <span class="grid size-9 place-items-center rounded bg-amber-600 text-sm text-white">B2B</span>
                    <span>Book2Book</span>
                </a>
                <nav class="order-3 flex w-full items-center gap-1 overflow-x-auto md:order-none md:w-auto">
                    ${navLink('/books', 'Catálogo')}
                    ${navLink('/library', 'Biblioteca')}
                    ${navLink('/trades', 'Trocas' + (state.unreadMessages ? ' (' + state.unreadMessages + ')' : ''))}
                    ${navLink('/notifications', 'Notificações' + (state.unreadNotifications ? ' (' + state.unreadNotifications + ')' : ''))}
                    ${navLink('/profile', 'Perfil')}
                </nav>
                <div class="flex items-center gap-2">
                    ${state.user ? `<span class="hidden max-w-36 truncate text-sm text-stone-600 sm:block">${escapeHtml(state.user.name)}</span><button data-action="logout" class="btn-secondary">Sair</button>` : `<a href="/login" class="btn-secondary">Entrar</a><a href="/register" class="btn-primary">Criar conta</a>`}
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
                    <a href="/books" class="btn-secondary">Descobrir livros</a>
                    ${state.user ? '<a href="/books/create" class="btn-primary">Adicionar livro</a>' : '<a href="/login" class="btn-primary">Entra para publicar</a>'}
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
                <h2 class="text-xl font-semibold">${mode === 'login' ? 'Bem-vindo de volta' : 'Começa a trocar livros'}</h2>
                <p class="mt-2 max-w-2xl text-stone-600">Encontra leitores perto de ti e dá uma nova vida aos teus livros.</p>
                <div class="mt-6 grid gap-3 text-sm text-stone-700">
                    <div class="rounded bg-stone-50 p-3">Adiciona livros à tua biblioteca.</div>
                    <div class="rounded bg-stone-50 p-3">Propõe trocas a leitores perto de ti.</div>
                    <div class="rounded bg-stone-50 p-3">Conversa sobre a troca e avalia-a depois de concluída.</div>
                </div>
            </div>
            <form data-form="${mode}" class="rounded border border-stone-200 bg-white p-6 shadow-sm">
                <div class="grid gap-4">
                    ${mode === 'register' ? input('name', 'Nome', 'text', true) : ''}
                    ${input('email', 'Email', 'email', true)}
                    ${input('password', 'Palavra-passe', 'password', true)}
                    ${mode === 'register' ? input('password_confirmation', 'Confirmar palavra-passe', 'password', true) : ''}
                    ${mode === 'register' ? input('city', 'Cidade', 'text') : ''}
                    ${mode === 'register' ? `<div class="grid grid-cols-2 gap-3">${input('lat', 'Latitude', 'number')} ${input('lng', 'Longitude', 'number')}</div>` : ''}
                    <button class="btn-primary w-full" type="submit">${mode === 'login' ? 'Entrar' : 'Criar conta'}</button>
                    <a class="text-center text-sm font-medium text-amber-700" href="${mode === 'login' ? '/register' : '/login'}">${mode === 'login' ? 'Ainda não tens conta?' : 'Já tens conta?'}</a>
                </div>
            </form>
        </section>
    `);

    const input = (name, label, type = 'text', required = false, value = '') => `
        <label class="grid gap-1 text-sm">
            <span class="font-medium text-stone-700">${label}</span>
            <input name="${name}" type="${type}" ${type === 'number' ? 'step="any"' : ''} ${required ? 'required' : ''} value="${escapeHtml(value)}" class="field">
        </label>
    `;

    const textarea = (name, label, value = '') => `
        <label class="grid gap-1 text-sm">
            <span class="font-medium text-stone-700">${label}</span>
            <textarea name="${name}" rows="4" class="field">${escapeHtml(value)}</textarea>
        </label>
    `;

    const genreSelects = (selected = '', required = false) => {
        const [main = '', sub = ''] = selected.split(' / ');
        const mainOptions = Object.keys(state.genres).map(genre => `<option value="${escapeHtml(genre)}" ${main === genre ? 'selected' : ''}>${escapeHtml(genre)}</option>`).join('');
        const subOptions = (state.genres[main] || []).map(genre => `<option value="${escapeHtml(genre)}" ${sub === genre ? 'selected' : ''}>${escapeHtml(genre)}</option>`).join('');

        return `<label class="grid gap-1 text-sm"><span>Género principal</span><select name="genre_main" class="field" data-genre-main ${required ? 'required' : ''}><option value="">Selecionar</option>${mainOptions}</select></label>
            <label class="grid gap-1 text-sm"><span>Subgénero</span><select name="genre_sub" class="field" data-genre-sub ${required ? 'required' : ''}><option value="">Selecionar</option>${subOptions}</select></label>`;
    };

    const profilePage = () => {
        if (!requireAuth()) {
            return '';
        }

        const user = state.user || {};

        return shell(`
            <form data-form="profile" class="grid gap-4 rounded border border-stone-200 bg-white p-6 shadow-sm md:grid-cols-2">
                ${input('name', 'Nome', 'text', true, user.name)}
                ${input('email', 'Email', 'email', true, user.email)}
                ${input('phone', 'Telefone', 'text', false, user.phone)}
                ${input('city', 'Cidade', 'text', false, user.city)}
                ${input('lat', 'Latitude', 'number', false, user.lat)}
                ${input('lng', 'Longitude', 'number', false, user.lng)}
                <div class="md:col-span-2">
                    <button class="btn-primary" type="submit">Guardar perfil</button>
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
                <input name="q" placeholder="Título ou autor" value="${escapeHtml(state.filters.q)}" class="field self-start md:col-span-2">
                <div class="grid gap-2">${genreSelects(state.filters.genre)}</div>
                <input name="language" placeholder="Idioma" value="${escapeHtml(state.filters.language)}" class="field self-start">
                <select name="order" class="field self-start">
                    <option value="recent" ${state.filters.order === 'recent' ? 'selected' : ''}>Mais recentes</option>
                    <option value="distance" ${state.filters.order === 'distance' ? 'selected' : ''}>Distância</option>
                </select>
                <input name="distance_km" type="number" min="0" step="any" placeholder="Distância máxima (km)" value="${escapeHtml(state.filters.distance_km)}" class="field">
                <button class="btn-primary md:col-span-4" type="submit">Pesquisar</button>
            </form>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">${state.books.map(bookCard).join('') || empty('Não foram encontrados livros disponíveis.')}</div>
        </section>
    `);

    const bookForm = (book = null) => `
        <form data-form="book" class="grid gap-3 rounded border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold">${book ? 'Editar livro' : 'Novo livro'}</h2>
            ${input('title', 'Título', 'text', true, book?.title)}
            ${input('author', 'Autor', 'text', true, book?.author)}
            ${input('isbn', 'ISBN', 'text', true, book?.isbn)}
            ${genreSelects(book?.genre, true)}
            ${input('language', 'Idioma', 'text', true, book?.language)}
            <div class="grid gap-1 text-sm">
                <span class="font-medium text-stone-700">Fotografia da capa</span>
                <label class="cover-dropzone" data-dropzone>
                    <input name="cover_image" type="file" accept="image/*" class="sr-only" data-cover-input ${book ? '' : 'required'}>
                    <span class="cover-dropzone__preview" data-cover-preview>
                        <span class="cover-dropzone__icon">+</span>
                    </span>
                    <span class="cover-dropzone__text">
                        <strong>Arrasta a fotografia para aqui</strong>
                        <small>ou escolhe JPG, PNG ou WebP até 4 MB</small>
                    </span>
                </label>
            </div>
            <label class="grid gap-1 text-sm"><span class="font-medium text-stone-700">Estado</span><select name="condition" class="field" required><option value="good" ${book?.condition === 'good' ? 'selected' : ''}>Bom</option><option value="new_like" ${book?.condition === 'new_like' ? 'selected' : ''}>Como novo</option><option value="acceptable" ${book?.condition === 'acceptable' ? 'selected' : ''}>Aceitável</option><option value="poor" ${book?.condition === 'poor' ? 'selected' : ''}>Gasto</option></select></label>
            <label class="grid gap-1 text-sm"><span>Descrição</span><textarea name="description" class="field" required>${escapeHtml(book?.description)}</textarea></label>
            <label class="flex items-center gap-2 rounded border border-stone-200 bg-stone-50 p-3 text-sm font-medium text-stone-700">
                <input name="is_available" type="checkbox" value="1" ${!book || book.is_available ? 'checked' : ''}>
                Disponível para troca
            </label>
            <button class="btn-primary" type="submit">${book ? 'Guardar alterações' : 'Publicar livro'}</button>
            <a href="/library" class="btn-secondary">Voltar à biblioteca</a>
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
            <a class="btn-secondary text-center" href="/books/${book.id}">Ver detalhes</a>
        </article>
    `;

    const cover = (book) => book.cover_image_url
        ? `<img src="${book.cover_image_url}" alt="" class="aspect-[4/3] w-full rounded object-cover">`
        : `<div class="grid aspect-[4/3] w-full place-items-center rounded bg-amber-100 text-amber-900">Sem capa</div>`;

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
                        ${book.owner?.distance_km ? badge(`${Number(book.owner.distance_km).toFixed(1)} km`) : ''}
                    </div>
                    <p class="text-stone-700">${escapeHtml(book.description || 'Sem descrição.')}</p>
                    <div class="rounded bg-stone-50 p-4 text-sm">
                        <strong>Proprietário</strong>
                        <div><a class="text-amber-700 underline" href="/users/${book.owner?.id}">${escapeHtml(book.owner?.name || 'Desconhecido')}</a></div>
                        <div class="text-stone-600">${escapeHtml(book.owner?.city || 'Localização não indicada')}</div>
                    </div>
                    <form data-form="request-trade" class="grid gap-3">
                        ${textarea('message', 'Mensagem para o proprietário')}
                        <label class="grid gap-1 text-sm"><span>Livro que ofereces em troca</span><select name="offered_book_id" class="field" required><option value="">Selecionar livro</option>${state.myBooks.filter(ownBook => ownBook.is_available).map(ownBook => `<option value="${ownBook.id}">${escapeHtml(ownBook.title)}</option>`).join('')}</select></label>
                        ${state.token && !state.myBooks.some(ownBook => ownBook.is_available) ? '<p class="text-sm">Publica primeiro um livro disponível na tua biblioteca.</p>' : ''}
                        <button class="btn-primary" type="submit" ${book.is_available && (!state.token || state.myBooks.some(ownBook => ownBook.is_available)) ? '' : 'disabled'}>Pedir troca</button>
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
                ${bookForm(state.editingBook)}
                <div class="grid gap-4 sm:grid-cols-2">${state.myBooks.map(myBookCard).join('') || empty('A tua biblioteca está vazia.')}</div>
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
                ${bookForm()}
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
            <div class="flex flex-wrap gap-2 text-xs">${badge(conditionLabel(book.condition))}${badge(book.is_available ? 'Disponível' : 'Indisponível')}</div>
            <button data-action="toggle-availability" data-book="${book.id}" data-available="${book.is_available ? '0' : '1'}" class="btn-secondary">${book.is_available ? 'Marcar indisponível' : 'Marcar disponível'}</button>
            <div class="flex gap-2">
                <button data-action="edit-book" data-book="${book.id}" class="btn-secondary">Editar</button>
                <button data-action="delete-book" data-book="${book.id}" class="btn-secondary text-red-700">Apagar</button>
            </div>
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
                    <p class="text-sm text-stone-600">Pedido de <a class="text-amber-700 underline" href="/users/${trade.requester?.id}">${escapeHtml(trade.requester?.name)}</a> · Dono: <a class="text-amber-700 underline" href="/users/${trade.owner?.id}">${escapeHtml(trade.owner?.name)}</a></p>
                    <p class="mt-2 text-sm text-stone-700">${escapeHtml(trade.message || 'No initial message.')}</p>
                    <p class="mt-2 text-sm font-medium">Livro oferecido: ${escapeHtml(trade.offered_book?.title || 'Ainda não indicado')}${trade.offered_book?.author ? ' · ' + escapeHtml(trade.offered_book.author) : ''}</p>
                    ${trade.offered_book?.cover_image_url ? `<img src="${escapeHtml(trade.offered_book.cover_image_url)}" alt="Capa do livro oferecido" class="mt-2 size-20 rounded object-cover">` : ''}
                    ${trade.unread_messages_count ? badge(trade.unread_messages_count + ' mensagens por ler') : ''}
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    ${isOwner && trade.status === 'pending' ? `<button data-action="trade-action" data-trade="${trade.id}" data-endpoint="accept" class="btn-primary">Accept</button><button data-action="trade-action" data-trade="${trade.id}" data-endpoint="reject" class="btn-secondary">Reject</button>` : ''}
                    ${isRequester && trade.status === 'pending' ? `<button data-action="trade-action" data-trade="${trade.id}" data-endpoint="cancel" class="btn-secondary">Cancel</button>` : ''}
                    ${isOwner && trade.status === 'accepted' ? `<button data-action="trade-action" data-trade="${trade.id}" data-endpoint="complete" class="btn-primary">Complete</button>` : ''}
                    <a href="/trades/${trade.id}/chat" class="btn-secondary">Ver conversa</a>
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
                    <p class="mt-2 text-sm">Livro oferecido: ${escapeHtml(trade?.offered_book?.title || 'Ainda não indicado')}</p>
                    <p class="mt-2 text-sm">Pedido inicial: ${escapeHtml(trade?.message || 'Sem mensagem inicial')}</p>
                    <a class="text-sm text-amber-700 underline" href="/users/${trade?.requester?.id}">Ver perfil de quem fez o pedido</a>
                    ${trade?.status === 'completed' ? reviewForm() : '<p class="mt-4 text-sm text-stone-500">Reviews open after completion.</p>'}
                </aside>
                <div class="grid gap-4 rounded border border-stone-200 bg-white p-4">
                    <button data-action="reload-chat" class="btn-secondary justify-self-end">Atualizar conversa</button>
                    <div class="grid max-h-[520px] gap-3 overflow-y-auto">${state.messages.map(messageBubble).join('') || empty('No messages yet.')}</div>
                    <form data-form="message" class="flex flex-col gap-2 sm:flex-row">
                        <input name="message" class="field" placeholder="Escreve uma mensagem ou anexa um ficheiro">
                        <input name="attachment" type="file" accept="image/jpeg,image/png,image/webp,application/pdf,application/epub+zip,.epub" class="field">
                        <button class="btn-primary" type="submit">Send</button>
                    </form>
                </div>
            </section>
        `);
    };

    const messageBubble = (message) => {
        const mine = state.user && message.sender?.id === state.user.id;

        return `<div class="grid ${mine ? 'justify-items-end' : 'justify-items-start'}"><div class="max-w-xl rounded ${mine ? 'bg-amber-600 text-white' : 'bg-stone-100 text-stone-900'} px-4 py-2"><p>${escapeHtml(message.message)}</p>${message.attachment_url ? `<button data-action="download-attachment" data-url="${escapeHtml(message.attachment_url)}" data-name="${escapeHtml(message.attachment_name)}" class="block underline">${escapeHtml(message.attachment_name)}</button>` : ''}<span class="text-xs opacity-75">${escapeHtml(message.sender?.name || '')}</span></div></div>`;
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
                <h2 class="font-semibold">${escapeHtml(notification.title)}</h2>
                <p class="text-sm text-stone-600">${escapeHtml(notification.data?.snippet || notification.data?.status || '')}</p>
                ${notification.data?.trade_id ? `<a class="text-sm text-amber-700 underline" href="/trades/${notification.data.trade_id}/chat">Abrir troca</a>` : ''}
            </div>
            ${notification.read_at ? badge('Read') : `<button data-action="mark-read" data-notification="${notification.id}" class="btn-secondary">Mark read</button>`}
        </article>
    `;

    const userProfilePage = () => {
        const profile = state.currentUserProfile;
        if (!profile) {
            return shell(empty('Perfil não encontrado.'));
        }

        return shell(`<section class="grid gap-5">
            <div class="rounded border bg-white p-5">
                <h2 class="text-xl font-semibold">${escapeHtml(profile.user.name)}</h2>
                <p>${escapeHtml(profile.user.city || 'Localização não indicada')}</p>
                <p>Avaliação: ${escapeHtml(profile.user.rating_avg ?? 'Ainda sem avaliações')}</p>
            </div>
            <h3 class="font-semibold">Livros disponíveis</h3>
            <div class="grid gap-4 sm:grid-cols-3">${profile.books.map(bookCard).join('') || empty('Sem livros disponíveis.')}</div>
            <h3 class="font-semibold">Avaliações</h3>
            <div class="grid gap-3">${profile.reviews.map(review => `<div class="rounded border bg-white p-4">${escapeHtml(review.rating)}/5 · ${escapeHtml(review.comment || '')}</div>`).join('') || empty('Sem avaliações.')}</div>
        </section>`);
    };

    const loadMe = async () => {
        if (!state.token) {
            return;
        }

        const payload = await api('/me');
        state.user = payload.data;
        localStorage.setItem('book2book_user', JSON.stringify(state.user));
    };

    const loadBooks = async () => {
        state.books = [];
        const params = new URLSearchParams(Object.entries(state.filters).filter(([, value]) => value !== ''));
        if (state.user?.lat && state.user?.lng) {
            params.set('lat', state.user.lat);
            params.set('lng', state.user.lng);
        }

        const payload = await api(`/books/search?${params.toString()}`);
        state.books = payload.data;
    };

    const loadGenres = async () => {
        const payload = await api('/books/genres');
        state.genres = payload.data;
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
        state.unreadMessages = payload.meta?.unread_messages_count || 0;
    };

    const loadChat = async () => {
        const [trade, messages] = await Promise.all([
            api(`/trades/${state.tradeId}`),
            api(`/trades/${state.tradeId}/messages?per_page=50`),
        ]);
        state.currentTrade = trade.data;
        state.messages = messages.data;
        await refreshUnread();
    };

    const loadNotifications = async () => {
        const payload = await api('/me/notifications?per_page=50');
        state.notifications = payload.data;
        state.unreadNotifications = payload.meta?.unread_count || 0;
    };

    const loadUserProfile = async () => {
        const payload = await api(`/users/${state.userId}`);
        state.currentUserProfile = payload.data;
    };

    const refreshUnread = async () => {
        if (!state.token) {
            return;
        }
        const [notifications, trades] = await Promise.all([
            api('/me/notifications?per_page=1'),
            api('/trades?per_page=50'),
        ]);
        const notificationCount = notifications.meta?.unread_count || 0;
        const messageCount = trades.meta?.unread_messages_count || 0;
        if (notificationCount !== state.unreadNotifications || messageCount !== state.unreadMessages) {
            state.unreadNotifications = notificationCount;
            state.unreadMessages = messageCount;
            if (state.page === 'notifications') {
                await loadNotifications();
            }
            if (state.page === 'trades') {
                await loadTrades();
            }
            if (state.page === 'chat') {
                const reloadButton = root.querySelector('[data-action="reload-chat"]');
                if (reloadButton) {
                    reloadButton.textContent = messageCount ? 'Novas mensagens — atualizar conversa' : 'Atualizar conversa';
                }
                return;
            }
            render();
        }
    };

    const bootPage = async () => {
        await withBusy(async () => {
            await loadGenres();
            if (state.token) {
                await loadMe();
                await refreshUnread();
            }

            if (state.page === 'books') {
                await loadBooks();
            } else if (state.page === 'book-create') {
                // Auth is checked by the page renderer.
            } else if (state.page === 'book-detail') {
                await loadBook();
                if (state.token) {
                    await loadLibrary();
                }
            } else if (state.page === 'user-profile') {
                await loadUserProfile();
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
        if (state.token) {
            window.setInterval(() => refreshUnread().catch(() => {}), 30000);
        }
    };

    const render = () => {
        root.innerHTML = {
            login: () => authPage('login'),
            register: () => authPage('register'),
            profile: profilePage,
            books: booksPage,
            'book-create': bookCreatePage,
            'book-detail': bookDetailPage,
            'user-profile': userProfilePage,
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
                if (data.city !== state.user.city && data.lat === String(state.user.lat) && data.lng === String(state.user.lng)) {
                    delete data.lat;
                    delete data.lng;
                }
                const payload = await api('/me', { method: 'PUT', body: JSON.stringify(data) });
                state.user = payload.data;
                localStorage.setItem('book2book_user', JSON.stringify(state.user));
                state.notice = 'Profile saved.';
            }

            if (form.dataset.form === 'search') {
                state.filters = {
                    ...state.filters,
                    q: data.q,
                    genre: data.genre_sub ? data.genre_main + ' / ' + data.genre_sub : data.genre_main,
                    language: data.language,
                    distance_km: data.distance_km,
                    order: data.order,
                };
                await loadBooks();
            }

            if (form.dataset.form === 'request-trade') {
                if (!state.token) {
                    navigate('/login');
                    return;
                }

                const payload = await api('/trades', {
                    method: 'POST',
                    body: JSON.stringify({ book_id: state.currentBook.id, message: data.message, offered_book_id: data.offered_book_id || null }),
                });
                navigate(`/trades/${payload.data.id}/chat`);
            }

            if (form.dataset.form === 'book') {
                const payload = new FormData(form);
                payload.set('genre', data.genre_main + ' / ' + data.genre_sub);
                payload.delete('genre_main');
                payload.delete('genre_sub');
                payload.set('is_available', data.is_available === '1' ? '1' : '0');

                if (!payload.get('cover_image')?.name) {
                    payload.delete('cover_image');
                }

                if (state.editingBook && state.page === 'library') {
                    payload.set('_method', 'PUT');
                    await api(`/me/books/${state.editingBook.id}`, { method: 'POST', body: payload });
                    state.editingBook = null;
                } else {
                    await api('/me/books', { method: 'POST', body: payload });
                }
                form.reset();
                state.notice = 'Book added to your library.';
                if (state.page === 'library') {
                    await loadLibrary();
                } else {
                    navigate('/library');
                }
            }

            if (form.dataset.form === 'message') {
                const payload = new FormData(form);
                if (!payload.get('attachment')?.name) {
                    payload.delete('attachment');
                }
                await api(`/trades/${state.tradeId}/messages`, { method: 'POST', body: payload });
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

        if (action === 'delete-book' && !window.confirm('Apagar este livro da tua biblioteca?')) {
            return;
        }

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

            if (action === 'edit-book') {
                state.editingBook = state.myBooks.find(book => String(book.id) === button.dataset.book);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            if (action === 'delete-book') {
                await api(`/me/books/${button.dataset.book}`, { method: 'DELETE' });
                state.editingBook = null;
                await loadLibrary();
            }

            if (action === 'download-attachment') {
                const response = await fetch(button.dataset.url, {
                    headers: { Authorization: `Bearer ${state.token}` },
                });
                if (!response.ok) {
                    throw new Error('Não foi possível descarregar o ficheiro.');
                }
                const objectUrl = URL.createObjectURL(await response.blob());
                const link = document.createElement('a');
                link.href = objectUrl;
                link.download = button.dataset.name || 'anexo';
                link.click();
                window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
            }

            if (action === 'reload-chat') {
                await loadChat();
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
        if (event.target.matches('[data-genre-main]')) {
            const subSelect = event.target.closest('form')?.querySelector('[data-genre-sub]');
            if (subSelect) {
                subSelect.innerHTML = '<option value="">Selecionar</option>' + (state.genres[event.target.value] || [])
                    .map(genre => `<option value="${escapeHtml(genre)}">${escapeHtml(genre)}</option>`).join('');
            }
            return;
        }

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
